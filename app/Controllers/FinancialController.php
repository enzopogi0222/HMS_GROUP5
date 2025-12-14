<?php

namespace App\Controllers;

use App\Models\FinancialTransactionModel;
use App\Models\CategoryModel;
use App\Models\TransactionModel;
use App\Services\FinancialService;
use App\Libraries\PermissionManager;

class FinancialController extends BaseController
{
    protected $transactionModel;
    protected $categoryModel;
    protected $financialService;
    protected $transactionsModel;

    public function __construct()
    {
        $this->transactionModel = new FinancialTransactionModel();
        $this->categoryModel = new CategoryModel();
        $this->financialService = new FinancialService();
        $this->transactionsModel = new TransactionModel();
    }

    public function demo()
    {
        return view('unified/financial-modal-demo');
    }

    public function test()
    {
        return view('unified/financial-test');
    }

    public function index()
    {
        try {
            $session = session();
            $userRole = $session->get('role') ?? 'accountant';
            $staffId  = (int)($session->get('staff_id') ?? 0);

            // Use FinancialService for high-level stats and billing accounts
            $stats    = $this->financialService->getFinancialStats($userRole, $staffId);
            $accounts = $this->financialService->getBillingAccounts([], $userRole, $staffId);
            
            // Get transactions from transactions table
            $transactions = [];
            $db = \Config\Database::connect();
            if ($db->tableExists('transactions')) {
                $transactions = $this->transactionsModel->getTransactions([]);
            }

            // Simple permission flags for existing view structure using PermissionManager
            $permissions = [];
            if (PermissionManager::hasPermission($userRole, 'billing', 'create')) {
                $permissions[] = 'create_bill';
            }
            if (PermissionManager::hasPermission($userRole, 'billing', 'process')) {
                $permissions[] = 'process_payment';
            }
            if (PermissionManager::hasPermission($userRole, 'billing', 'create')) {
                $permissions[] = 'create_expense';
            }

            // Debug: log stats and accounts
            log_message('debug', 'FinancialController index - accounts count: ' . count($accounts));
            log_message('debug', 'FinancialController index - stats: ' . json_encode($stats));
            
            // Additional debugging: Check database directly
            $db = \Config\Database::connect();
            if ($db->tableExists('billing_accounts')) {
                $totalAccounts = $db->table('billing_accounts')->countAllResults();
                log_message('debug', 'FinancialController index - Total billing_accounts in database: ' . $totalAccounts);
            }
            if ($db->tableExists('billing_items')) {
                $totalItems = $db->table('billing_items')->countAllResults();
                log_message('debug', 'FinancialController index - Total billing_items in database: ' . $totalItems);
                
                // Get billing IDs that have items
                if ($totalItems > 0) {
                    $billingIdsWithItems = $db->table('billing_items')
                        ->select('billing_id')
                        ->distinct()
                        ->get()
                        ->getResultArray();
                    $billingIds = array_column($billingIdsWithItems, 'billing_id');
                    log_message('debug', 'FinancialController index - Billing IDs with items: ' . implode(', ', $billingIds));
                }
            }

            $data = [
                'title'       => 'Financial Management',
                'userRole'    => $userRole,
                'stats'       => $stats,
                'accounts'    => $accounts,
                'transactions' => $transactions,
                'permissions' => $permissions,
            ];

            return view('unified/financial-management', $data);
        } catch (\Exception $e) {
            log_message('error', 'FinancialManagement::index error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            echo "Error: " . $e->getMessage();
            echo "<br><br>Financial management system is being set up. Please try again in a moment.";
        }
    }

    /**
     * API: Get single billing account with items for modal view
     */
    public function getBillingAccount($billingId)
    {
        $session = session();
        $userRole = $session->get('role') ?? 'accountant';
        $staffId  = (int)($session->get('staff_id') ?? 0);

        $account = $this->financialService->getBillingAccount((int)$billingId, $userRole, $staffId);

        if (!$account) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Billing account not found',
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'data'    => $account,
        ]);
    }

    /**
     * Mark a billing account as paid.
     */
    public function markBillingAccountPaid($billingId)
    {
        $session  = session();
        $userRole = $session->get('role') ?? 'accountant';

        if (!in_array($userRole, ['admin', 'accountant'], true)) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'You are not allowed to mark billing accounts as paid.',
            ]);
        }

        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setStatusCode(405)->setJSON([
                'success' => false,
                'message' => 'Invalid request method.',
            ]);
        }

        // Validate billing ID
        $billingId = (int)$billingId;
        if ($billingId <= 0) {
            log_message('error', "markBillingAccountPaid: Invalid billing ID: {$billingId}");
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Invalid billing account ID.',
            ]);
        }

        try {
            $result = $this->financialService->markBillingAccountPaid($billingId);
            
            // Check if operation was successful
            $isSuccess = isset($result['success']) && $result['success'] === true;
            $statusCode = $isSuccess ? 200 : 400;

            // Log the result for debugging
            if (!$isSuccess) {
                log_message('error', "markBillingAccountPaid: Failed for billing ID {$billingId}. Message: " . ($result['message'] ?? 'Unknown error'));
            } else {
                log_message('debug', "markBillingAccountPaid: Success for billing ID {$billingId}. Message: " . ($result['message'] ?? 'Success'));
            }

            return $this->response->setStatusCode($statusCode)->setJSON([
                'success' => $isSuccess,
                'message' => $result['message'] ?? ($isSuccess ? 'Billing account status updated successfully' : 'Unable to update billing account status.'),
            ]);
        } catch (\Exception $e) {
            log_message('error', "markBillingAccountPaid: Exception for billing ID {$billingId}. Error: " . $e->getMessage());
            log_message('error', "Stack trace: " . $e->getTraceAsString());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'An error occurred while updating the billing account: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete a billing account and its items.
     */
    public function deleteBillingAccount($billingId)
    {
        $session  = session();
        $userRole = $session->get('role') ?? 'accountant';

        if (!in_array($userRole, ['admin', 'accountant'], true)) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'You are not allowed to delete billing accounts.',
            ]);
        }

        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setStatusCode(405)->setJSON([
                'success' => false,
                'message' => 'Invalid request method.',
            ]);
        }

        $result = $this->financialService->deleteBillingAccount((int)$billingId);
        $status = !empty($result['success']) ? 200 : 400;

        return $this->response->setStatusCode($status)->setJSON([
            'success' => !empty($result['success']),
            'message' => $result['message'] ?? 'Unable to delete billing account.',
        ]);
    }

    public function addTransaction()
    {
        if ($this->request->getMethod() === 'POST') {
            $validationRules = [
                'user_id' => 'required|integer|greater_than[0]',
                'type' => 'required|in_list[Income,Expense]',
                'category' => 'required|string|min_length[1]|max_length[255]',
                'amount' => 'required|numeric|greater_than[0]',
                'transaction_date' => 'required|valid_date[Y-m-d]',
            ];

            if ($this->validate($validationRules)) {
                $data = [
                    'user_id' => $this->request->getPost('user_id'),
                    'type' => $this->request->getPost('type'),
                    'category' => $this->request->getPost('category'),
                    'amount' => $this->request->getPost('amount'),
                    'description' => $this->request->getPost('description'),
                    'transaction_date' => $this->request->getPost('transaction_date'),
                ];

                if ($this->transactionModel->insert($data)) {
                    // Check if this is an AJAX request
                    if ($this->request->isAJAX()) {
                        return $this->response->setJSON([
                            'status' => 'success',
                            'message' => 'Transaction added successfully!'
                        ]);
                    } else {
                        return redirect()->to('/financial-management')->with('success', 'Transaction added successfully!');
                    }
                } else {
                    if ($this->request->isAJAX()) {
                        return $this->response->setJSON([
                            'status' => 'error',
                            'message' => 'Failed to add transaction.'
                        ]);
                    } else {
                        return redirect()->back()->with('error', 'Failed to add transaction.');
                    }
                }
            } else {
                if ($this->request->isAJAX()) {
                    return $this->response->setJSON([
                        'status' => 'error',
                        'message' => 'Please correct the errors in the form.',
                        'errors' => $this->validator->getErrors()
                    ]);
                } else {
                    return redirect()->back()->with('error', 'Please correct the errors in the form.')->with('validation', $this->validator);
                }
            }
        }

        $data = [
            'title' => 'Add Transaction',
            'categories' => $this->categoryModel->getCategoriesGrouped(),
            'users' => $this->getUsers(),
        ];

        return view('unified/add-transaction', $data);
    }

    public function getUsersAPI()
    {
        $users = $this->getUsers();
        return $this->response->setJSON(['users' => $users]);
    }

    public function getCategoriesByType()
    {
        $type = $this->request->getGet('type');
        
        if ($type === 'all') {
            // Return all categories grouped by type
            $categories = $this->categoryModel->getCategoriesGrouped();
            return $this->response->setJSON($categories);
        } else {
            // Return categories for specific type
            $categories = $this->categoryModel->getCategoriesByType($type);
            return $this->response->setJSON($categories);
        }
    }

    /**
     * Add billing item manually (for appointments, prescriptions, lab orders, or rooms)
     */
    public function addBillingItem()
    {
        $session = session();
        $userRole = $session->get('role') ?? 'accountant';
        $staffId = (int)($session->get('staff_id') ?? 0);

        if (!in_array($userRole, ['admin', 'accountant', 'receptionist', 'it_staff'], true)) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Insufficient permissions to add billing items',
            ]);
        }

        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setStatusCode(405)->setJSON([
                'success' => false,
                'message' => 'Invalid request method',
            ]);
        }

        $input = $this->request->getJSON(true) ?? $this->request->getPost();

        // Validate required fields
        if (empty($input['patient_id']) || empty($input['item_type'])) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'patient_id and item_type are required',
            ]);
        }

        $patientId = (int)$input['patient_id'];
        $admissionId = !empty($input['admission_id']) ? (int)$input['admission_id'] : null;
        $itemType = $input['item_type'];

        // Get or create billing account
        $account = $this->financialService->getOrCreateBillingAccountForPatient($patientId, $admissionId, $staffId);
        if (!$account || empty($account['billing_id'])) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Failed to get/create billing account',
            ]);
        }

        $billingId = (int)$account['billing_id'];
        $result = null;

        // Add item based on type
        switch ($itemType) {
            case 'appointment':
                if (empty($input['appointment_id'])) {
                    return $this->response->setStatusCode(422)->setJSON([
                        'success' => false,
                        'message' => 'appointment_id is required for appointment items',
                    ]);
                }
                $unitPrice = (float)($input['unit_price'] ?? 500.00);
                $result = $this->financialService->addItemFromAppointment(
                    $billingId,
                    (int)$input['appointment_id'],
                    $unitPrice,
                    1,
                    $staffId
                );
                break;

            case 'prescription':
                if (empty($input['prescription_id'])) {
                    return $this->response->setStatusCode(422)->setJSON([
                        'success' => false,
                        'message' => 'prescription_id is required for prescription items',
                    ]);
                }
                $unitPrice = (float)($input['unit_price'] ?? 100.00);
                $quantity = (int)($input['quantity'] ?? 1);
                $result = $this->financialService->addItemFromPrescription(
                    $billingId,
                    (int)$input['prescription_id'],
                    $unitPrice,
                    $quantity,
                    $staffId
                );
                break;

            case 'lab_order':
                if (empty($input['lab_order_id'])) {
                    return $this->response->setStatusCode(422)->setJSON([
                        'success' => false,
                        'message' => 'lab_order_id is required for lab order items',
                    ]);
                }
                $unitPrice = (float)($input['unit_price'] ?? 500.00);
                $result = $this->financialService->addItemFromLabOrder(
                    $billingId,
                    (int)$input['lab_order_id'],
                    $unitPrice,
                    $staffId
                );
                break;

            case 'room':
                if (empty($input['room_assignment_id'])) {
                    return $this->response->setStatusCode(422)->setJSON([
                        'success' => false,
                        'message' => 'room_assignment_id is required for room items',
                    ]);
                }
                $unitPrice = !empty($input['unit_price']) ? (float)$input['unit_price'] : null;
                $result = $this->financialService->addItemFromRoomAssignment(
                    $billingId,
                    (int)$input['room_assignment_id'],
                    $unitPrice,
                    $staffId
                );
                break;

            default:
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => 'Invalid item_type. Must be: appointment, prescription, lab_order, or room',
                ]);
        }

        $statusCode = ($result['success'] ?? false) ? 200 : 400;
        return $this->response->setStatusCode($statusCode)->setJSON($result ?? [
            'success' => false,
            'message' => 'Failed to add billing item',
        ]);
    }

    private function getUsers()
    {
        $db = \Config\Database::connect();
        
        // Check if accountant table exists
        if (!$db->tableExists('accountant')) {
            log_message('error', 'Accountant table does not exist');
            return [];
        }
        
        // Check if staff table exists
        if (!$db->tableExists('staff')) {
            log_message('error', 'Staff table does not exist');
            return [];
        }
        
        $query = $db->table('accountant a')
                  ->join('staff s', 's.staff_id = a.staff_id')
                  ->select('a.accountant_id as id, s.first_name, s.last_name, s.staff_id');
        
        log_message('debug', 'SQL Query: ' . $db->getLastQuery());
        
        $result = $query->get()->getResultArray();
        
        log_message('debug', 'Accountant data: ' . json_encode($result));
        
        return $result;
    }

    /**
     * API: Get transactions with filters
     */
    public function getTransactionsAPI()
    {
        $session = session();
        $userRole = $session->get('role') ?? 'accountant';
        
        if (!in_array($userRole, ['admin', 'accountant', 'it_staff'], true)) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Insufficient permissions',
            ]);
        }

        $filters = [
            'type' => $this->request->getGet('type'),
            'payment_status' => $this->request->getGet('payment_status'),
            'date_from' => $this->request->getGet('date_from'),
            'date_to' => $this->request->getGet('date_to'),
            'search' => $this->request->getGet('search'),
        ];

        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });

        try {
            $transactions = $this->transactionsModel->getTransactions($filters);
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $transactions,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'FinancialController::getTransactionsAPI - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Failed to fetch transactions',
            ]);
        }
    }

    /**
     * API: Get financial statistics
     */
    public function getFinancialStatsAPI()
    {
        $session = session();
        $userRole = $session->get('role') ?? 'accountant';
        $staffId = (int)($session->get('staff_id') ?? 0);

        try {
            $stats = $this->financialService->getFinancialStats($userRole, $staffId);
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'FinancialController::getFinancialStatsAPI - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Failed to fetch financial statistics',
            ]);
        }
    }

    /**
     * API: Get transaction by ID
     */
    public function getTransaction($transactionId = null)
    {
        $session = session();
        $userRole = $session->get('role') ?? 'accountant';
        
        if (!in_array($userRole, ['admin', 'accountant', 'it_staff'], true)) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Insufficient permissions',
            ]);
        }

        if (!$transactionId) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Transaction ID is required',
            ]);
        }

        try {
            // Find transaction by transaction_id (the unique identifier, not the primary key id)
            $transaction = $this->transactionsModel->where('transaction_id', $transactionId)->first();
            
            if (!$transaction) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'Transaction not found',
                ]);
            }

            // Get related data
            $db = \Config\Database::connect();
            
            if ($transaction['patient_id']) {
                $patient = $db->table('patients')
                    ->select('first_name, last_name')
                    ->where('patient_id', $transaction['patient_id'])
                    ->get()
                    ->getRowArray();
                if ($patient) {
                    $transaction['patient_first_name'] = $patient['first_name'];
                    $transaction['patient_last_name'] = $patient['last_name'];
                }
            }
            
            if ($transaction['resource_id']) {
                $resource = $db->table('resources')
                    ->select('equipment_name')
                    ->where('id', $transaction['resource_id'])
                    ->get()
                    ->getRowArray();
                if ($resource) {
                    $transaction['resource_name'] = $resource['equipment_name'];
                }
            }
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $transaction,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'FinancialController::getTransaction - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Failed to fetch transaction',
            ]);
        }
    }

    /**
     * API: Delete transaction
     */
    public function deleteTransaction($transactionId = null)
    {
        $session = session();
        $userRole = $session->get('role') ?? 'accountant';
        
        if (!in_array($userRole, ['admin', 'accountant'], true)) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Insufficient permissions. Only administrators and accountants can delete transactions.',
            ]);
        }

        // Get transaction ID from route parameter or request body
        if (!$transactionId) {
            $data = $this->request->getJSON(true) ?? $this->request->getPost();
            $transactionId = $data['transaction_id'] ?? null;
        }

        if (!$transactionId) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Transaction ID is required',
            ]);
        }

        try {
            // Find transaction by transaction_id (not id)
            $transaction = $this->transactionsModel->where('transaction_id', $transactionId)->first();
            
            if (!$transaction) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'Transaction not found',
                ]);
            }

            // Get the primary key (id) to delete
            $id = $transaction['id'] ?? null;
            if (!$id) {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'Transaction record is missing ID',
                ]);
            }

            // Delete the transaction using primary key
            $deleted = $this->transactionsModel->delete($id);
            
            if ($deleted) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Transaction deleted successfully',
                ]);
            } else {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'Failed to delete transaction',
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'FinancialController::deleteTransaction - ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Failed to delete transaction: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Diagnostic endpoint to check expense transactions
     */
    public function diagnoseExpenses()
    {
        $session = session();
        $userRole = $session->get('role') ?? 'accountant';
        
        if (!in_array($userRole, ['admin', 'accountant', 'it_staff'], true)) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Insufficient permissions',
            ]);
        }

        $db = \Config\Database::connect();
        $diagnostics = [];

        // Check transactions table
        if ($db->tableExists('transactions')) {
            // Count expense transactions
            $expenseCount = $db->table('transactions')
                ->where('type', 'expense')
                ->countAllResults(false);
            
            $expenseTotal = $db->table('transactions')
                ->selectSum('amount')
                ->where('type', 'expense')
                ->where('amount IS NOT NULL', null, false)
                ->get()
                ->getRow();

            // Count stock_in transactions
            $stockInCount = $db->table('transactions')
                ->where('type', 'stock_in')
                ->countAllResults(false);
            
            $stockInWithAmount = $db->table('transactions')
                ->where('type', 'stock_in')
                ->where('amount IS NOT NULL', null, false)
                ->countAllResults(false);

            // Check Stock Purchase expenses
            $stockPurchaseCount = $db->table('transactions')
                ->where('type', 'expense')
                ->where('category', 'Stock Purchase')
                ->countAllResults(false);

            $stockPurchaseTotal = $db->table('transactions')
                ->selectSum('amount')
                ->where('type', 'expense')
                ->where('category', 'Stock Purchase')
                ->where('amount IS NOT NULL', null, false)
                ->get()
                ->getRow();

            $diagnostics['transactions'] = [
                'expense_count' => $expenseCount,
                'expense_total' => $expenseTotal->amount ?? 0,
                'stock_in_count' => $stockInCount,
                'stock_in_with_amount' => $stockInWithAmount,
                'stock_purchase_expense_count' => $stockPurchaseCount,
                'stock_purchase_expense_total' => $stockPurchaseTotal->amount ?? 0,
            ];

            // Get sample transactions
            $sampleExpenses = $db->table('transactions')
                ->where('type', 'expense')
                ->limit(5)
                ->get()
                ->getResultArray();

            $sampleStockIn = $db->table('transactions')
                ->where('type', 'stock_in')
                ->limit(5)
                ->get()
                ->getResultArray();

            $diagnostics['samples'] = [
                'expenses' => $sampleExpenses,
                'stock_in' => $sampleStockIn,
            ];
        }

        // Get calculated stats
        $stats = $this->financialService->getFinancialStats($userRole, (int)($session->get('staff_id') ?? 0));

        return $this->response->setJSON([
            'success' => true,
            'diagnostics' => $diagnostics,
            'calculated_stats' => [
                'total_expenses' => $stats['total_expenses'] ?? 0,
                'monthly_expenses' => $stats['monthly_expenses'] ?? 0,
            ],
        ]);
    }
}
