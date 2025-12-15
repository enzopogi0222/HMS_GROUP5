<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Services\PatientService;
use App\Services\RoomService;
use App\Libraries\PermissionManager;

class PatientManagement extends BaseController
{
    protected $patientService;
    protected RoomService $roomService;
    protected $session;
    protected $userRole;
    protected $staffId;
    protected $db;

    public function __construct()
    {
        $this->patientService = new PatientService();
        $this->roomService = new RoomService();
        $this->session = session();
        $this->userRole = $this->session->get('role');
        $this->staffId = $this->session->get('staff_id');
        $this->db = \Config\Database::connect();
        
        // Authentication is now handled by the roleauth filter in routes
    }

    /**
     * Main unified patient management - Role-based
     */
    public function index()
    {
        // Get patient data based on role permissions
        $patients = $this->patientService->getPatientsByRole($this->userRole, $this->staffId);
        $stats = $this->patientService->getPatientStats($this->userRole, $this->staffId);
        $availableDoctors = $this->patientService->getAvailableDoctors();

        $roomTypes = [];
        if ($this->db->tableExists('room_type')) {
            $builder = $this->db->table('room_type')
                ->select('room_type_id, type_name');

            if ($this->db->fieldExists('base_daily_rate', 'room_type')) {
                $builder->select('base_daily_rate');
            }

            $roomTypes = $builder
                ->orderBy('type_name', 'ASC')
                ->get()
                ->getResultArray();
        }
        $rooms = [];
        $roomInventory = [];
        if ($this->db->tableExists('room')) {
            $rooms = $this->roomService->getRooms();
        }

        foreach ($rooms as $room) {
            $typeId = (int) ($room['room_type_id'] ?? 0);
            if (! $typeId) {
                continue;
            }

            $bedNames = [];
            if (! empty($room['bed_names'])) {
                $decoded = json_decode((string) $room['bed_names'], true);
                if (is_array($decoded)) {
                    $bedNames = array_values($decoded);
                }
            }

            $roomInventory[$typeId][] = [
                'room_id'       => (int) ($room['room_id'] ?? 0),
                'room_number'   => (string) ($room['room_number'] ?? ''),
                'room_name'     => (string) ($room['room_name'] ?? ''),
                'floor_number'  => (string) ($room['floor_number'] ?? ''),
                'department_id' => (int) ($room['department_id'] ?? 0),
                'status'        => (string) ($room['status'] ?? ''),
                'bed_capacity'  => (int) ($room['bed_capacity'] ?? 0),
                'bed_names'     => $bedNames,
            ];
        }
        $permissions = PermissionManager::getRolePermissions($this->userRole);

        $departments = [];
        if ($this->db->tableExists('department')) {
            if ($this->db->tableExists('medical_department')) {
                $departments = $this->db->table('department d')
                    ->select('d.department_id, d.name')
                    ->join('medical_department md', 'md.department_id = d.department_id', 'inner')
                    ->orderBy('d.name', 'ASC')
                    ->get()
                    ->getResultArray();
            }

            if (empty($departments)) {
                $builder = $this->db->table('department')
                    ->select('department_id, name');

                if ($this->db->fieldExists('type', 'department')) {
                    $builder->whereIn('type', ['Medical', 'medical', 'MEDICAL']);
                }

                $departments = $builder
                    ->orderBy('name','ASC')
                    ->get()
                    ->getResultArray();
            }

            if (empty($departments)) {
                $departments = $this->db->table('department')
                    ->select('department_id, name')
                    ->orderBy('name', 'ASC')
                    ->get()
                    ->getResultArray();
            }
        }

        $data = [
            'title' => $this->getPageTitle(),
            'userRole' => $this->userRole,
            'patients' => $patients,
            'patientStats' => $stats,
            'availableDoctors' => $availableDoctors,
            'permissions' => $permissions['patients'] ?? [],
            'total_patients' => count($patients),
            'roomTypes' => $roomTypes,
            'roomInventory' => $roomInventory,
            'departments' => $departments,
        ];

        // Use unified view for all roles
        return view('unified/patient-management', $data);
    }

    /**
     * Patient Records front-end view
     */
    public function patientRecords()
    {
        if (!in_array($this->userRole, ['admin', 'doctor', 'nurse', 'pharmacist', 'laboratorist', 'receptionist', 'accountant', 'it_staff'], true)) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Insufficient permissions']);
        }

        $stats = $this->patientService->getPatientStats($this->userRole, $this->staffId);
        $patients = $this->patientService->getPatientsByRole($this->userRole, $this->staffId);

        $data = [
            'title' => 'Patient Records',
            'userRole' => $this->userRole,
            'patientStats' => $stats,
            'patients' => $patients,
        ];

        return view('unified/patient-records', $data);
    }

    /**
     * Get comprehensive patient records API
     */
    public function getPatientRecordsAPI($patientId)
    {
        try {
            if (!$this->canView()) {
                return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Insufficient permissions']);
            }

            if (!$patientId) {
                return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Invalid patient ID']);
            }

            $result = $this->patientService->getPatientRecords($patientId);
            
            if ($result['status'] === 'success') {
                return $this->response->setJSON($result);
            }
            
            // Return error with status code
            $statusCode = isset($result['message']) && strpos($result['message'], 'not found') !== false ? 404 : 500;
            return $this->response->setStatusCode($statusCode)->setJSON($result);
        } catch (\Throwable $e) {
            log_message('error', 'PatientRecordsAPI Error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Record vital signs for a patient
     */
    public function recordVitalSigns($patientId = null)
    {
        try {
            // Check permissions - nurses, doctors, and admins can record vital signs
            if (!in_array($this->userRole, ['admin', 'doctor', 'nurse'])) {
                return $this->response->setStatusCode(403)->setJSON([
                    'status' => 'error',
                    'message' => 'Insufficient permissions to record vital signs'
                ]);
            }

            if ($this->request->getMethod() !== 'POST') {
                return $this->response->setStatusCode(405)->setJSON([
                    'status' => 'error',
                    'message' => 'Method not allowed'
                ]);
            }

            $input = $this->request->getJSON(true) ?? $this->request->getPost();
            $patientId = $patientId ?? $input['patient_id'] ?? null;

            if (!$patientId) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 'error',
                    'message' => 'Patient ID is required'
                ]);
            }

            // Validate patient exists
            $patientTable = $this->db->tableExists('patient') ? 'patient' : 'patients';
            $patient = $this->db->table($patientTable)
                ->where('patient_id', $patientId)
                ->get()
                ->getRowArray();

            if (!$patient) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Patient not found'
                ]);
            }

            // Get nurse_id if user is a nurse - connect to nurse table via staff_id
            $nurseId = null;
            if ($this->userRole === 'nurse' && $this->staffId) {
                // Check if nurse table exists (singular 'nurse' as per migration)
                if ($this->db->tableExists('nurse')) {
                    $nurse = $this->db->table('nurse')
                        ->where('staff_id', $this->staffId)
                        ->get()
                        ->getRowArray();
                    $nurseId = $nurse['nurse_id'] ?? null;
                } elseif ($this->db->tableExists('nurses')) {
                    // Fallback to plural if it exists
                    $nurse = $this->db->table('nurses')
                        ->where('staff_id', $this->staffId)
                        ->get()
                        ->getRowArray();
                    $nurseId = $nurse['nurse_id'] ?? null;
                }
                // If table doesn't exist or nurse record not found, nurse_id will remain null
            }

            // Prepare vital signs data
            $vitalData = [
                'patient_id' => $patientId,
                'nurse_id' => $nurseId,
                'temperature' => !empty($input['temperature']) ? (float)$input['temperature'] : null,
                'blood_pressure_systolic' => !empty($input['blood_pressure_systolic']) ? (int)$input['blood_pressure_systolic'] : null,
                'blood_pressure_diastolic' => !empty($input['blood_pressure_diastolic']) ? (int)$input['blood_pressure_diastolic'] : null,
                'pulse_rate' => !empty($input['pulse_rate']) ? (int)$input['pulse_rate'] : null,
                'respiratory_rate' => !empty($input['respiratory_rate']) ? (int)$input['respiratory_rate'] : null,
                'oxygen_saturation' => !empty($input['oxygen_saturation']) ? (float)$input['oxygen_saturation'] : null,
                'height' => !empty($input['height']) ? (float)$input['height'] : null,
                'weight' => !empty($input['weight']) ? (float)$input['weight'] : null,
                'bmi' => null, // Will calculate if height and weight are provided
                'notes' => $input['notes'] ?? null,
                'recorded_at' => $input['recorded_at'] ?? date('Y-m-d H:i:s'),
            ];

            // Calculate BMI if height and weight are provided
            if ($vitalData['height'] && $vitalData['weight'] && $vitalData['height'] > 0) {
                $heightInMeters = $vitalData['height'] / 100; // Convert cm to meters
                $vitalData['bmi'] = round($vitalData['weight'] / ($heightInMeters * $heightInMeters), 2);
            }

            // Validate that at least one vital sign is provided
            $hasVitalSign = $vitalData['temperature'] !== null ||
                           $vitalData['blood_pressure_systolic'] !== null ||
                           $vitalData['pulse_rate'] !== null ||
                           $vitalData['respiratory_rate'] !== null ||
                           $vitalData['oxygen_saturation'] !== null ||
                           $vitalData['weight'] !== null ||
                           $vitalData['height'] !== null;

            if (!$hasVitalSign) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 'error',
                    'message' => 'At least one vital sign must be provided'
                ]);
            }

            // Check if vital_signs table exists
            if (!$this->db->tableExists('vital_signs')) {
                return $this->response->setStatusCode(500)->setJSON([
                    'status' => 'error',
                    'message' => 'Vital signs table does not exist. Please run migrations.'
                ]);
            }

            // Insert vital signs directly (nurse_id can be NULL if nurses table doesn't exist)
            $result = $this->db->table('vital_signs')->insert($vitalData);

            if ($result) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Vital signs recorded successfully',
                    'data' => $vitalData
                ]);
            } else {
                return $this->response->setStatusCode(500)->setJSON([
                    'status' => 'error',
                    'message' => 'Failed to record vital signs'
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'RecordVitalSigns Error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Create Patient - All authorized roles
     */
    public function createPatient()
    {
        if (!$this->canCreate()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Insufficient permissions']);
        }

        if ($this->request->getMethod() === 'POST') {
            $isAjax = $this->request->isAJAX() || 
                      $this->request->getHeaderLine('Accept') == 'application/json' ||
                      $this->request->getHeaderLine('X-Requested-With') == 'XMLHttpRequest';

            $input = $this->request->getJSON(true) ?? $this->request->getPost();
            
            $result = $this->patientService->createPatient(
                $input,
                $this->userRole,
                $this->staffId
            );
            
            if ($isAjax) {
                return $this->response->setJSON($result);
            }
            
            if ($result['status'] === 'success') {
                session()->setFlashdata('success', $result['message']);
            } else {
                session()->setFlashdata('error', $result['message']);
            }
            
            return redirect()->to($this->getRedirectUrl());
        }

        $data = ['title' => 'Add Patient'];
        return view($this->getCreateViewPath(), $data);
    }

    /**
     * Update Patient - Role-based permissions
     */
    public function updatePatient($patientId = null)
    {
        if (!$this->canEdit()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Insufficient permissions']);
        }

        $patientId = $patientId ?? $this->request->getPost('patient_id');
        
        if (!$patientId) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Invalid patient ID']);
        }

        $input = $this->request->getJSON(true) ?? $this->request->getPost();
        
        $result = $this->patientService->updatePatient($patientId, $input);
        
        if ($result['status'] === 'success') {
            return $this->response->setJSON(['status' => 'success', 'message' => $result['message']]);
        }
        
        return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => $result['message']]);
    }

    /**
     * Get Single Patient
     */
    public function getPatient($patientId)
    {
        if (!$this->canView()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Insufficient permissions']);
        }

        if (!$patientId) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Invalid patient ID']);
        }

        $result = $this->patientService->getPatient($patientId);
        
        if ($result['status'] === 'success') {
            return $this->response->setJSON(['status' => 'success', 'data' => $result['patient']]);
        }
        
        return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => $result['message']]);
    }

    /**
     * Delete Patient - Admin only
     */
    public function deletePatient($patientId)
    {
        if (!$this->canDelete()) {
            // Check if this is an AJAX request
            if ($this->request->isAJAX() || $this->request->getHeaderLine('Accept') == 'application/json') {
                return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Insufficient permissions']);
            }
            session()->setFlashdata('error', 'Insufficient permissions');
            return redirect()->to($this->getRedirectUrl());
        }

        if (!$patientId) {
            // Check if this is an AJAX request
            if ($this->request->isAJAX() || $this->request->getHeaderLine('Accept') == 'application/json') {
                return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Invalid patient ID']);
            }
            session()->setFlashdata('error', 'Invalid patient ID');
            return redirect()->to($this->getRedirectUrl());
        }

        $result = $this->patientService->deletePatient($patientId, $this->userRole);
        
        // Check if this is an AJAX request
        if ($this->request->isAJAX() || $this->request->getHeaderLine('Accept') == 'application/json') {
            if ($result['success']) {
                return $this->response->setJSON(['status' => 'success', 'message' => $result['message']]);
            } else {
                return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => $result['message']]);
            }
        }
        
        // For non-AJAX requests, use redirect
        if ($result['success']) {
            session()->setFlashdata('success', $result['message']);
        } else {
            session()->setFlashdata('error', $result['message']);
        }
        
        return redirect()->to($this->getRedirectUrl());
    }

    /**
     * API Endpoint for Patient List
     */
    public function getPatientsAPI()
    {
        if (!$this->canView()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Insufficient permissions']);
        }

        try {
            $patients = $this->patientService->getAllPatients($this->userRole, $this->staffId);
            return $this->response->setJSON(['status' => 'success', 'data' => $patients]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Failed to load patients']);
        }
    }

    /**
     * Update Patient Status (Complete, Discharge, etc.)
     */
    public function updatePatientStatus($patientId)
    {
        if (!$this->canEdit()) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Permission denied'
            ]);
        }

        $status = $this->request->getPost('status');
        $notes = $this->request->getPost('notes') ?? '';
        
        try {
            $updateData = ['status' => $status];
            if (!empty($notes)) {
                $updateData['medical_notes'] = $notes;
            }
            
            if ($this->db->table('patient')->where('patient_id', $patientId)->update($updateData)) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Patient status updated successfully'
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Update patient status error: ' . $e->getMessage());
        }
        
        return $this->response->setJSON([
            'status' => 'error',
            'message' => 'Failed to update patient status'
        ]);
    }

    /**
     * Get Available Doctors API
     */
    public function getDoctorsAPI()
    {
        if (!$this->canView()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Insufficient permissions']);
        }

        try {
            $doctors = $this->patientService->getAvailableDoctors();
            return $this->response->setJSON(['status' => 'success', 'data' => $doctors]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Failed to load doctors']);
        }
    }

    /**
     * Assign Doctor to Patient
     */
    public function assignDoctor($patientId)
    {
        if (!in_array($this->userRole, ['admin', 'receptionist', 'it_staff'])) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Permission denied'
            ]);
        }

        $doctorId = $this->request->getPost('doctor_id');
        
        try {
            if ($this->db->table('patient')->where('patient_id', $patientId)->update(['primary_doctor_id' => $doctorId])) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Doctor assigned successfully'
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Assign doctor error: ' . $e->getMessage());
        }
        
        return $this->response->setJSON([
            'status' => 'error',
            'message' => 'Failed to assign doctor'
        ]);
    }

    // ===================================================================
    // PERMISSION AND HELPER METHODS
    // ===================================================================

    /**
     * Check if user can create patients
     */
    private function canCreate()
    {
        return PermissionManager::hasPermission($this->userRole, 'patients', 'create');
    }

    /**
     * Check if user can edit patients
     */
    private function canEdit()
    {
        return PermissionManager::hasPermission($this->userRole, 'patients', 'edit');
    }

    /**
     * Check if user can delete patients
     */
    private function canDelete()
    {
        return PermissionManager::hasPermission($this->userRole, 'patients', 'delete');
    }

    /**
     * Check if user can view patients
     */
    private function canView()
    {
        return PermissionManager::hasPermission($this->userRole, 'patients', 'view');
    }

    /**
     * Get page title based on user role
     */
    private function getPageTitle()
    {
        switch ($this->userRole) {
            case 'admin':
                return 'Patient Management';
            case 'doctor':
                return 'My Patients';
            case 'nurse':
                return 'Department Patients';
            case 'receptionist':
                return 'Patient Registration';
            case 'pharmacist':
                return 'Patient Prescriptions';
            case 'laboratorist':
                return 'Patient Lab Tests';
            case 'accountant':
                return 'Patient Billing';
            case 'it_staff':
                return 'Patient Records';
            default:
                return 'Patient Directory';
        }
    }

    /**
     * Get redirect URL based on user role
     */
    private function getRedirectUrl()
    {
        switch ($this->userRole) {
            case 'admin':
                return base_url('admin/patient-management');
            case 'doctor':
                return base_url('doctor/patients');
            case 'nurse':
                return base_url('nurse/patients');
            case 'receptionist':
                return base_url('receptionist/patients');
            case 'pharmacist':
                return base_url('pharmacist/patients');
            case 'laboratorist':
                return base_url('laboratorist/patients');
            case 'accountant':
                return base_url('accountant/patients');
            case 'it_staff':
                return base_url('it-staff/patients');
            default:
                return base_url('dashboard');
        }
    }

    /**
     * Get view path based on user role
     */
    private function getViewPath()
    {
        switch ($this->userRole) {
            case 'admin':
                return 'admin/patient-management';
            case 'doctor':
                return 'doctor/patient';
            case 'nurse':
                return 'nurse/patient-management';
            case 'receptionist':
                return 'receptionist/patient-registration';
            case 'pharmacist':
            case 'laboratorist':
            case 'accountant':
            case 'it_staff':
                return 'admin/patient-management'; // Use admin view for other roles
            default:
                return 'admin/patient-management';
        }
    }

    /**
     * Get create view path based on user role
     */
    private function getCreateViewPath()
    {
        switch ($this->userRole) {
            case 'admin':
                return 'admin/add-patient';
            case 'doctor':
                return 'doctor/add-patient';
            case 'nurse':
                return 'nurse/add-patient';
            case 'receptionist':
                return 'receptionist/add-patient';
            default:
                return 'admin/add-patient';
        }
    }
}
