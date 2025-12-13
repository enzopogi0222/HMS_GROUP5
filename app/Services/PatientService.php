<?php

namespace App\Services;

use CodeIgniter\Database\ConnectionInterface;
use App\Libraries\PermissionManager;

class PatientService
{
    protected $db;
    protected string $patientTable;
    protected array $patientTableColumns = [];

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->patientTable = $this->resolvePatientTableName();
        $this->patientTableColumns = $this->loadPatientTableColumns();
    }

    public function createPatient($input, $userRole, $staffId = null)
    {
        // Determine primary doctor assignment when the schema supports it
        $primaryDoctorId = null;
        $hasPrimaryDoctorColumn = $this->patientTableHasColumn('primary_doctor_id');

        if ($hasPrimaryDoctorColumn) {
            $primaryDoctorId = $this->determinePrimaryDoctor($input, $userRole, $staffId);

            if (!$primaryDoctorId) {
                log_message('warning', 'PatientService::createPatient - No doctor available for assignment, defaulting to NULL');
            }
        }

        // Validation
        $validation = \Config\Services::validation();
        $validation->setRules($this->getValidationRules(), $this->getValidationMessages());

        if (!$validation->run($input)) {
            return [
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validation->getErrors(),
            ];
        }

        // Prepare data for primary patient table
        $data = $this->preparePatientData($input, $primaryDoctorId);
        $data = $this->sanitizePatientDataForTable($data);

        $this->db->transBegin();

        try {
            // Log data being inserted in development mode for debugging
            if (ENVIRONMENT === 'development') {
                log_message('debug', 'PatientService: Attempting to insert patient data: ' . json_encode($data));
            }
            
            // Insert main patient record
            $insertResult = $this->db->table($this->patientTable)->insert($data);
            
            if (!$insertResult) {
                // Get database error
                $error = $this->db->error();
                $errorMessage = 'Unknown database error';
                if (!empty($error)) {
                    $errorMessage = is_array($error) 
                        ? ($error['message'] ?? json_encode($error))
                        : (string)$error;
                }
                log_message('error', 'PatientService: Insert failed - ' . $errorMessage);
                throw new \RuntimeException('Failed to insert patient record: ' . $errorMessage);
            }
            
            $newPatientId = (int) $this->db->insertID();
            
            if (!$newPatientId) {
                log_message('error', 'PatientService: Insert succeeded but no patient ID returned');
                throw new \RuntimeException('Failed to get inserted patient ID');
            }

            // Create emergency contact record
            $this->createEmergencyContactRecord($newPatientId, $input);

            // Persist insurance/HMO details when provided
            $this->persistInsuranceDetails($newPatientId, $input);

            // Insert role-specific records (outpatient visit or inpatient admission tree)
            $this->persistRoleSpecificRecords($newPatientId, $input);

            if ($this->db->transStatus() === false) {
                // Something went wrong in the transaction
                $this->db->transRollback();
                return [
                    'status' => 'error',
                    'message' => 'Database error: failed to save patient records (transaction rolled back).',
                ];
            }

            $this->db->transCommit();

            return [
                'status' => 'success',
                'message' => 'Patient added successfully',
                'id' => $newPatientId,
            ];
        } catch (\Throwable $e) {
            // Ensure we never leave partial patient data behind
            if ($this->db->transStatus() !== false) {
                $this->db->transRollback();
            }

            $errorMessage = $e->getMessage();
            log_message('error', 'Failed to insert patient (transaction): ' . $errorMessage);
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());

            // Return more detailed error message in development, generic in production
            $message = ENVIRONMENT === 'development' 
                ? 'Database error: ' . $errorMessage
                : 'Database error: Unable to save patient. Please check the logs for details.';

            return [
                'status' => 'error',
                'message' => $message,
                'error_details' => ENVIRONMENT === 'development' ? [
                    'message' => $errorMessage,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : null,
            ];
        }
    }

    /**
     * Get patients based on user role and permissions
     */
    public function getPatientsByRole($userRole, $staffId)
    {
        try {
            $builder = $this->db->table($this->patientTable . ' p');

            $hasPrimaryDoctor = $this->patientTableHasColumn('primary_doctor_id');
            if ($hasPrimaryDoctor) {
                // Join doctor table first, then staff to get doctor name
                $builder->select('p.*, CONCAT(s.first_name, " ", s.last_name) as assigned_doctor_name')
                        ->join('doctor d', 'd.doctor_id = p.primary_doctor_id', 'left')
                        ->join('staff s', 's.staff_id = d.staff_id', 'left');
            } else {
                $builder->select('p.*');
            }

            // Use PermissionManager to determine view scope
            // Check if user has view permission for patients
            if (!PermissionManager::hasAnyPermission($userRole, 'patients', ['view', 'view_all', 'view_assigned', 'view_own'])) {
                $builder->where('1=0'); // No permission, show no patients
            } elseif (PermissionManager::hasPermission($userRole, 'patients', 'view_all')) {
                // Users with view_all can see all patients (admin, receptionist, nurse, it_staff)
                // No additional filtering needed
            } elseif (PermissionManager::hasPermission($userRole, 'patients', 'view_own')) {
                // Doctors can see only their assigned patients
                if ($hasPrimaryDoctor && $staffId) {
                    // Get doctor_id from doctor table using staff_id
                    $doctorInfo = $this->db->table('doctor')->where('staff_id', $staffId)->get()->getRowArray();
                    $doctorId = $doctorInfo['doctor_id'] ?? null;
                    
                    if ($doctorId) {
                        $builder->where('p.primary_doctor_id', $doctorId);
                    } else {
                        $builder->where('1=0'); // Show no patients if doctor record not found
                    }
                }
            } else {
                // For roles like pharmacist and laboratorist who have view but need specific filtering
                if ($userRole === 'pharmacist') {
                    // Pharmacists can see patients with prescriptions
                    $builder->join('prescription pr', 'pr.patient_id = p.patient_id', 'inner')
                           ->groupBy('p.patient_id');
                } elseif ($userRole === 'laboratorist') {
                    // Laboratorists can see patients with lab tests
                    $builder->join('lab_test lt', 'lt.patient_id = p.patient_id', 'inner')
                           ->groupBy('p.patient_id');
                } else {
                    // Other roles see no patients
                    $builder->where('1=0');
                }
            }

            $patients = $builder->orderBy('p.patient_id', 'DESC')
                ->get()
                ->getResultArray();

            // Compute ages and format data
            foreach ($patients as &$p) {
                $p['age'] = $p['date_of_birth']
                    ? (new \DateTime())->diff(new \DateTime($p['date_of_birth']))->y
                    : null;
                $p['id'] = $p['patient_id'];
                $p['full_name'] = trim(($p['first_name'] ?? '') . ' ' . ($p['middle_name'] ?? '') . ' ' . ($p['last_name'] ?? ''));
            }

            return $patients;
            
        } catch (\Throwable $e) {
            log_message('error', 'Error fetching patients: ' . $e->getMessage());
            return [];
        }
    }


    /**
     * Get single patient by ID
     */
    public function getPatient($id)
    {
        try {
            $builder = $this->db->table($this->patientTable . ' p');
            
            // Check if primary_doctor_id column exists and join to get doctor name
            $hasPrimaryDoctor = $this->patientTableHasColumn('primary_doctor_id');
            if ($hasPrimaryDoctor) {
                // Join doctor table first, then staff to get doctor name
                $builder->select('p.*, CONCAT(s.first_name, " ", s.last_name) as assigned_doctor_name')
                        ->join('doctor d', 'd.doctor_id = p.primary_doctor_id', 'left')
                        ->join('staff s', 's.staff_id = d.staff_id', 'left');
            } else {
                $builder->select('p.*');
            }
            
            $patient = $builder->where('p.patient_id', $id)
                ->get()
                ->getRowArray();

            if (!$patient) {
                return [
                    'status' => 'error',
                    'message' => 'Patient not found',
                ];
            }

            // Compute age
            $patient['age'] = $patient['date_of_birth']
                ? (new \DateTime())->diff(new \DateTime($patient['date_of_birth']))->y
                : null;

            // Ensure all address fields are included even if null
            $addressFields = ['province', 'city', 'barangay', 'subdivision', 'house_number', 'zip_code'];
            foreach ($addressFields as $field) {
                if (!isset($patient[$field])) {
                    $patient[$field] = null;
                }
            }

            // Always initialize insurance fields to null so they're always present in the response
            // This ensures frontend always receives these fields (even if null) instead of undefined
            $patient['insurance_provider'] = $patient['insurance_provider'] ?? null;
            $patient['membership_number'] = $patient['membership_number'] ?? null;
            $patient['hmo_cardholder_name'] = $patient['hmo_cardholder_name'] ?? null;
            $patient['cardholder_name'] = $patient['cardholder_name'] ?? null;
            $patient['member_type'] = $patient['member_type'] ?? null;
            $patient['relationship'] = $patient['relationship'] ?? null;
            $patient['plan_name'] = $patient['plan_name'] ?? null;
            $patient['mbl'] = $patient['mbl'] ?? null;
            $patient['maximum_benefit_limit'] = $patient['maximum_benefit_limit'] ?? null;
            $patient['max_benefit_limit'] = $patient['max_benefit_limit'] ?? null;
            $patient['pre_existing_coverage'] = $patient['pre_existing_coverage'] ?? null;
            $patient['coverage_start_date'] = $patient['coverage_start_date'] ?? null;
            $patient['coverage_end_date'] = $patient['coverage_end_date'] ?? null;
            $patient['card_status'] = $patient['card_status'] ?? null;
            $patient['plan_coverage_types'] = $patient['plan_coverage_types'] ?? [];
            $patient['coverage_types'] = $patient['coverage_types'] ?? [];

            // Load insurance/HMO details from insurance_details table if it exists
            if ($this->db->tableExists('insurance_details')) {
                try {
                    // Build column list dynamically based on what columns actually exist
                    $availableColumns = [];
                    $expectedColumns = [
                        'insurance_detail_id', 'patient_id', 'provider', 'membership_number', 
                        'card_holder_name', 'cardholder_name', 'member_type', 'relationship', 
                        'plan_name', 'coverage_type', 'mbl', 'maximum_benefit_limit', 
                        'pre_existing_coverage', 'start_date', 'end_date', 'coverage_start_date', 
                        'coverage_end_date', 'card_status', 'created_at', 'updated_at'
                    ];
                    
                    foreach ($expectedColumns as $col) {
                        if ($this->db->fieldExists($col, 'insurance_details')) {
                            $availableColumns[] = $col;
                        }
                    }
                    
                    // If no columns found, try a simple SELECT * approach
                    if (empty($availableColumns)) {
                        $insuranceDetails = $this->db->table('insurance_details')
                            ->where('patient_id', (int)$id)
                            ->orderBy('created_at', 'DESC')
                            ->limit(1)
                            ->get()
                            ->getRowArray();
                    } else {
                        // Use raw query with only existing columns
                        $columnsStr = implode(', ', $availableColumns);
                        $query = $this->db->query(
                            "SELECT {$columnsStr} FROM insurance_details WHERE patient_id = ? ORDER BY created_at DESC LIMIT 1",
                            [(int)$id]
                        );
                        $insuranceDetails = $query->getRowArray();
                    }
                    
                    log_message('debug', 'Insurance details query for patient ' . $id . ' returned: ' . ($insuranceDetails ? 'FOUND' : 'NOT FOUND'));
                    
                    if ($insuranceDetails) {
                        log_message('debug', 'Loaded insurance_details for patient ' . $id . ': ' . json_encode($insuranceDetails));
                        log_message('debug', 'Raw relationship value: ' . var_export($insuranceDetails['relationship'] ?? 'NOT SET', true));
                        log_message('debug', 'Raw mbl value: ' . var_export($insuranceDetails['mbl'] ?? 'NOT SET', true));
                        log_message('debug', 'Raw coverage_type value: ' . var_export($insuranceDetails['coverage_type'] ?? 'NOT SET', true));
                        log_message('debug', 'coverage_type type: ' . gettype($insuranceDetails['coverage_type'] ?? null));
                        
                        // Map insurance_details fields to patient data fields
                        // Handle both old schema (provider) and new schema (insurance_provider)
                        $patient['insurance_provider'] = $insuranceDetails['provider'] ?? $insuranceDetails['insurance_provider'] ?? $patient['insurance_provider'] ?? null;
                        $patient['membership_number'] = $insuranceDetails['membership_number'] ?? $insuranceDetails['hmo_member_id'] ?? $insuranceDetails['policy_number'] ?? $patient['membership_number'] ?? null;
                        $patient['hmo_cardholder_name'] = $insuranceDetails['card_holder_name'] ?? $insuranceDetails['cardholder_name'] ?? $insuranceDetails['member_name'] ?? $insuranceDetails['hmo_cardholder_name'] ?? $patient['hmo_cardholder_name'] ?? null;
                        $patient['cardholder_name'] = $insuranceDetails['card_holder_name'] ?? $insuranceDetails['cardholder_name'] ?? $insuranceDetails['member_name'] ?? $insuranceDetails['hmo_cardholder_name'] ?? $patient['cardholder_name'] ?? null;
                        $patient['member_type'] = $insuranceDetails['member_type'] ?? $patient['member_type'] ?? null;
                        
                        // Relationship - load directly, check for empty string too
                        $relationshipValue = $insuranceDetails['relationship'] ?? null;
                        if ($relationshipValue === '' || $relationshipValue === null) {
                            $patient['relationship'] = null;
                        } else {
                            $patient['relationship'] = trim($relationshipValue);
                        }
                        // Also check patient table directly in case it's stored there
                        if (($patient['relationship'] === null || $patient['relationship'] === '') && isset($patient['hmo_relationship']) && $patient['hmo_relationship'] !== null && $patient['hmo_relationship'] !== '') {
                            $patient['relationship'] = trim($patient['hmo_relationship']);
                        }
                        
                        $patient['plan_name'] = $insuranceDetails['plan_name'] ?? $patient['plan_name'] ?? null;
                        
                        // MBL - handle DECIMAL type properly (might be returned as string)
                        // Check both 'mbl' and 'maximum_benefit_limit' columns
                        $mblValue = $insuranceDetails['mbl'] ?? $insuranceDetails['maximum_benefit_limit'] ?? null;
                        if ($mblValue !== null && $mblValue !== '' && $mblValue !== '0.00' && $mblValue !== '0') {
                            // Convert to number if it's a string
                            $mblValue = is_numeric($mblValue) ? (float)$mblValue : $mblValue;
                            $patient['mbl'] = $mblValue;
                            $patient['maximum_benefit_limit'] = $mblValue;
                            $patient['max_benefit_limit'] = $mblValue;
                        } else {
                            $patient['mbl'] = null;
                            $patient['maximum_benefit_limit'] = null;
                            $patient['max_benefit_limit'] = null;
                        }
                        
                        $patient['pre_existing_coverage'] = $insuranceDetails['pre_existing_coverage'] ?? $patient['pre_existing_coverage'] ?? null;
                        // Handle both old schema (start_date/end_date) and new schema (coverage_start_date/coverage_end_date)
                        $patient['coverage_start_date'] = $insuranceDetails['start_date'] ?? $insuranceDetails['coverage_start_date'] ?? $patient['coverage_start_date'] ?? null;
                        $patient['coverage_end_date'] = $insuranceDetails['end_date'] ?? $insuranceDetails['coverage_end_date'] ?? $patient['coverage_end_date'] ?? null;
                        $patient['card_status'] = $insuranceDetails['card_status'] ?? $patient['card_status'] ?? null;
                        
                        // Handle coverage types - CodeIgniter might auto-decode JSON, or it might be a string
                        // For JSON fields, MySQL returns them as strings that need to be decoded
                        $coverageType = $insuranceDetails['coverage_type'] ?? null;
                        if ($coverageType !== null && $coverageType !== '' && $coverageType !== 'null') {
                            if (is_string($coverageType)) {
                                // Remove any whitespace and check if it's JSON
                                $trimmed = trim($coverageType);
                                if (strlen($trimmed) > 0 && (($trimmed[0] === '[' || $trimmed[0] === '{'))) {
                                    // Looks like JSON, try to decode
                                    $decoded = json_decode($coverageType, true);
                                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && !empty($decoded)) {
                                        $patient['plan_coverage_types'] = array_filter(array_map('trim', $decoded));
                                    } else {
                                        // If not valid JSON, try comma-separated
                                        $patient['plan_coverage_types'] = array_filter(array_map('trim', explode(',', $coverageType)));
                                    }
                                } else {
                                    // Not JSON, treat as comma-separated
                                    $patient['plan_coverage_types'] = array_filter(array_map('trim', explode(',', $coverageType)));
                                }
                            } else if (is_array($coverageType) && !empty($coverageType)) {
                                // Already decoded (CodeIgniter might auto-decode JSON)
                                $patient['plan_coverage_types'] = array_filter(array_map('trim', $coverageType));
                            } else {
                                $patient['plan_coverage_types'] = [];
                            }
                        } else {
                            // Also check patient table directly for coverage types
                            $patientCoverage = $patient['plan_coverage_types'] ?? $patient['coverage_types'] ?? $patient['hmo_coverage_type'] ?? null;
                            if ($patientCoverage !== null && $patientCoverage !== '' && $patientCoverage !== 'null') {
                                if (is_string($patientCoverage)) {
                                    try {
                                        $decoded = json_decode($patientCoverage, true);
                                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                            $patient['plan_coverage_types'] = array_filter(array_map('trim', $decoded));
                                        } else {
                                            $patient['plan_coverage_types'] = array_filter(array_map('trim', explode(',', $patientCoverage)));
                                        }
                                    } catch (\Exception $e) {
                                        $patient['plan_coverage_types'] = array_filter(array_map('trim', explode(',', $patientCoverage)));
                                    }
                                } else if (is_array($patientCoverage)) {
                                    $patient['plan_coverage_types'] = array_filter(array_map('trim', $patientCoverage));
                                }
                            } else {
                                $patient['plan_coverage_types'] = [];
                            }
                        }
                        
                        // Also set it as a direct field for easier access
                        if (isset($patient['plan_coverage_types']) && !empty($patient['plan_coverage_types'])) {
                            $patient['coverage_types'] = $patient['plan_coverage_types'];
                        }
                        
                        log_message('debug', 'Mapped insurance fields - relationship: ' . var_export($patient['relationship'], true) . ', mbl: ' . var_export($patient['mbl'], true) . ', coverage_types: ' . json_encode($patient['plan_coverage_types'] ?? []));
                        log_message('debug', 'Final patient data includes insurance_provider: ' . var_export($patient['insurance_provider'] ?? 'NOT SET', true));
                    } else {
                        log_message('debug', 'No insurance_details record found for patient ' . $id);
                        // Set all insurance fields to null explicitly so frontend knows they exist
                        $patient['insurance_provider'] = null;
                        $patient['membership_number'] = null;
                        $patient['hmo_cardholder_name'] = null;
                        $patient['cardholder_name'] = null;
                        $patient['member_type'] = null;
                        $patient['relationship'] = null;
                        $patient['plan_name'] = null;
                        $patient['mbl'] = null;
                        $patient['maximum_benefit_limit'] = null;
                        $patient['max_benefit_limit'] = null;
                        $patient['pre_existing_coverage'] = null;
                        $patient['coverage_start_date'] = null;
                        $patient['coverage_end_date'] = null;
                        $patient['card_status'] = null;
                        $patient['plan_coverage_types'] = [];
                        $patient['coverage_types'] = [];
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'Failed to load insurance details for patient ' . $id . ': ' . $e->getMessage());
                    log_message('error', 'Stack trace: ' . $e->getTraceAsString());
                    // Set all insurance fields to null on error
                    $patient['insurance_provider'] = null;
                    $patient['membership_number'] = null;
                    $patient['hmo_cardholder_name'] = null;
                    $patient['cardholder_name'] = null;
                    $patient['member_type'] = null;
                    $patient['relationship'] = null;
                    $patient['plan_name'] = null;
                    $patient['mbl'] = null;
                    $patient['maximum_benefit_limit'] = null;
                    $patient['max_benefit_limit'] = null;
                    $patient['pre_existing_coverage'] = null;
                    $patient['coverage_start_date'] = null;
                    $patient['coverage_end_date'] = null;
                    $patient['card_status'] = null;
                    $patient['plan_coverage_types'] = [];
                    $patient['coverage_types'] = [];
                }
            } else {
                log_message('debug', 'insurance_details table does not exist');
            }

            // Get current room assignment if patient is an inpatient
            if ($this->db->tableExists('inpatient_room_assignments') && $this->db->tableExists('inpatient_admissions')) {
                $assignmentBuilder = $this->db->table('inpatient_room_assignments ira')
                    ->select('ira.room_type, ira.floor_number, ira.room_number, ira.bed_number, ira.daily_rate')
                    ->join('inpatient_admissions ia', 'ia.admission_id = ira.admission_id', 'inner')
                    ->where('ia.patient_id', $id);
                
                // Check for discharge column - try different possible column names
                if ($this->db->fieldExists('discharge_datetime', 'inpatient_admissions')) {
                    $assignmentBuilder->where('ia.discharge_datetime IS NULL', null, false);
                } elseif ($this->db->fieldExists('discharge_date', 'inpatient_admissions')) {
                    $assignmentBuilder->groupStart()
                        ->where('ia.discharge_date IS NULL', null, false)
                        ->orWhere('ia.discharge_date', '')
                    ->groupEnd();
                } elseif ($this->db->fieldExists('status', 'inpatient_admissions')) {
                    $assignmentBuilder->where('ia.status', 'active');
                }
                // If no discharge/status column exists, just get the most recent (assume active)
                
                $currentAssignment = $assignmentBuilder
                    ->orderBy('ira.room_assignment_id', 'DESC')
                    ->get()
                    ->getRowArray();

                if ($currentAssignment) {
                    $patient['room_type'] = $currentAssignment['room_type'] ?? null;
                    $patient['floor_number'] = $currentAssignment['floor_number'] ?? null;
                    $patient['room_number'] = $currentAssignment['room_number'] ?? null;
                    $patient['bed_number'] = $currentAssignment['bed_number'] ?? null;
                    $patient['daily_rate'] = $currentAssignment['daily_rate'] ?? null;
                }
            }

            // Load emergency contact data from emergency_contacts table
            if ($this->db->tableExists('emergency_contacts')) {
                try {
                    $emergencyContact = $this->db->table('emergency_contacts')
                        ->where('patient_id', (int)$id)
                        ->orderBy('contact_id', 'DESC')
                        ->limit(1)
                        ->get()
                        ->getRowArray();

                    if ($emergencyContact) {
                        // Map emergency contact fields to patient data
                        $patient['guardian_name'] = $emergencyContact['name'] ?? $patient['guardian_name'] ?? $patient['emergency_contact'] ?? null;
                        $patient['emergency_contact'] = $emergencyContact['name'] ?? $patient['emergency_contact'] ?? null;
                        $patient['emergency_contact_name'] = $emergencyContact['name'] ?? $patient['emergency_contact_name'] ?? null;
                        
                        $patient['guardian_contact'] = $emergencyContact['contact_number'] ?? $patient['guardian_contact'] ?? $patient['emergency_phone'] ?? null;
                        $patient['emergency_phone'] = $emergencyContact['contact_number'] ?? $patient['emergency_phone'] ?? null;
                        $patient['emergency_contact_phone'] = $emergencyContact['contact_number'] ?? $patient['emergency_contact_phone'] ?? null;
                        
                        $patient['guardian_relationship'] = $emergencyContact['relationship'] ?? $patient['guardian_relationship'] ?? $patient['emergency_contact_relationship'] ?? null;
                        $patient['emergency_contact_relationship'] = $emergencyContact['relationship'] ?? $patient['emergency_contact_relationship'] ?? null;
                        
                        // Handle "Other" relationship
                        if (isset($emergencyContact['relationship_other']) && $emergencyContact['relationship_other']) {
                            $patient['guardian_relationship_other'] = $emergencyContact['relationship_other'];
                            $patient['emergency_contact_relationship_other'] = $emergencyContact['relationship_other'];
                        }
                        
                        log_message('debug', 'Loaded emergency contact for patient ' . $id . ': name=' . ($emergencyContact['name'] ?? 'N/A') . ', phone=' . ($emergencyContact['contact_number'] ?? 'N/A'));
                    } else {
                        // Initialize emergency contact fields to null if no record exists
                        $patient['guardian_name'] = $patient['guardian_name'] ?? $patient['emergency_contact'] ?? null;
                        $patient['guardian_contact'] = $patient['guardian_contact'] ?? $patient['emergency_phone'] ?? null;
                        $patient['guardian_relationship'] = $patient['guardian_relationship'] ?? $patient['emergency_contact_relationship'] ?? null;
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'Failed to load emergency contact for patient ' . $id . ': ' . $e->getMessage());
                    // Initialize fields to null on error
                    $patient['guardian_name'] = $patient['guardian_name'] ?? $patient['emergency_contact'] ?? null;
                    $patient['guardian_contact'] = $patient['guardian_contact'] ?? $patient['emergency_phone'] ?? null;
                    $patient['guardian_relationship'] = $patient['guardian_relationship'] ?? $patient['emergency_contact_relationship'] ?? null;
                }
            } else {
                // If table doesn't exist, use patient table fields
                $patient['guardian_name'] = $patient['guardian_name'] ?? $patient['emergency_contact'] ?? null;
                $patient['guardian_contact'] = $patient['guardian_contact'] ?? $patient['emergency_phone'] ?? null;
                $patient['guardian_relationship'] = $patient['guardian_relationship'] ?? $patient['emergency_contact_relationship'] ?? null;
            }

            return [
                'status' => 'success',
                'patient' => $patient,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'Error fetching patient: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Database error',
            ];
        }
    }

    public function updatePatient($id, $input)
    {
        // Validation
        $validation = \Config\Services::validation();
        $validation->setRules($this->getValidationRules(), $this->getValidationMessages());

        if (!$validation->run($input)) {
            return [
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validation->getErrors(),
            ];
        }

        $data = [
            'first_name' => $input['first_name'],
            'middle_name' => $input['middle_name'] ?? null,
            'last_name' => $input['last_name'],
            'gender' => ucfirst(strtolower($input['gender'] ?? '')),
            'civil_status' => $input['civil_status'],
            'date_of_birth' => $input['date_of_birth'],
            'contact_no' => $input['phone'] ?? null,
            'email' => $input['email'] ?? null,
            'address' => $input['address'],
            'province' => $input['province'],
            'city' => $input['city'],
            'barangay' => $input['barangay'],
            'subdivision' => $input['subdivision'] ?? null,
            'house_number' => $input['house_number'] ?? null,
            'zip_code' => $input['zip_code'],
            'insurance_provider' => $input['insurance_provider'] ?? null,
            'insurance_number' => $input['insurance_number'] ?? null,
            'emergency_contact' => $input['emergency_contact_name'] ?? $input['guardian_name'] ?? null,
            'emergency_phone' => $input['emergency_contact_phone'] ?? $input['guardian_contact'] ?? null,
            'emergency_contact_relationship' => $input['emergency_contact_relationship'] ?? $input['guardian_relationship'] ?? null,
            'patient_type' => ucfirst(strtolower($input['patient_type'] ?? 'Outpatient')),
            'blood_group' => $input['blood_group'] ?? null,
            'medical_notes' => $input['medical_notes'] ?? null,
            'status' => ucfirst(strtolower($input['status'] ?? 'Active')),
        ];

        $data = $this->sanitizePatientDataForTable($data);

        try {
            $this->db->table($this->patientTable)->where('patient_id', $id)->update($data);
            
            // Update emergency contact record if provided
            $this->updateEmergencyContactRecord($id, $input);
            
            // Also update insurance details if provided
            $this->persistInsuranceDetails($id, $input);
            
            return [
                'status' => 'success',
                'message' => 'Patient updated successfully',
            ];
        } catch (\Throwable $e) {
            log_message('error', 'Failed to update patient: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Delete patient
     */
    public function deletePatient($id, $userRole)
    {
        try {
            // Check if patient exists
            $existing = $this->db->table($this->patientTable)->where('patient_id', $id)->get()->getRowArray();
            if (!$existing) {
                return ['success' => false, 'message' => 'Patient not found'];
            }

            // Only admin and IT staff can delete patients
            if (!in_array($userRole, ['admin', 'it_staff'])) {
                return ['success' => false, 'message' => 'Insufficient permissions'];
            }

            // Delete patient record
            if ($this->db->table($this->patientTable)->where('patient_id', $id)->delete()) {
                return ['success' => true, 'message' => 'Patient deleted successfully'];
            }

            return ['success' => false, 'message' => 'Failed to delete patient'];

        } catch (\Throwable $e) {
            log_message('error', 'Patient deletion error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Get patient statistics based on user role
     */
    public function getPatientStats($userRole, $staffId)
    {
        try {
            $stats = [];

            switch ($userRole) {
                case 'admin':
                case 'it_staff':
                    $stats = [
                        'total_patients' => $this->db->table($this->patientTable)->countAllResults(),
                        'active_patients' => $this->countPatientFieldValue('status', 'Active'),
                        'inactive_patients' => $this->countPatientFieldValue('status', 'Inactive'),
                        'outpatients' => $this->countPatientFieldValue('patient_type', 'Outpatient'),
                        'inpatients' => $this->countPatientFieldValue('patient_type', 'Inpatient'),
                        'emergency_patients' => $this->countPatientFieldValue('patient_type', 'Emergency'),
                    ];
                    break;

                case 'doctor':
                    $doctorInfo = $this->db->table('doctor')->where('staff_id', $staffId)->get()->getRowArray();
                    $doctorId = $doctorInfo['doctor_id'] ?? null;

                    if ($doctorId) {
                        $stats = [
                            'my_patients' => $this->db->table($this->patientTable)->where('primary_doctor_id', $doctorId)->countAllResults(),
                            'active_patients' => $this->countPatientFieldValue('status', 'Active', ['primary_doctor_id' => $doctorId]),
                            'new_patients_month' => $this->db->table($this->patientTable)
                                ->where('primary_doctor_id', $doctorId)
                                ->where('date_registered >=', date('Y-m-01'))
                                ->countAllResults(),
                            'emergency_patients' => $this->countPatientFieldValue('patient_type', 'Emergency', ['primary_doctor_id' => $doctorId]),
                        ];
                    } else {
                        $stats = [
                            'my_patients' => 0,
                            'active_patients' => 0,
                            'new_patients_month' => 0,
                            'emergency_patients' => 0,
                        ];
                    }
                    break;

                case 'nurse':
                    // Nurses can see all patients (view_all permission)
                    $stats = [
                        'total_patients' => $this->db->table($this->patientTable)->countAllResults(),
                        'active_patients' => $this->countPatientFieldValue('status', 'Active'),
                        'new_patients_today' => $this->db->table($this->patientTable)->where('date_registered', date('Y-m-d'))->countAllResults(),
                        'new_patients_week' => $this->db->table($this->patientTable)->where('date_registered >=', date('Y-m-d', strtotime('-7 days')))->countAllResults(),
                    ];
                    break;

                case 'receptionist':
                    $stats = [
                        'total_patients' => $this->db->table($this->patientTable)->countAllResults(),
                        'active_patients' => $this->countPatientFieldValue('status', 'Active'),
                        'new_patients_today' => $this->db->table($this->patientTable)->where('date_registered', date('Y-m-d'))->countAllResults(),
                        'new_patients_week' => $this->db->table($this->patientTable)->where('date_registered >=', date('Y-m-d', strtotime('-7 days')))->countAllResults(),
                    ];
                    break;

                case 'pharmacist':
                    $stats = [
                        'patients_with_prescriptions' => $this->db->table($this->patientTable . ' p')
                            ->join('prescription pr', 'pr.patient_id = p.patient_id', 'inner')
                            ->countAllResults(),
                        'active_prescriptions' => $this->db->table('prescription')->where('status', 'Active')->countAllResults(),
                    ];
                    break;

                case 'laboratorist':
                    $stats = [
                        'patients_with_tests' => $this->db->table($this->patientTable . ' p')
                            ->join('lab_test lt', 'lt.patient_id = p.patient_id', 'inner')
                            ->countAllResults(),
                        'pending_tests' => $this->db->table('lab_test')->where('status', 'Pending')->countAllResults(),
                    ];
                    break;

                case 'accountant':
                    $stats = [
                        'total_patients' => $this->db->table($this->patientTable)->countAllResults(),
                        'active_patients' => $this->countPatientFieldValue('status', 'Active'),
                        'patients_with_insurance' => $this->db->table($this->patientTable)->where('insurance_provider IS NOT NULL')->countAllResults(),
                    ];
                    break;

                default:
                    $stats = [
                        'total_patients' => $this->db->table($this->patientTable)->countAllResults(),
                        'active_patients' => $this->countPatientFieldValue('status', 'Active'),
                    ];
            }

            return $stats;
        } catch (\Throwable $e) {
            log_message('error', 'Patient stats error: ' . $e->getMessage());
            return [];
        }
    }

    private function countPatientFieldValue(string $field, $value, array $additionalConditions = []): int
    {
        if (! $this->patientTableHasColumn($field)) {
            return 0;
        }

        $builder = $this->db->table($this->patientTable);
        $builder->where($field, $value);

        foreach ($additionalConditions as $key => $val) {
            $builder->where($key, $val);
        }

        return $builder->countAllResults();
    }

    /**
     * Get all patients for API
     */
    public function getAllPatients($userRole = null, $staffId = null)
    {
        try {
            if ($userRole && $staffId) {
                return $this->getPatientsByRole($userRole, $staffId);
            }

            $builder = $this->db->table($this->patientTable . ' p');
            
            $hasPrimaryDoctor = $this->patientTableHasColumn('primary_doctor_id');
            if ($hasPrimaryDoctor) {
                // Join doctor table first, then staff to get doctor name
                $builder->select('p.*, CONCAT(s.first_name, " ", s.last_name) as assigned_doctor_name')
                        ->join('doctor d', 'd.doctor_id = p.primary_doctor_id', 'left')
                        ->join('staff s', 's.staff_id = d.staff_id', 'left');
            } else {
                $builder->select('p.*');
            }
            
            $patients = $builder->orderBy('p.patient_id', 'DESC')
                ->get()
                ->getResultArray();
            
            foreach ($patients as &$p) {
                $p['age'] = $p['date_of_birth']
                    ? (new \DateTime())->diff(new \DateTime($p['date_of_birth']))->y
                    : null;
                $p['id'] = $p['patient_id'];
                $p['full_name'] = trim(($p['first_name'] ?? '') . ' ' . ($p['middle_name'] ?? '') . ' ' . ($p['last_name'] ?? ''));
            }

            return $patients;

        } catch (\Throwable $e) {
            log_message('error', 'Patient list error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get available doctors for patient assignment
     */
    public function getAvailableDoctors()
    {
        if (!$this->db->tableExists('staff')) {
            return [];
        }

        // Check if doctor table exists and has specialization
        $hasDoctorTable = $this->db->tableExists('doctor');
        
        if ($hasDoctorTable) {
            $staffDoctors = $this->db->table('staff s')
                ->select('s.staff_id, s.first_name, s.last_name, d.specialization, d.status')
                ->join('roles r', 'r.role_id = s.role_id', 'inner')
                ->join('doctor d', 'd.staff_id = s.staff_id', 'left')
                ->where('r.slug', 'doctor')
                ->where('d.status', 'Active')
                ->orderBy('s.first_name', 'ASC')
                ->get()
                ->getResultArray();
        } else {
            $staffDoctors = $this->db->table('staff s')
                ->select('s.staff_id, s.first_name, s.last_name')
                ->join('roles r', 'r.role_id = s.role_id', 'inner')
                ->where('r.slug', 'doctor')
                ->orderBy('s.first_name', 'ASC')
                ->get()
                ->getResultArray();
        }

        return $this->formatDoctorData($staffDoctors);
    }

    private function formatDoctorData($doctors)
    {
        return array_map(function($d) {
            // Ensure we have valid names
            $first = trim($d['first_name'] ?? '');
            $last = trim($d['last_name'] ?? '');
            if ($first === '' && $last === '') {
                $d['first_name'] = 'Doctor';
                $d['last_name'] = (string)($d['staff_id'] ?? '');
            }
            $d['full_name'] = trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? ''));
            $d['id'] = $d['staff_id'];
            $d['specialization'] = $d['specialization'] ?? null;
            return $d;
        }, $doctors);
    }

    private function determinePrimaryDoctor($input, $userRole, $staffId)
    {
        if ($userRole === 'doctor') {
            // For doctors, get their doctor_id from the doctor table using their staff_id
            $doctorRecord = $this->db->table('doctor')
                ->select('doctor_id')
                ->where('staff_id', $staffId)
                ->get()
                ->getRowArray();
            return $doctorRecord['doctor_id'] ?? null;
        }

        // Admin, Receptionist, and IT Staff can optionally choose a doctor; otherwise fallback
        if (in_array($userRole, ['admin', 'receptionist', 'it_staff'])) {
            if (!empty($input['assigned_doctor'])) {
                // Convert staff_id to doctor_id
                $doctorRecord = $this->db->table('doctor')
                    ->select('doctor_id')
                    ->where('staff_id', (int)$input['assigned_doctor'])
                    ->get()
                    ->getRowArray();
                return $doctorRecord['doctor_id'] ?? null;
            }

            // Check patient type - do not auto-assign for outpatients
            $patientType = strtolower($input['patient_type'] ?? 'outpatient');
            if ($patientType === 'outpatient') {
                // For outpatients, do not automatically assign a doctor
                return null;
            }

            // Fallback to first available doctor from doctor table (for inpatients and other types)
            $firstDoctor = $this->db->table('doctor d')
                ->select('d.doctor_id')
                ->join('staff s', 's.staff_id = d.staff_id', 'inner')
                ->orderBy('s.first_name', 'ASC')
                ->get()
                ->getRowArray();

            return $firstDoctor['doctor_id'] ?? null;
        }

        return null;
    }

    private function getValidationRules()
    {
        return [
            'first_name' => 'required|min_length[2]|max_length[100]',
            'last_name' => 'required|min_length[2]|max_length[100]',
            'gender' => 'required|in_list[male,female,other,MALE,FEMALE,OTHER,Male,Female,Other]',
            'date_of_birth' => 'required|valid_date',
            'civil_status' => 'required',
            'phone' => 'required|regex_match[/^09\d{9}$/]',
            'email' => 'permit_empty|valid_email',
            'address' => 'required',
            // Inpatient-only details are optional at backend level; frontend enforces them when needed
            'province' => 'permit_empty|max_length[100]',
            'city' => 'permit_empty|max_length[100]',
            'barangay' => 'permit_empty|max_length[100]',
            'zip_code' => 'permit_empty|max_length[20]',
            'emergency_contact_name' => 'permit_empty|max_length[100]',
            'emergency_contact_phone' => 'permit_empty|regex_match[/^09\d{9}$/]',
            'patient_type' => 'permit_empty|in_list[outpatient,inpatient,emergency,Outpatient,Inpatient,Emergency]',
        ];
    }

    private function getValidationMessages(): array
    {
        return [
            'phone' => [
                'regex_match' => 'Contact number must start with 09 and be exactly 11 digits.',
            ],
            'emergency_contact_phone' => [
                'regex_match' => 'Emergency contact number must start with 09 and be exactly 11 digits.',
            ],
        ];
    }

    private function preparePatientData($input, $primaryDoctorId)
    {
        $data = [
            'first_name' => $input['first_name'] ?? null,
            'middle_name' => $input['middle_name'] ?? null,
            'last_name' => $input['last_name'] ?? null,
            'gender' => ucfirst(strtolower($input['gender'] ?? '')),
            'sex' => ucfirst(strtolower($input['gender'] ?? $input['sex'] ?? '')),
            'civil_status' => $input['civil_status'] ?? null,
            'date_of_birth' => $input['date_of_birth'] ?? null,
            'contact_no' => $input['phone'] ?? ($input['contact_no'] ?? ($input['contact_number'] ?? null)),
            'contact_number' => $input['phone'] ?? ($input['contact_number'] ?? ($input['contact_no'] ?? null)),
            'email' => $input['email'] ?? null,
            'address' => $input['address'] ?? null,
            'house_number' => $input['house_number'] ?? null,
            'subdivision' => $input['subdivision'] ?? null,
            'province' => $input['province'] ?? null,
            'city' => $input['city'] ?? null,
            'barangay' => $input['barangay'] ?? null,
            'zip_code' => $input['zip_code'] ?? null,
            'insurance_provider' => $input['insurance_provider'] ?? null,
            'insurance_number' => $input['insurance_number'] ?? null,
            'insurance_card_number' => $input['insurance_card_number'] ?? null,
            'insurance_validity' => $input['insurance_validity'] ?? null,
            'hmo_member_id' => $input['hmo_member_id'] ?? null,
            'hmo_approval_code' => $input['hmo_approval_code'] ?? null,
            'hmo_cardholder_name' => $input['hmo_cardholder_name'] ?? null,
            'hmo_coverage_type' => $input['hmo_coverage_type'] ?? null,
            'hmo_expiry_date' => $input['hmo_expiry_date'] ?? null,
            'hmo_contact_person' => $input['hmo_contact_person'] ?? null,
            'hmo_attachment' => $input['hmo_attachment'] ?? null,
            'emergency_contact' => $input['emergency_contact_name'] ?? ($input['emergency_contact'] ?? null),
            'emergency_phone' => $input['emergency_contact_phone'] ?? ($input['emergency_phone'] ?? null),
            'emergency_contact_name' => $input['emergency_contact_name'] ?? ($input['emergency_contact'] ?? null),
            'emergency_contact_relationship' => $input['emergency_contact_relationship'] ?? null,
            'patient_type' => ucfirst(strtolower($input['patient_type'] ?? 'Outpatient')),
            'blood_group' => $input['blood_group'] ?? null,
            'medical_notes' => $input['medical_notes'] ?? null,
            'date_registered' => date('Y-m-d'),
            'status' => ucfirst(strtolower($input['status'] ?? 'Active')),
        ];

        // Include primary_doctor_id only if the column exists in the schema
        if ($this->patientTableHasColumn('primary_doctor_id')) {
            $data['primary_doctor_id'] = $primaryDoctorId;
        }

        return $data;
    }

    /**
     * Check if a column exists on the patient table
     */
    private function patientTableHasColumn(string $column): bool
    {
        return in_array($column, $this->patientTableColumns, true);
    }

    private function loadPatientTableColumns(): array
    {
        try {
            if (! $this->db->tableExists($this->patientTable)) {
                return [];
            }

            $fields = $this->db->getFieldData($this->patientTable);
            $columns = array_map(fn ($field) => $field->name ?? null, $fields);
            return array_filter($columns);
        } catch (\Throwable $e) {
            log_message('warning', 'Unable to load patient table columns: ' . $e->getMessage());
            return [];
        }
    }

    private function sanitizePatientDataForTable(array $data): array
    {
        if (empty($this->patientTableColumns)) {
            // If we can't get columns, return data as-is but log a warning
            log_message('debug', 'PatientService: Could not load patient table columns, saving all provided fields');
            return $data;
        }

        $allowed = array_flip($this->patientTableColumns);
        $sanitized = array_intersect_key($data, $allowed);
        
        // Log any fields that were filtered out for debugging (only in debug mode)
        $filteredOut = array_diff_key($data, $allowed);
        if (!empty($filteredOut) && ENVIRONMENT === 'development') {
            log_message('debug', 'PatientService: Filtered out fields that don\'t exist in table: ' . implode(', ', array_keys($filteredOut)));
        }
        
        return $sanitized;
    }

    /**
     * Create emergency contact record
     */
    private function createEmergencyContactRecord(int $patientId, array $input): void
    {
        if (!$this->db->tableExists('emergency_contacts')) {
            return;
        }

        $contactName = $input['emergency_contact_name'] ?? $input['emergency_contact'] ?? $input['guardian_name'] ?? null;
        $contactPhone = $input['emergency_contact_phone'] ?? $input['emergency_phone'] ?? $input['guardian_contact'] ?? null;
        $relationship = $input['emergency_contact_relationship'] ?? $input['guardian_relationship'] ?? null;

        if (!$contactName || !$contactPhone) {
            return;
        }

        $contactData = [
            'patient_id' => $patientId,
            'name' => $contactName,
            'relationship' => $relationship ?? 'Other',
            'contact_number' => $contactPhone,
        ];

        try {
            $this->db->table('emergency_contacts')->insert($contactData);
        } catch (\Throwable $e) {
            log_message('error', 'Failed to insert emergency contact for patient ' . $patientId . ': ' . $e->getMessage());
        }
    }

    /**
     * Update emergency contact record for a patient
     */
    private function updateEmergencyContactRecord(int $patientId, array $input): void
    {
        if (!$this->db->tableExists('emergency_contacts')) {
            return;
        }

        $contactName = $input['emergency_contact_name'] ?? $input['emergency_contact'] ?? $input['guardian_name'] ?? null;
        $contactPhone = $input['emergency_contact_phone'] ?? $input['emergency_phone'] ?? $input['guardian_contact'] ?? null;
        $relationship = $input['emergency_contact_relationship'] ?? $input['guardian_relationship'] ?? null;

        if (!$contactName || !$contactPhone) {
            return;
        }

        $contactData = [
            'name' => $contactName,
            'relationship' => $relationship ?? 'Other',
            'contact_number' => $contactPhone,
        ];

        try {
            // Check if emergency contact record exists
            $existing = $this->db->table('emergency_contacts')
                ->where('patient_id', $patientId)
                ->get()
                ->getRowArray();

            if ($existing) {
                // Update existing record
                $this->db->table('emergency_contacts')
                    ->where('patient_id', $patientId)
                    ->update($contactData);
            } else {
                // Create new record
                $contactData['patient_id'] = $patientId;
                $this->db->table('emergency_contacts')->insert($contactData);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Failed to update emergency contact for patient ' . $patientId . ': ' . $e->getMessage());
        }
    }

    /**
     * Persist insurance/HMO details to insurance_details table
     */
    private function persistInsuranceDetails(int $patientId, array $input): void
    {
        if (!$this->db->tableExists('insurance_details')) {
            return;
        }

        // Check if we have any insurance information
        $hasInsurance = (
            !empty($input['insurance_provider'] ?? null) ||
            !empty($input['insurance_number'] ?? null) ||
            !empty($input['insurance_card_number'] ?? null) ||
            !empty($input['hmo_member_id'] ?? null) ||
            !empty($input['hmo_cardholder_name'] ?? null)
        );

        if (!$hasInsurance) {
            return;
        }

        // Get patient name for card holder if not provided
        $cardHolderName = $input['hmo_cardholder_name'] ?? 
                         $input['insurance_cardholder_name'] ?? 
                         trim(($input['first_name'] ?? '') . ' ' . ($input['last_name'] ?? ''));

        if (empty($cardHolderName)) {
            // Try to get from database
            try {
                $patientTable = $this->resolvePatientTableName();
                $patient = $this->db->table($patientTable)
                    ->select('first_name, last_name')
                    ->where('patient_id', $patientId)
                    ->get()
                    ->getRowArray();
                
                if ($patient) {
                    $cardHolderName = trim(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? ''));
                }
            } catch (\Throwable $e) {
                log_message('error', 'Failed to fetch patient name for insurance details: ' . $e->getMessage());
            }
        }

        // Determine provider
        $provider = $input['insurance_provider'] ?? 
                   $input['hmo_provider'] ?? 
                   'Unknown';

        // Determine membership number
        $membershipNumber = $input['hmo_member_id'] ?? 
                          $input['insurance_number'] ?? 
                          $input['insurance_card_number'] ?? 
                          '';

        // Determine plan name (default if not provided)
        $planName = $input['hmo_plan_name'] ?? 
                   $input['insurance_plan'] ?? 
                   'Standard Plan';

        // Determine coverage type (JSON array) from form input
        $coverageType = null;
        if (!empty($input['plan_coverage_types']) && is_array($input['plan_coverage_types'])) {
            // Use the actual coverage types from the form checkboxes
            $coverageType = json_encode(array_filter(array_map('trim', $input['plan_coverage_types'])));
        } else if (!empty($input['plan_coverage_types']) && is_string($input['plan_coverage_types'])) {
            // If it's a string, try to parse it
            $decoded = json_decode($input['plan_coverage_types'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $coverageType = json_encode($decoded);
            } else {
                // Treat as comma-separated
                $coverageType = json_encode(array_filter(array_map('trim', explode(',', $input['plan_coverage_types']))));
            }
        } else {
            // Fallback: infer from patient type
            $patientType = strtolower($input['patient_type'] ?? 'outpatient');
            if ($patientType === 'inpatient') {
                $coverageType = json_encode(['Inpatient']);
            } else {
                $coverageType = json_encode(['Outpatient']);
            }
        }

        // Determine dates
        $hmoExpiryDate = $input['hmo_expiry_date'] ?? null;
        $insuranceValidity = $input['insurance_validity'] ?? null;
        $hmoStartDate = $input['hmo_start_date'] ?? null;
        
        // Calculate start date
        if ($hmoStartDate) {
            $startDate = $hmoStartDate;
        } elseif ($insuranceValidity) {
            $startDate = $insuranceValidity;
        } elseif ($hmoExpiryDate) {
            // If we only have expiry date, calculate start date as 1 year before
            $startDate = date('Y-m-d', strtotime($hmoExpiryDate . ' -1 year'));
        } else {
            $startDate = date('Y-m-d');
        }
        
        // Calculate end date
        if ($hmoExpiryDate) {
            $endDate = $hmoExpiryDate;
        } elseif ($insuranceValidity) {
            $endDate = $insuranceValidity;
        } else {
            $endDate = date('Y-m-d', strtotime('+1 year'));
        }

        // Prepare insurance detail data
        $insuranceData = [
            'patient_id' => $patientId,
            'provider' => $provider,
            'membership_number' => $membershipNumber ?: 'N/A',
            'card_holder_name' => $cardHolderName ?: 'Unknown',
            'member_type' => $input['member_type'] ?? $input['hmo_member_type'] ?? 'Principal',
            'relationship' => $input['relationship'] ?? $input['hmo_relationship'] ?? null,
            'plan_name' => $planName,
            'coverage_type' => $coverageType,
            'mbl' => $input['mbl'] ?? $input['hmo_mbl'] ?? $input['maximum_benefit_limit'] ?? null,
            'pre_existing_coverage' => $input['pre_existing_coverage'] ?? null,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'card_status' => $input['card_status'] ?? 'Active',
            'created_at' => date('Y-m-d H:i:s'),
        ];

        // Filter to only include columns that exist in the table
        try {
            $fields = $this->db->getFieldData('insurance_details');
            $existingColumns = array_map(static fn($field) => $field->name ?? null, $fields);
            $existingColumns = array_filter($existingColumns);
            $insuranceData = array_intersect_key($insuranceData, array_flip($existingColumns));
        } catch (\Throwable $e) {
            log_message('warning', 'Failed to get insurance_details table columns: ' . $e->getMessage());
        }

        try {
            // Check if insurance details already exist for this patient
            $existing = $this->db->table('insurance_details')
                ->where('patient_id', $patientId)
                ->get()
                ->getRowArray();
            
            if ($existing) {
                // Update existing record
                $this->db->table('insurance_details')
                    ->where('patient_id', $patientId)
                    ->update($insuranceData);
            } else {
                // Insert new record
                $this->db->table('insurance_details')->insert($insuranceData);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Failed to save insurance details for patient ' . $patientId . ': ' . $e->getMessage());
            log_message('error', 'Insurance data attempted: ' . json_encode($insuranceData));
            // Don't throw - insurance details are optional, so we continue even if this fails
        }
    }

    private function persistRoleSpecificRecords(int $patientId, array $input): void
    {
        if (! $patientId) {
            return;
        }

        $patientType = strtolower($input['patient_type'] ?? 'outpatient');

        if ($patientType === 'inpatient') {
            $this->createInpatientAdmissionRecord($patientId, $input);
            return;
        }

        // Default: treat as outpatient visit
        $this->createOutpatientVisitRecord($patientId, $input);
    }

    /**
     * Create outpatient visit record
     */
    private function createOutpatientVisitRecord(int $patientId, array $input): void
    {
        if (!$this->db->tableExists('outpatient_visits')) {
            return;
        }

        // Get assigned doctor name if staff_id is provided
        $assignedDoctor = null;
        if (!empty($input['assigned_doctor'])) {
            if (is_numeric($input['assigned_doctor'])) {
                $assignedDoctor = $this->resolveDoctorFullName($input['assigned_doctor']);
            } else {
                $assignedDoctor = $input['assigned_doctor'];
            }
        }

        $visitData = [
            'patient_id' => $patientId,
            'department' => $input['department'] ?? null,
            'assigned_doctor' => $assignedDoctor,
            'appointment_datetime' => $input['appointment_datetime'] ?? null,
            'visit_type' => $input['visit_type'] ?? null,
            'chief_complaint' => $input['chief_complaint'] ?? null,
            'allergies' => $input['allergies'] ?? null,
            'existing_conditions' => $input['existing_conditions'] ?? null,
            'current_medications' => $input['current_medications'] ?? null,
            'blood_pressure' => $input['blood_pressure'] ?? null,
            'heart_rate' => $input['heart_rate'] ?? null,
            'respiratory_rate' => $input['respiratory_rate'] ?? null,
            'temperature' => $input['temperature'] ?? null,
            'weight' => $input['weight_kg'] ?? $input['weight'] ?? null,
            'height' => $input['height_cm'] ?? $input['height'] ?? null,
            'payment_type' => $input['payment_type'] ?? null,
        ];

        try {
            // Filter to only include columns that exist
            $fields = $this->db->getFieldData('outpatient_visits');
            $existingColumns = array_map(static fn($field) => $field->name ?? null, $fields);
            $existingColumns = array_filter($existingColumns);

            $visitData = array_intersect_key($visitData, array_flip($existingColumns));
            $this->db->table('outpatient_visits')->insert($visitData);
        } catch (\Throwable $e) {
            log_message('error', 'Failed to insert outpatient visit for patient ' . $patientId . ': ' . $e->getMessage());
        }
    }

    /**
     * Create inpatient admission + related inpatient records
     */
    private function createInpatientAdmissionRecord(int $patientId, array $input): void
    {
        if (! $this->db->tableExists('inpatient_admissions')) {
            return;
        }

        $consent = $input['consent_uploaded'] ?? $input['consent_signed'] ?? null;

        // Normalize admission_type to match ENUM values in inpatient_admissions table
        $rawAdmissionType = $input['admission_type'] ?? null;
        $normalizedAdmissionType = null;

        if ($rawAdmissionType !== null && $rawAdmissionType !== '') {
            switch ($rawAdmissionType) {
                case 'Transfer from other facility/hospital':
                    $normalizedAdmissionType = 'Transfer';
                    break;
                default:
                    $normalizedAdmissionType = $rawAdmissionType;
                    break;
            }

            if (! in_array($normalizedAdmissionType, ['ER', 'Scheduled', 'Transfer'], true)) {
                $normalizedAdmissionType = null;
            }
        }

        // Base admission data
        $admissionData = [
            'patient_id'          => $patientId,
            'admission_datetime'  => $input['admission_datetime'] ?? null,
            'admission_type'      => $normalizedAdmissionType,
            'admitting_diagnosis' => $input['admitting_diagnosis'] ?? null,
            'admitting_doctor'    => $input['admitting_doctor'] ?? null,
            'consent_signed'      => in_array($consent, ['1', 'true', 'yes', 'on'], true) ? 1 : 0,
            'insurance_provider'  => $input['insurance_provider'] ?? null,
            'insurance_card_number' => $input['insurance_card_number'] ?? null,
            'insurance_validity'  => $input['insurance_validity'] ?? null,
            'hmo_member_id'       => $input['hmo_member_id'] ?? null,
            'hmo_approval_code'   => $input['hmo_approval_code'] ?? null,
            'hmo_cardholder_name' => $input['hmo_cardholder_name'] ?? null,
            'hmo_coverage_type'   => $input['hmo_coverage_type'] ?? null,
            'hmo_expiry_date'     => $input['hmo_expiry_date'] ?? null,
            'hmo_contact_person'  => $input['hmo_contact_person'] ?? null,
        ];

        // Filter admission data to only include columns that actually exist on inpatient_admissions
        try {
            $fields = $this->db->getFieldData('inpatient_admissions');
            $existingColumns = array_map(static fn($field) => $field->name ?? null, $fields);
            $existingColumns = array_filter($existingColumns);

            $admissionData = array_intersect_key($admissionData, array_flip($existingColumns));
        } catch (\Throwable $e) {
            // If schema inspection fails, continue with raw $admissionData; insert try/catch below
            // will still guard the transaction and log any concrete DB error.
        }

        try {
            $this->db->table('inpatient_admissions')->insert($admissionData);

            $admissionId = (int) $this->db->insertID();

            if ($admissionId) {
                $this->createInpatientMedicalHistoryRecord($admissionId, $input);
                $this->createInpatientInitialAssessmentRecord($admissionId, $input);
                $this->createInpatientRoomAssignmentRecord($admissionId, $input);

                $this->createInsuranceClaimForInpatient($patientId, $admissionId, $input);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Failed to insert inpatient admission for patient ' . $patientId . ': ' . $e->getMessage());
        }
    }

    private function createInpatientMedicalHistoryRecord(int $admissionId, array $input): void
    {
        if (! $this->db->tableExists('inpatient_medical_history')) {
            return;
        }

        $historyData = [
            'admission_id' => $admissionId,
            'allergies' => $input['history_allergies'] ?? $input['allergies'] ?? null,
            'past_medical_history' => $input['past_medical_history'] ?? null,
            'past_surgical_history' => $input['past_surgical_history'] ?? null,
            'family_history' => $input['family_history'] ?? null,
            'current_medications' => $input['history_current_medications'] ?? $input['current_medications'] ?? null,
        ];

        try {
            $this->db->table('inpatient_medical_history')->insert($historyData);
        } catch (\Throwable $e) {
            log_message('error', 'Failed to insert inpatient medical history for admission ' . $admissionId . ': ' . $e->getMessage());
        }
    }

    private function createInpatientInitialAssessmentRecord(int $admissionId, array $input): void
    {
        if (! $this->db->tableExists('inpatient_initial_assessment')) {
            return;
        }

        $assessmentData = [
            'admission_id' => $admissionId,
            'blood_pressure' => $input['assessment_bp'] ?? null,
            'heart_rate' => $input['assessment_hr'] ?? null,
            'respiratory_rate' => $input['assessment_rr'] ?? null,
            'temperature' => $input['assessment_temp'] ?? null,
            'spo2' => $input['assessment_spo2'] ?? null,
            'level_of_consciousness' => $input['level_of_consciousness'] ?? null,
            'pain_level' => $input['pain_level'] ?? null,
            'initial_findings' => $input['initial_findings'] ?? null,
            'remarks' => $input['assessment_remarks'] ?? null,
        ];

        try {
            $this->db->table('inpatient_initial_assessment')->insert($assessmentData);
        } catch (\Throwable $e) {
            log_message('error', 'Failed to insert inpatient initial assessment for admission ' . $admissionId . ': ' . $e->getMessage());
        }
    }

    private function createInpatientRoomAssignmentRecord(int $admissionId, array $input): void
    {
        if (! $this->db->tableExists('inpatient_room_assignments')) {
            return;
        }

        // Normalize daily_rate: the UI may submit a placeholder like 'Auto-calculated'
        // which is not a valid DECIMAL value for the DB schema.
        $rawDailyRate = $input['daily_rate'] ?? null;
        $normalizedDailyRate = null;

        if ($rawDailyRate !== null && $rawDailyRate !== '') {
            // Accept numeric strings, otherwise leave as NULL
            $numeric = is_numeric($rawDailyRate) ? (float) $rawDailyRate : null;
            if ($numeric !== null) {
                $normalizedDailyRate = $numeric;
            }
        }

        // Get room_type from input, handling both text and enum values
        $roomType = $input['room_type'] ?? null;
        if ($roomType && !in_array($roomType, ['Ward', 'Semi-Private', 'Private', 'Isolation', 'ICU'], true)) {
            // Try to map common variations
            $roomTypeMap = [
                'ward' => 'Ward',
                'semi-private' => 'Semi-Private',
                'semi_private' => 'Semi-Private',
                'private' => 'Private',
                'isolation' => 'Isolation',
                'icu' => 'ICU',
            ];
            $roomType = $roomTypeMap[strtolower($roomType)] ?? null;
        }

        $roomNumber = $input['room_number'] ?? null;
        $floorNumber = $input['floor_number'] ?? null;
        $bedNumber = $input['bed_number'] ?? null;
        $roomId = null;

        // Look up room_id from room table if room_number is provided
        if ($roomNumber && $this->db->tableExists('room')) {
            $roomBuilder = $this->db->table('room')
                ->where('room_number', $roomNumber);

            // Match floor_number if provided
            if ($floorNumber) {
                $roomBuilder->where('floor_number', $floorNumber);
            }

            $room = $roomBuilder->get()->getRowArray();

            if ($room && isset($room['room_id'])) {
                $roomId = (int) $room['room_id'];

                // Validate room is available or can be assigned
                $roomStatus = $room['status'] ?? 'available';
                if ($roomStatus === 'maintenance') {
                    log_message('warning', "Attempted to assign patient to room {$roomNumber} which is under maintenance");
                    // Continue anyway, but log the warning
                }

                // Update room status to 'occupied' if it's available
                if ($roomStatus === 'available') {
                    try {
                        $this->db->table('room')
                            ->where('room_id', $roomId)
                            ->update(['status' => 'occupied']);
                    } catch (\Throwable $e) {
                        log_message('error', 'Failed to update room status to occupied: ' . $e->getMessage());
                    }
                }
            } else {
                log_message('warning', "Room {$roomNumber} on floor {$floorNumber} not found in room table");
            }
        }

        $roomData = [
            'admission_id' => $admissionId,
            'room_id' => $roomId,
            'room_type' => $roomType,
            'floor_number' => $floorNumber,
            'room_number' => $roomNumber,
            'bed_number' => $bedNumber,
            'daily_rate' => $normalizedDailyRate,
        ];

        try {
            $this->db->table('inpatient_room_assignments')->insert($roomData);
            $roomAssignmentId = (int) $this->db->insertID();

            // Auto-add room charge to billing if room assignment was created successfully
            if ($roomAssignmentId > 0 && (!empty($roomNumber) || !empty($roomType))) {
                $this->addRoomChargeToBilling($admissionId, $roomAssignmentId, $normalizedDailyRate);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Failed to insert inpatient room assignment for admission ' . $admissionId . ': ' . $e->getMessage());
            
            // Rollback room status if assignment failed
            if ($roomId && $this->db->tableExists('room')) {
                try {
                    $this->db->table('room')
                        ->where('room_id', $roomId)
                        ->update(['status' => 'available']);
                } catch (\Throwable $rollbackError) {
                    log_message('error', 'Failed to rollback room status: ' . $rollbackError->getMessage());
                }
            }
        }
    }

    /**
     * Add room charge to billing account when patient is admitted with room assignment
     */
    private function addRoomChargeToBilling(int $admissionId, int $roomAssignmentId, ?float $dailyRate = null): void
    {
        try {
            // Get patient ID from admission
            $admission = $this->db->table('inpatient_admissions')
                ->where('admission_id', $admissionId)
                ->get()
                ->getRowArray();

            if (!$admission || empty($admission['patient_id'])) {
                log_message('warning', "Cannot add room charge to billing: Admission {$admissionId} not found or has no patient_id");
                return;
            }

            $patientId = (int) $admission['patient_id'];
            $staffId = (int) (session()->get('staff_id') ?? 0);

            // Check if FinancialService is available
            if (!class_exists(\App\Services\FinancialService::class)) {
                log_message('warning', 'FinancialService not available for auto-billing room charge');
                return;
            }

            $financialService = new \App\Services\FinancialService();

            // Get or create billing account for this patient and admission
            $account = $financialService->getOrCreateBillingAccountForPatient($patientId, $admissionId, $staffId);
            if (!$account || empty($account['billing_id'])) {
                log_message('warning', "Failed to get/create billing account for patient {$patientId}, admission {$admissionId}");
                return;
            }

            $billingId = (int) $account['billing_id'];

            // Add room charge to billing (quantity = 1 day initially, can be updated on discharge)
            $result = $financialService->addItemFromInpatientRoomAssignment(
                $billingId,
                $roomAssignmentId,
                $dailyRate,
                $staffId,
                1 // Initial quantity: 1 day
            );

            if (!empty($result['success'])) {
                log_message('info', "Room charge added to billing account {$billingId} for admission {$admissionId}");
            } else {
                log_message('warning', "Failed to add room charge to billing: " . ($result['message'] ?? 'Unknown error'));
            }
        } catch (\Throwable $e) {
            log_message('error', 'Failed to add room charge to billing for admission ' . $admissionId . ': ' . $e->getMessage());
        }
    }

    private function createInsuranceClaimForInpatient(int $patientId, int $admissionId, array $input): void
    {
        if (! $this->db->tableExists('insurance_claims')) {
            return;
        }

        $hasInsurance = (
            ! empty($input['insurance_provider'] ?? null) ||
            ! empty($input['insurance_card_number'] ?? null) ||
            ! empty($input['hmo_member_id'] ?? null)
        );

        if (! $hasInsurance) {
            return;
        }

        $patientName = trim(
            ($input['first_name'] ?? '') . ' ' .
            ($input['last_name'] ?? '')
        );

        if ($patientName === '') {
            try {
                $patientTable = $this->resolvePatientTableName();
                $row = $this->db->table($patientTable)
                    ->select('first_name, last_name')
                    ->where('patient_id', $patientId)
                    ->get()
                    ->getRowArray();

                if ($row) {
                    $patientName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
                }
            } catch (\Throwable $e) {
                // Fallback: keep empty patient name if lookup fails
            }
        }

        $policyNo = $input['insurance_card_number'] ?? ($input['hmo_member_id'] ?? '');
        $diagnosisCode = $input['admitting_diagnosis'] ?? null;

        $refNo = 'IC-' . date('Ymd-His') . '-' . random_int(100, 999);

        $claimData = [
            'ref_no'        => $refNo,
            'patient_name'  => $patientName !== '' ? $patientName : 'Inpatient Patient',
            'policy_no'     => $policyNo,
            'claim_amount'  => 0.00,
            'diagnosis_code'=> $diagnosisCode,
            'notes'         => 'Inpatient claim for admission ID ' . $admissionId,
            'status'        => 'Pending',
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => null,
        ];

        try {
            $this->db->table('insurance_claims')->insert($claimData);
        } catch (\Throwable $e) {
            log_message('error', 'Failed to create insurance claim for inpatient admission ' . $admissionId . ': ' . $e->getMessage());
        }
    }

    private function resolveDoctorFullName(mixed $staffId): ?string
    {
        if (! $staffId || ! $this->db->tableExists('staff')) {
            return null;
        }

        $staff = $this->db->table('staff')
            ->select('first_name, last_name')
            ->where('staff_id', (int) $staffId)
            ->get()
            ->getRowArray();

        if (! $staff) {
            return null;
        }

        $fullName = trim(($staff['first_name'] ?? '') . ' ' . ($staff['last_name'] ?? ''));
        return $fullName !== '' ? $fullName : null;
    }

    /**
     * Get comprehensive patient records including all related data
     */
    public function getPatientRecords($patientId)
    {
        try {
            $patient = $this->getPatient($patientId);
            
            if ($patient['status'] !== 'success') {
                return [
                    'status' => 'error',
                    'message' => $patient['message'] ?? 'Patient not found'
                ];
            }

            $records = [
                'patient' => $patient['patient'],
                'appointments' => $this->getPatientAppointments($patientId),
                'prescriptions' => $this->getPatientPrescriptions($patientId),
                'lab_orders' => $this->getPatientLabOrders($patientId),
                'outpatient_visits' => $this->getPatientOutpatientVisits($patientId),
                'inpatient_admissions' => $this->getPatientInpatientAdmissions($patientId),
                'financial_records' => $this->getPatientFinancialRecords($patientId),
                'vital_signs' => $this->getPatientVitalSigns($patientId),
                'emergency_contacts' => $this->getPatientEmergencyContacts($patientId),
            ];

            return [
                'status' => 'success',
                'records' => $records
            ];
        } catch (\Throwable $e) {
            log_message('error', 'Error fetching patient records: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return [
                'status' => 'error',
                'message' => 'Failed to fetch patient records: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get patient emergency contacts
     */
    private function getPatientEmergencyContacts($patientId)
    {
        try {
            if (!$this->db->tableExists('emergency_contacts')) {
                return [];
            }

            return $this->db->table('emergency_contacts')
                ->where('patient_id', $patientId)
                ->orderBy('contact_id', 'DESC')
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            log_message('error', 'Error fetching patient emergency contacts: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get patient appointments
     */
    private function getPatientAppointments($patientId)
    {
        try {
            if (!$this->db->tableExists('appointments')) {
                return [];
            }

            return $this->db->table('appointments a')
                ->select('a.*, CONCAT(s.first_name, " ", s.last_name) as doctor_name')
                ->join('staff s', 's.staff_id = a.doctor_id', 'left')
                ->where('a.patient_id', $patientId)
                ->orderBy('a.appointment_date', 'DESC')
                ->orderBy('a.appointment_time', 'DESC')
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            log_message('error', 'Error fetching patient appointments: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get patient prescriptions
     */
    private function getPatientPrescriptions($patientId)
    {
        try {
            if (!$this->db->tableExists('prescriptions')) {
                return [];
            }

            $prescriptions = $this->db->table('prescriptions')
                ->where('patient_id', $patientId)
                ->orderBy('created_at', 'DESC')
                ->get()
                ->getResultArray();

            // Load prescription items for each prescription
            foreach ($prescriptions as &$prescription) {
                if ($this->db->tableExists('prescription_items')) {
                    $prescription['items'] = $this->db->table('prescription_items')
                        ->where('prescription_id', $prescription['id'])
                        ->get()
                        ->getResultArray();
                }
            }

            return $prescriptions;
        } catch (\Throwable $e) {
            log_message('error', 'Error fetching patient prescriptions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get patient lab orders
     */
    private function getPatientLabOrders($patientId)
    {
        try {
            if (!$this->db->tableExists('lab_orders')) {
                return [];
            }

            return $this->db->table('lab_orders')
                ->where('patient_id', $patientId)
                ->orderBy('ordered_at', 'DESC')
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            log_message('error', 'Error fetching patient lab orders: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get patient outpatient visits
     */
    private function getPatientOutpatientVisits($patientId)
    {
        try {
            if (!$this->db->tableExists('outpatient_visits')) {
                return [];
            }

            return $this->db->table('outpatient_visits')
                ->where('patient_id', $patientId)
                ->orderBy('created_at', 'DESC')
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            log_message('error', 'Error fetching patient outpatient visits: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get patient inpatient admissions
     */
    private function getPatientInpatientAdmissions($patientId)
    {
        try {
            if (!$this->db->tableExists('inpatient_admissions')) {
                return [];
            }

            $admissions = $this->db->table('inpatient_admissions')
                ->where('patient_id', $patientId)
                ->orderBy('admission_datetime', 'DESC')
                ->get()
                ->getResultArray();

            // Load related records for each admission
            foreach ($admissions as &$admission) {
                $admissionId = $admission['admission_id'] ?? null;
                
                if ($admissionId) {
                    // Medical history
                    if ($this->db->tableExists('inpatient_medical_history')) {
                        $admission['medical_history'] = $this->db->table('inpatient_medical_history')
                            ->where('admission_id', $admissionId)
                            ->get()
                            ->getRowArray();
                    }

                    // Initial assessment
                    if ($this->db->tableExists('inpatient_initial_assessment')) {
                        $admission['initial_assessment'] = $this->db->table('inpatient_initial_assessment')
                            ->where('admission_id', $admissionId)
                            ->get()
                            ->getRowArray();
                    }

                    // Room assignments
                    if ($this->db->tableExists('inpatient_room_assignments')) {
                        $admission['room_assignments'] = $this->db->table('inpatient_room_assignments')
                            ->where('admission_id', $admissionId)
                            ->get()
                            ->getResultArray();
                    }
                }
            }

            return $admissions;
        } catch (\Throwable $e) {
            log_message('error', 'Error fetching patient inpatient admissions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get patient financial records from billing_accounts and billing_items
     */
    private function getPatientFinancialRecords($patientId)
    {
        try {
            // Ensure patient_id is an integer
            $patientId = (int)$patientId;
            
            $records = [
                'invoices' => [],
                'payments' => [],
                'insurance_claims' => [],
                'transactions' => [],
            ];

            // Fetch billing accounts (these will be treated as invoices)
            if ($this->db->tableExists('billing_accounts')) {
                // Check if patient_id field exists
                if (!$this->db->fieldExists('patient_id', 'billing_accounts')) {
                    log_message('warning', 'getPatientFinancialRecords: patient_id field does not exist in billing_accounts table');
                    return $records;
                }
                
                $builder = $this->db->table('billing_accounts')
                    ->where('patient_id', $patientId);
                
                // Order by created_at if it exists, otherwise by billing_id
                if ($this->db->fieldExists('created_at', 'billing_accounts')) {
                    $builder->orderBy('created_at', 'DESC');
                } else {
                    $builder->orderBy('billing_id', 'DESC');
                }
                
                $billingAccounts = $builder->get()->getResultArray();
                
                log_message('debug', "getPatientFinancialRecords: Found " . count($billingAccounts) . " billing accounts for patient {$patientId}");

                // Convert billing accounts to invoices format
                foreach ($billingAccounts as $account) {
                    $billingId = (int)($account['billing_id'] ?? 0);
                    
                    // Get total amount from billing items
                    $totalAmount = 0.0;
                    $netAmount = 0.0;
                    if ($this->db->tableExists('billing_items') && $billingId > 0) {
                        $items = $this->db->table('billing_items')
                            ->where('billing_id', $billingId)
                            ->get()
                            ->getResultArray();
                        
                        foreach ($items as $item) {
                            $lineTotal = (float)($item['line_total'] ?? 0);
                            $totalAmount += $lineTotal;
                            
                            // Use final_amount if available, otherwise use line_total
                            if ($this->db->fieldExists('final_amount', 'billing_items') && isset($item['final_amount'])) {
                                $netAmount += (float)$item['final_amount'];
                            } else {
                                $netAmount += $lineTotal;
                            }
                        }
                    }
                    
                    // Map billing account to invoice format
                    // Use created_at if it exists, otherwise use current date or null
                    $createdDate = null;
                    if (isset($account['created_at']) && !empty($account['created_at'])) {
                        $createdDate = $account['created_at'];
                    } elseif ($this->db->fieldExists('created_at', 'billing_accounts')) {
                        $createdDate = date('Y-m-d H:i:s');
                    }
                    
                    $invoice = [
                        'invoice_id' => $billingId,
                        'id' => $billingId,
                        'billing_id' => $billingId,
                        'total_amount' => $totalAmount,
                        'amount' => $netAmount,
                        'status' => $account['status'] ?? 'open',
                        'created_at' => $createdDate,
                        'invoice_date' => $createdDate,
                        'admission_id' => $account['admission_id'] ?? null,
                    ];
                    
                    $records['invoices'][] = $invoice;
                    
                    // If status is 'paid', also add to payments
                    if (($account['status'] ?? '') === 'paid') {
                        $records['payments'][] = [
                            'payment_id' => $billingId,
                            'id' => $billingId,
                            'amount' => $netAmount,
                            'payment_method' => $account['payment_method'] ?? 'Not specified',
                            'status' => 'completed',
                            'payment_date' => $createdDate ?? date('Y-m-d H:i:s'),
                            'created_at' => $createdDate ?? date('Y-m-d H:i:s'),
                        ];
                    }
                }
            }

            // Fetch billing items as transactions
            if ($this->db->tableExists('billing_items')) {
                // Check if patient_id field exists
                if (!$this->db->fieldExists('patient_id', 'billing_items')) {
                    log_message('warning', 'getPatientFinancialRecords: patient_id field does not exist in billing_items table');
                } else {
                    $itemsBuilder = $this->db->table('billing_items')
                        ->where('patient_id', $patientId);
                    
                    // Order by created_at if it exists, otherwise by item_id
                    if ($this->db->fieldExists('created_at', 'billing_items')) {
                        $itemsBuilder->orderBy('created_at', 'DESC');
                    } else {
                        $itemsBuilder->orderBy('item_id', 'DESC');
                    }
                    
                    $billingItems = $itemsBuilder->get()->getResultArray();
                    
                    log_message('debug', "getPatientFinancialRecords: Found " . count($billingItems) . " billing items for patient {$patientId}");
                    
                    foreach ($billingItems as $item) {
                    $transaction = [
                        'transaction_id' => $item['item_id'] ?? null,
                        'id' => $item['item_id'] ?? null,
                        'billing_id' => $item['billing_id'] ?? null,
                        'description' => $item['description'] ?? 'Billing Item',
                        'quantity' => $item['quantity'] ?? 1,
                        'unit_price' => $item['unit_price'] ?? 0,
                            'line_total' => $item['line_total'] ?? 0,
                            'final_amount' => $item['final_amount'] ?? $item['line_total'] ?? 0,
                            'transaction_date' => isset($item['created_at']) ? $item['created_at'] : (isset($item['item_id']) ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s')),
                            'created_at' => isset($item['created_at']) ? $item['created_at'] : date('Y-m-d H:i:s'),
                        'appointment_id' => $item['appointment_id'] ?? null,
                        'prescription_id' => $item['prescription_id'] ?? null,
                        'lab_order_id' => $item['lab_order_id'] ?? null,
                    ];
                    
                    $records['transactions'][] = $transaction;
                    }
                }
            }

            // Legacy support: Also check old tables if they exist
            if ($this->db->tableExists('invoices') && $this->db->fieldExists('patient_id', 'invoices')) {
                try {
                    $oldInvoicesBuilder = $this->db->table('invoices')
                        ->where('patient_id', $patientId);
                    
                    if ($this->db->fieldExists('created_at', 'invoices')) {
                        $oldInvoicesBuilder->orderBy('created_at', 'DESC');
                    }
                    
                    $oldInvoices = $oldInvoicesBuilder->get()->getResultArray();
                    $records['invoices'] = array_merge($records['invoices'], $oldInvoices);
                } catch (\Exception $e) {
                    log_message('warning', 'Error fetching legacy invoices: ' . $e->getMessage());
                }
            }

            if ($this->db->tableExists('payments') && $this->db->fieldExists('patient_id', 'payments')) {
                try {
                    $oldPaymentsBuilder = $this->db->table('payments')
                        ->where('patient_id', $patientId);
                    
                    if ($this->db->fieldExists('payment_date', 'payments')) {
                        $oldPaymentsBuilder->orderBy('payment_date', 'DESC');
                    }
                    
                    $oldPayments = $oldPaymentsBuilder->get()->getResultArray();
                    $records['payments'] = array_merge($records['payments'], $oldPayments);
                } catch (\Exception $e) {
                    log_message('warning', 'Error fetching legacy payments: ' . $e->getMessage());
                }
            }

            if ($this->db->tableExists('insurance_claims')) {
                $builder = $this->db->table('insurance_claims');
                
                if ($this->db->fieldExists('patient_id', 'insurance_claims')) {
                    $builder->where('patient_id', $patientId);
                } else {
                    $patient = $this->db->table($this->patientTable)
                        ->select('first_name, last_name')
                        ->where('patient_id', $patientId)
                        ->get()
                        ->getRowArray();
                    
                    if ($patient) {
                        $patientName = trim(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? ''));
                        if ($patientName) {
                            $builder->like('patient_name', $patientName);
                        }
                    }
                }
                
                $records['insurance_claims'] = $builder
                    ->orderBy('created_at', 'DESC')
                    ->get()
                    ->getResultArray();
            }

            log_message('debug', "getPatientFinancialRecords: Returning " . count($records['invoices']) . " invoices, " . count($records['payments']) . " payments, " . count($records['transactions']) . " transactions for patient {$patientId}");
            
            return $records;
        } catch (\Throwable $e) {
            log_message('error', 'Error fetching patient financial records: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            // Return empty records instead of failing completely
            return [
                'invoices' => [],
                'payments' => [],
                'insurance_claims' => [],
                'transactions' => [],
            ];
        }
    }

    /**
     * Get patient vital signs
     */
    private function getPatientVitalSigns($patientId)
    {
        try {
            if (!$this->db->tableExists('vital_signs')) {
                return [];
            }

            return $this->db->table('vital_signs')
                ->where('patient_id', $patientId)
                ->orderBy('recorded_at', 'DESC')
                ->limit(50) // Limit to last 50 records
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            log_message('error', 'Error fetching patient vital signs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Resolve patient table name
     */
    private function resolvePatientTableName(): string
    {
        if ($this->db->tableExists('patient')) {
            return 'patient';
        }

        if ($this->db->tableExists('patients')) {
            return 'patients';
        }

        throw new \RuntimeException('Neither "patient" nor "patients" table exists. Please run the appropriate migrations.');
    }
}