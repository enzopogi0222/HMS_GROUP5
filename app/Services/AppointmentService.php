<?php

namespace App\Services;

class AppointmentService
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Create appointment with role-based doctor assignment
     */
    public function createAppointment($input, $userRole, $staffId = null)
    {
        $doctorId = $this->determineDoctorId($input, $userRole, $staffId);
        if (!$doctorId) {
            return ['success' => false, 'message' => 'No doctor available for assignment'];
        }

        $validation = \Config\Services::validation();
        $validation->setRules($this->getValidationRules($userRole));
        if (!$validation->run($input)) {
            return ['success' => false, 'message' => 'Validation failed', 'errors' => $validation->getErrors()];
        }

        // For doctors: validate that patient is assigned to them
        if ($userRole === 'doctor' && $staffId) {
            if (!$this->isPatientAssignedToDoctor($input['patient_id'], $staffId)) {
                return [
                    'success' => false,
                    'message' => 'You can only create appointments for patients assigned to you.',
                    'errors' => ['patient_id' => 'Patient is not assigned to you']
                ];
            }
        }

        $data = $this->prepareAppointmentData($input, $doctorId, $userRole);
        $appointmentDate = $data['appointment_date'] ?? null;
        if (!$appointmentDate || ($timestamp = strtotime($appointmentDate)) === false) {
            return ['success' => false, 'message' => 'Invalid appointment date'];
        }

        $weekdayName = date('N', $timestamp);
        if (!$this->db->table('staff_schedule')->where('staff_id', $doctorId)->where('weekday', $weekdayName)->where('status', 'active')->countAllResults()) {
            return ['success' => false, 'message' => 'Selected doctor has no shift on this day'];
        }

        if ($this->db->table('appointments')->where('doctor_id', $doctorId)->where('appointment_date', $appointmentDate)->whereIn('status', ['scheduled', 'in-progress'])->countAllResults() > 0) {
            return ['success' => false, 'message' => 'Doctor already has an active appointment on this date'];
        }

        try {
            $this->db->table('appointments')->insert($data);
            $insertId = $this->db->insertID();
            return [
                'success' => true,
                'message' => 'Appointment scheduled successfully',
                'id' => $insertId,
                'appointment_id' => 'APT-' . date('Ymd') . '-' . str_pad($insertId, 4, '0', STR_PAD_LEFT)
            ];
        } catch (\Throwable $e) {
            log_message('error', 'Failed to create appointment: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Get appointments with optional filtering
     */
    public function getAppointments($filters = [])
    {
        try {
            $builder = $this->buildAppointmentQuery();
            foreach (['doctor_id', 'date', 'status', 'patient_id'] as $key) {
                if (isset($filters[$key])) {
                    $builder->where('a.' . ($key === 'date' ? 'appointment_date' : ($key === 'patient_id' ? 'patient_id' : $key)), $filters[$key]);
                }
            }
            $appointments = $builder->orderBy('a.appointment_date', 'DESC')->orderBy('a.appointment_time', 'DESC')->get()->getResultArray();
            return ['success' => true, 'data' => $appointments];
        } catch (\Throwable $e) {
            log_message('error', 'Error fetching appointments: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to fetch appointments', 'data' => []];
        }
    }

    /**
     * Get single appointment by ID (DB primary key `id`, aliased as appointment_id)
     */
    public function getAppointment($id)
    {
        try {
            $appointment = $this->buildAppointmentQuery()->select('a.id as appointment_id')->where('a.id', $id)->get()->getRowArray();
            if (!$appointment) {
                return ['success' => false, 'message' => 'Appointment not found'];
            }
            return ['success' => true, 'appointment' => $appointment];
        } catch (\Throwable $e) {
            log_message('error', 'Error fetching appointment: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error'];
        }
    }

    /**
     * Update appointment status (DB primary key `id`)
     * Automatically adds to billing when status is 'completed'
     */
    public function updateAppointmentStatus($id, $status, $userRole = null, $staffId = null)
    {
        if (!in_array($status, ['scheduled', 'in-progress', 'completed', 'cancelled', 'no-show'], true)) {
            return ['success' => false, 'message' => 'Invalid status value'];
        }

        try {
            // Get appointment before updating
            $appointment = $this->db->table('appointments')->where('id', $id)->get()->getRowArray();
            if (!$appointment) {
                return ['success' => false, 'message' => 'Appointment not found'];
            }

            $builder = $this->db->table('appointments')->where('id', $id);
            if ($userRole === 'doctor' && $staffId) {
                $builder->where('doctor_id', $staffId);
            }
            $updated = $builder->update(['status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
            
            if ($updated && $this->db->affectedRows() > 0) {
                // Automatically add to billing when appointment is completed
                // Wrap in try-catch to prevent billing errors from blocking status update
                if ($status === 'completed') {
                    try {
                        $this->addAppointmentToBilling($id, $appointment, $staffId);
                    } catch (\Throwable $billingError) {
                        // Log the billing error but don't fail the status update
                        log_message('error', 'Failed to add appointment to billing: ' . $billingError->getMessage());
                        // Status update was successful, so return success even if billing failed
                    }
                }
                
                return ['success' => true, 'message' => 'Appointment status updated successfully'];
            }
            
            return ['success' => false, 'message' => 'Appointment not found or no permission to update'];
        } catch (\Throwable $e) {
            log_message('error', 'Failed to update appointment status: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Automatically add completed appointment to billing account
     */
    private function addAppointmentToBilling($appointmentId, $appointment, $staffId = null): void
    {
        try {
            // Check if FinancialService is available
            if (!class_exists(\App\Services\FinancialService::class)) {
                log_message('warning', 'FinancialService not available for auto-billing appointment');
                return;
            }

            $patientId = (int)($appointment['patient_id'] ?? 0);
            if ($patientId <= 0) {
                log_message('warning', "Appointment {$appointmentId}: No patient ID found");
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
                log_message('error', "Appointment {$appointmentId}: Failed to get/create billing account for patient {$patientId}");
                return;
            }

            $billingId = (int)$account['billing_id'];

            // Check if appointment is already in billing
            // Only count if the appointment still exists (not orphaned from deleted appointment)
            if ($this->db->tableExists('billing_items') && $this->db->tableExists('appointments')) {
                $existing = $this->db->table('billing_items bi')
                    ->join('appointments a', 'a.id = bi.appointment_id', 'inner')
                    ->where('bi.billing_id', $billingId)
                    ->where('bi.appointment_id', $appointmentId)
                    ->where('a.id', $appointmentId) // Ensure appointment still exists
                    ->countAllResults();
                
                if ($existing > 0) {
                    log_message('debug', "Appointment {$appointmentId}: Already in billing account {$billingId}");
                    return; // Already added
                }
            }

            // Get consultation fee
            $consultationFee = $this->getConsultationFee($appointment['appointment_type'] ?? 'Consultation');

            // Add to billing
            $result = $financialService->addItemFromAppointment(
                $billingId,
                $appointmentId,
                $consultationFee,
                1,
                $staffId
            );

            if (!($result['success'] ?? false)) {
                log_message('error', "Appointment {$appointmentId}: Failed to add to billing - " . ($result['message'] ?? 'Unknown error'));
            }
        } catch (\Throwable $e) {
            log_message('error', 'AppointmentService::addAppointmentToBilling error: ' . $e->getMessage());
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
            log_message('error', 'AppointmentService::getPatientType error: ' . $e->getMessage());
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
            log_message('error', 'AppointmentService::getActiveAdmissionId error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get consultation fee based on appointment type
     */
    private function getConsultationFee(string $appointmentType): float
    {
        // Default fees (can be moved to config or database)
        $fees = [
            'Consultation' => 500.00,
            'Follow-up' => 300.00,
            'Check-up' => 400.00,
            'Emergency' => 1000.00,
        ];

        return $fees[$appointmentType] ?? 500.00; // Default fee
    }

    /**
     * Delete appointment (DB primary key `id`)
     */
    public function deleteAppointment($id, $userRole = null, $staffId = null)
    {
        try {
            $builder = $this->db->table('appointments')->where('id', $id);
            if ($userRole === 'doctor' && $staffId) {
                $builder->where('doctor_id', $staffId);
            }
            $deleted = $builder->delete();
            return $deleted && $this->db->affectedRows() > 0
                ? ['success' => true, 'message' => 'Appointment deleted successfully']
                : ['success' => false, 'message' => 'Appointment not found or no permission to delete'];
        } catch (\Throwable $e) {
            log_message('error', 'Failed to delete appointment: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }


    private function determineDoctorId($input, $userRole, $staffId)
    {
        if ($userRole === 'doctor') return $staffId;
        if (in_array($userRole, ['receptionist', 'admin'])) {
            if (!empty($input['doctor_id'])) return (int)$input['doctor_id'];
            $firstDoctor = $this->db->table('staff')->select('staff_id')->where('role', 'doctor')->where('status', 'active')->get()->getRowArray();
            return $firstDoctor ? $firstDoctor['staff_id'] : null;
        }
        return null;
    }

    private function getValidationRules($userRole)
    {
        $baseRules = [
            'patient_id' => 'required|numeric',
            'appointment_date' => 'required|valid_date',
            'appointment_type' => 'required|in_list[Consultation,Follow-up,Check-up]',
        ];

        if ($userRole === 'doctor') {
            // Doctor-specific validation - same as admin/receptionist (time and duration have defaults)
            $baseRules['date'] = 'required|valid_date';
            $baseRules['type'] = 'required|in_list[Consultation,Follow-up,Check-up,Emergency]';
            // time and duration are optional (will use defaults)
            // Unset admin/receptionist field names since doctors use different field names
            unset($baseRules['appointment_date'], $baseRules['appointment_time'], $baseRules['appointment_type']);
        } else {
            // Receptionist/Admin validation for unified modal
            $baseRules['doctor_id'] = 'required|numeric';
        }

        return $baseRules;
    }

    private function prepareAppointmentData($input, $doctorId, $userRole)
    {
        $baseData = ['patient_id' => $input['patient_id'], 'doctor_id' => $doctorId, 'status' => 'scheduled', 'created_at' => date('Y-m-d H:i:s')];
        if ($userRole === 'doctor') {
            $baseData['appointment_date'] = $input['date'];
            $baseData['appointment_time'] = $input['time'] ?? '09:00:00'; // Default time like admin/receptionist
            $baseData['appointment_type'] = $input['type'];
            $baseData['reason'] = $input['reason'] ?? null;
            $baseData['duration'] = $input['duration'] ?? 30; // Default duration like admin/receptionist
        } else {
            $baseData['appointment_date'] = $input['appointment_date'];
            $baseData['appointment_time'] = '09:00:00';
            $baseData['appointment_type'] = $input['appointment_type'];
            $baseData['reason'] = $input['notes'] ?? null;
            $baseData['duration'] = 30;
        }
        return $baseData;
    }

    private function buildAppointmentQuery()
    {
        return $this->db->table('appointments a')
            ->select('a.*, p.patient_id, p.first_name as patient_first_name, p.last_name as patient_last_name, p.email as patient_email, p.date_of_birth, TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) as patient_age, CONCAT(p.first_name, " ", p.last_name) as patient_full_name, s.staff_id as doctor_id, s.first_name as doctor_first_name, s.last_name as doctor_last_name, CONCAT(s.first_name, " ", s.last_name) as doctor_name, DATE_FORMAT(a.appointment_date, "%W, %M %d, %Y") as formatted_date, TIME_FORMAT(a.appointment_time, "%h:%i %p") as formatted_time')
            ->join('patients p', 'p.patient_id = a.patient_id', 'left')
            ->join('staff s', 's.staff_id = a.doctor_id', 'left');
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
            log_message('error', 'AppointmentService::isPatientAssignedToDoctor error: ' . $e->getMessage());
            return false;
        }
    }
}