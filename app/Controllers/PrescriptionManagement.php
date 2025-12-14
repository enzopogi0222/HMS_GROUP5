<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Services\PrescriptionService;
use App\Services\ResourceService;
use App\Services\FinancialService;
use App\Services\PatientService;
use App\Libraries\PermissionManager;

class PrescriptionManagement extends BaseController
{
    protected $prescriptionService;
    protected $permissionManager;
    protected $financialService;
    protected $patientService;
    protected $userRole;
    protected $staffId;
    protected $db;

    public function __construct()
    {
        $this->prescriptionService = new PrescriptionService();
        $this->permissionManager = new PermissionManager();
        $this->financialService = new FinancialService();
        $this->patientService = new PatientService();
        $this->db = \Config\Database::connect();
        $session = session();
        $this->userRole = $session->get('role');
        $this->staffId = $session->get('staff_id');
    }
    
    private function jsonResponse($status, $message, $data = null, $statusCode = 200)
    {
        $response = ['status' => $status, 'message' => $message];
        if ($data !== null) $response['data'] = $data;
        $response['csrf'] = ['name' => csrf_token(), 'value' => csrf_hash()];
        return $this->response->setStatusCode($statusCode)->setJSON($response);
    }

    /**
     * Add a prescription to a patient's billing account
     */
    public function addToBilling($id)
    {
        // Only allow specific roles to bill prescriptions
        if (!in_array($this->userRole, ['admin', 'accountant', 'pharmacist'])) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'You are not allowed to add prescriptions to billing.'
            ]);
        }

        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setStatusCode(405)->setJSON([
                'success' => false,
                'message' => 'Invalid request method.'
            ]);
        }

        try {
            $payload = $this->request->getJSON(true) ?? $this->request->getPost();

            $unitPrice = isset($payload['unit_price']) ? (float)$payload['unit_price'] : 0.0;
            $quantity  = isset($payload['quantity']) ? (int)$payload['quantity'] : null;

            // Load prescription to get patient_id and validate existence
            $prescription = $this->prescriptionService->getPrescription($id);
            if (!$prescription) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'Prescription not found.'
                ]);
            }

            // Only allow completed or dispensed prescriptions to be billed
            $status = strtolower($prescription['status'] ?? '');
            if (!in_array($status, ['completed', 'dispensed'])) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => 'Only completed or dispensed prescriptions can be added to billing.'
                ]);
            }

            $patientId = (int)($prescription['patient_id'] ?? $prescription['pat_id'] ?? 0);
            if ($patientId <= 0) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => 'Prescription is not linked to a valid patient.'
                ]);
            }

            // Ensure the patient has a billing account
            $account = $this->financialService->getOrCreateBillingAccountForPatient($patientId, null, (int)$this->staffId);
            if (!$account || empty($account['billing_id'])) {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'Unable to create or load billing account for this patient.'
                ]);
            }

            $billingId = (int)$account['billing_id'];

            // If unit_price not provided, get it from resources using selling_price
            if ($unitPrice <= 0) {
                // Try to get selling_price from prescription items
                $db = \Config\Database::connect();
                if ($db->tableExists('prescription_items')) {
                    $items = $db->table('prescription_items')
                        ->where('prescription_id', $id)
                        ->get()
                        ->getResultArray();
                    
                    if (!empty($items)) {
                        $firstItem = $items[0];
                        $resourceId = $firstItem['medication_resource_id'] ?? $firstItem['resource_id'] ?? null;
                        $medicationName = $firstItem['medication_name'] ?? '';
                        
                        // Get selling_price from resources - PRIORITIZE selling_price over price
                        if ($resourceId && $db->tableExists('resources')) {
                            $resource = $db->table('resources')
                                ->select('selling_price, price')
                                ->where('id', $resourceId)
                                ->where('category', 'Medications')
                                ->get()
                                ->getRowArray();
                            
                            if ($resource) {
                                // Prioritize selling_price - only use price as last resort
                                if (!empty($resource['selling_price']) && (float)$resource['selling_price'] > 0) {
                                    $unitPrice = (float)$resource['selling_price'];
                                    log_message('debug', "PrescriptionManagement::addToBilling - Using selling_price: ₱{$unitPrice} for resource ID {$resourceId}");
                                } else {
                                    // Only use price if selling_price is not set
                                    $unitPrice = (float)($resource['price'] ?? 0);
                                    if ($unitPrice > 0) {
                                        log_message('warning', "PrescriptionManagement::addToBilling - Resource ID {$resourceId} has no selling_price, using price: ₱{$unitPrice}");
                                    }
                                }
                            }
                        }
                        
                        // If still no price, try by medication name
                        if ($unitPrice <= 0 && !empty($medicationName) && $db->tableExists('resources')) {
                            $resource = $db->table('resources')
                                ->select('selling_price, price')
                                ->where('category', 'Medications')
                                ->groupStart()
                                    ->like('equipment_name', $medicationName)
                                    ->orLike('medication_name', $medicationName)
                                ->groupEnd()
                                ->get()
                                ->getRowArray();
                            
                            if ($resource) {
                                // Prioritize selling_price - only use price as last resort
                                if (!empty($resource['selling_price']) && (float)$resource['selling_price'] > 0) {
                                    $unitPrice = (float)$resource['selling_price'];
                                    log_message('debug', "PrescriptionManagement::addToBilling - Using selling_price: ₱{$unitPrice} for medication '{$medicationName}'");
                                } else {
                                    // Only use price if selling_price is not set
                                    $unitPrice = (float)($resource['price'] ?? 0);
                                    if ($unitPrice > 0) {
                                        log_message('warning', "PrescriptionManagement::addToBilling - Medication '{$medicationName}' has no selling_price, using price: ₱{$unitPrice}");
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if ($unitPrice <= 0) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => 'Unit price must be greater than zero. Please provide unit price or ensure medication has a selling price set.'
                ]);
            }

            // Add item from prescription
            $result = $this->financialService->addItemFromPrescription($billingId, (int)$id, $unitPrice, $quantity, (int)$this->staffId);

            $statusCode = !empty($result['success']) ? 200 : 500;

            return $this->response->setStatusCode($statusCode)->setJSON([
                'success' => !empty($result['success']),
                'message' => $result['message'] ?? 'Unable to add prescription to billing.'
            ]);

        } catch (\Throwable $e) {
            log_message('error', 'PrescriptionManagement::addToBilling error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'An unexpected error occurred while adding prescription to billing.'
            ]);
        }
    }

    /**
     * Main prescription management view - role-based
     */
    public function index()
    {
        // Get role-specific page configuration for redirects
        $pageConfig = $this->getPageConfig();

        try {
            // Check basic prescription access permission
            if (!$this->canViewPrescriptions()) {
                return redirect()->to($pageConfig['redirectUrl'])->with('error', 'Access denied');
            }

            // Get role-specific data
            $prescriptions = $this->prescriptionService->getPrescriptionsByRole($this->userRole, $this->staffId);
            $stats = $this->prescriptionService->getPrescriptionStats($this->userRole, $this->staffId);
            $availablePatients = $this->getAvailablePatientsForRole();

            // Get permissions for this role
            $permissions = $this->getUserPermissions();

            $data = [
                'title' => $pageConfig['title'],
                'prescriptions' => $prescriptions,
                'stats' => $stats,
                'availablePatients' => $availablePatients,
                'userRole' => $this->userRole,
                'permissions' => $permissions,
                'pageConfig' => $pageConfig,
                'statuses' => $this->getPrescriptionStatuses(),
                'priorities' => $this->getPrescriptionPriorities()
            ];

            return view('unified/prescription-management', $data);

        } catch (\Throwable $e) {
            log_message('error', 'PrescriptionManagement::index error: ' . $e->getMessage());
            return redirect()->to($pageConfig['redirectUrl'])->with('error', 'Failed to load prescription management');
        }
    }

    /**
     * Get prescriptions API - role-based filtering
     */
    public function getPrescriptionsAPI()
    {
        try {
            if (!$this->canViewPrescriptions()) {
                return $this->jsonResponse('error', 'Access denied', null, 403);
            }

            $prescriptions = $this->prescriptionService->getPrescriptionsByRole($this->userRole, $this->staffId, $this->getFiltersFromRequest());
            return $this->jsonResponse('success', 'Prescriptions loaded', $prescriptions);

        } catch (\Throwable $e) {
            log_message('error', 'PrescriptionManagement::getPrescriptionsAPI error: ' . $e->getMessage());
            return $this->jsonResponse('error', 'Failed to load prescriptions', null, 500);
        }
    }

    /**
     * Create a new prescription
     */
    public function create()
    {
        try {
            if (!$this->canCreatePrescription()) {
                return $this->jsonResponse('error', 'Permission denied', null, 403);
            }

            $input = $this->request->getJSON(true) ?? $this->request->getPost();
            $result = $this->prescriptionService->createPrescription($input, $this->userRole, $this->staffId);
            
            $response = ['status' => $result['success'] ? 'success' : 'error', 'message' => $result['message']];
            if (isset($result['prescription_id'])) $response['prescription_id'] = $result['prescription_id'];
            if (isset($result['id'])) $response['id'] = $result['id'];
            if (isset($result['errors'])) $response['errors'] = $result['errors'];
            $response['csrf'] = ['name' => csrf_token(), 'value' => csrf_hash()];
            
            return $this->response->setStatusCode($result['success'] ? 200 : 422)->setJSON($response);

        } catch (\Throwable $e) {
            log_message('error', 'PrescriptionManagement::create error: ' . $e->getMessage());
            return $this->jsonResponse('error', 'Failed to create prescription', null, 500);
        }
    }

    /**
     * Update a prescription
     */
    public function update()
    {
        try {
            $input = $this->request->getJSON(true) ?? $this->request->getPost();
            
            if (empty($input['id'])) {
                return $this->jsonResponse('error', 'Prescription ID is required', null, 422);
            }

            $result = $this->prescriptionService->updatePrescription($input['id'], $input, $this->userRole, $this->staffId);
            $statusCode = $result['success'] ? 200 : ($result['message'] === 'Permission denied' ? 403 : 422);
            
            return $this->jsonResponse($result['success'] ? 'success' : 'error', $result['message'], null, $statusCode);

        } catch (\Throwable $e) {
            log_message('error', 'PrescriptionManagement::update error: ' . $e->getMessage());
            return $this->jsonResponse('error', 'Failed to update prescription', null, 500);
        }
    }

    /**
     * Delete a prescription
     */
    public function delete()
    {
        try {
            $input = $this->request->getJSON(true) ?? $this->request->getPost();
            $id = $input['id'] ?? null;

            if (!$id) {
                return $this->jsonResponse('error', 'Prescription ID is required', null, 422);
            }

            $result = $this->prescriptionService->deletePrescription($id, $this->userRole, $this->staffId);
            $statusCode = $result['success'] ? 200 : ($result['message'] === 'Permission denied' ? 403 : 422);
            
            return $this->jsonResponse($result['success'] ? 'success' : 'error', $result['message'], null, $statusCode);

        } catch (\Throwable $e) {
            log_message('error', 'PrescriptionManagement::delete error: ' . $e->getMessage());
            return $this->jsonResponse('error', 'Failed to delete prescription', null, 500);
        }
    }

    /**
     * Get a single prescription
     */
    public function getPrescription($id)
    {
        try {
            if (!$this->canViewPrescriptions()) {
                return $this->jsonResponse('error', 'Access denied', null, 403);
            }

            $prescription = $this->prescriptionService->getPrescription($id);
            
            if (!$prescription) {
                return $this->jsonResponse('error', 'Prescription not found', null, 404);
            }
            
            return $this->jsonResponse('success', 'Prescription loaded', $prescription);

        } catch (\Throwable $e) {
            log_message('error', 'PrescriptionManagement::getPrescription error: ' . $e->getMessage());
            return $this->jsonResponse('error', 'Failed to load prescription', null, 500);
        }
    }

    /**
     * Update prescription status
     */
    public function updateStatus($id)
    {
        try {
            $input = $this->request->getJSON(true) ?? $this->request->getPost();
            $status = $input['status'] ?? null;

            if (!$status) {
                return $this->jsonResponse('error', 'Status is required', null, 422);
            }

            $result = $this->prescriptionService->updatePrescriptionStatus($id, $status, $this->userRole, $this->staffId);
            $statusCode = $result['success'] ? 200 : ($result['message'] === 'Permission denied' ? 403 : 422);
            
            // Include errors in response if available
            $responseData = null;
            if (!$result['success'] && !empty($result['errors'])) {
                $responseData = ['errors' => $result['errors']];
            }
            
            return $this->jsonResponse($result['success'] ? 'success' : 'error', $result['message'], $responseData, $statusCode);

        } catch (\Throwable $e) {
            log_message('error', 'PrescriptionManagement::updateStatus error: ' . $e->getMessage());
            return $this->jsonResponse('error', 'Failed to update prescription status', null, 500);
        }
    }

    /**
     * Get available patients for prescription creation
     */
    public function getAvailablePatientsAPI()
    {
        try {
            if (!$this->canCreatePrescription()) {
                return $this->jsonResponse('error', 'Access denied', null, 403);
            }

            $patients = $this->prescriptionService->getAvailablePatients($this->userRole, $this->staffId);
            return $this->jsonResponse('success', 'Patients loaded', $patients);

        } catch (\Throwable $e) {
            log_message('error', 'PrescriptionManagement::getAvailablePatientsAPI error: ' . $e->getMessage());
            return $this->jsonResponse('error', 'Failed to load available patients', null, 500);
        }
    }

    /**
     * Get available doctors for prescription assignment
     * Admin can assign doctors, Nurses need doctors for draft approval
     */
    public function getAvailableDoctorsAPI()
    {
        try {
            // Admin and nurses can access doctors list
            if (!in_array($this->userRole, ['admin', 'nurse'])) {
                return $this->response->setStatusCode(403)->setJSON([
                    'status' => 'error',
                    'message' => 'Access denied'
                ]);
            }

            // Use PatientService method which handles database structure variations
            try {
                $doctors = $this->patientService->getAvailableDoctors();
            } catch (\Throwable $e) {
                log_message('error', 'PrescriptionManagement::getAvailableDoctorsAPI - PatientService failed: ' . $e->getMessage());
                // Fallback: try direct query
                try {
                    if ($this->db->tableExists('staff')) {
                        $doctors = $this->db->table('staff')
                            ->select('staff_id, first_name, last_name')
                            ->where('role', 'doctor')
                            ->orderBy('first_name', 'ASC')
                            ->get()
                            ->getResultArray();
                    } else {
                        $doctors = [];
                    }
                } catch (\Throwable $e2) {
                    log_message('error', 'PrescriptionManagement::getAvailableDoctorsAPI - Fallback query failed: ' . $e2->getMessage());
                    $doctors = [];
                }
            }
            
            // Ensure we return an array even if empty
            if (!is_array($doctors)) {
                $doctors = [];
            }
            
            // Normalize the data format to match expected frontend format
            $formattedDoctors = array_map(function($doctor) {
                return [
                    'staff_id' => $doctor['staff_id'] ?? $doctor['id'] ?? null,
                    'first_name' => $doctor['first_name'] ?? '',
                    'last_name' => $doctor['last_name'] ?? '',
                    'specialization' => $doctor['specialization'] ?? null
                ];
            }, $doctors);
            
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $formattedDoctors
            ]);

        } catch (\Throwable $e) {
            log_message('error', 'PrescriptionManagement::getAvailableDoctorsAPI error: ' . $e->getMessage());
            log_message('error', 'PrescriptionManagement::getAvailableDoctorsAPI trace: ' . $e->getTraceAsString());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Failed to load available doctors'
            ]);
        }
    }

    /**
     * Get available medications from Resource Management (Medications category)
     */
    public function getAvailableMedicationsAPI()
    {
        try {
            if (!$this->canCreatePrescription()) {
                return $this->response->setStatusCode(403)->setJSON([
                    'status' => 'error',
                    'message' => 'Access denied',
                ]);
            }

            $search = $this->request->getGet('search');
            $resourceService = new ResourceService();
            $medications = $resourceService->getMedications($search);

            return $this->response->setJSON([
                'status' => 'success',
                'data' => $medications,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'PrescriptionManagement::getAvailableMedicationsAPI error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Failed to load medications',
            ]);
        }
    }

    // Permission methods

    private function canViewPrescriptions()
    {
        return $this->permissionManager->hasPermission($this->userRole, 'prescriptions', 'view') ||
               $this->permissionManager->hasPermission($this->userRole, 'prescriptions', 'view_own') ||
               $this->permissionManager->hasPermission($this->userRole, 'prescriptions', 'view_all');
    }

    private function canCreatePrescription()
    {
        // Doctors can create prescriptions (primary prescribers)
        // Nurses can create draft prescriptions (needs doctor approval)
        return $this->permissionManager->hasPermission($this->userRole, 'prescriptions', 'create') ||
               $this->permissionManager->hasPermission($this->userRole, 'prescriptions', 'create_draft');
    }

    private function canEditPrescription()
    {
        return $this->permissionManager->hasPermission($this->userRole, 'prescriptions', 'edit') ||
               $this->permissionManager->hasPermission($this->userRole, 'prescriptions', 'edit_own');
    }

    private function canDeletePrescription()
    {
        return $this->permissionManager->hasPermission($this->userRole, 'prescriptions', 'delete');
    }

    // Helper methods

    private function getUserPermissions()
    {
        return [
            'canView' => $this->canViewPrescriptions(),
            'canCreate' => $this->canCreatePrescription(),
            'canEdit' => $this->canEditPrescription(),
            'canDelete' => $this->canDeletePrescription(),
            'canViewAll' => $this->permissionManager->hasPermission($this->userRole, 'prescriptions', 'view_all'),
            'canViewOwn' => $this->permissionManager->hasPermission($this->userRole, 'prescriptions', 'view_own'),
            'canFulfill' => $this->permissionManager->hasPermission($this->userRole, 'prescriptions', 'fulfill')
        ];
    }

    private function getPageConfig()
    {
        return match($this->userRole) {
            'admin' => ['title' => 'Prescription Management', 'subtitle' => 'Manage all prescriptions and medication orders', 'redirectUrl' => 'admin/dashboard', 'showSidebar' => true, 'sidebarType' => 'admin'],
            'doctor' => ['title' => 'My Prescriptions', 'subtitle' => 'Create and manage patient prescriptions', 'redirectUrl' => 'doctor/dashboard', 'showSidebar' => true, 'sidebarType' => 'doctor'],
            'nurse' => ['title' => 'Department Prescriptions', 'subtitle' => 'View department prescription orders', 'redirectUrl' => 'nurse/dashboard', 'showSidebar' => true, 'sidebarType' => 'nurse'],
            'pharmacist' => ['title' => 'Prescription Queue', 'subtitle' => 'Process and dispense medications', 'redirectUrl' => 'pharmacist/dashboard', 'showSidebar' => true, 'sidebarType' => 'pharmacist'],
            'receptionist' => ['title' => 'Prescription Overview', 'subtitle' => 'View prescription status for coordination', 'redirectUrl' => 'receptionist/dashboard', 'showSidebar' => true, 'sidebarType' => 'receptionist'],
            'it_staff' => ['title' => 'Prescription Management', 'subtitle' => 'System administration of prescriptions', 'redirectUrl' => 'it-staff/dashboard', 'showSidebar' => true, 'sidebarType' => 'admin'],
            default => ['title' => 'Prescription Management', 'subtitle' => 'Manage all prescriptions and medication orders', 'redirectUrl' => 'admin/dashboard', 'showSidebar' => true, 'sidebarType' => 'admin']
        };
    }

    private function getAvailablePatientsForRole()
    {
        if ($this->canCreatePrescription()) {
            return $this->prescriptionService->getAvailablePatients($this->userRole, $this->staffId);
        }
        return [];
    }

    private function getFiltersFromRequest()
    {
        $filters = [];
        
        if ($date = $this->request->getGet('date')) {
            $filters['date'] = $date;
        }
        
        if ($status = $this->request->getGet('status')) {
            $filters['status'] = $status;
        }
        
        if ($patientId = $this->request->getGet('patient_id')) {
            $filters['patient_id'] = $patientId;
        }
        
        if ($doctorId = $this->request->getGet('doctor_id')) {
            $filters['doctor_id'] = $doctorId;
        }
        
        if ($search = $this->request->getGet('search')) {
            $filters['search'] = $search;
        }

        // Date range filters
        if ($startDate = $this->request->getGet('start_date')) {
            $endDate = $this->request->getGet('end_date') ?? $startDate;
            $filters['date_range'] = [
                'start' => $startDate,
                'end' => $endDate
            ];
        }

        return $filters;
    }

    private function getPrescriptionStatuses()
    {
        return [
            ['status' => 'active'],
            ['status' => 'pending'],
            ['status' => 'ready'],
            ['status' => 'completed'],
            ['status' => 'cancelled'],
            ['status' => 'expired'],
            ['status' => 'draft'] // Draft status for nurse-created prescriptions
        ];
    }

    private function getPrescriptionPriorities()
    {
        return [
            ['priority' => 'routine'],
            ['priority' => 'priority'],
            ['priority' => 'stat']
        ];
    }
}
