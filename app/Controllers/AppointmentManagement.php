<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Services\AppointmentService;
use App\Services\FinancialService;
use App\Services\RoomService;

use App\Libraries\PermissionManager;

class AppointmentManagement extends BaseController
{
    protected $db;
    protected $appointmentService;
    protected $financialService;

    /** @var RoomService */
    protected $roomService;

    protected $userRole;
    protected $staffId;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->appointmentService = new AppointmentService();
        $this->financialService = new FinancialService();
        $this->roomService = new RoomService();

        $session = session();
        $this->userRole = $session->get('role');
        $this->staffId = $session->get('staff_id');
    }

    /**
     * Main appointment management view - Role-based
     */
    public function index()
    {
        try {
            $roomTypes = [];
            $departments = [];
            $roomInventory = [];

            if ($this->db->tableExists('room')) {
                $rooms = $this->roomService->getRooms();

                foreach ($rooms as $room) {
                    $typeId = (int) ($room['room_type_id'] ?? 0);
                    if (!$typeId) {
                        continue;
                    }

                    $bedNames = [];
                    if (!empty($room['bed_names'])) {
                        $decoded = json_decode((string) $room['bed_names'], true);
                        if (is_array($decoded)) {
                            $bedNames = array_values($decoded);
                        }
                    }

                    $roomInventory[$typeId][] = [
                        'room_id'       => (int) ($room['room_id'] ?? 0),
                        'room_number'   => (string) ($room['room_number'] ?? ''),
                        'room_name'     => (string) ($room['type_name'] ?? ''),
                        'floor_number'  => (string) ($room['floor_number'] ?? ''),
                        'department_id' => (int) ($room['department_id'] ?? 0),
                        'status'        => (string) ($room['status'] ?? ''),
                        'bed_capacity'  => (int) ($room['bed_capacity'] ?? 0),
                        'bed_names'     => $bedNames,
                    ];
                }

                $roomTypes = $this->getRoomTypesForAssignments();
                $departments = $this->getDepartmentsForAssignments();
            }

            $data = [
                'title' => $this->getPageTitle(),
                'appointmentStats' => $this->getAppointmentStats(),
                'appointments' => $this->getAppointments(),
                'doctors' => $this->getDoctorsListData(),
                'availablePatients' => $this->getAvailablePatients(),
                'userRole' => $this->userRole,
                'permissions' => $this->getUserPermissions(),
                'roomTypes' => $roomTypes,
                'departments' => $departments,
                'roomInventory' => $roomInventory,
            ];

            return view('unified/appointments', $data);
        } catch (\Exception $e) {
            log_message('error', 'AppointmentManagement index error: ' . $e->getMessage());
            return view('unified/appointments', [
                'title' => 'Appointments',
                'appointmentStats' => [],
                'appointments' => [],
                'doctors' => [],
                'availablePatients' => [],
                'userRole' => $this->userRole ?? 'guest',
                'permissions' => []
            ]);
        }
    }

    /**
     * Create Appointment - All authorized roles
     */
    public function createAppointment()
    {
        // Check permissions using PermissionManager
        if (!PermissionManager::hasPermission($this->userRole, 'appointments', 'create')) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'You do not have permission to create appointments.',
            ]);
        }

        $input = $this->request->getJSON(true) ?? $this->request->getPost();
        return $this->response->setJSON($this->appointmentService->createAppointment($input, $this->userRole, $this->staffId));
    }

    public function updateAppointment($appointmentId = null)
    {
        $appointmentId = $appointmentId ?? $this->request->getPost('appointment_id');
        if (!$this->canEditAppointment($appointmentId)) {
            return $this->jsonResponse('error', 'Permission denied');
        }

        $input = $this->request->getJSON(true) ?? $this->request->getPost();
        try {
            $updateData = array_filter([
                'appointment_date' => $input['appointment_date'] ?? null,
                'appointment_time' => $input['appointment_time'] ?? null,
                'reason' => $input['reason'] ?? $input['notes'] ?? null,
                // Allow roles with assign_doctor permission to update doctor_id
                'doctor_id' => (PermissionManager::hasPermission($this->userRole, 'patients', 'assign_doctor') && !empty($input['doctor_id'])) ? (int)$input['doctor_id'] : null
            ], fn($v) => $v !== null);
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            
            $builder = $this->db->table('appointments');
            // Filter by doctor_id if user has view_own permission
            if (PermissionManager::hasPermission($this->userRole, 'appointments', 'view_own') && $this->staffId) {
                $builder->where('doctor_id', $this->staffId);
            }
            $result = $builder->where('id', $appointmentId)->update($updateData);
            return $this->jsonResponse($result ? 'success' : 'error', $result ? 'Appointment updated successfully' : 'Failed to update appointment');
        } catch (\Throwable $e) {
            log_message('error', 'Update appointment error: ' . $e->getMessage());
            return $this->jsonResponse('error', 'Database error');
        }
    }

    public function getAppointment($appointmentId)
    {
        if (!$this->canViewAppointment($appointmentId)) {
            return $this->jsonResponse('error', 'Permission denied');
        }
        $result = $this->appointmentService->getAppointment($appointmentId);
        return $this->response->setJSON($result['success'] ? ['status' => 'success', 'data' => $result['appointment']] : ['status' => 'error', 'message' => $result['message']]);
    }

    public function deleteAppointment($appointmentId)
    {
        if (!$this->canDeleteAppointment($appointmentId)) {
            return $this->jsonResponse('error', 'Permission denied');
        }
        return $this->response->setJSON($this->appointmentService->deleteAppointment($appointmentId, $this->userRole, $this->staffId));
    }

    public function getAppointmentsAPI()
    {
        $filters = [];
        // Filter by doctor_id if user has view_own permission
        if (PermissionManager::hasPermission($this->userRole, 'appointments', 'view_own') && $this->staffId) {
            $filters['doctor_id'] = $this->staffId;
        }
        foreach (['date', 'status', 'patient_id'] as $key) {
            if ($value = $this->request->getGet($key)) {
                $filters[$key] = $value;
            }
        }
        // Roles with view_all permission can filter by doctor_id
        if (PermissionManager::hasPermission($this->userRole, 'appointments', 'view_all') && ($doctorId = $this->request->getGet('doctor_id'))) {
            $filters['doctor_id'] = $doctorId;
        }
        $result = $this->appointmentService->getAppointments($filters);
        return $this->response->setJSON(['status' => $result['success'] ? 'success' : 'error', 'data' => $result['data'] ?? [], 'message' => $result['message'] ?? null]);
    }

    public function getPatientAppointments($patientId)
    {
        if (!$this->canViewPatientAppointments($patientId)) {
            return $this->jsonResponse('error', 'Permission denied');
        }
        $filters = ['patient_id' => $patientId];
        // Filter by doctor_id if user has view_own permission
        if (PermissionManager::hasPermission($this->userRole, 'appointments', 'view_own') && $this->staffId) {
            $filters['doctor_id'] = $this->staffId;
        }
        $result = $this->appointmentService->getAppointments($filters);
        return $this->response->setJSON(['status' => $result['success'] ? 'success' : 'error', 'data' => $result['data'] ?? [], 'message' => $result['message'] ?? null]);
    }

    public function updateAppointmentStatus($appointmentId)
    {
        try {
            if (!$this->canEditAppointment($appointmentId)) {
                return $this->response->setStatusCode(403)->setJSON([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Permission denied',
                    'csrf' => ['name' => csrf_token(), 'value' => csrf_hash()]
                ]);
            }
            
            $status = $this->request->getPost('status');
            if (empty($status)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Status is required',
                    'csrf' => ['name' => csrf_token(), 'value' => csrf_hash()]
                ]);
            }

            // Only doctors can mark appointments as completed
            if (strtolower((string) $status) === 'completed' && $this->userRole !== 'doctor') {
                return $this->response->setStatusCode(403)->setJSON([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Only the doctor can mark the appointment as completed.',
                    'csrf' => ['name' => csrf_token(), 'value' => csrf_hash()]
                ]);
            }
            
            $result = $this->appointmentService->updateAppointmentStatus($appointmentId, $status, $this->userRole, $this->staffId);
            
            $statusCode = $result['success'] ? 200 : 422;
            return $this->response->setStatusCode($statusCode)->setJSON([
                'status' => $result['success'] ? 'success' : 'error',
                'success' => $result['success'],
                'message' => $result['message'] ?? 'Status update failed',
                'csrf' => ['name' => csrf_token(), 'value' => csrf_hash()]
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'AppointmentManagement::updateAppointmentStatus error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'success' => false,
                'message' => 'Failed to update appointment status: ' . $e->getMessage(),
                'csrf' => ['name' => csrf_token(), 'value' => csrf_hash()]
            ]);
        }
    }

    /**
     * Add an appointment charge to patient's billing account (admin/accountant)
     */
    public function addToBilling($appointmentId)
    {
        if (!in_array($this->userRole, ['admin', 'accountant'])) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Permission denied. Only admin and accountant can add appointments to billing.'
            ]);
        }

        if (!$this->request->is('post')) {
            return $this->response->setStatusCode(405)->setJSON([
                'success' => false,
                'message' => 'Invalid request method'
            ]);
        }

        try {
            $input = $this->request->getJSON(true) ?? $this->request->getPost();

            $unitPrice = isset($input['unit_price']) ? (float)$input['unit_price'] : 0.0;
            $quantity  = isset($input['quantity']) ? (int)$input['quantity'] : 1;

            if ($unitPrice <= 0) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => 'Invalid unit price. Please enter a positive amount.'
                ]);
            }

            if (empty($appointmentId) || !is_numeric($appointmentId)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => 'Invalid appointment ID'
                ]);
            }

            // Load appointment to get patient
            // Note: appointments table uses 'id' as primary key, not 'appointment_id'
            $appointment = $this->db->table('appointments')
                ->where('id', $appointmentId)
                ->get()
                ->getRowArray();

            if (!$appointment || empty($appointment['patient_id'])) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Appointment or patient not found'
                ]);
            }

            $patientId = (int)$appointment['patient_id'];

            // Determine patient type and get admission_id if inpatient
            $admissionId = null;
            if ($this->db->tableExists('inpatient_admissions')) {
                try {
                    // Check if discharge_date column exists before using it
                    $hasDischargeDate = $this->db->fieldExists('discharge_date', 'inpatient_admissions');
                    $hasStatus = $this->db->fieldExists('status', 'inpatient_admissions');
                    
                    $builder = $this->db->table('inpatient_admissions')
                        ->where('patient_id', $patientId);
                    
                    // If discharge_date column exists, check for non-discharged admissions
                    if ($hasDischargeDate) {
                        $builder->groupStart()
                            ->where('discharge_date', null)
                            ->orWhere('discharge_date', '')
                        ->groupEnd();
                    } elseif ($hasStatus) {
                        // If status column exists, check for active status
                        $builder->where('status', 'active');
                    }
                    // If neither exists, just get the most recent admission (assume it's active)
                    
                    $activeAdmission = $builder
                        ->orderBy('admission_id', 'DESC')
                        ->limit(1)
                        ->get()
                        ->getRowArray();
                    
                    if ($activeAdmission) {
                        // If discharge_date exists and is set, skip this admission
                        if ($hasDischargeDate && !empty($activeAdmission['discharge_date'])) {
                            $admissionId = null;
                        } else {
                            $admissionId = (int)$activeAdmission['admission_id'];
                        }
                    }
                } catch (\Throwable $e) {
                    // If there's an error checking for admission, just proceed without admission_id
                    // This allows outpatients to still be billed
                    log_message('debug', 'Error checking for admission: ' . $e->getMessage());
                    $admissionId = null;
                }
            }

            // Get or create billing account for this patient
            $account = $this->financialService->getOrCreateBillingAccountForPatient($patientId, $admissionId, (int)$this->staffId);

            if (!$account || empty($account['billing_id'])) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Unable to create or load billing account'
                ]);
            }

            $billingId = (int)$account['billing_id'];

            // Check if appointment is already in billing to prevent duplicates
            // Since we already verified appointment exists, just check if it's in billing_items
            if ($this->db->tableExists('billing_items')) {
                $existing = $this->db->table('billing_items')
                    ->where('billing_id', $billingId)
                    ->where('appointment_id', (int)$appointmentId)
                    ->countAllResults();
                
                if ($existing > 0) {
                    // Double-check: verify this is not an orphaned item from a deleted appointment
                    // by checking if the appointment_id in billing_items matches an existing appointment
                    $appointmentCheck = $this->db->table('appointments')
                        ->where('id', (int)$appointmentId)
                        ->countAllResults();
                    
                    // If appointment exists and is in billing, it's a real duplicate
                    if ($appointmentCheck > 0) {
                        // Get details of existing billing item for logging
                        $existingItem = $this->db->table('billing_items')
                            ->where('billing_id', $billingId)
                            ->where('appointment_id', (int)$appointmentId)
                            ->get()
                            ->getRowArray();
                        
                        log_message('info', "Duplicate check: Appointment ID {$appointmentId} already in billing account {$billingId}. Existing item ID: " . ($existingItem['item_id'] ?? 'unknown'));
                        
                        return $this->response->setJSON([
                            'success' => true,
                            'message' => 'This appointment is already in the billing account.',
                            'billing_id' => $billingId,
                            'existing_item_id' => $existingItem['item_id'] ?? null
                        ]);
                    } else {
                        // Orphaned item - log it but allow adding (will create new item)
                        log_message('warning', "Found orphaned billing item for appointment {$appointmentId} - appointment no longer exists, allowing new entry");
                    }
                }
            }

            // Add appointment item to billing
            $result = $this->financialService->addItemFromAppointment(
                $billingId,
                (int)$appointmentId,
                $unitPrice,
                $quantity,
                (int)$this->staffId
            );

            if ($result['success']) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Appointment successfully added to billing account.',
                    'billing_id' => $billingId
                ]);
            } else {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to add appointment to billing'
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'AppointmentManagement::addToBilling error: ' . $e->getMessage());
            log_message('error', 'AppointmentManagement::addToBilling stack trace: ' . $e->getTraceAsString());
            
            // Get database error if available
            $dbError = $this->db->error();
            $errorMessage = 'An error occurred while adding appointment to billing.';
            
            if (!empty($dbError['message'])) {
                log_message('error', 'Database error: ' . json_encode($dbError));
                $errorMessage .= ' Database error: ' . $dbError['message'];
            } else {
                $errorMessage .= ' Error: ' . $e->getMessage();
            }
            
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => $errorMessage
            ]);
        }
    }


    // ===================================================================
    // PRIVATE HELPER METHODS
    // ===================================================================

    /**
     * Get Appointments based on user role
     */
    private function getAppointments()
    {
        $filters = [];
        
        // Role-based filtering - filter by doctor_id if user has view_own permission
        if (PermissionManager::hasPermission($this->userRole, 'appointments', 'view_own') && $this->staffId) {
            $filters['doctor_id'] = $this->staffId;
        }
        
        $result = $this->appointmentService->getAppointments($filters);
        return $result['data'] ?? [];
    }

    /**
     * Room types for assign-room modal on appointments page
     */
    private function getRoomTypesForAssignments(): array
    {
        if (! $this->db->tableExists('room_type')) {
            return [];
        }

        $builder = $this->db->table('room_type')
            ->select('room_type_id, type_name');

        if ($this->db->fieldExists('base_daily_rate', 'room_type')) {
            $builder->select('base_daily_rate');
        }

        return $builder->orderBy('type_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Departments for assign-room modal on appointments page
     */
    private function getDepartmentsForAssignments(): array
    {
        if (! $this->db->tableExists('department')) {
            return [];
        }

        // Prefer medical departments if medical_department table exists
        if ($this->db->tableExists('medical_department')) {
            return $this->db->table('department d')
                ->select('d.department_id, d.name, d.floor')
                ->join('medical_department md', 'md.department_id = d.department_id', 'inner')
                ->orderBy('d.name', 'ASC')
                ->get()
                ->getResultArray();
        }

        $builder = $this->db->table('department')
            ->select('department_id, name, floor');

        if ($this->db->fieldExists('type', 'department')) {
            $builder->whereIn('type', ['Medical', 'medical', 'MEDICAL']);
        }

        return $builder
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get Appointment Statistics
     */
    private function getAppointmentStats()
    {
        $stats = [
            'today_total' => 0,
            'today_completed' => 0,
            'today_pending' => 0,
            'week_total' => 0,
            'week_cancelled' => 0,
            'week_no_shows' => 0,
            'next_appointment' => 'None',
            'hours_scheduled' => 0
        ];
        
        try {
            $today = date('Y-m-d');
            $weekStart = date('Y-m-d', strtotime('monday this week'));
            $weekEnd = date('Y-m-d', strtotime('sunday this week'));
            
            $builder = $this->db->table('appointments');
            
            // Apply role-based filtering
            if (PermissionManager::hasPermission($this->userRole, 'appointments', 'view_own') && $this->staffId) {
                $builder->where('doctor_id', $this->staffId);
            } elseif ($this->userRole === 'nurse') {
                // Filter by department for nurses
                $builder->join('staff', 'staff.staff_id = appointments.doctor_id')
                        ->join('staff as nurse_staff', 'nurse_staff.staff_id = ' . $this->staffId)
                        ->where('staff.department = nurse_staff.department');
            }
            
            // Today's stats
            $todayBuilder = clone $builder;
            $stats['today_total'] = $todayBuilder->where('appointment_date', $today)->countAllResults(false);
            $stats['today_completed'] = $todayBuilder->where('status', 'completed')->countAllResults(false);
            $stats['today_pending'] = $todayBuilder->whereIn('status', ['scheduled', 'in-progress'])->countAllResults();
            
            // Week stats
            $weekBuilder = clone $builder;
            $stats['week_total'] = $weekBuilder->where('appointment_date >=', $weekStart)
                                             ->where('appointment_date <=', $weekEnd)
                                             ->countAllResults(false);
            $stats['week_cancelled'] = $weekBuilder->where('status', 'cancelled')->countAllResults(false);
            $stats['week_no_shows'] = $weekBuilder->where('status', 'no-show')->countAllResults();
            
            // Next appointment
            $nextBuilder = clone $builder;
            $nextAppointment = $nextBuilder->select('appointment_time')
                                          ->where('appointment_date', $today)
                                          ->where('appointment_time >', date('H:i:s'))
                                          ->whereIn('status', ['scheduled', 'in-progress'])
                                          ->orderBy('appointment_time', 'ASC')
                                          ->limit(1)
                                          ->get()
                                          ->getRow();
            
            if ($nextAppointment) {
                $stats['next_appointment'] = date('g:i A', strtotime($nextAppointment->appointment_time));
            }
            
            // Hours scheduled today
            $hoursBuilder = clone $builder;
            $appointments = $hoursBuilder->select('duration')
                                        ->where('appointment_date', $today)
                                        ->whereIn('status', ['scheduled', 'in-progress', 'completed'])
                                        ->get()
                                        ->getResultArray();
            
            $totalMinutes = 0;
            foreach ($appointments as $appointment) {
                $totalMinutes += (int)($appointment['duration'] ?? 30);
            }
            $stats['hours_scheduled'] = round($totalMinutes / 60, 1);
            
        } catch (\Throwable $e) {
            log_message('error', 'Appointment stats error: ' . $e->getMessage());
        }
        
        return $stats;
    }

    private function getUserPermissions()
    {
        $role = $this->userRole;
        return [
            'canCreate' => PermissionManager::hasPermission($role, 'appointments', 'create'),
            'canEdit' => PermissionManager::hasPermission($role, 'appointments', 'edit'),
            'canDelete' => PermissionManager::hasPermission($role, 'appointments', 'delete'),
            'canViewAll' => PermissionManager::hasPermission($role, 'appointments', 'view_all'),
            'canUpdateStatus' => PermissionManager::hasPermission($role, 'appointments', 'edit'),
            'canSchedule' => PermissionManager::hasPermission($role, 'appointments', 'create'),
            'canReschedule' => PermissionManager::hasPermission($role, 'appointments', 'reschedule'),
            'canAddToBill' => PermissionManager::hasPermission($role, 'billing', 'create') || PermissionManager::hasPermission($role, 'billing', 'process')
        ];
    }

    private function canViewAppointment($appointmentId)
    {
        // Check if user has view_all permission
        if (PermissionManager::hasPermission($this->userRole, 'appointments', 'view_all')) {
            return true;
        }
        // Check if user has view_own permission and owns the appointment
        if (PermissionManager::hasPermission($this->userRole, 'appointments', 'view_own') && $this->staffId) {
            return !empty($this->db->table('appointments')->where('id', $appointmentId)->where('doctor_id', $this->staffId)->get()->getRow());
        }
        return false;
    }

    private function canEditAppointment($appointmentId)
    {
        // Check if user has edit permission
        if (!PermissionManager::hasPermission($this->userRole, 'appointments', 'edit')) {
            return false;
        }
        // If user has view_all, they can edit
        if (PermissionManager::hasPermission($this->userRole, 'appointments', 'view_all')) {
            return true;
        }
        // If user has view_own, check if they own the appointment
        if (PermissionManager::hasPermission($this->userRole, 'appointments', 'view_own') && $this->staffId) {
            return $this->canViewAppointment($appointmentId);
        }
        // Special case for nurses
        if ($this->userRole === 'nurse') {
            return $this->isAppointmentInNurseDepartment($appointmentId);
        }
        return false;
    }

    private function canDeleteAppointment($appointmentId)
    {
        return PermissionManager::hasPermission($this->userRole, 'appointments', 'delete');
    }

    private function getPageTitle()
    {
        return match($this->userRole) {
            'admin' => 'System Appointments',
            'doctor' => 'My Appointments',
            'nurse' => 'Department Appointments',
            'receptionist' => 'Appointment Booking',
            default => 'Appointments'
        };
    }

    private function getDoctorsListData()
    {
        try {
            return $this->db->table('doctor d')
                ->select('s.staff_id, s.first_name, s.last_name, d.specialization')
                ->join('staff s', 's.staff_id = d.staff_id', 'inner')
                ->where('d.status', 'Active')
                ->get()
                ->getResultArray();
        } catch (\Exception $e) {
            log_message('error', 'Get doctors list error: ' . $e->getMessage());
            return [];
        }
    }

    private function getAvailablePatients()
    {
        try {
            $dateFilter = $this->request->getGet('date');
            // Determine which table name to use
            $tableName = $this->db->tableExists('patients') ? 'patients' : ($this->db->tableExists('patient') ? 'patient' : 'patients');
            
            $selectFields = 'p.patient_id, p.first_name, p.last_name, p.date_of_birth';
            if ($this->db->fieldExists('patient_type', $tableName)) {
                $selectFields .= ', p.patient_type';
            }
            
            $builder = $this->db->table($tableName . ' p')
                ->select($selectFields);
            
            // For doctors: filter by primary_doctor_id
            $userRole = session()->get('role');
            $staffId = session()->get('staff_id');
            
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

            // Exclude patients that already have an appointment on the selected date
            if (!empty($dateFilter) && $this->db->tableExists('appointments')) {
                $builder->whereNotIn('p.patient_id', function ($sub) use ($dateFilter) {
                    return $sub->select('patient_id')
                        ->from('appointments')
                        ->where('appointment_date', $dateFilter)
                        ->whereIn('status', ['scheduled', 'in-progress']);
                });
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
        } catch (\Exception $e) {
            log_message('error', 'Get available patients error: ' . $e->getMessage());
            return [];
        }
    }

    public function getPatientsList()
    {
        try {
            return $this->response->setJSON(['status' => 'success', 'data' => $this->getAvailablePatients()]);
        } catch (\Exception $e) {
            log_message('error', 'Get patients list error: ' . $e->getMessage());
            return $this->jsonResponse('error', 'Failed to load patients');
        }
    }

    public function getDoctorsList()
    {
        try {
            return $this->response->setJSON(['status' => 'success', 'data' => $this->getDoctorsListData()]);
        } catch (\Exception $e) {
            log_message('error', 'Get doctors API error: ' . $e->getMessage());
            return $this->jsonResponse('error', 'Failed to load doctors');
        }
    }

    public function getAvailableDoctorsByDate()
    {
        try {
            $date = $this->request->getGet('date') ?: date('Y-m-d');
            if (!($timestamp = strtotime($date))) {
                return $this->jsonResponse('error', 'Invalid date');
            }
            $weekday = (int) date('N', $timestamp);

            $doctors = [];

            // Prefer doctors with an active schedule on this weekday (when schedules are configured)
            if ($this->db->tableExists('staff_schedule')) {
                $doctors = $this->db->table('staff_schedule ss')
                    ->select('s.staff_id, s.first_name, s.last_name, d.specialization')
                    ->join('doctor d', 'd.staff_id = ss.staff_id', 'inner')
                    ->join('staff s', 's.staff_id = ss.staff_id', 'inner')
                    ->where('ss.status', 'active')
                    ->where('ss.weekday', $weekday)
                    ->where('d.status', 'Active')
                    ->groupBy('s.staff_id')
                    ->orderBy('s.first_name', 'ASC')
                    ->get()
                    ->getResultArray();
            }

            // Fallback: if schedules are not configured, still show all active doctors
            if (empty($doctors)) {
                $doctors = $this->getDoctorsListData();
            }

            return $this->response->setJSON(['status' => 'success', 'data' => $doctors]);
        } catch (\Throwable $e) {
            log_message('error', 'Get available doctors by date error: ' . $e->getMessage());
            return $this->jsonResponse('error', 'Failed to load available doctors');
        }
    }

    public function getDoctorSlotsByDate()
    {
        try {
            $doctorId = (int) ($this->request->getGet('doctor_id') ?? 0);
            $date = $this->request->getGet('date') ?: date('Y-m-d');
            if ($doctorId <= 0) {
                return $this->jsonResponse('error', 'Doctor is required');
            }
            if (!($timestamp = strtotime($date))) {
                return $this->jsonResponse('error', 'Invalid date');
            }

            $weekday = (int) date('N', $timestamp);

            if (!$this->db->tableExists('staff_schedule')) {
                return $this->response->setJSON(['status' => 'success', 'data' => ['duty' => null, 'slots' => [], 'booked' => []]]);
            }

            $scheduleBuilder = $this->db->table('staff_schedule')
                ->where('staff_id', $doctorId)
                ->where('weekday', $weekday)
                ->where('status', 'active');

            if ($this->db->fieldExists('effective_from', 'staff_schedule')) {
                $scheduleBuilder->groupStart()
                    ->where('effective_from', null)
                    ->orWhere('effective_from <=', $date)
                ->groupEnd();
            }

            if ($this->db->fieldExists('effective_to', 'staff_schedule')) {
                $scheduleBuilder->groupStart()
                    ->where('effective_to', null)
                    ->orWhere('effective_to >=', $date)
                ->groupEnd();
            }

            $rows = $scheduleBuilder->get()->getResultArray();
            if (empty($rows)) {
                return $this->response->setJSON(['status' => 'success', 'data' => ['duty' => null, 'slots' => [], 'booked' => []]]);
            }

            $starts = [];
            $ends = [];
            foreach ($rows as $row) {
                $start = $row['start_time'] ?? null;
                $end = $row['end_time'] ?? null;

                if ((!$start || !$end) && !empty($row['slot'])) {
                    $slot = strtolower((string) $row['slot']);
                    if ($slot === 'morning') {
                        $start = '08:00:00';
                        $end = '12:00:00';
                    } elseif ($slot === 'afternoon') {
                        $start = '13:00:00';
                        $end = '17:00:00';
                    } elseif ($slot === 'night') {
                        $start = '18:00:00';
                        $end = '22:00:00';
                    } elseif ($slot === 'all_day') {
                        $start = '00:00:00';
                        $end = '23:59:59';
                    }
                }

                if ($start && $end) {
                    $starts[] = $start;
                    $ends[] = $end;
                }
            }

            if (empty($starts) || empty($ends)) {
                return $this->response->setJSON(['status' => 'success', 'data' => ['duty' => null, 'slots' => [], 'booked' => []]]);
            }

            sort($starts);
            rsort($ends);
            $dutyStart = $starts[0];
            $dutyEnd = $ends[0];

            $bookedTimes = [];
            if ($this->db->tableExists('appointments')) {
                $bookedRows = $this->db->table('appointments')
                    ->select('appointment_time')
                    ->where('doctor_id', $doctorId)
                    ->where('appointment_date', $date)
                    ->whereIn('status', ['scheduled', 'in-progress'])
                    ->get()
                    ->getResultArray();

                foreach ($bookedRows as $r) {
                    if (!empty($r['appointment_time'])) {
                        $bookedTimes[] = date('H:i', strtotime($r['appointment_time']));
                    }
                }
            }
            $bookedLookup = array_flip($bookedTimes);

            $slotMinutes = 15;
            $slots = [];
            $t = strtotime('1970-01-01 ' . $dutyStart);
            $endT = strtotime('1970-01-01 ' . $dutyEnd);
            if ($t !== false && $endT !== false) {
                while ($t <= $endT) {
                    $hm = date('H:i', $t);
                    if (!isset($bookedLookup[$hm])) {
                        $slots[] = $hm;
                    }
                    $t += $slotMinutes * 60;
                }
            }

            return $this->response->setJSON([
                'status' => 'success',
                'data' => [
                    'duty' => ['start' => $dutyStart, 'end' => $dutyEnd],
                    'slots' => $slots,
                    'booked' => $bookedTimes
                ]
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Get doctor slots by date error: ' . $e->getMessage());
            return $this->jsonResponse('error', 'Failed to load doctor slots');
        }
    }

    private function isAppointmentInNurseDepartment($appointmentId)
    {
        try {
            return !empty($this->db->table('appointments a')
                ->join('staff ns', 'ns.staff_id', $this->staffId)
                ->join('staff ds', 'ds.staff_id = a.doctor_id')
                ->where('a.id', $appointmentId)
                ->where('ns.department = ds.department')
                ->get()->getRow());
        } catch (\Throwable $e) {
            log_message('error', 'Check nurse department error: ' . $e->getMessage());
            return false;
        }
    }

    private function canViewPatientAppointments($patientId)
    {
        return match($this->userRole) {
            'admin', 'receptionist' => true,
            'doctor' => !empty($this->db->table('patient')->where('patient_id', $patientId)->where('primary_doctor_id', $this->staffId)->get()->getRow()),
            'nurse' => !empty($this->db->table('patient p')->join('staff ps', 'ps.staff_id = p.primary_doctor_id')->join('staff ns', 'ns.staff_id = ' . $this->staffId)->where('p.patient_id', $patientId)->where('ps.department = ns.department')->get()->getRow()),
            default => false
        };
    }

    private function jsonResponse($status, $message, $data = null)
    {
        $response = ['status' => $status, 'message' => $message];
        if ($data !== null) $response['data'] = $data;
        return $this->response->setJSON($response);
    }
}