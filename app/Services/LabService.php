<?php

namespace App\Services;

use CodeIgniter\Database\ConnectionInterface;

class LabService
{
    protected $db;

    public function __construct(ConnectionInterface $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
    }

    /**
     * Build base query for lab orders with role-based filtering
     */
    private function buildLabOrderQuery(string $userRole, ?int $staffId = null)
    {
        $builder = $this->db->table('lab_orders lo')
            ->select('lo.*, p.first_name, p.last_name')
            ->join('patients p', 'p.patient_id = lo.patient_id', 'left');

        if (in_array($userRole, ['admin', 'it_staff', 'accountant', 'laboratorist', 'nurse', 'receptionist'], true)) {
            // Full visibility for these roles
        } elseif ($userRole === 'doctor' && $staffId) {
            $builder->where('lo.doctor_id', $staffId);
        } else {
            $builder->where('1', '0');
        }

        return $builder;
    }

    /**
     * List lab orders based on role and optional filters.
     * For outpatients: only shows lab orders that have been paid.
     * For inpatients: shows all lab orders (billed later).
     */
    public function getLabOrdersByRole(string $userRole, ?int $staffId = null, array $filters = []): array
    {
        try {
            if (!$this->db->tableExists('lab_orders')) {
return [];
            }

            $builder = $this->buildLabOrderQuery($userRole, $staffId);

            if (!empty($filters['status'])) {
                $builder->where('lo.status', $filters['status']);
            }
            if (!empty($filters['priority'])) {
                $builder->where('lo.priority', $filters['priority']);
            }
            if (!empty($filters['date'])) {
                $builder->where('DATE(lo.ordered_at)', $filters['date']);
            }
            if (!empty($filters['patient_id'])) {
                $builder->where('lo.patient_id', (int) $filters['patient_id']);
            }
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $builder->groupStart()
                    ->like('lo.test_code', $search)
                    ->orLike('lo.test_name', $search)
                    ->orLike('p.first_name', $search)
                    ->orLike('p.last_name', $search)
                    ->groupEnd();
            }

            $orders = $builder->orderBy('lo.ordered_at', 'DESC')->get()->getResultArray();

            // Filter out unpaid outpatient lab orders
            $filteredOrders = [];
            foreach ($orders as $o) {
                $o['patient_name'] = trim(($o['first_name'] ?? '') . ' ' . ($o['last_name'] ?? ''));
                
                // Add patient type for each order
                if (!empty($o['patient_id'])) {
                    $o['patient_type'] = $this->getPatientType((int)$o['patient_id']);
                }
                
                // For outpatients: include if paid OR if status is 'in_progress' (newly created orders)
                // For inpatients: include all (no payment check needed)
                $patientType = strtolower($o['patient_type'] ?? 'outpatient');
                $labOrderId = (int)($o['lab_order_id'] ?? 0);
                $orderStatus = strtolower($o['status'] ?? 'ordered');
                
                if ($patientType === 'outpatient' && $labOrderId > 0) {
                    // Include if lab order is paid OR if it's in 'in_progress' status (newly created)
                    if ($this->isLabOrderPaid($labOrderId) || $orderStatus === 'in_progress') {
                        $filteredOrders[] = $o;
                    }
                    // Skip unpaid outpatient lab orders that are not in progress
                } else {
                    // Include all inpatient lab orders (no payment check)
                    $filteredOrders[] = $o;
                }
            }

            return $filteredOrders;
        } catch (\Throwable $e) {
            log_message('error', 'LabService::getLabOrdersByRole error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Single lab order with basic permission awareness handled at controller level.
     */
    public function getLabOrder(int $labOrderId): ?array
    {
        try {
            if (!$this->db->tableExists('lab_orders')) {
                return null;
            }

            $order = $this->db->table('lab_orders lo')
                ->select('lo.*, p.first_name, p.last_name, p.date_of_birth')
                ->join('patients p', 'p.patient_id = lo.patient_id', 'left')
                ->where('lo.lab_order_id', $labOrderId)
                ->get()
                ->getRowArray();

            if ($order) {
                $order['patient_name'] = trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? ''));
            }

            return $order ?: null;
        } catch (\Throwable $e) {
            log_message('error', 'LabService::getLabOrder error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Simple stats for dashboard cards.
     */
    public function getLabStats(string $userRole, ?int $staffId = null): array
    {
        try {
            if (!$this->db->tableExists('lab_orders')) {
                return [];
            }

            $today = date('Y-m-d');

            $base = $this->db->table('lab_orders');

            if ($userRole === 'doctor' && $staffId) {
                $base->where('doctor_id', $staffId);
            }

            return [
                'total_orders'   => (clone $base)->countAllResults(),
                'today_orders'   => (clone $base)->where('DATE(ordered_at)', $today)->countAllResults(),
                'in_progress'    => (clone $base)->where('status', 'in_progress')->countAllResults(),
                'completed'      => (clone $base)->where('status', 'completed')->countAllResults(),
            ];
        } catch (\Throwable $e) {
            log_message('error', 'LabService::getLabStats error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Create a new lab order.
     * For outpatients: automatically adds to billing account.
     * For inpatients: billing is handled later (upon discharge or completion).
     */
    public function createLabOrder(array $data, string $userRole, ?int $staffId = null): array
    {
        try {
            // Only doctors, admins, and optionally nurses can create lab orders
            // Doctors: Yes (main requester)
            // Admins: Yes (can create lab orders)
            // Nurses: Optional (depends on policy - currently disabled, can be enabled if needed)
            if (!in_array($userRole, ['doctor', 'admin', 'nurse'], true)) {
                return ['success' => false, 'message' => 'Permission denied. Only doctors and administrators can create lab orders.'];
            }

            if (!$this->db->tableExists('lab_orders')) {
                return ['success' => false, 'message' => 'Lab orders table is missing'];
            }

            $validation = \Config\Services::validation();
            if (!$validation->setRules([
                'patient_id' => 'required|integer',
                'test_code'  => 'required|max_length[100]',
                'test_name'  => 'permit_empty|max_length[191]',
                'priority'   => 'permit_empty|in_list[routine,urgent,stat]',
            ])->run($data)) {
                return ['success' => false, 'message' => 'Validation failed', 'errors' => $validation->getErrors()];
            }

            $doctorId = $data['doctor_id'] ?? $staffId;
            $patientId = (int) $data['patient_id'];

            // For doctors: validate that patient is assigned to them
            if ($userRole === 'doctor' && $staffId) {
                if (!$this->isPatientAssignedToDoctor($patientId, $staffId)) {
                    return ['success' => false, 'message' => 'You can only create lab orders for patients assigned to you.', 'errors' => ['patient_id' => 'Patient is not assigned to you']];
                }
            }

            $insert = [
                'patient_id'     => $patientId,
                'doctor_id'      => (int) $doctorId,
                'appointment_id' => !empty($data['appointment_id']) ? (int) $data['appointment_id'] : null,
                'test_code'      => $data['test_code'],
                'test_name'      => $data['test_name'] ?? null,
                'status'         => 'in_progress', // Automatically set to in_progress for both inpatients and outpatients
                'priority'       => $data['priority'] ?? 'routine',
                'ordered_at'     => date('Y-m-d H:i:s'),
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ];

            $this->db->table('lab_orders')->insert($insert);
            $id = (int) $this->db->insertID();

            if ($id <= 0) {
                return ['success' => false, 'message' => 'Failed to create lab order'];
            }

            // For outpatients: automatically add to billing
            $patientType = $this->getPatientType($patientId);
            if (strtolower($patientType) === 'outpatient') {
                $this->addLabOrderToBillingForOutpatient($id, $patientId, $data['test_code'], $staffId);
                return [
                    'success' => true, 
                    'message' => 'Lab order created successfully and set to in progress. For outpatients, payment should be processed before completion.', 
                    'lab_order_id' => $id,
                    'requires_payment' => true,
                    'patient_type' => 'outpatient'
                ];
            }

            return ['success' => true, 'message' => 'Lab order created successfully and set to in progress', 'lab_order_id' => $id];
        } catch (\Throwable $e) {
            log_message('error', 'LabService::createLabOrder error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create lab order'];
        }
    }

    /**
     * Automatically add lab order to billing for outpatients
     */
    private function addLabOrderToBillingForOutpatient(int $labOrderId, int $patientId, string $testCode, ?int $staffId): void
    {
        $this->addLabOrderToBilling($labOrderId, $patientId, $testCode, $staffId, null);
    }

    /**
     * Automatically add lab order to billing for inpatients
     */
    private function addLabOrderToBillingForInpatient(int $labOrderId, int $patientId, string $testCode, ?int $staffId, ?int $admissionId): void
    {
        $this->addLabOrderToBilling($labOrderId, $patientId, $testCode, $staffId, $admissionId);
    }

    /**
     * Add lab order to billing (works for both inpatient and outpatient)
     */
    private function addLabOrderToBilling(int $labOrderId, int $patientId, string $testCode, ?int $staffId, ?int $admissionId = null): void
    {
        try {
            // Check if FinancialService is available
            if (!class_exists(\App\Services\FinancialService::class)) {
                log_message('warning', 'FinancialService not available for auto-billing lab order');
                return;
            }

            $financialService = new \App\Services\FinancialService();

            // Get test price from lab_tests table
            $unitPrice = $this->getTestPrice($testCode);
            if ($unitPrice <= 0) {
                log_message('warning', "Lab order {$labOrderId}: No valid price found for test code {$testCode}, using default 500.00");
                $unitPrice = 500.00; // Default price
            }

            // Get or create billing account for patient (with admission_id for inpatients)
            $account = $financialService->getOrCreateBillingAccountForPatient($patientId, $admissionId, $staffId);
            if (!$account || empty($account['billing_id'])) {
                log_message('error', "Lab order {$labOrderId}: Failed to get/create billing account for patient {$patientId}" . ($admissionId ? ", admission {$admissionId}" : ""));
                return;
            }

            $billingId = (int) $account['billing_id'];

            // Check if lab order is already in ANY billing account (prevent duplicates across all accounts)
            if ($this->db->tableExists('billing_items') && $this->db->fieldExists('lab_order_id', 'billing_items')) {
                $existing = $this->db->table('billing_items')
                    ->where('lab_order_id', $labOrderId)
                    ->countAllResults();
                
                if ($existing > 0) {
                    log_message('warning', "Lab order {$labOrderId}: Already exists in billing. Skipping duplicate addition.");
                    return; // Already added to some billing account
                }
            }

            // Add lab order to billing
            if (method_exists($financialService, 'addItemFromLabOrder')) {
                $result = $financialService->addItemFromLabOrder($billingId, $labOrderId, $unitPrice, $staffId);
                if (!($result['success'] ?? false)) {
                    log_message('error', "Lab order {$labOrderId}: Failed to add to billing - " . ($result['message'] ?? 'Unknown error'));
                } else {
                    log_message('debug', "Lab order {$labOrderId}: Successfully added to billing account {$billingId}" . ($admissionId ? " (inpatient admission {$admissionId})" : " (outpatient)"));
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'LabService::addLabOrderToBilling error: ' . $e->getMessage());
        }
    }

    /**
     * Get test price from lab_tests table
     */
    private function getTestPrice(string $testCode): float
    {
        try {
            if (!$this->db->tableExists('lab_tests')) {
                return 0.0;
            }

            $test = $this->db->table('lab_tests')
                ->where('test_code', $testCode)
                ->where('status', 'active')
                ->get()
                ->getRowArray();

            if ($test && isset($test['default_price'])) {
                return (float) $test['default_price'];
            }

            return 0.0;
        } catch (\Throwable $e) {
            log_message('error', 'LabService::getTestPrice error: ' . $e->getMessage());
            return 0.0;
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
            log_message('error', 'LabService::getActiveAdmissionId error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update basic lab order fields (not status).
     */
    public function updateLabOrder(int $labOrderId, array $data, string $userRole, ?int $staffId = null): array
    {
        try {
            // Only doctors can update their own lab orders (basic fields like test_code, test_name, priority)
            // Lab staff process labs through updateStatus, not updateLabOrder
            if ($userRole !== 'doctor') {
                return ['success' => false, 'message' => 'Permission denied. Only doctors can update lab orders.'];
            }

            if (!$this->db->tableExists('lab_orders')) {
                return ['success' => false, 'message' => 'Lab orders table is missing'];
            }

            $order = $this->getLabOrder($labOrderId);
            if (!$order) {
                return ['success' => false, 'message' => 'Lab order not found'];
            }

            if ($userRole === 'doctor' && $staffId && (int) $order['doctor_id'] !== (int) $staffId) {
                return ['success' => false, 'message' => 'You can only edit your own lab orders'];
            }

            $allowedFields = ['test_code', 'test_name', 'priority'];
            $update = array_intersect_key($data, array_flip($allowedFields));

            if (empty($update)) {
                return ['success' => false, 'message' => 'Nothing to update'];
            }

            $update['updated_at'] = date('Y-m-d H:i:s');

            $this->db->table('lab_orders')
                ->where('lab_order_id', $labOrderId)
                ->update($update);

            return ['success' => true, 'message' => 'Lab order updated successfully'];
        } catch (\Throwable $e) {
            log_message('error', 'LabService::updateLabOrder error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update lab order'];
        }
    }

    /**
     * Update status only; billing handled by controller/FinancialService when completed.
     * For outpatients: payment must be verified before allowing status to change to 'in_progress'.
     * For inpatients: can proceed without payment verification.
     */
    public function updateStatus(int $labOrderId, string $status, string $userRole, ?int $staffId = null): array
    {
        try {
            if (!$this->db->tableExists('lab_orders')) {
                return ['success' => false, 'message' => 'Lab orders table is missing'];
            }

            $validStatuses = ['ordered', 'in_progress', 'completed', 'cancelled'];
            if (!in_array($status, $validStatuses, true)) {
                return ['success' => false, 'message' => 'Invalid status'];
            }

            $order = $this->getLabOrder($labOrderId);
            if (!$order) {
                return ['success' => false, 'message' => 'Lab order not found'];
            }

            // Permission: Only laboratorist can process labs (update status)
            // Doctors cannot update status - they can only create orders
            if (!in_array($userRole, ['laboratorist'], true)) {
                return ['success' => false, 'message' => 'Permission denied. Only lab staff can process lab orders.'];
            }

            // Check payment requirement for outpatients when changing to 'in_progress'
            if ($status === 'in_progress') {
                $patientId = (int) ($order['patient_id'] ?? 0);
                if ($patientId > 0) {
                    $patientType = $this->getPatientType($patientId);
                    
                    // For outpatients, verify payment before allowing lab procedure to start
                    if (strtolower($patientType) === 'outpatient') {
                        // First, ensure lab order is added to billing
                        $testCode = $order['test_code'] ?? '';
                        if (!empty($testCode)) {
                            // Check if lab order is already in billing
                            $inBilling = false;
                            $billingId = null;
                            if ($this->db->tableExists('billing_items') && $this->db->fieldExists('lab_order_id', 'billing_items')) {
                                $billingItem = $this->db->table('billing_items')
                                    ->where('lab_order_id', $labOrderId)
                                    ->get()
                                    ->getRowArray();
                                
                                if ($billingItem) {
                                    $inBilling = true;
                                    $billingId = (int)($billingItem['billing_id'] ?? 0);
                                }
                            }
                            
                            // If not in billing, add it automatically
                            if (!$inBilling) {
                                $this->addLabOrderToBillingForOutpatient($labOrderId, $patientId, $testCode, $staffId);
                                // Re-check billing after adding
                                if ($this->db->tableExists('billing_items') && $this->db->fieldExists('lab_order_id', 'billing_items')) {
                                    $billingItem = $this->db->table('billing_items')
                                        ->where('lab_order_id', $labOrderId)
                                        ->get()
                                        ->getRowArray();
                                    if ($billingItem) {
                                        $billingId = (int)($billingItem['billing_id'] ?? 0);
                                    }
                                }
                            }
                        }
                        
                        // STRICT: Verify payment before allowing procedure to start
                        // This is mandatory for outpatients - no exceptions
                        if (!$this->isLabOrderPaid($labOrderId)) {
                            $testName = $order['test_name'] ?? $order['test_code'] ?? 'lab test';
                            $errorMessage = 'PAYMENT REQUIRED: Outpatient lab procedures require payment before they can begin. ';
                            
                            if ($billingId) {
                                $errorMessage .= "Please process payment for Billing ID {$billingId} in the Financial Management section, then try again.";
                            } else {
                                $errorMessage .= "The lab order for '{$testName}' has been added to billing. Please process the payment first, then try to start the procedure again.";
                            }
                            
                            return [
                                'success' => false,
                                'message' => $errorMessage,
                                'requires_payment' => true,
                                'billing_id' => $billingId
                            ];
                        }
                    }
                    // Inpatients can proceed without payment verification
                }
            }

            // Additional safety check: If current status is 'ordered' and trying to go to 'in_progress',
            // and patient is outpatient, ensure payment is verified (double-check)
            if ($status === 'in_progress' && strtolower($order['status'] ?? '') === 'ordered') {
                $patientId = (int) ($order['patient_id'] ?? 0);
                if ($patientId > 0) {
                    $patientType = $this->getPatientType($patientId);
                    if (strtolower($patientType) === 'outpatient' && !$this->isLabOrderPaid($labOrderId)) {
                        return [
                            'success' => false,
                            'message' => 'PAYMENT REQUIRED: Payment verification failed. Outpatient lab procedures cannot proceed without confirmed payment. Please process payment in the Financial Management section before starting the lab procedure.',
                            'requires_payment' => true
                        ];
                    }
                }
            }

            // Check payment requirement for outpatients when completing lab order
            if ($status === 'completed') {
                $patientId = (int) ($order['patient_id'] ?? 0);
                if ($patientId > 0) {
                    $patientType = $this->getPatientType($patientId);
                    
                    // For outpatients, verify payment before allowing completion
                    if (strtolower($patientType) === 'outpatient') {
                        // Get billing ID for error message
                        $billingId = null;
                        if ($this->db->tableExists('billing_items') && $this->db->fieldExists('lab_order_id', 'billing_items')) {
                            $billingItem = $this->db->table('billing_items')
                                ->where('lab_order_id', $labOrderId)
                                ->get()
                                ->getRowArray();
                            
                            if ($billingItem) {
                                $billingId = (int)($billingItem['billing_id'] ?? 0);
                            }
                        }
                        
                        // STRICT: Verify payment before allowing completion
                        // Outpatients must pay before lab order can be completed
                        if (!$this->isLabOrderPaid($labOrderId)) {
                            $testName = $order['test_name'] ?? $order['test_code'] ?? 'lab test';
                            $errorMessage = 'PAYMENT REQUIRED: Outpatient lab orders cannot be completed without payment. ';
                            
                            if ($billingId) {
                                $errorMessage .= "Please process payment for Billing ID {$billingId} in the Financial Management section, then try to complete the lab order again.";
                            } else {
                                $errorMessage .= "The lab order for '{$testName}' must be paid before completion. Please process the payment first.";
                            }
                            
                            return [
                                'success' => false,
                                'message' => $errorMessage,
                                'requires_payment' => true,
                                'billing_id' => $billingId
                            ];
                        }
                    }
                    // Inpatients can complete without payment verification (billed later)
                }
            }

            $update = [
                'status'     => $status,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($status === 'completed' && empty($order['completed_at'])) {
                $update['completed_at'] = date('Y-m-d H:i:s');
            }

            $this->db->table('lab_orders')
                ->where('lab_order_id', $labOrderId)
                ->update($update);

            // For inpatients: automatically add to billing when lab order is completed
            if ($status === 'completed') {
                $patientId = (int) ($order['patient_id'] ?? 0);
                if ($patientId > 0) {
                    $patientType = $this->getPatientType($patientId);
                    
                    // Check if lab order is already in billing (prevent duplicates)
                    $inBilling = false;
                    if ($this->db->tableExists('billing_items') && $this->db->fieldExists('lab_order_id', 'billing_items')) {
                        $inBilling = $this->db->table('billing_items')
                            ->where('lab_order_id', $labOrderId)
                            ->countAllResults() > 0;
                    }
                    
                    if (!$inBilling) {
                        if (strtolower($patientType) === 'inpatient') {
                            $testCode = $order['test_code'] ?? '';
                            if (!empty($testCode)) {
                                // Get active admission ID for inpatient
                                $admissionId = $this->getActiveAdmissionId($patientId);
                                if ($admissionId) {
                                    $this->addLabOrderToBillingForInpatient($labOrderId, $patientId, $testCode, $staffId, $admissionId);
                                } else {
                                    log_message('warning', "Lab order {$labOrderId}: Patient {$patientId} is marked as inpatient but has no active admission");
                                }
                            }
                        } else {
                            // If patient is now outpatient but lab order wasn't added to billing, add it now
                            // This handles cases where patient type changed or was misidentified
                            $testCode = $order['test_code'] ?? '';
                            if (!empty($testCode)) {
                                log_message('info', "Lab order {$labOrderId}: Patient {$patientId} is outpatient, adding to billing on completion");
                                $this->addLabOrderToBillingForOutpatient($labOrderId, $patientId, $testCode, $staffId);
                            }
                        }
                    } else {
                        log_message('debug', "Lab order {$labOrderId}: Already in billing, skipping duplicate addition");
                    }
                }
            }

            return ['success' => true, 'message' => 'Status updated successfully'];
        } catch (\Throwable $e) {
            log_message('error', 'LabService::updateStatus error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update status'];
        }
    }

    /**
     * Get patient type (inpatient/outpatient) from patient record
     */
    private function getPatientType(int $patientId): string
    {
        try {
            if (!$this->db->tableExists('patients')) {
                return 'outpatient'; // Default to outpatient if table doesn't exist
            }

            $patient = $this->db->table('patients')
                ->select('patient_type')
                ->where('patient_id', $patientId)
                ->get()
                ->getRowArray();

            if ($patient && !empty($patient['patient_type'])) {
                return $patient['patient_type'];
            }

            // If patient_type is not set, check if patient has active inpatient admission
            if ($this->db->tableExists('inpatient_admissions')) {
                $activeAdmission = $this->db->table('inpatient_admissions')
                    ->where('patient_id', $patientId)
                    ->groupStart()
                        ->where('discharge_date', null)
                        ->orWhere('discharge_date', '')
                    ->groupEnd()
                    ->get()
                    ->getRowArray();

                if ($activeAdmission) {
                    return 'inpatient';
                }
            }

            return 'outpatient'; // Default to outpatient
        } catch (\Throwable $e) {
            log_message('error', 'LabService::getPatientType error: ' . $e->getMessage());
            return 'outpatient'; // Default to outpatient on error
        }
    }

    /**
     * Check if lab order has been paid
     * Returns true if:
     * 1. Lab order is linked to a billing item, AND
     * 2. The billing account has status 'paid' OR has completed payments that cover the lab order cost
     */
    private function isLabOrderPaid(int $labOrderId): bool
    {
        try {
            // Check if billing_items table exists and has lab_order_id field
            if (!$this->db->tableExists('billing_items')) {
                log_message('debug', "Lab order {$labOrderId}: billing_items table does not exist");
                return false;
            }

            if (!$this->db->fieldExists('lab_order_id', 'billing_items')) {
                // If lab_order_id field doesn't exist, we can't link lab orders to billing
                $order = $this->getLabOrder($labOrderId);
                if (!$order || empty($order['patient_id'])) {
                    log_message('debug', "Lab order {$labOrderId}: Order not found or has no patient");
                    return false;
                }

                // For backward compatibility, if lab_order_id field doesn't exist,
                // we'll require payment verification through patient billing account
                $patientId = (int)$order['patient_id'];
                return $this->checkPatientBillingAccountPaid($patientId);
            }

            // Find billing items linked to this lab order
            $billingItems = $this->db->table('billing_items')
                ->where('lab_order_id', $labOrderId)
                ->get()
                ->getResultArray();

            if (empty($billingItems)) {
                log_message('debug', "Lab order {$labOrderId}: Not added to billing yet");
                return false; // Lab order not added to billing yet
            }

            // Calculate total amount for this lab order
            $labOrderTotal = 0.0;
            foreach ($billingItems as $item) {
                $labOrderTotal += (float)($item['line_total'] ?? ($item['unit_price'] ?? 0) * ($item['quantity'] ?? 1));
            }

            // Get unique billing IDs
            $billingIds = array_unique(array_column($billingItems, 'billing_id'));

            // Check if any billing account is marked as paid or has payments covering the lab order
            if ($this->db->tableExists('billing_accounts')) {
                foreach ($billingIds as $billingId) {
                    $account = $this->db->table('billing_accounts')
                        ->where('billing_id', $billingId)
                        ->get()
                        ->getRowArray();

                    if ($account) {
                        // Check if billing account status is 'paid'
                        if (isset($account['status']) && strtolower($account['status']) === 'paid') {
                            log_message('debug', "Lab order {$labOrderId}: Billing account {$billingId} is marked as paid");
                            return true;
                        }

                        // Check if there are completed payments for this billing account
                        if ($this->db->tableExists('payments')) {
                            $totalPaid = 0.0;
                            
                            // Check if payments table has billing_id field
                            if ($this->db->fieldExists('billing_id', 'payments')) {
                                $payments = $this->db->table('payments')
                                    ->where('billing_id', $billingId)
                                    ->where('status', 'completed')
                                    ->get()
                                    ->getResultArray();
                                
                                foreach ($payments as $payment) {
                                    $totalPaid += (float)($payment['amount'] ?? 0);
                                }
                            } else {
                                // If payments table doesn't have billing_id, check via bill_id
                                if ($this->db->tableExists('bills') && isset($account['bill_id'])) {
                                    $payments = $this->db->table('payments')
                                        ->where('bill_id', $account['bill_id'])
                                        ->where('status', 'completed')
                                        ->get()
                                        ->getResultArray();
                                    
                                    foreach ($payments as $payment) {
                                        $totalPaid += (float)($payment['amount'] ?? 0);
                                    }
                                }
                            }

                            // Check if total paid covers at least the lab order amount
                            if ($totalPaid >= $labOrderTotal && $labOrderTotal > 0) {
                                log_message('debug', "Lab order {$labOrderId}: Payment verified - paid {$totalPaid} for lab order amount {$labOrderTotal}");
                                return true;
                            } elseif ($totalPaid > 0 && $labOrderTotal == 0) {
                                // If lab order amount is 0 (free), any payment is sufficient
                                log_message('debug', "Lab order {$labOrderId}: Free lab order with payment recorded");
                                return true;
                            }
                        }
                    }
                }
            }

            log_message('debug', "Lab order {$labOrderId}: Payment not verified");
            return false;
        } catch (\Throwable $e) {
            log_message('error', 'LabService::isLabOrderPaid error: ' . $e->getMessage());
            return false; // On error, assume not paid to be safe
        }
    }

    /**
     * Check if patient's billing account has been paid (fallback for systems without lab_order_id in billing_items)
     */
    private function checkPatientBillingAccountPaid(int $patientId): bool
    {
        try {
            if (!$this->db->tableExists('billing_accounts')) {
                return false;
            }

            $account = $this->db->table('billing_accounts')
                ->where('patient_id', $patientId)
                ->where('admission_id IS NULL') // Outpatient account
                ->get()
                ->getRowArray();

            if (!$account) {
                return false;
            }

            // Check if account status is paid
            if (isset($account['status']) && strtolower($account['status']) === 'paid') {
                return true;
            }

            // Check for completed payments
            if ($this->db->tableExists('payments')) {
                $billingId = (int)($account['billing_id'] ?? 0);
                if ($billingId > 0 && $this->db->fieldExists('billing_id', 'payments')) {
                    $payment = $this->db->table('payments')
                        ->where('billing_id', $billingId)
                        ->where('status', 'completed')
                        ->get()
                        ->getRowArray();
                    
                    return !empty($payment);
                }
            }

            return false;
        } catch (\Throwable $e) {
            log_message('error', 'LabService::checkPatientBillingAccountPaid error: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteLabOrder(int $labOrderId, string $userRole, ?int $staffId = null): array
    {
        try {
            if (!in_array($userRole, ['admin', 'it_staff'], true)) {
                return ['success' => false, 'message' => 'Permission denied'];
            }

            if (!$this->db->tableExists('lab_orders')) {
                return ['success' => false, 'message' => 'Lab orders table is missing'];
            }

            $order = $this->getLabOrder($labOrderId);
            if (!$order) {
                return ['success' => false, 'message' => 'Lab order not found'];
            }

            $this->db->table('lab_orders')
                ->where('lab_order_id', $labOrderId)
                ->delete();

            return ['success' => true, 'message' => 'Lab order deleted successfully'];
        } catch (\Throwable $e) {
            log_message('error', 'LabService::deleteLabOrder error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to delete lab order'];
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
            log_message('error', 'LabService::isPatientAssignedToDoctor error: ' . $e->getMessage());
            return false;
        }
    }
}

