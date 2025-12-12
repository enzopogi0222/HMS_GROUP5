<?php

namespace App\Services;

use App\Libraries\PermissionManager;

class PrescriptionService
{
    protected $db;
    protected $permissionManager;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->permissionManager = new PermissionManager();
    }

    /**
     * Get prescriptions based on user role and permissions
     */
    public function getPrescriptionsByRole($userRole, $staffId = null, $filters = [])
    {
        try {
            $builder = $this->buildPrescriptionQuery();
            
            if (!in_array($userRole, ['admin', 'it_staff', 'doctor', 'nurse', 'pharmacist', 'receptionist'])) {
                $builder->where('1', '0');
            }

            if (!empty($filters['status'])) $builder->where('p.status', $filters['status']);
            if (!empty($filters['date'])) $builder->where('DATE(p.created_at)', $filters['date']);
            if (!empty($filters['patient_id'])) $builder->where('p.patient_id', $filters['patient_id']);
            if (!empty($filters['date_range'])) {
                $builder->where('p.created_at >=', $filters['date_range']['start'])
                        ->where('p.created_at <=', $filters['date_range']['end']);
            }
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $builder->groupStart()
                    ->like('p.rx_number', $search)
                    ->orLike('p.medication', $search)
                    ->orLike('p.patient_name', $search)
                    ->orLike('p.prescriber', $search)
                    ->groupEnd();
            }

            return $builder->orderBy('p.created_at', 'DESC')->get()->getResultArray();

        } catch (\Throwable $e) {
            log_message('error', 'PrescriptionService::getPrescriptionsByRole error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get prescription statistics based on user role
     */
    public function getPrescriptionStats($userRole, $staffId = null)
    {
        try {
            $stats = [];
            $today = date('Y-m-d');
            $thisWeek = [
                'start' => date('Y-m-d', strtotime('monday this week')),
                'end' => date('Y-m-d', strtotime('sunday this week'))
            ];

            $stats = match($userRole) {
                'admin', 'it_staff' => [
                    'total_prescriptions' => $this->getTotalPrescriptions(),
                    'today_prescriptions' => $this->getPrescriptionsCount(['date' => $today]),
                    'week_prescriptions' => $this->getPrescriptionsCount(['date_range' => $thisWeek]),
                    'active_prescriptions' => $this->getPrescriptionsCount(['status' => 'active']),
                    'completed_prescriptions' => $this->getPrescriptionsCount(['status' => 'completed']),
                    'pending_prescriptions' => $this->getPrescriptionsCount(['status' => 'pending']),
                    'cancelled_prescriptions' => $this->getPrescriptionsCount(['status' => 'cancelled']),
                    'active_doctors' => $this->getActiveDoctorsCount()
                ],
                'doctor' => [
                    'my_prescriptions' => $this->getPrescriptionsCount([], 'doctor', $staffId),
                    'today_prescriptions' => $this->getPrescriptionsCount(['date' => $today], 'doctor', $staffId),
                    'week_prescriptions' => $this->getPrescriptionsCount(['date_range' => $thisWeek], 'doctor', $staffId),
                    'active_prescriptions' => $this->getPrescriptionsCount(['status' => 'active'], 'doctor', $staffId),
                    'completed_prescriptions' => $this->getPrescriptionsCount(['status' => 'completed'], 'doctor', $staffId),
                    'pending_prescriptions' => $this->getPrescriptionsCount(['status' => 'pending'], 'doctor', $staffId),
                    'my_patients' => $this->getMyPatientsCount($staffId)
                ],
                'nurse' => (function() use ($staffId) {
                    $userDept = $this->getUserDepartment($staffId);
                    $today = date('Y-m-d');
                    return [
                        'department_prescriptions' => $this->getDepartmentPrescriptionsCount($userDept),
                        'today_prescriptions' => $this->getDepartmentPrescriptionsCount($userDept, ['date' => $today]),
                        'active_prescriptions' => $this->getDepartmentPrescriptionsCount($userDept, ['status' => 'active']),
                        'pending_prescriptions' => $this->getDepartmentPrescriptionsCount($userDept, ['status' => 'pending']),
                        'department' => $userDept
                    ];
                })(),
                'pharmacist' => [
                    'total_prescriptions' => $this->getTotalPrescriptions(),
                    'pending_prescriptions' => $this->getPrescriptionsCount(['status' => 'active']),
                    'dispensed_today' => $this->getPrescriptionsCount(['status' => 'completed', 'date' => $today]),
                    'ready_to_dispense' => $this->getPrescriptionsCount(['status' => 'ready']),
                    'stat_prescriptions' => $this->getStatPrescriptionsCount()
                ],
                'receptionist' => [
                    'total_prescriptions' => $this->getTotalPrescriptions(),
                    'today_prescriptions' => $this->getPrescriptionsCount(['date' => $today]),
                    'active_prescriptions' => $this->getPrescriptionsCount(['status' => 'active']),
                    'completed_prescriptions' => $this->getPrescriptionsCount(['status' => 'completed'])
                ],
                default => ['message' => 'No prescription access for this role']
            };

            return $stats;

        } catch (\Throwable $e) {
            log_message('error', 'PrescriptionService::getPrescriptionStats error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Create a new prescription (supporting one prescription with many medicines)
     */
    public function createPrescription($data, $userRole, $staffId = null)
    {
        try {
            // Validate permissions - admin, doctors can create, nurses can create drafts
            $canCreate = $this->permissionManager->hasPermission($userRole, 'prescriptions', 'create');
            $canCreateDraft = $this->permissionManager->hasPermission($userRole, 'prescriptions', 'create_draft');
            
            if (!$canCreate && !$canCreateDraft) {
                return ['success' => false, 'message' => 'Permission denied. Only administrators, doctors, and nurses can create prescriptions.'];
            }

            // Basic header validation
            $validation = \Config\Services::validation();
            $validation->setRules([
                'patient_id' => 'required|integer',
                // items array will be validated manually
                'items'      => 'required'
            ]);

            if (!$validation->run($data)) {
                return [
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $validation->getErrors()
                ];
            }

            // For doctors: validate that patient is assigned to them
            if ($userRole === 'doctor' && $staffId) {
                if (!$this->isPatientAssignedToDoctor($data['patient_id'], $staffId)) {
                    return [
                        'success' => false,
                        'message' => 'You can only create prescriptions for patients assigned to you.',
                        'errors'  => ['patient_id' => 'Patient is not assigned to you']
                    ];
                }
            }

            // Validate items array for multi-medicine support
            if (empty($data['items']) || !is_array($data['items'])) {
                return [
                    'success' => false,
                    'message' => 'At least one medication item is required',
                    'errors'  => ['items' => 'At least one medication item is required']
                ];
            }

            $items = [];
            foreach ($data['items'] as $index => $item) {
                $medName = trim($item['medication_name'] ?? $item['medication'] ?? '');
                $qty     = (int)($item['quantity'] ?? 0);

                if ($medName === '' || $qty <= 0) {
                    return [
                        'success' => false,
                        'message' => 'Each medication must have a name and positive quantity',
                        'errors'  => [
                            "items[$index]" => 'Medication name and quantity are required'
                        ]
                    ];
                }

                $durationStr = $item['duration'] ?? '';
                $daysSupply  = null;
                if ($durationStr !== '') {
                    $daysSupply = (int) filter_var($durationStr, FILTER_SANITIZE_NUMBER_INT) ?: null;
                }

                $items[] = [
                    'medication_resource_id' => !empty($item['medication_resource_id']) ? (int) $item['medication_resource_id'] : null,
                    'medication_name'        => $medName,
                    'dosage'                 => $item['dosage'] ?? null,
                    'frequency'              => $item['frequency'] ?? null,
                    'duration'               => $durationStr ?: null,
                    'days_supply'            => $daysSupply,
                    'quantity'               => $qty,
                    'notes'                  => $item['notes'] ?? null,
                ];
            }

            // Generate rx_number
            $rxNumber = $this->generatePrescriptionId();
            
            // Validate rx_number is not empty
            if (empty($rxNumber) || trim($rxNumber) === '') {
                log_message('error', 'PrescriptionService::createPrescription - Generated empty rx_number');
                return [
                    'success' => false,
                    'message' => 'Failed to generate prescription ID. Please try again.'
                ];
            }

            // Get patient name (use correct patients table)
            $patient = $this->db->table('patients')
                ->select('first_name, last_name')
                ->where('patient_id', $data['patient_id'])
                ->get()
                ->getRowArray();
            
            $patientName = $patient ? ($patient['first_name'] . ' ' . $patient['last_name']) : 'Unknown';

            // Get prescriber/doctor information
            // For admin and doctors: use their own staff_id or specified doctor_id
            // For nurses: require doctor_id to be specified (for draft approval)
            if ($userRole === 'doctor') {
                $prescriberId = $staffId;
            } elseif ($userRole === 'admin') {
                // Admin can specify a doctor_id or use their own if they're a doctor
                $prescriberId = !empty($data['doctor_id']) ? (int) $data['doctor_id'] : $staffId;
            } elseif ($userRole === 'nurse') {
                // Nurses must specify a doctor for draft prescriptions
                if (empty($data['doctor_id'])) {
                    return [
                        'success' => false,
                        'message' => 'Doctor assignment is required for draft prescriptions. Please select a doctor to approve this prescription.',
                        'errors' => ['doctor_id' => 'Doctor is required']
                    ];
                }
                $prescriberId = (int) $data['doctor_id'];
            } else {
                $prescriberId = $data['doctor_id'] ?? $staffId;
            }
            
            $prescriber = $this->db->table('staff')
                ->select('first_name, last_name')
                ->where('staff_id', $prescriberId)
                ->get()
                ->getRowArray();
            
            $prescriberName = $prescriber ? ('Dr. ' . $prescriber['first_name'] . ' ' . $prescriber['last_name']) : 'Unknown';

            // Get user_id for created_by (from users table linked to staff_id)
            $user = $this->db->table('users')
                ->select('user_id')
                ->where('staff_id', $staffId)
                ->get()
                ->getRowArray();
            
            $createdBy = $user['user_id'] ?? null;

            // NOTE: Resource stock reservation is currently defined for a single
            // medication/quantity. For multi-medicine prescriptions we keep the
            // logic disabled here to avoid incorrect reservations. It can be
            // reworked later on a per-item basis if needed.

            // Determine status based on role:
            // - Admin and Doctors create active prescriptions (primary prescribers)
            // - Nurses create draft prescriptions (needs doctor approval)
            $defaultStatus = ($userRole === 'nurse') ? 'draft' : 'active';
            $status = $this->mapStatus($data['status'] ?? $defaultStatus);
            
            // Ensure nurses can only create drafts
            if ($userRole === 'nurse' && $status !== 'draft') {
                $status = 'draft';
            }

            // Use first item to build a legacy summary for list views
            $firstItem          = $items[0];
            $firstMedication    = $firstItem['medication_name'];
            $medicationSummary  = $firstMedication . (count($items) > 1 ? ' +' . (count($items) - 1) . ' more' : '');

            // Ensure rx_number is never empty or null
            if (empty($rxNumber) || trim($rxNumber) === '') {
                log_message('error', 'PrescriptionService::createPrescription - rx_number is empty, regenerating');
                $rxNumber = $this->generatePrescriptionId();
                if (empty($rxNumber)) {
                    return [
                        'success' => false,
                        'message' => 'Failed to generate prescription ID. Please try again.'
                    ];
                }
            }

            // Check if prescription_id column exists (legacy support) and set it to avoid unique constraint violation
            $hasPrescriptionIdColumn = $this->db->fieldExists('prescription_id', 'prescriptions');
            $hasDoctorIdColumn = $this->db->fieldExists('doctor_id', 'prescriptions');
            
            $prescriptionData = [
                'rx_number'    => trim($rxNumber),
                'patient_id'   => (int) $data['patient_id'],
                'patient_name' => $patientName,
                // legacy single-medication columns now store a summary so existing
                // list views continue to work without change
                'medication'   => $medicationSummary,
                'dosage'       => $firstItem['dosage'] ?? null,
                'frequency'    => $firstItem['frequency'],
                'days_supply'  => $firstItem['days_supply'],
                'quantity'     => $firstItem['quantity'],
                'prescriber'   => $prescriberName,
                'priority'     => $data['priority'] ?? 'routine',
                'notes'        => $data['notes'] ?? null,
                'status'       => $status,
                'created_by'   => $createdBy,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s')
            ];
            
            // Set prescription_id if column exists (for legacy database structure)
            if ($hasPrescriptionIdColumn) {
                $prescriptionData['prescription_id'] = trim($rxNumber);
            }
            
            // Set doctor_id if column exists (for doctor assignment, especially for nurse drafts)
            if ($hasDoctorIdColumn) {
                $prescriptionData['doctor_id'] = $prescriberId;
            }

            // Insert header + items in a transaction
            $this->db->transBegin();

            $insertResult = $this->db->table('prescriptions')->insert($prescriptionData);

            if (!$insertResult) {
                $dbError = $this->db->error();
                $this->db->transRollback();
                log_message('error', 'PrescriptionService::createPrescription - Header insert failed: ' . json_encode($dbError));

                return [
                    'success' => false,
                    'message' => 'Failed to create prescription header: ' . ($dbError['message'] ?? 'Unknown database error')
                ];
            }

            $prescriptionId = (int) $this->db->insertID();

            $itemTable = $this->db->table('prescription_items');
            foreach ($items as $itemRow) {
                $row = $itemRow;
                $row['prescription_id'] = $prescriptionId;
                $row['created_at']      = date('Y-m-d H:i:s');
                $row['updated_at']      = date('Y-m-d H:i:s');

                if (!$itemTable->insert($row)) {
                    $dbError = $this->db->error();
                    $this->db->transRollback();
                    log_message('error', 'PrescriptionService::createPrescription - Item insert failed: ' . json_encode($dbError));

                    return [
                        'success' => false,
                        'message' => 'Failed to create prescription items: ' . ($dbError['message'] ?? 'Unknown database error')
                    ];
                }
            }

            $this->db->transCommit();

            // Automatically add to billing if prescription is created with completed/dispensed status
            if (in_array($status, ['completed', 'dispensed'])) {
                // Fetch the full prescription to ensure we have all data
                $fullPrescription = $this->getPrescription($prescriptionId);
                if ($fullPrescription) {
                    $this->addPrescriptionToBilling($prescriptionId, $fullPrescription, $staffId);
                }
            }

            return [
                'success'         => true,
                'message'         => 'Prescription created successfully',
                'prescription_id' => $rxNumber,
                'id'              => $prescriptionId
            ];

        } catch (\Throwable $e) {
            log_message('error', 'PrescriptionService::createPrescription error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create prescription'];
        }
    }

    /**
     * Update a prescription
     */
    public function updatePrescription($id, $data, $userRole, $staffId = null)
    {
        try {
            // Get existing prescription
            $existingPrescription = $this->getPrescription($id);
            if (!$existingPrescription) {
                return ['success' => false, 'message' => 'Prescription not found'];
            }

            // Check permissions
            if (!$this->canEditPrescription($existingPrescription, $userRole, $staffId)) {
                return ['success' => false, 'message' => 'Permission denied'];
            }

            $updateData = array_filter([
                'medication' => $data['medication'] ?? null,
                'dosage' => $data['dosage'] ?? null,
                'frequency' => $data['frequency'] ?? null,
                'days_supply' => isset($data['duration']) ? (!empty($data['duration']) ? (int)filter_var($data['duration'], FILTER_SANITIZE_NUMBER_INT) : null) : null,
                'quantity' => isset($data['quantity']) ? (int)$data['quantity'] : null,
                'notes' => $data['notes'] ?? null,
                'status' => isset($data['status']) ? $this->mapStatus($data['status']) : null,
                'updated_at' => date('Y-m-d H:i:s')
            ], fn($v) => $v !== null);

            if (count($updateData) <= 1) {
                return ['success' => false, 'message' => 'No fields to update'];
            }
            
            $newStatus = isset($data['status']) ? $this->mapStatus($data['status']) : null;
            $oldStatus = $existingPrescription['status'] ?? null;
            
            $result = $this->db->table('prescriptions')->where('id', $id)->update($updateData);
            
            if ($result) {
                // Automatically add to billing when prescription is dispensed/completed
                if ($newStatus && in_array($newStatus, ['dispensed', 'completed']) && $oldStatus !== $newStatus) {
                    $this->addPrescriptionToBilling($id, $existingPrescription, $staffId);
                }
                
                return ['success' => true, 'message' => 'Prescription updated successfully'];
            }

            // Log database error
            $dbError = $this->db->error();
            log_message('error', 'PrescriptionService::updatePrescription - Database error: ' . json_encode($dbError));
            
            return ['success' => false, 'message' => 'Failed to update prescription'];

        } catch (\Throwable $e) {
            log_message('error', 'PrescriptionService::updatePrescription error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update prescription'];
        }
    }

    /**
     * Delete a prescription
     */
    public function deletePrescription($id, $userRole, $staffId = null)
    {
        try {
            // Get existing prescription
            $existingPrescription = $this->getPrescription($id);
            if (!$existingPrescription) {
                return ['success' => false, 'message' => 'Prescription not found'];
            }

            // Check permissions
            if (!$this->canDeletePrescription($existingPrescription, $userRole, $staffId)) {
                return ['success' => false, 'message' => 'Permission denied'];
            }

            if ($this->db->table('prescriptions')->where('id', $id)->delete()) {
                return ['success' => true, 'message' => 'Prescription deleted successfully'];
            }

            return ['success' => false, 'message' => 'Failed to delete prescription'];

        } catch (\Throwable $e) {
            log_message('error', 'PrescriptionService::deletePrescription error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to delete prescription'];
        }
    }

    /**
     * Get a single prescription
     */
    public function getPrescription($id)
    {
        try {
            $prescription = $this->db->table('prescriptions p')
                ->select([
                    'p.id',
                    'p.rx_number as prescription_id',
                    'p.patient_id',
                    'p.patient_name',
                    'p.medication',
                    'p.dosage',
                    'p.frequency',
                    'p.days_supply as duration',
                    'p.quantity',
                    'p.prescriber',
                    'p.priority',
                    'p.notes',
                    'p.status',
                    'p.created_at',
                    'p.updated_at',
                    'p.dispensed_at',
                    'p.created_by',
                    'COALESCE(pat.patient_id, p.patient_id) as pat_id',
                    'pat.first_name as patient_first_name',
                    'pat.last_name as patient_last_name',
                    'pat.date_of_birth',
                    // patients table uses contact_number, not contact_no, and has no status/primary_doctor_id
                    'pat.contact_number as patient_phone',
                    'pat.email as patient_email'
                ])
                ->join('patients pat', 'pat.patient_id = p.patient_id', 'left')
                ->where('p.id', $id)
                ->get()
                ->getRowArray();
            
            if ($prescription) {
                // Use prescription's patient_name if available, otherwise construct from patient table
                if (empty($prescription['patient_name']) && !empty($prescription['patient_first_name'])) {
                    $prescription['patient_name'] = trim(($prescription['patient_first_name'] ?? '') . ' ' . ($prescription['patient_last_name'] ?? ''));
                }

                // Load associated medication items (one prescription, many medicines)
                try {
                    $items = $this->db->table('prescription_items')
                        ->where('prescription_id', $prescription['id'])
                        ->orderBy('id', 'ASC')
                        ->get()
                        ->getResultArray();
                } catch (\Throwable $e) {
                    log_message('error', 'PrescriptionService::getPrescription - Failed to load items: ' . $e->getMessage());
                    $items = [];
                }

                $prescription['items'] = $items;
            }
            
            return $prescription;

        } catch (\Throwable $e) {
            log_message('error', 'PrescriptionService::getPrescription error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update prescription status
     */
    public function updatePrescriptionStatus($id, $status, $userRole, $staffId = null)
    {
        try {
            // Get existing prescription
            $existingPrescription = $this->getPrescription($id);
            if (!$existingPrescription) {
                return ['success' => false, 'message' => 'Prescription not found'];
            }

            // Check permissions
            if (!$this->canEditPrescription($existingPrescription, $userRole, $staffId)) {
                return ['success' => false, 'message' => 'Permission denied'];
            }

            $dbStatus = $this->mapStatus($status);
            $validStatuses = ['in_progress', 'queued', 'verifying', 'ready', 'dispensed', 'cancelled'];
            
            if (!in_array($dbStatus, $validStatuses)) {
                return ['success' => false, 'message' => 'Invalid status'];
            }

            $oldStatus = $existingPrescription['status'] ?? null;
            $updateData = [
                'status' => $dbStatus,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Also set dispensed_at timestamp if status is being changed to dispensed
            if ($dbStatus === 'dispensed' && $oldStatus !== 'dispensed') {
                $updateData['dispensed_at'] = date('Y-m-d H:i:s');
            }

            if ($this->db->table('prescriptions')->where('id', $id)->update($updateData)) {
                // Automatically add to billing when prescription is completed or dispensed
                // Note: 'completed' status maps to 'dispensed' via mapStatus, so we check for 'dispensed'
                if ($dbStatus === 'dispensed' && $oldStatus !== 'dispensed') {
                    // Get updated prescription data
                    $updatedPrescription = $this->getPrescription($id);
                    if ($updatedPrescription) {
                        $this->addPrescriptionToBilling($id, $updatedPrescription, $staffId);
                    }
                }
                
                return ['success' => true, 'message' => 'Prescription status updated successfully. ' . 
                    ($dbStatus === 'dispensed' ? 'Prescription has been automatically added to billing.' : '')];
            }

            return ['success' => false, 'message' => 'Failed to update prescription status'];

        } catch (\Throwable $e) {
            log_message('error', 'PrescriptionService::updatePrescriptionStatus error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update prescription status'];
        }
    }

    /**
     * Get available patients for prescription creation
     */
    public function getAvailablePatients($userRole, $staffId = null)
    {
        try {
            // Use the correct patients table and existing columns (match AppointmentManagement)
            // Determine which table name to use
            $tableName = $this->db->tableExists('patients') ? 'patients' : ($this->db->tableExists('patient') ? 'patient' : 'patients');
            
            $selectFields = [
                'p.patient_id',
                'p.first_name',
                'p.last_name',
                'p.date_of_birth'
            ];
            
            if ($this->db->fieldExists('patient_type', $tableName)) {
                $selectFields[] = 'p.patient_type';
            }
            
            $builder = $this->db->table($tableName . ' p')
                ->select($selectFields);
            
            // For doctors: filter by primary_doctor_id
            if ($userRole === 'doctor' && $staffId) {
                $hasPrimaryDoctor = $this->db->fieldExists('primary_doctor_id', $tableName);
                if ($hasPrimaryDoctor) {
                    // Get doctor_id from doctor table using staff_id
                    $doctorInfo = $this->db->table('doctor')
                        ->select('doctor_id')
                        ->where('staff_id', $staffId)
                        ->get()
                        ->getRowArray();
                    
                    $doctorId = $doctorInfo['doctor_id'] ?? null;
                    
                    if ($doctorId) {
                        $builder->where('p.primary_doctor_id', $doctorId);
                    } else {
                        // If doctor record not found, return empty list
                        $builder->where('1=0');
                    }
                }
            }
            
            $patients = $builder->orderBy('p.first_name', 'ASC')
                ->get()
                ->getResultArray();
            
            // Always try to derive patient_type if not set or if column doesn't exist
            if ($this->db->tableExists('inpatient_admissions')) {
                foreach ($patients as &$patient) {
                    if (!isset($patient['patient_type']) || empty($patient['patient_type'])) {
                        $hasActiveAdmission = $this->db->table('inpatient_admissions')
                            ->where('patient_id', $patient['patient_id'])
                            ->groupStart()
                                ->where('discharge_date', null)
                                ->orWhere('discharge_date', '')
                            ->groupEnd()
                            ->countAllResults() > 0;
                        
                        $patient['patient_type'] = $hasActiveAdmission ? 'Inpatient' : 'Outpatient';
                    } else {
                        $patient['patient_type'] = ucfirst(strtolower(trim($patient['patient_type'])));
                    }
                }
            } else {
                foreach ($patients as &$patient) {
                    if (!isset($patient['patient_type']) || empty($patient['patient_type'])) {
                        $patient['patient_type'] = 'Outpatient';
                    } else {
                        $patient['patient_type'] = ucfirst(strtolower(trim($patient['patient_type'])));
                    }
                }
            }
            
            return $patients;

        } catch (\Throwable $e) {
            log_message('error', 'PrescriptionService::getAvailablePatients error: ' . $e->getMessage());
            return [];
        }
    }

    // Private helper methods
    
    private function buildPrescriptionQuery()
    {
        return $this->db->table('prescriptions p')
            ->select([
                'p.id', 'p.rx_number as prescription_id', 'p.patient_id', 'p.patient_name',
                'p.medication', 'p.dosage', 'p.frequency', 'p.days_supply as duration',
                'p.quantity', 'p.prescriber', 'p.priority', 'p.notes', 'p.status',
                'p.created_at', 'p.updated_at', 'p.dispensed_at', 'p.created_by',
                'COALESCE(pat.patient_id, p.patient_id) as pat_id', 'pat.date_of_birth',
                'pat.contact_number as patient_phone', 'pat.email as patient_email'
            ])
            ->join('patients pat', 'pat.patient_id = p.patient_id', 'left');
    }

    private function getTotalPrescriptions()
    {
        return $this->db->table('prescriptions')->countAllResults();
    }

    private function getPrescriptionsCount($filters = [], $userRole = null, $staffId = null)
    {
        $builder = $this->db->table('prescriptions p');
        
        if ($userRole === 'doctor' && $staffId) {
            // Join to the correct patients table when filtering by doctor
            $builder->join('patients pat', 'pat.patient_id = p.patient_id', 'inner')
                ->where('pat.primary_doctor_id', $staffId);
        }

        if (!empty($filters['date'])) {
            $builder->where('DATE(p.created_at)', $filters['date']);
        }
        
        if (!empty($filters['status'])) {
            $builder->where('p.status', $filters['status']);
        }

        if (!empty($filters['date_range'])) {
            $builder->where('p.created_at >=', $filters['date_range']['start']);
            $builder->where('p.created_at <=', $filters['date_range']['end']);
        }

        return $builder->countAllResults();
    }

    private function getDepartmentPrescriptionsCount($department, $filters = [])
    {
        try {
            $builder = $this->db->table('prescriptions p')
                ->join('patient pat', 'pat.patient_id = p.patient_id', 'inner')
                ->join('staff s', 's.staff_id = pat.primary_doctor_id', 'inner')
                ->where('s.department', $department);

            if (!empty($filters['date'])) {
                $builder->where('DATE(p.created_at)', $filters['date']);
            }
            
            if (!empty($filters['status'])) {
                $builder->where('p.status', $filters['status']);
            }

            return $builder->countAllResults();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function getActiveDoctorsCount()
    {
        try {
            return $this->db->table('staff')
                ->where('role', 'doctor')
                ->where('status', 'Active')
                ->countAllResults();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function getMyPatientsCount($staffId)
    {
        try {
            return $this->db->table('patient')
                ->where('primary_doctor_id', $staffId)
                ->where('status', 'Active')
                ->countAllResults();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function getStatPrescriptionsCount()
    {
        try {
            return $this->db->table('prescriptions')
                ->where('status', 'active')
                ->where('priority', 'stat')
                ->countAllResults();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function getUserDepartment($staffId)
    {
        try {
            $result = $this->db->table('staff')
                ->select('department')
                ->where('staff_id', $staffId)
                ->get()
                ->getRowArray();
            return $result['department'] ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function generatePrescriptionId()
    {
        $prefix = 'RX';
        $year = date('Y');
        $month = date('m');
        $maxAttempts = 100;
        $attempt = 0;

        do {
            $unique = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $prescriptionId = $prefix . $year . $month . $unique;
            $exists = $this->db->table('prescriptions')
                ->where('rx_number', $prescriptionId)
                ->countAllResults() > 0;
            $attempt++;
        } while ($exists && $attempt < $maxAttempts);

        if ($attempt >= $maxAttempts) {
            // Fallback: use timestamp-based ID if random generation fails
            $prescriptionId = $prefix . $year . $month . substr(time(), -6) . rand(10, 99);
        }

        // Final validation - ensure it's never empty
        if (empty($prescriptionId) || trim($prescriptionId) === '') {
            $prescriptionId = $prefix . $year . $month . substr(time(), -4) . rand(100, 999);
        }

        return $prescriptionId;
    }

    private function canEditPrescription($prescription, $userRole, $staffId = null)
    {
        // Admin and IT staff can edit all prescriptions
        if (in_array($userRole, ['admin', 'it_staff'])) {
            return true;
        }

        // Doctors can edit their own prescriptions
        if ($userRole === 'doctor' && $staffId && $prescription['doctor_id'] == $staffId) {
            return true;
        }

        // Pharmacists can update status for dispensing
        if ($userRole === 'pharmacist') {
            return true;
        }

        return false;
    }

    private function canDeletePrescription($prescription, $userRole, $staffId = null)
    {
        return $userRole === 'admin' || 
               ($userRole === 'doctor' && $staffId && ($prescription['doctor_id'] ?? null) == $staffId && 
                in_array($prescription['status'] ?? '', ['active', 'pending', 'queued']));
    }
    
    private function mapStatus($status)
    {
        $statusMap = [
            'active' => 'in_progress',
            'pending' => 'in_progress',
            'ready' => 'ready',
            'completed' => 'dispensed',
            'cancelled' => 'cancelled',
            'verifying' => 'verifying',
            'queued' => 'in_progress',
            'in_progress' => 'in_progress',
            'dispensed' => 'dispensed',
            'draft' => 'draft' // Draft status for nurse-created prescriptions awaiting doctor approval
        ];
        return $statusMap[$status] ?? $status;
    }

    /**
     * Automatically add dispensed prescription to billing account
     */
    private function addPrescriptionToBilling($prescriptionId, $prescription, $staffId = null): void
    {
        try {
            // Check if FinancialService is available
            if (!class_exists(\App\Services\FinancialService::class)) {
                log_message('warning', 'FinancialService not available for auto-billing prescription');
                return;
            }

            $patientId = (int)($prescription['patient_id'] ?? 0);
            if ($patientId <= 0) {
                log_message('warning', "Prescription {$prescriptionId}: No patient ID found");
                return;
            }

            $financialService = new \App\Services\FinancialService();

            // Get patient type and admission ID
            $patientType = $this->getPatientType($patientId);
            $admissionId = null;
            
            if (strtolower($patientType) === 'inpatient') {
                $admissionId = $this->getActiveAdmissionId($patientId);
            }

            // Get or create billing account
            $account = $financialService->getOrCreateBillingAccountForPatient($patientId, $admissionId, $staffId);
            if (!$account || empty($account['billing_id'])) {
                log_message('error', "Prescription {$prescriptionId}: Failed to get/create billing account for patient {$patientId}");
                return;
            }

            $billingId = (int)$account['billing_id'];

            // Check if prescription is already in billing
            if ($this->db->tableExists('billing_items')) {
                $existing = $this->db->table('billing_items')
                    ->where('billing_id', $billingId)
                    ->where('prescription_id', $prescriptionId)
                    ->countAllResults();
                
                if ($existing > 0) {
                    log_message('debug', "Prescription {$prescriptionId}: Already in billing account {$billingId}");
                    return; // Already added
                }
            }

            // Calculate medication cost
            $medicationCost = $this->calculateMedicationCost($prescriptionId, $prescription);
            $quantity = (int)($prescription['quantity'] ?? $prescription['dispensed_quantity'] ?? 1);

            // Add to billing
            $result = $financialService->addItemFromPrescription(
                $billingId,
                $prescriptionId,
                $medicationCost,
                $quantity,
                $staffId
            );

            if (!($result['success'] ?? false)) {
                log_message('error', "Prescription {$prescriptionId}: Failed to add to billing - " . ($result['message'] ?? 'Unknown error'));
            }
        } catch (\Throwable $e) {
            log_message('error', 'PrescriptionService::addPrescriptionToBilling error: ' . $e->getMessage());
        }
    }

    /**
     * Calculate medication cost from prescription items or inventory
     */
    private function calculateMedicationCost($prescriptionId, $prescription): float
    {
        try {
            // Try to get cost from prescription_items if table exists
            if ($this->db->tableExists('prescription_items')) {
                $items = $this->db->table('prescription_items')
                    ->where('prescription_id', $prescriptionId)
                    ->get()
                    ->getResultArray();

                if (!empty($items)) {
                    $totalCost = 0.0;
                    foreach ($items as $item) {
                        // Try to get price from resources using medication_resource_id or medication_name
                        $resourceId = $item['medication_resource_id'] ?? $item['resource_id'] ?? null;
                        $unitPrice = $this->getMedicationPrice($item['medication_name'] ?? '', $resourceId);
                        $quantity = (int)($item['quantity'] ?? 1);
                        $totalCost += $unitPrice * $quantity;
                    }
                    return $totalCost > 0 ? $totalCost : 100.00; // Default if calculation fails
                }
            }

            // Fallback: try to get price from inventory using medication name
            $medicationName = $prescription['medication'] ?? '';
            if (!empty($medicationName)) {
                $price = $this->getMedicationPrice($medicationName);
                return $price > 0 ? $price : 100.00; // Default medication cost
            }

            return 100.00; // Default medication cost
        } catch (\Throwable $e) {
            log_message('error', 'PrescriptionService::calculateMedicationCost error: ' . $e->getMessage());
            return 100.00; // Default on error
        }
    }

    /**
     * Get medication price from inventory/resources
     */
    private function getMedicationPrice(string $medicationName, ?int $resourceId = null): float
    {
        try {
            // Try to get price from resources table by ID
            if ($this->db->tableExists('resources') && $resourceId) {
                $resource = $this->db->table('resources')
                    ->select('price, unit_price, selling_price')
                    ->where('id', $resourceId)
                    ->where('category', 'Medications')
                    ->get()
                    ->getRowArray();

                if ($resource) {
                    // Use price field first, then fallback to selling_price or unit_price
                    return (float)($resource['price'] ?? $resource['selling_price'] ?? $resource['unit_price'] ?? 0);
                }
            }

            // Try to find by medication name
            if ($this->db->tableExists('resources') && !empty($medicationName)) {
                $resource = $this->db->table('resources')
                    ->select('price, unit_price, selling_price')
                    ->where('category', 'Medications')
                    ->groupStart()
                        ->like('equipment_name', $medicationName)
                        ->orLike('medication_name', $medicationName)
                    ->groupEnd()
                    ->get()
                    ->getRowArray();

                if ($resource) {
                    // Use price field first, then fallback to selling_price or unit_price
                    return (float)($resource['price'] ?? $resource['selling_price'] ?? $resource['unit_price'] ?? 0);
                }
            }

            return 0.0;
        } catch (\Throwable $e) {
            log_message('error', 'PrescriptionService::getMedicationPrice error: ' . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Get patient type (inpatient/outpatient)
     */
    private function getPatientType(int $patientId): string
    {
        try {
            if (!$this->db->tableExists('patients')) {
                return 'outpatient';
            }

            $patient = $this->db->table('patients')
                ->select('patient_type')
                ->where('patient_id', $patientId)
                ->get()
                ->getRowArray();

            if ($patient && !empty($patient['patient_type'])) {
                return strtolower($patient['patient_type']);
            }

            // Check for active admission
            if ($this->db->tableExists('inpatient_admissions')) {
                $builder = $this->db->table('inpatient_admissions')
                    ->where('patient_id', $patientId);
                
                // Only check discharge_date if the column exists
                if ($this->db->fieldExists('discharge_date', 'inpatient_admissions')) {
                    $builder->groupStart()
                        ->where('discharge_date', null)
                        ->orWhere('discharge_date', '')
                    ->groupEnd();
                }
                
                $admission = $builder->get()->getRowArray();

                if ($admission) {
                    return 'inpatient';
                }
            }

            return 'outpatient';
        } catch (\Throwable $e) {
            log_message('error', 'PrescriptionService::getPatientType error: ' . $e->getMessage());
            return 'outpatient';
        }
    }

    /**
     * Get active admission ID for inpatient
     */
    private function getActiveAdmissionId(int $patientId): ?int
    {
        try {
            if (!$this->db->tableExists('inpatient_admissions')) {
                return null;
            }

            $builder = $this->db->table('inpatient_admissions')
                ->select('admission_id')
                ->where('patient_id', $patientId);
            
            // Only check discharge_date if the column exists
            if ($this->db->fieldExists('discharge_date', 'inpatient_admissions')) {
                $builder->groupStart()
                    ->where('discharge_date', null)
                    ->orWhere('discharge_date', '')
                ->groupEnd();
            }
            
            $admission = $builder->get()->getRowArray();

            return $admission ? (int)$admission['admission_id'] : null;
        } catch (\Throwable $e) {
            log_message('error', 'PrescriptionService::getActiveAdmissionId error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if a patient is assigned to a doctor
     * @param int $patientId Patient ID
     * @param int $staffId Doctor's staff_id
     * @return bool True if patient is assigned to the doctor
     */
    private function isPatientAssignedToDoctor($patientId, $staffId)
    {
        try {
            // Get doctor_id from staff_id
            $doctorRecord = $this->db->table('doctor')
                ->select('doctor_id')
                ->where('staff_id', $staffId)
                ->get()
                ->getRowArray();
            
            if (!$doctorRecord || empty($doctorRecord['doctor_id'])) {
                return false;
            }
            
            $doctorId = $doctorRecord['doctor_id'];
            
            // Check both 'patient' and 'patients' table names
            $patientTable = $this->db->tableExists('patient') ? 'patient' : ($this->db->tableExists('patients') ? 'patients' : null);
            
            if (!$patientTable) {
                return false;
            }
            
            // Check if primary_doctor_id column exists
            if (!$this->db->fieldExists('primary_doctor_id', $patientTable)) {
                // If column doesn't exist, allow access (backward compatibility)
                return true;
            }
            
            // Check if patient's primary_doctor_id matches doctor_id
            $patient = $this->db->table($patientTable)
                ->select('primary_doctor_id')
                ->where('patient_id', $patientId)
                ->get()
                ->getRowArray();
            
            return $patient && isset($patient['primary_doctor_id']) && (int)$patient['primary_doctor_id'] === (int)$doctorId;
        } catch (\Throwable $e) {
            log_message('error', 'PrescriptionService::isPatientAssignedToDoctor error: ' . $e->getMessage());
            return false;
        }
    }
}
