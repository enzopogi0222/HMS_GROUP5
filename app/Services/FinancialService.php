<?php

namespace App\Services;

use CodeIgniter\Database\ConnectionInterface;

class FinancialService
{
    protected $db;

    public function __construct(ConnectionInterface $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
    }

    // Helpers
    private function sumIfTable(string $table, string $column, array $where = []): float
    {
        if (!$this->db->tableExists($table)) {
            return 0.0;
        }
        $builder = $this->db->table($table)->selectSum($column);
        foreach ($where as $k => $v) {
            $builder->where($k, $v);
        }
        $row = $builder->get()->getRow();
        return (isset($row) && isset($row->{$column})) ? (float)$row->{$column} : 0.0;
    }

    private function countIfTable(string $table, array $where = []): int
    {
        if (!$this->db->tableExists($table)) {
            return 0;
        }
        $builder = $this->db->table($table);
        foreach ($where as $k => $v) {
            $builder->where($k, $v);
        }
        return (int)$builder->countAllResults();
    }

    public function getFinancialStats(string $userRole, int $userId = null): array
    {
        try {
            return match ($userRole) {
                'admin', 'accountant', 'it_staff' => $this->getSystemWideStats(),
                'doctor' => $this->getDoctorStats($userId),
                'receptionist' => $this->getReceptionistStats(),
                default => $this->getBasicStats(),
            };
        } catch (\Exception $e) {
            log_message('error', 'FinancialService error: ' . $e->getMessage());
            return $this->getBasicStats();
        }
    }

    private function getSystemWideStats(): array
    {
        $totalIncome = $this->sumIfTable('payments', 'amount', ['status' => 'completed']);
        $totalExpenses = $this->sumIfTable('expenses', 'amount');
        $pendingBills = $this->countIfTable('bills', ['status' => 'pending']);

        return [
            'total_income' => (float)$totalIncome,
            'total_expenses' => (float)$totalExpenses,
            'net_balance' => (float)$totalIncome - (float)$totalExpenses,
            'pending_bills' => $pendingBills,
            'paid_bills' => $this->countIfTable('bills', ['status' => 'paid']),
            'monthly_income' => $this->getMonthlyIncome(),
            'monthly_expenses' => $this->getMonthlyExpenses(),
            'profit_margin' => (float)$totalIncome - (float)$totalExpenses,
        ];
    }

    private function getMonthlyIncome(): float
    {
        if (!$this->db->tableExists('payments')) {
            return 0.0;
        }
        return (float)$this->db->table('payments')
            ->selectSum('amount')
            ->where('MONTH(payment_date)', date('m'))
            ->where('YEAR(payment_date)', date('Y'))
            ->where('status', 'completed')
            ->get()->getRow()->amount ?? 0.0;
    }

    private function getMonthlyExpenses(): float
    {
        if (!$this->db->tableExists('expenses')) {
            return 0.0;
        }
        return (float)$this->db->table('expenses')
            ->selectSum('amount')
            ->where('MONTH(expense_date)', date('m'))
            ->where('YEAR(expense_date)', date('Y'))
            ->get()->getRow()->amount ?? 0.0;
    }

    private function getDoctorStats(int $doctorId): array
    {
        $income = 0.0;
        if ($this->db->tableExists('payments') && $this->db->tableExists('bills')) {
            $row = $this->db->table('payments p')
                ->join('bills b', 'b.bill_id = p.bill_id')
                ->selectSum('p.amount')
                ->where('b.doctor_id', $doctorId)
                ->where('p.status', 'completed')
                ->get()->getRow();
            $income = (float)($row->amount ?? 0.0);
        }

        $monthlyIncome = 0.0;
        if ($this->db->tableExists('payments') && $this->db->tableExists('bills')) {
            $row = $this->db->table('payments p')
                ->join('bills b', 'b.bill_id = p.bill_id')
                ->selectSum('p.amount')
                ->where('b.doctor_id', $doctorId)
                ->where('p.status', 'completed')
                ->where('MONTH(p.payment_date)', date('m'))
                ->where('YEAR(p.payment_date)', date('Y'))
                ->get()->getRow();
            $monthlyIncome = (float)($row->amount ?? 0.0);
        }

        return [
            'total_income' => (float)$income,
            'my_income' => (float)$income,
            'monthly_income' => (float)$monthlyIncome,
            'total_expenses' => 0,
            'net_balance' => (float)$income,
            'pending_bills' => $this->countIfTable('bills', ['doctor_id' => $doctorId, 'status' => 'pending']),
            'paid_bills' => 0,
            'overdue_bills' => 0,
        ];
    }

    private function getReceptionistStats(): array
    {
        $todayIncome = 0.0;
        if ($this->db->tableExists('payments')) {
            $row = $this->db->table('payments')
                ->selectSum('amount')
                ->where('DATE(payment_date)', date('Y-m-d'))
                ->where('status', 'completed')
                ->get()->getRow();
            $todayIncome = (float)($row->amount ?? 0.0);
        }

        return [
            'total_income' => (float)$todayIncome,
            'total_expenses' => 0,
            'net_balance' => (float)$todayIncome,
            'pending_bills' => $this->countIfTable('bills', ['status' => 'pending']),
            'paid_bills' => 0,
            'overdue_bills' => 0,
        ];
    }

    private function getBasicStats(): array
    {
        return [
            'total_income' => 0,
            'total_expenses' => 0,
            'net_balance' => 0,
            'pending_bills' => 0,
            'paid_bills' => 0
        ];
    }

    public function createBill(array $billData, string $userRole, int $userId): array
    {
        if (!in_array($userRole, ['admin', 'accountant', 'receptionist', 'doctor', 'it_staff'])) {
            return ['success' => false, 'message' => 'Insufficient permissions'];
        }

        if (!$this->db->tableExists('bills')) {
            return ['success' => false, 'message' => 'Bills table is missing'];
        }

        try {
            $this->db->table('bills')->insert([
                'bill_number' => 'BILL-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                'patient_id' => $billData['patient_id'] ?? null,
                'doctor_id' => $billData['doctor_id'] ?? $userId,
                'total_amount' => $billData['total_amount'] ?? 0,
                'status' => $billData['status'] ?? 'pending',
                'bill_date' => date('Y-m-d H:i:s'),
                'created_by' => $userId
            ]);

            return ['success' => true, 'message' => 'Bill created successfully', 'bill_id' => $this->db->insertID()];
        } catch (\Exception $e) {
            log_message('error', 'FinancialService::createBill error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error creating bill'];
        }
    }

    public function processPayment(array $paymentData, string $userRole, int $userId): array
    {
        if (!in_array($userRole, ['admin', 'accountant', 'receptionist', 'it_staff'])) {
            return ['success' => false, 'message' => 'Insufficient permissions'];
        }

        if (!$this->db->tableExists('payments')) {
            return ['success' => false, 'message' => 'Payments table is missing'];
        }

        try {
            $this->db->table('payments')->insert([
                'bill_id' => $paymentData['bill_id'] ?? null,
                'amount' => $paymentData['amount'] ?? 0,
                'payment_method' => $paymentData['payment_method'] ?? 'cash',
                'payment_date' => date('Y-m-d H:i:s'),
                'status' => 'completed',
                'processed_by' => $userId
            ]);

            return ['success' => true, 'message' => 'Payment processed successfully'];
        } catch (\Exception $e) {
            log_message('error', 'FinancialService::processPayment error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error processing payment'];
        }
    }

    public function createExpense(array $expenseData, string $userRole, int $userId): array
    {
        if (!in_array($userRole, ['admin', 'accountant', 'it_staff'])) {
            return ['success' => false, 'message' => 'Insufficient permissions'];
        }

        if (!$this->db->tableExists('expenses')) {
            return ['success' => false, 'message' => 'Expenses table is missing'];
        }

        try {
            $this->db->table('expenses')->insert([
                'expense_name' => $expenseData['name'] ?? '',
                'amount' => $expenseData['amount'] ?? 0,
                'category' => $expenseData['category'] ?? 'other',
                'expense_date' => $expenseData['date'] ?? date('Y-m-d'),
                'created_by' => $userId
            ]);

            return ['success' => true, 'message' => 'Expense created successfully'];
        } catch (\Exception $e) {
            log_message('error', 'FinancialService::createExpense error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error creating expense'];
        }
    }

    public function handleFinancialTransactionFormSubmission(array $data, string $userRole, int $userId): array
    {
        $type = $data['type'] ?? '';
        $validation = $this->validateTransactionPermissions($type, $userRole);
        if (!$validation['valid']) {
            return $validation;
        }

        $validation = $this->validateTransactionData($data);
        if (!$validation['valid']) {
            return $validation;
        }

        if (!$this->db->tableExists('financial_transaction')) {
            return ['success' => false, 'message' => 'Financial transaction table is missing'];
        }

        try {
            $this->db->table('financial_transaction')->insert([
                'user_id' => $userId,
                'type' => $type,
                'category' => $data['category'],
                'amount' => (float)$data['amount'],
                'description' => $data['description'] ?? null,
                'transaction_date' => $data['transaction_date'],
                'created_at' => date('Y-m-d H:i:s')
            ]);

            return ['success' => true, 'message' => 'Financial transaction created successfully', 'transaction_id' => $this->db->insertID()];
        } catch (\Exception $e) {
            log_message('error', 'FinancialService::handleFinancialTransactionFormSubmission error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error creating financial record'];
        }
    }

    private function validateTransactionPermissions(string $type, string $userRole): array
    {
        if ($type === 'Income' && !in_array($userRole, ['admin', 'accountant', 'receptionist', 'doctor', 'it_staff'])) {
            return ['valid' => false, 'success' => false, 'message' => 'Insufficient permissions to create income records'];
        }
        if ($type === 'Expense' && !in_array($userRole, ['admin', 'accountant', 'it_staff'])) {
            return ['valid' => false, 'success' => false, 'message' => 'Insufficient permissions to create expense records'];
        }
        if (!in_array($type, ['Income', 'Expense'])) {
            return ['valid' => false, 'success' => false, 'message' => 'Invalid transaction type'];
        }
        return ['valid' => true];
    }

    private function validateTransactionData(array $data): array
    {
        $requiredFields = ['type', 'category', 'amount', 'transaction_date'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return ['valid' => false, 'success' => false, 'message' => "Field '{$field}' is required"];
            }
        }

        $amount = (float)($data['amount'] ?? 0);
        if ($amount <= 0) {
            return ['valid' => false, 'success' => false, 'message' => 'Amount must be greater than zero'];
        }

        if (!strtotime($data['transaction_date'])) {
            return ['valid' => false, 'success' => false, 'message' => 'Invalid date format'];
        }

        return ['valid' => true];
    }

    public function createFinancialRecord(array $data, string $userRole, int $userId): array
    {
        $category = $data['category'] ?? '';
        $validation = $this->validateTransactionPermissions($category, $userRole);
        if (!$validation['valid']) {
            return $validation;
        }

        $requiredFields = ['transaction_name', 'category', 'amount', 'date'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Field '{$field}' is required"];
            }
        }

        $amount = (float)($data['amount'] ?? 0);
        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Amount must be greater than zero'];
        }

        if (!strtotime($data['date'])) {
            return ['success' => false, 'message' => 'Invalid date format'];
        }

        try {
            if ($category === 'Income') {
                return $this->createIncomeRecord($data, $amount, $userId);
            } elseif ($category === 'Expense') {
                return $this->createExpenseRecord($data, $amount, $userId);
            }

            return ['success' => false, 'message' => 'Invalid category'];
        } catch (\Exception $e) {
            log_message('error', 'FinancialService::createFinancialRecord error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error creating financial record'];
        }
    }

    private function createIncomeRecord(array $data, float $amount, int $userId): array
    {
        if (!$this->db->tableExists('payments')) {
            return ['success' => false, 'message' => 'Payments table is missing'];
        }

        $this->db->table('payments')->insert([
            'bill_id' => null,
            'amount' => $amount,
            'payment_method' => $data['payment_method'] ?? 'cash',
            'payment_date' => $data['date'] . ' ' . date('H:i:s'),
            'status' => 'completed',
            'processed_by' => $userId,
            'description' => $data['description'] ?? null
        ]);

        return ['success' => true, 'message' => 'Income record created successfully'];
    }

    private function createExpenseRecord(array $data, float $amount, int $userId): array
    {
        if (!$this->db->tableExists('expenses')) {
            return ['success' => false, 'message' => 'Expenses table is missing'];
        }

        $this->db->table('expenses')->insert([
            'expense_name' => $data['transaction_name'],
            'amount' => $amount,
            'category' => $data['expense_category'] ?? 'other',
            'expense_date' => $data['date'],
            'created_by' => $userId,
            'description' => $data['description'] ?? null
        ]);

        return ['success' => true, 'message' => 'Expense record created successfully'];
    }
    public function getAllTransactions(string $userRole, int $userId = null): array
    {
        try {
            $transactions = [];

            if ($this->db->tableExists('payments')) {
                $payments = $this->db->table('payments')
                    ->select('id, amount, payment_date as date, \'Income\' as category, payment_method as transaction_name, description')
                    ->where('status', 'completed')
                    ->orderBy('payment_date', 'DESC')
                    ->limit(10)
                    ->get()
                    ->getResultArray();

                foreach ($payments as $payment) {
                    $transactions[] = array_merge($payment, [
                        'type' => 'income',
                        'transaction_name' => $payment['payment_method'] . ' Payment' . ($payment['description'] ? ' - ' . $payment['description'] : '')
                    ]);
                }
            }

            if ($this->db->tableExists('expenses')) {
                $expenses = $this->db->table('expenses')
                    ->select('id, expense_name as transaction_name, amount, expense_date as date, category as expense_category, description, \'Expense\' as category')
                    ->orderBy('expense_date', 'DESC')
                    ->limit(10)
                    ->get()
                    ->getResultArray();

                foreach ($expenses as $expense) {
                    $transactions[] = array_merge($expense, ['type' => 'expense']);
                }
            }

            usort($transactions, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
            return array_slice($transactions, 0, 20);
        } catch (\Exception $e) {
            log_message('error', 'FinancialService::getAllTransactions error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Billing accounts & items (integration with patients, appointments, prescriptions)
     */

    public function getOrCreateBillingAccountForPatient(int $patientId, ?int $admissionId = null, int $createdByStaffId = null): ?array
    {
        if (!$this->db->tableExists('billing_accounts')) {
            return null;
        }

        try {
            $account = $this->findExistingBillingAccount($patientId, $admissionId);
            if ($account) {
                return $account;
            }

            $this->createBillingAccount($patientId, $admissionId, $createdByStaffId);
            return $this->findExistingBillingAccount($patientId, $admissionId);
        } catch (\Exception $e) {
            log_message('error', 'FinancialService::getOrCreateBillingAccountForPatient error: ' . $e->getMessage());
            return null;
        }
    }

    private function findExistingBillingAccount(int $patientId, ?int $admissionId): ?array
    {
        $builder = $this->db->table('billing_accounts')->where('patient_id', $patientId);
        if ($admissionId !== null) {
            $builder->where('admission_id', $admissionId);
        } else {
            // For outpatients, admission_id should be NULL
            $builder->where('admission_id IS NULL');
        }
        if ($this->db->fieldExists('status', 'billing_accounts')) {
            $builder->where('status', 'open');
        }
        $account = $builder->get()->getRowArray();
        if ($account) {
            log_message('debug', "findExistingBillingAccount: Found billing account {$account['billing_id']} for patient {$patientId}, admission_id=" . ($admissionId ?? 'NULL'));
        } else {
            log_message('debug', "findExistingBillingAccount: No billing account found for patient {$patientId}, admission_id=" . ($admissionId ?? 'NULL'));
        }
        return $account ?: null;
    }

    private function createBillingAccount(int $patientId, ?int $admissionId, ?int $createdByStaffId): void
    {
        $insertData = ['patient_id' => $patientId];
        
        // Add admission_id if provided (for inpatients) or set to NULL if field allows it (for outpatients)
        if ($this->db->fieldExists('admission_id', 'billing_accounts')) {
            // For outpatients, admission_id should be NULL
            // For inpatients, admission_id should be set
            $insertData['admission_id'] = $admissionId; // This will be NULL for outpatients
        }
        
        if ($this->db->fieldExists('status', 'billing_accounts')) {
            $insertData['status'] = 'open';
        }
        if ($this->db->fieldExists('created_by', 'billing_accounts') && $createdByStaffId !== null) {
            $insertData['created_by'] = $createdByStaffId;
        }
        if ($this->db->fieldExists('created_at', 'billing_accounts')) {
            $insertData['created_at'] = date('Y-m-d H:i:s');
        }

        $result = $this->db->table('billing_accounts')->insert($insertData);
        
        if (!$result) {
            $error = $this->db->error();
            $errorMsg = $error['message'] ?? 'Unknown database error';
            log_message('error', 'Failed to create billing account. Error: ' . $errorMsg);
            log_message('error', 'Insert data: ' . json_encode($insertData));
            throw new \RuntimeException('Failed to create billing account: ' . $errorMsg);
        }
    }

    /**
     * Update the status of a billing account (if the status column exists).
     */
    public function updateBillingAccountStatus(int $billingId, string $status): array
    {
        if (!$this->db->tableExists('billing_accounts')) {
            return ['success' => false, 'message' => 'Billing accounts table is missing'];
        }

        if (!$this->db->fieldExists('status', 'billing_accounts')) {
            return ['success' => false, 'message' => 'Status field does not exist on billing_accounts'];
        }

        try {
            $this->db->table('billing_accounts')->where('billing_id', $billingId)->update(['status' => $status]);
            
            // If status is being set to 'paid', automatically update lab order status from 'ordered' to 'in_progress'
            if (strtolower($status) === 'paid') {
                $this->updateLabOrderStatusAfterPayment($billingId);
            }
            
            return $this->db->affectedRows() > 0
                ? ['success' => true, 'message' => 'Billing account status updated']
                : ['success' => false, 'message' => 'Billing account not found or status unchanged'];
        } catch (\Exception $e) {
            log_message('error', 'FinancialService::updateBillingAccountStatus error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error updating billing account status'];
        }
    }
    
    /**
     * Update lab order status from 'ordered' to 'in_progress' after payment is completed
     */
    private function updateLabOrderStatusAfterPayment(int $billingId): void
    {
        try {
            // Check if billing_items table exists and has lab_order_id field
            if (!$this->db->tableExists('billing_items') || !$this->db->fieldExists('lab_order_id', 'billing_items')) {
                return;
            }

            // Check if lab_orders table exists
            if (!$this->db->tableExists('lab_orders')) {
                return;
            }

            // Find all lab orders linked to this billing account
            $billingItems = $this->db->table('billing_items')
                ->where('billing_id', $billingId)
                ->where('lab_order_id IS NOT NULL')
                ->where('lab_order_id !=', 0)
                ->get()
                ->getResultArray();

            if (empty($billingItems)) {
                return;
            }

            // Get unique lab order IDs
            $labOrderIds = array_unique(array_filter(array_column($billingItems, 'lab_order_id')));

            if (empty($labOrderIds)) {
                return;
            }

            // Update lab orders with status 'ordered' to 'in_progress'
            $this->db->table('lab_orders')
                ->whereIn('lab_order_id', $labOrderIds)
                ->where('status', 'ordered')
                ->update([
                    'status' => 'in_progress',
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

            $updatedCount = $this->db->affectedRows();
            if ($updatedCount > 0) {
                log_message('info', "Updated {$updatedCount} lab order(s) from 'ordered' to 'in_progress' after payment for billing ID {$billingId}");
            }
        } catch (\Throwable $e) {
            log_message('error', 'FinancialService::updateLabOrderStatusAfterPayment error: ' . $e->getMessage());
        }
    }

    /**
     * Convenience wrapper to mark an account as paid.
     */
    public function markBillingAccountPaid(int $billingId): array
    {
        return $this->updateBillingAccountStatus($billingId, 'paid');
    }

    /**
     * Delete a billing account and its related billing items.
     */
    public function deleteBillingAccount(int $billingId): array
    {
        if (!$this->db->tableExists('billing_accounts')) {
            return ['success' => false, 'message' => 'Billing accounts table is missing'];
        }

        try {
            if ($this->db->tableExists('billing_items')) {
                $this->db->table('billing_items')->where('billing_id', $billingId)->delete();
            }

            $this->db->table('billing_accounts')->where('billing_id', $billingId)->delete();
            return $this->db->affectedRows() > 0
                ? ['success' => true, 'message' => 'Billing account deleted successfully']
                : ['success' => false, 'message' => 'Billing account not found'];
        } catch (\Exception $e) {
            log_message('error', 'FinancialService::deleteBillingAccount error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error deleting billing account'];
        }
    }

    public function addItemFromAppointment(int $billingId, int $appointmentId, float $unitPrice, int $quantity = 1, ?int $createdByStaffId = null): array
    {
        if (!$this->db->tableExists('billing_items') || !$this->db->tableExists('appointments')) {
            return ['success' => false, 'message' => 'Billing or appointments table is missing'];
        }

        try {
            // Note: appointments table uses 'id' as primary key, not 'appointment_id'
            $appointment = $this->db->table('appointments')->where('id', $appointmentId)->get()->getRowArray();
            if (!$appointment) {
                return ['success' => false, 'message' => 'Appointment not found'];
            }

            // Check for duplicate: only if appointment still exists and is in this billing account
            if ($this->db->fieldExists('appointment_id', 'billing_items')) {
                $existing = $this->db->table('billing_items')
                    ->where('billing_id', $billingId)
                    ->where('appointment_id', $appointmentId)
                    ->countAllResults();
                
                if ($existing > 0) {
                    return ['success' => true, 'message' => 'This appointment is already in the billing account.'];
                }
            }

            $patientId = (int)($appointment['patient_id'] ?? 0);
            if ($patientId <= 0) {
                return ['success' => false, 'message' => 'Appointment has no patient linked'];
            }

            $description = ($appointment['appointment_type'] ?? 'Consultation') . 
                (!empty($appointment['appointment_date']) ? ' - ' . date('Y-m-d', strtotime($appointment['appointment_date'])) : '');

            $this->insertBillingItem([
                'billing_id' => $billingId,
                'patient_id' => $patientId,
                'appointment_id' => $appointmentId,
                'prescription_id' => null,
                'description' => $description,
                'quantity' => max(1, (int)$quantity),
                'unit_price' => max(0, (float)$unitPrice),
            ], $createdByStaffId);

            return ['success' => true, 'message' => 'Appointment item added to billing'];
        } catch (\Exception $e) {
            log_message('error', 'FinancialService::addItemFromAppointment error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error adding appointment to billing'];
        }
    }

    public function addItemFromPrescription(int $billingId, int $prescriptionId, float $unitPrice, ?int $quantity = null, ?int $createdByStaffId = null): array
    {
        if (!$this->db->tableExists('billing_items') || !$this->db->tableExists('prescriptions')) {
            return ['success' => false, 'message' => 'Billing or prescriptions table is missing'];
        }

        try {
            $prescription = $this->db->table('prescriptions')->where('id', $prescriptionId)->get()->getRowArray();
            if (!$prescription) {
                return ['success' => false, 'message' => 'Prescription not found'];
            }

            $patientId = (int)($prescription['patient_id'] ?? 0);
            if ($patientId <= 0) {
                return ['success' => false, 'message' => 'Prescription has no patient linked'];
            }

            if ($this->db->table('billing_items')->where('billing_id', $billingId)->where('prescription_id', $prescriptionId)->countAllResults() > 0) {
                return ['success' => true, 'message' => 'Prescription is already added to this billing account.'];
            }

            $descriptionParts = array_filter([$prescription['medication'] ?? '', $prescription['dosage'] ?? '']);
            $description = !empty($descriptionParts) ? implode(' - ', $descriptionParts) : 'Medication';

            if ($quantity === null) {
                $quantity = (int)($prescription['dispensed_quantity'] ?? $prescription['quantity'] ?? 1);
            }

            $this->insertBillingItem([
                'billing_id' => $billingId,
                'patient_id' => $patientId,
                'appointment_id' => null,
                'prescription_id' => $prescriptionId,
                'description' => $description,
                'quantity' => max(1, (int)$quantity),
                'unit_price' => max(0, (float)$unitPrice),
            ], $createdByStaffId);

            return ['success' => true, 'message' => 'Prescription item added to billing'];
        } catch (\Exception $e) {
            log_message('error', 'FinancialService::addItemFromPrescription error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error adding prescription to billing'];
        }
    }

    public function addItemFromLabOrder(int $billingId, int $labOrderId, float $unitPrice, ?int $createdByStaffId = null): array
    {
        if (!$this->db->tableExists('billing_items') || !$this->db->tableExists('lab_orders')) {
            return ['success' => false, 'message' => 'Billing or lab_orders table is missing'];
        }

        try {
            $order = $this->db->table('lab_orders')->where('lab_order_id', $labOrderId)->get()->getRowArray();
            if (!$order) {
                return ['success' => false, 'message' => 'Lab order not found'];
            }

            $patientId = (int)($order['patient_id'] ?? 0);
            if ($patientId <= 0) {
                return ['success' => false, 'message' => 'Lab order has no patient linked'];
            }

            // Check if lab order is already in ANY billing account (prevent duplicates)
            if ($this->db->fieldExists('lab_order_id', 'billing_items')) {
                $existing = $this->db->table('billing_items')
                    ->where('lab_order_id', $labOrderId)
                    ->countAllResults();
                
                if ($existing > 0) {
                    // Get the billing account where it's already added
                    $existingItem = $this->db->table('billing_items')
                        ->where('lab_order_id', $labOrderId)
                        ->get()
                        ->getRowArray();
                    
                    if ($existingItem && (int)$existingItem['billing_id'] === $billingId) {
                        return ['success' => true, 'message' => 'Lab order is already added to this billing account.'];
                    } else {
                        return ['success' => false, 'message' => 'This lab order has already been added to another billing account. Each lab order can only be billed once.'];
                    }
                }
            }

            // Note: Lab orders can be added to billing in different statuses:
            // - Outpatients: Added when 'ordered' (payment required before procedure)
            // - Inpatients: Added when 'completed' (billed upon completion)
            // This method is called by LabService which handles the status check appropriately

            $descriptionParts = array_filter([$order['test_name'] ?? '', $order['test_code'] ?? '']);
            $description = 'Lab Test: ' . (!empty($descriptionParts) ? implode(' - ', $descriptionParts) : 'Laboratory Test');
            
            // Add completion date if available
            if (!empty($order['completed_at'])) {
                $description .= ' (Completed: ' . date('M d, Y', strtotime($order['completed_at'])) . ')';
            }

            $itemData = [
                'billing_id' => $billingId,
                'patient_id' => $patientId,
                'appointment_id' => !empty($order['appointment_id']) ? (int)$order['appointment_id'] : null,
                'prescription_id' => null,
                'description' => $description,
                'quantity' => 1,
                'unit_price' => max(0, (float)$unitPrice),
            ];

            if ($this->db->fieldExists('lab_order_id', 'billing_items')) {
                $itemData['lab_order_id'] = $labOrderId;
            }

            $this->insertBillingItem($itemData, $createdByStaffId);
            return ['success' => true, 'message' => 'Lab order item added to billing'];
        } catch (\Exception $e) {
            log_message('error', 'FinancialService::addItemFromLabOrder error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error adding lab order to billing'];
        }
    }

    public function addItemFromRoomAssignment(int $billingId, int $assignmentId, ?float $unitPricePerDay = null, ?int $createdByStaffId = null): array
    {
        if (!$this->db->tableExists('billing_items') || !$this->db->tableExists('room_assignment')) {
            return ['success' => false, 'message' => 'Billing or room_assignment table is missing'];
        }

        try {
            $assignment = $this->db->table('room_assignment')->where('assignment_id', $assignmentId)->get()->getRowArray();
            if (!$assignment) {
                return ['success' => false, 'message' => 'Room assignment not found'];
            }

            $patientId = (int)($assignment['patient_id'] ?? 0);
            if ($patientId <= 0) {
                return ['success' => false, 'message' => 'Room assignment has no patient linked'];
            }

            if ($this->db->fieldExists('room_assignment_id', 'billing_items')) {
                if ($this->db->table('billing_items')->where('billing_id', $billingId)->where('room_assignment_id', $assignmentId)->countAllResults() > 0) {
                    return ['success' => true, 'message' => 'Room assignment is already added to this billing account.'];
                }
            }

            $totalDays = max(1, (int)($assignment['total_days'] ?? 0) ?: ((int)($assignment['total_hours'] ?? 0) > 0 ? 1 : 1));
            $dailyRate = $unitPricePerDay ?? (float)($assignment['room_rate_at_time'] ?? $assignment['bed_rate_at_time'] ?? 0);

            if ($dailyRate <= 0) {
                return ['success' => false, 'message' => 'No valid room rate available for this assignment'];
            }

            $descriptionParts = ['Room charge'];
            if (!empty($assignment['admission_id'])) {
                $descriptionParts[] = 'Admission #' . $assignment['admission_id'];
            }
            if (!empty($assignment['date_in'])) {
                $descriptionParts[] = 'from ' . date('Y-m-d', strtotime($assignment['date_in']));
            }
            if (!empty($assignment['date_out'])) {
                $descriptionParts[] = 'to ' . date('Y-m-d', strtotime($assignment['date_out']));
            }

            $itemData = [
                'billing_id' => $billingId,
                'patient_id' => $patientId,
                'appointment_id' => null,
                'prescription_id' => null,
                'description' => implode(' - ', $descriptionParts),
                'quantity' => $totalDays,
                'unit_price' => max(0, $dailyRate),
            ];

            if ($this->db->fieldExists('room_assignment_id', 'billing_items')) {
                $itemData['room_assignment_id'] = $assignmentId;
            }

            $this->insertBillingItem($itemData, $createdByStaffId);
            return ['success' => true, 'message' => 'Room assignment item added to billing'];
        } catch (\Exception $e) {
            log_message('error', 'FinancialService::addItemFromRoomAssignment error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error adding room assignment to billing'];
        }
    }

    /**
     * Add billing item from inpatient room assignment (inpatient_room_assignments table)
     * This is used when patients are admitted with room assignments
     */
    public function addItemFromInpatientRoomAssignment(int $billingId, int $inpatientRoomAssignmentId, ?float $unitPricePerDay = null, ?int $createdByStaffId = null, int $quantity = 1): array
    {
        if (!$this->db->tableExists('billing_items') || !$this->db->tableExists('inpatient_room_assignments')) {
            return ['success' => false, 'message' => 'Billing or inpatient_room_assignments table is missing'];
        }

        try {
            $roomAssignment = $this->db->table('inpatient_room_assignments ira')
                ->select('ira.*, ia.patient_id, ia.admission_id')
                ->join('inpatient_admissions ia', 'ia.admission_id = ira.admission_id', 'left')
                ->where('ira.room_assignment_id', $inpatientRoomAssignmentId)
                ->get()
                ->getRowArray();

            if (!$roomAssignment) {
                return ['success' => false, 'message' => 'Inpatient room assignment not found'];
            }

            $patientId = (int)($roomAssignment['patient_id'] ?? 0);
            if ($patientId <= 0) {
                return ['success' => false, 'message' => 'Room assignment has no patient linked'];
            }

            // Check if already added to this billing account
            if ($this->db->fieldExists('inpatient_room_assignment_id', 'billing_items')) {
                $existing = $this->db->table('billing_items')
                    ->where('billing_id', $billingId)
                    ->where('inpatient_room_assignment_id', $inpatientRoomAssignmentId)
                    ->countAllResults();
                if ($existing > 0) {
                    return ['success' => true, 'message' => 'Room assignment is already added to this billing account.'];
                }
            }

            // Get daily rate
            $dailyRate = $unitPricePerDay ?? (float)($roomAssignment['daily_rate'] ?? 0);

            if ($dailyRate <= 0) {
                // Try to get rate from room_type if available
                if (!empty($roomAssignment['room_type']) && $this->db->tableExists('room_type')) {
                    $roomType = $this->db->table('room_type')
                        ->where('type_name', $roomAssignment['room_type'])
                        ->get()
                        ->getRowArray();
                    if ($roomType && !empty($roomType['base_daily_rate'])) {
                        $dailyRate = (float)$roomType['base_daily_rate'];
                    }
                }
                
                if ($dailyRate <= 0) {
                    return ['success' => false, 'message' => 'No valid room rate available. Please set daily rate.'];
                }
            }

            // Build description
            $descriptionParts = ['Room charge'];
            if (!empty($roomAssignment['room_type'])) {
                $descriptionParts[] = $roomAssignment['room_type'];
            }
            if (!empty($roomAssignment['room_number'])) {
                $descriptionParts[] = 'Room ' . $roomAssignment['room_number'];
            }
            if (!empty($roomAssignment['bed_number'])) {
                $descriptionParts[] = 'Bed ' . $roomAssignment['bed_number'];
            }
            if (!empty($roomAssignment['admission_id'])) {
                $descriptionParts[] = 'Admission #' . $roomAssignment['admission_id'];
            }

            $itemData = [
                'billing_id' => $billingId,
                'patient_id' => $patientId,
                'appointment_id' => null,
                'prescription_id' => null,
                'description' => implode(' - ', $descriptionParts),
                'quantity' => max(1, $quantity), // Default to 1 day, can be updated later
                'unit_price' => max(0, $dailyRate),
            ];

            // Add reference to inpatient room assignment if field exists
            if ($this->db->fieldExists('inpatient_room_assignment_id', 'billing_items')) {
                $itemData['inpatient_room_assignment_id'] = $inpatientRoomAssignmentId;
            }

            $this->insertBillingItem($itemData, $createdByStaffId);
            return ['success' => true, 'message' => 'Room charge added to billing account'];
        } catch (\Exception $e) {
            log_message('error', 'FinancialService::addItemFromInpatientRoomAssignment error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error adding room charge to billing: ' . $e->getMessage()];
        }
    }

    private function insertBillingItem(array $itemData, ?int $createdByStaffId): void
    {
        $itemData['line_total'] = $itemData['quantity'] * $itemData['unit_price'];
        if ($this->db->fieldExists('created_by_staff_id', 'billing_items') && $createdByStaffId !== null) {
            $itemData['created_by_staff_id'] = $createdByStaffId;
        }
        if ($this->db->fieldExists('created_at', 'billing_items')) {
            $itemData['created_at'] = date('Y-m-d H:i:s');
        }
        $this->db->table('billing_items')->insert($itemData);
    }

    public function getBillingAccount(int $billingId, string $userRole, ?int $staffId = null): ?array
    {
        if (!$this->db->tableExists('billing_accounts')) {
            return null;
        }

        try {
            $account = $this->db->table('billing_accounts')->where('billing_id', $billingId)->get()->getRowArray();
            if (!$account) {
                return null;
            }

            $this->attachPatientInfo($account);
            $this->attachPatientTypeAndAdmission($account);
            $this->attachBillingItems($account, $billingId);
            return $account;
        } catch (\Exception $e) {
            log_message('error', 'FinancialService::getBillingAccount error: ' . $e->getMessage());
            return null;
        }
    }

    private function attachPatientInfo(array &$account): void
    {
        // Ensure patient_name is always set, even if empty
        if (!isset($account['patient_name'])) {
            $account['patient_name'] = null;
        }
        
        if (empty($account['patient_id']) || !$this->db->tableExists('patients')) {
            // If we have first_name and last_name from JOIN, use them
            if (!empty($account['first_name']) || !empty($account['last_name'])) {
                $firstName = $account['first_name'] ?? '';
                $lastName = $account['last_name'] ?? '';
                $fullName = trim($firstName . ' ' . $lastName);
                $account['patient_name'] = $fullName ?: ('Patient #' . ($account['patient_id'] ?? 'Unknown'));
                $account['patient_full_name'] = $fullName;
            } else {
                $account['patient_name'] = 'Patient #' . ($account['patient_id'] ?? 'Unknown');
            }
            return;
        }

        $patient = $this->db->table('patients')->where('patient_id', (int)$account['patient_id'])->get()->getRowArray();
        if (!$patient) {
            // Fallback: use first_name and last_name from JOIN if available
            if (!empty($account['first_name']) || !empty($account['last_name'])) {
                $firstName = $account['first_name'] ?? '';
                $lastName = $account['last_name'] ?? '';
                $fullName = trim($firstName . ' ' . $lastName);
                $account['patient_name'] = $fullName ?: ('Patient #' . ($account['patient_id'] ?? 'Unknown'));
                $account['patient_full_name'] = $fullName;
            } else {
                $account['patient_name'] = 'Patient #' . ($account['patient_id'] ?? 'Unknown');
            }
            return;
        }

        $firstName = $patient['first_name'] ?? $account['first_name'] ?? '';
        $lastName = $patient['last_name'] ?? $account['last_name'] ?? '';
        $fullName = trim($firstName . ' ' . $lastName);

        $account['first_name'] = $firstName;
        $account['last_name'] = $lastName;
        $account['patient_full_name'] = $fullName ?: ($patient['full_name'] ?? '');
        $account['patient_name'] = $account['patient_name'] ?: ($account['patient_full_name'] ?: ('Patient #' . ($account['patient_id'] ?? 'Unknown')));
    }

    /**
     * Attach patient type (inpatient/outpatient) and admission information
     */
    private function attachPatientTypeAndAdmission(array &$account): void
    {
        // Check if patient_id field exists and has a value
        if (!$this->db->fieldExists('patient_id', 'billing_accounts') || empty($account['patient_id'])) {
            $account['patient_type'] = 'Outpatient';
            return;
        }

        $patientId = (int)$account['patient_id'];

        // Check if patient has patient_type field
        if ($this->db->tableExists('patients') && $this->db->fieldExists('patient_type', 'patients')) {
            $patient = $this->db->table('patients')
                ->select('patient_type')
                ->where('patient_id', $patientId)
                ->get()
                ->getRowArray();
            
            if ($patient && !empty($patient['patient_type'])) {
                $account['patient_type'] = ucfirst(strtolower(trim($patient['patient_type'])));
            }
        }

        // If patient_type is not set, check for active admission
        if (empty($account['patient_type']) && $this->db->tableExists('inpatient_admissions')) {
            $builder = $this->db->table('inpatient_admissions')
                ->where('patient_id', $patientId);
            
            // Only check discharge_date if the column exists
            if ($this->db->fieldExists('discharge_date', 'inpatient_admissions')) {
                $builder->groupStart()
                    ->where('discharge_date', null)
                    ->orWhere('discharge_date', '')
                ->groupEnd();
            }
            
            $activeAdmission = $builder->get()->getRowArray();

            if ($activeAdmission) {
                $account['patient_type'] = 'Inpatient';
            } else {
                $account['patient_type'] = 'Outpatient';
            }
        }

        // Default to Outpatient if still not set
        if (empty($account['patient_type'])) {
            $account['patient_type'] = 'Outpatient';
        }

        // If this is an inpatient billing account, attach admission details
        if (strtolower($account['patient_type']) === 'inpatient' && !empty($account['admission_id'])) {
            $this->attachAdmissionInfo($account, (int)$account['admission_id']);
        }
    }

    /**
     * Attach admission information for inpatient billing accounts
     */
    private function attachAdmissionInfo(array &$account, int $admissionId): void
    {
        if (!$this->db->tableExists('inpatient_admissions')) {
            return;
        }

        $admission = $this->db->table('inpatient_admissions')
            ->where('admission_id', $admissionId)
            ->get()
            ->getRowArray();

        if ($admission) {
            $account['admission'] = [
                'admission_id' => $admissionId,
                'admission_datetime' => $admission['admission_datetime'] ?? null,
                'admission_type' => $admission['admission_type'] ?? null,
                'admitting_diagnosis' => $admission['admitting_diagnosis'] ?? null,
                'admitting_doctor' => $admission['admitting_doctor'] ?? null,
                'discharge_date' => $admission['discharge_date'] ?? null,
            ];
        }
    }

    private function attachBillingItems(array &$account, int $billingId): void
    {
        if (!$this->db->tableExists('billing_items')) {
            $account['items'] = [];
            $account['total_amount'] = 0.0;
            return;
        }

        $items = $this->db->table('billing_items')
            ->where('billing_id', $billingId)
            ->orderBy('item_id', 'ASC')
            ->get()
            ->getResultArray();

        // Enhance lab order items with status information
        if ($this->db->tableExists('lab_orders') && $this->db->fieldExists('lab_order_id', 'billing_items')) {
            foreach ($items as &$item) {
                if (!empty($item['lab_order_id'])) {
                    $labOrder = $this->db->table('lab_orders')
                        ->where('lab_order_id', $item['lab_order_id'])
                        ->get()
                        ->getRowArray();
                    
                    if ($labOrder) {
                        // Only include completed lab orders in billing details
                        if (strtolower($labOrder['status'] ?? '') === 'completed') {
                            // Enhance description with lab order details
                            $statusInfo = '';
                            if (!empty($labOrder['completed_at'])) {
                                $statusInfo = ' (Completed: ' . date('M d, Y', strtotime($labOrder['completed_at'])) . ')';
                            }
                            
                            // Update description to include lab test information
                            $testInfo = [];
                            if (!empty($labOrder['test_name'])) {
                                $testInfo[] = $labOrder['test_name'];
                            }
                            if (!empty($labOrder['test_code'])) {
                                $testInfo[] = '[' . $labOrder['test_code'] . ']';
                            }
                            
                            if (!empty($testInfo)) {
                                $item['description'] = 'Lab Test: ' . implode(' ', $testInfo) . $statusInfo;
                            } else {
                                $item['description'] = ($item['description'] ?? 'Laboratory Test') . $statusInfo;
                            }
                        } else {
                            // For non-completed lab orders, mark them but still show
                            $item['description'] = ($item['description'] ?? 'Laboratory Test') . ' (Status: ' . ucfirst($labOrder['status'] ?? 'Unknown') . ')';
                        }
                    }
                }
            }
        }

        $totalAmount = array_sum(array_map(fn($item) => (float)($item['line_total'] ?? 0), $items));

        $account['items'] = $items;
        $account['total_amount'] = $totalAmount;
    }

    public function getBillingAccounts(array $filters, string $userRole, ?int $staffId = null): array
    {
        if (!$this->db->tableExists('billing_accounts')) {
            log_message('debug', 'getBillingAccounts: billing_accounts table does not exist');
            return [];
        }

        try {
            // First, get all billing accounts
            $builder = $this->db->table('billing_accounts ba');

            if ($this->db->tableExists('patients')) {
                $builder->join('patients p', 'p.patient_id = ba.patient_id', 'left')
                    ->select('ba.*, p.first_name, p.last_name');
            } else {
                $builder->select('ba.*');
            }

            if (!empty($filters['patient_id'])) {
                $builder->where('ba.patient_id', (int)$filters['patient_id']);
            }
            if (!empty($filters['status']) && $this->db->fieldExists('status', 'billing_accounts')) {
                $builder->where('ba.status', $filters['status']);
            }
            if (!empty($filters['from_date']) && $this->db->fieldExists('created_at', 'billing_accounts')) {
                $builder->where('ba.created_at >=', $filters['from_date']);
            }
            if (!empty($filters['to_date']) && $this->db->fieldExists('created_at', 'billing_accounts')) {
                $builder->where('ba.created_at <=', $filters['to_date']);
            }

            $accounts = $builder->orderBy('ba.billing_id', 'DESC')->get()->getResultArray();
            log_message('debug', 'getBillingAccounts: Found ' . count($accounts) . ' billing accounts before filtering by items');

            // Filter to only show accounts that have billing items
            if ($this->db->tableExists('billing_items')) {
                $accountsWithItems = [];
                foreach ($accounts as $account) {
                    $itemCount = $this->db->table('billing_items')
                        ->where('billing_id', $account['billing_id'])
                        ->countAllResults();
                    
                    log_message('debug', "getBillingAccounts: Billing account {$account['billing_id']} has {$itemCount} items");
                    
                    if ($itemCount > 0) {
                        $accountsWithItems[] = $account;
                    }
                }
                $accounts = $accountsWithItems;
                log_message('debug', 'getBillingAccounts: After filtering, ' . count($accounts) . ' accounts have items');
            } else {
                log_message('debug', 'getBillingAccounts: billing_items table does not exist');
            }

            foreach ($accounts as &$account) {
                try {
                    $this->attachPatientInfo($account);
                    
                    // Debug logging - use isset to avoid undefined key error
                    $patientName = isset($account['patient_name']) ? $account['patient_name'] : 'NOT SET';
                    log_message('debug', "Billing account {$account['billing_id']}: patient_id={$account['patient_id']}, patient_name={$patientName}");
                } catch (\Exception $e) {
                    log_message('error', "Error attaching patient info for billing account {$account['billing_id']}: " . $e->getMessage());
                    // Set a default patient name if attachment fails
                    if (!isset($account['patient_name'])) {
                        $account['patient_name'] = 'Patient #' . ($account['patient_id'] ?? 'Unknown');
                    }
                }
            }

            log_message('debug', 'FinancialService::getBillingAccounts returning ' . count($accounts) . ' accounts with items');

            return $accounts;
        } catch (\Exception $e) {
            log_message('error', 'FinancialService::getBillingAccounts error: ' . $e->getMessage());
            return [];
        }
    }
}