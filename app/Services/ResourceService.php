<?php

namespace App\Services;

use CodeIgniter\Database\ConnectionInterface;
use App\Libraries\PermissionManager;
use App\Models\ResourceModel;
use App\Models\TransactionModel;

class ResourceService
{
    protected $db;
    protected $permissionManager;
    protected $resourceModel;
    protected $transactionModel;

    public function __construct(ConnectionInterface $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
        $this->permissionManager = new PermissionManager();
        $this->resourceModel = new ResourceModel();
        $this->transactionModel = new TransactionModel();
    }

    public function getResources($role, $staffId = null, $filters = [])
    {
        try {
            return $this->resourceModel->getResources($filters, $role, $staffId);
        } catch (\Exception $e) {
            log_message('error', 'ResourceService::getResources - ' . $e->getMessage());
            return [];
        }
    }

    public function getResourceById($resourceId, $role, $staffId = null)
    {
        try {
            if (!$this->permissionManager->hasPermission($role, 'resources', 'view')) {
                return null;
            }

            $resource = $this->resourceModel->find($resourceId);
            
            if (!$resource) {
                return null;
            }

            // Apply role-based filtering
            $allowedCategories = $this->getCategories($role);
            if (!in_array($resource['category'] ?? '', $allowedCategories)) {
                return null; // Resource not accessible to this role
            }

            return $resource;
        } catch (\Exception $e) {
            log_message('error', 'ResourceService::getResourceById - ' . $e->getMessage());
            return null;
        }
    }

    public function getResourceStats($role, $staffId = null)
    {
        try {
            return $this->resourceModel->getStats($role, $staffId);
        } catch (\Exception $e) {
            log_message('error', 'ResourceService::getResourceStats - ' . $e->getMessage());
            return [
                'total_resources' => 0,
                'stock_in' => 0,
                'stock_out' => 0,
                'categories' => 0,
                'low_quantity' => 0
            ];
        }
    }

    public function createResource($data, $role, $staffId)
    {
        try {
            if (!$this->permissionManager->hasPermission($role, 'resources', 'create')) {
                return ['success' => false, 'message' => 'Insufficient permissions'];
            }

            $quantity = (int)($data['quantity'] ?? 1);
            
            // Don't allow creating resources with quantity 0 - they would be immediately deleted
            if ($quantity === 0) {
                return ['success' => false, 'message' => 'Cannot create resource with quantity 0. Resources are automatically removed when stock runs out.'];
            }
            
            $resourceData = [
                'equipment_name' => trim($data['equipment_name'] ?? ''),
                'category' => $data['category'] ?? '',
                'quantity' => $quantity,
                'status' => $data['status'] ?? 'Stock In',
                'location' => trim($data['location'] ?? ''),
                'batch_number' => trim($data['batch_number'] ?? ''),
                'expiry_date' => !empty($data['expiry_date']) ? $data['expiry_date'] : null,
                'serial_number' => trim($data['serial_number'] ?? ''),
                'price' => !empty($data['purchase_cost']) ? (float)$data['purchase_cost'] : (!empty($data['price']) ? (float)$data['price'] : null),
                'selling_price' => !empty($data['selling_price']) ? (float)$data['selling_price'] : null,
                'remarks' => trim($data['remarks'] ?? '')
            ];

            // Validate category based on user role
            $allowedCategories = $this->getCategories($role);
            if (!in_array($resourceData['category'], $allowedCategories)) {
                return ['success' => false, 'message' => 'You do not have permission to create resources in this category. Allowed categories: ' . implode(', ', $allowedCategories)];
            }

            // Validate medications require batch number, expiry date, and selling price
            if ($resourceData['category'] === 'Medications') {
                if (empty($resourceData['batch_number'])) {
                    return ['success' => false, 'message' => 'Batch number is required for medications'];
                }
                if (empty($resourceData['expiry_date'])) {
                    return ['success' => false, 'message' => 'Expiry date is required for medications'];
                }
                if ($resourceData['expiry_date'] < date('Y-m-d')) {
                    return ['success' => false, 'message' => 'Cannot add expired medication. Expiry date is in the past'];
                }
                if (empty($resourceData['selling_price']) || (float)$resourceData['selling_price'] < 0) {
                    return ['success' => false, 'message' => 'Selling price is required and must be 0 or greater for medications'];
                }
            }

            // Start transaction for atomic stock operation
            $this->db->transStart();

            if (!$this->resourceModel->insert($resourceData)) {
                $this->db->transRollback();
                return [
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $this->resourceModel->errors()
                ];
            }

            $resourceId = $this->resourceModel->getInsertID();

            // Get purchase cost if provided (for expense transaction)
            // Priority: 1) purchase_cost from form, 2) price from resources table, 3) null
            $purchaseCost = null;
            if (isset($data['purchase_cost']) && !empty($data['purchase_cost'])) {
                // Use purchase_cost from form (unit cost)
                $unitPurchaseCost = (float)$data['purchase_cost'];
                $purchaseCost = $unitPurchaseCost * $quantity; // Total cost for expense transaction
            } elseif (isset($resourceData['price']) && !empty($resourceData['price'])) {
                // Fallback: use price column from resources table as purchase cost
                $unitPurchaseCost = (float)$resourceData['price'];
                $purchaseCost = $unitPurchaseCost * $quantity; // Total cost for expense transaction
            }

            // Create stock_in transaction record (and expense if purchase cost provided)
            $this->createStockTransaction($resourceId, 'stock_in', $quantity, $resourceData['equipment_name'], $staffId, $purchaseCost);

            // Complete transaction
            $this->db->transComplete();

            // Check transaction status
            if ($this->db->transStatus() === false) {
                log_message('error', 'ResourceService::createResource - Transaction failed');
                return ['success' => false, 'message' => 'Failed to create resource. Transaction rolled back.'];
            }

            return [
                'success' => true,
                'message' => 'Resource created successfully',
                'resource_id' => $resourceId
            ];

        } catch (\Exception $e) {
            // Rollback on exception
            if ($this->db->transStatus() !== false) {
                $this->db->transRollback();
            }
            log_message('error', 'ResourceService::createResource - ' . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()];
        }
    }

    public function updateResource($resourceId, $data, $role, $staffId)
    {
        try {
            if (!$this->permissionManager->hasPermission($role, 'resources', 'edit')) {
                return ['success' => false, 'message' => 'Insufficient permissions'];
            }

            $resource = $this->resourceModel->find($resourceId);
            if (!$resource) {
                return ['success' => false, 'message' => 'Resource not found'];
            }

            $allowedFields = ['equipment_name', 'category', 'quantity', 'status', 'location',
                            'batch_number', 'expiry_date', 'serial_number', 'remarks'];
            $updateData = [];
            
            foreach ($allowedFields as $field) {
                if (!isset($data[$field])) continue;
                
                if (in_array($field, ['equipment_name', 'location', 'batch_number', 'serial_number', 'remarks'], true)) {
                    $updateData[$field] = trim($data[$field]);
                } elseif ($field === 'quantity') {
                    $updateData[$field] = (int)$data[$field];
                } elseif ($field === 'expiry_date') {
                    $updateData[$field] = !empty($data[$field]) ? $data[$field] : null;
                } else {
                    $updateData[$field] = $data[$field];
                }
            }

            // Validate category based on user role (if category is being updated)
            if (isset($updateData['category'])) {
                $allowedCategories = $this->getCategories($role);
                if (!in_array($updateData['category'], $allowedCategories)) {
                    return ['success' => false, 'message' => 'You do not have permission to update resources to this category. Allowed categories: ' . implode(', ', $allowedCategories)];
                }
            }

            // Validate medications if category is being updated or is already Medications
            $newCategory = $updateData['category'] ?? $resource['category'] ?? '';
            if ($newCategory === 'Medications') {
                $batchNumber = $updateData['batch_number'] ?? $resource['batch_number'] ?? '';
                $expiryDate = $updateData['expiry_date'] ?? $resource['expiry_date'] ?? '';
                
                if (empty($batchNumber)) {
                    return ['success' => false, 'message' => 'Batch number is required for medications'];
                }
                if (empty($expiryDate)) {
                    return ['success' => false, 'message' => 'Expiry date is required for medications'];
                }
            }

            // Automatically delete resource when quantity reaches 0 (unless assigned to staff)
            $currentQuantity = (int)($resource['quantity'] ?? 0);
            $newQuantity = isset($updateData['quantity']) ? (int)$updateData['quantity'] : $currentQuantity;
            $assignedToStaff = !empty($resource['assigned_to_staff_id']);
            
            // Start transaction for atomic stock operation
            $this->db->transStart();
            
            // Check if quantity is being set to 0 and resource is not assigned to staff
            if (isset($updateData['quantity']) && $newQuantity === 0 && !$assignedToStaff) {
                // Automatically delete the resource when stock runs out
                if ($this->resourceModel->delete($resourceId)) {
                    $this->db->transComplete();
                    if ($this->db->transStatus() === false) {
                        $this->db->transRollback();
                        return ['success' => false, 'message' => 'Failed to remove resource. Transaction rolled back.'];
                    }
                    return [
                        'success' => true, 
                        'message' => 'Resource automatically removed - stock is out',
                        'deleted' => true
                    ];
                } else {
                    $this->db->transRollback();
                    return [
                        'success' => false,
                        'message' => 'Failed to remove resource'
                    ];
                }
            }
            
            // Only auto-update status if quantity is being changed and status is not explicitly set
            if (isset($updateData['quantity']) && !isset($updateData['status']) && $newQuantity > 0) {
                if ($newQuantity > 0 && !$assignedToStaff) {
                    // When quantity becomes > 0 and not assigned to staff, set status to 'Stock In'
                    // Only change if it was previously 'Stock Out' due to zero quantity
                    if (($resource['status'] ?? '') === 'Stock Out' && $currentQuantity === 0) {
                        $updateData['status'] = 'Stock In';
                    }
                }
            }

            if (empty($updateData)) {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'No changes provided'];
            }

            // Track quantity change for stock transaction
            $quantityChange = 0;
            if (isset($updateData['quantity'])) {
                $quantityChange = $newQuantity - $currentQuantity;
            }

            if (!$this->resourceModel->update($resourceId, $updateData)) {
                $this->db->transRollback();
                return [
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $this->resourceModel->errors()
                ];
            }

            // Create stock transaction if quantity changed
            if ($quantityChange != 0) {
                $transactionType = $quantityChange > 0 ? 'stock_in' : 'stock_out';
                $this->createStockTransaction(
                    $resourceId, 
                    $transactionType, 
                    abs($quantityChange), 
                    $resource['equipment_name'] ?? 'Resource', 
                    $staffId
                );
            }

            // Complete transaction
            $this->db->transComplete();

            // Check transaction status
            if ($this->db->transStatus() === false) {
                log_message('error', 'ResourceService::updateResource - Transaction failed');
                return ['success' => false, 'message' => 'Failed to update resource. Transaction rolled back.'];
            }

            return ['success' => true, 'message' => 'Resource updated successfully'];

        } catch (\Exception $e) {
            // Rollback on exception
            if ($this->db->transStatus() !== false) {
                $this->db->transRollback();
            }
            log_message('error', 'ResourceService::updateResource - ' . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()];
        }
    }

    public function deleteResource($resourceId, $role, $staffId)
    {
        try {
            if (!$this->permissionManager->hasPermission($role, 'resources', 'delete')) {
                return ['success' => false, 'message' => 'Insufficient permissions'];
            }

            $resource = $this->resourceModel->find($resourceId);
            if (!$resource) {
                return ['success' => false, 'message' => 'Resource not found'];
            }

            // Resources with quantity 0 are automatically deleted, so manual deletion is only needed
            // for resources that are assigned to staff or have quantity > 0
            // Allow deletion of any resource (automatic deletion only happens when quantity reaches 0)

            // Start transaction for atomic deletion
            $this->db->transStart();

            $deleted = $this->resourceModel->delete($resourceId);

            if (!$deleted) {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Failed to delete resource'];
            }

            // Complete transaction
            $this->db->transComplete();

            // Check transaction status
            if ($this->db->transStatus() === false) {
                log_message('error', 'ResourceService::deleteResource - Transaction failed');
                return ['success' => false, 'message' => 'Failed to delete resource. Transaction rolled back.'];
            }

            return ['success' => true, 'message' => 'Resource deleted successfully'];

        } catch (\Exception $e) {
            // Rollback on exception
            if ($this->db->transStatus() !== false) {
                $this->db->transRollback();
            }
            log_message('error', 'ResourceService::deleteResource - ' . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()];
        }
    }


    public function getCategories($role)
    {
        $allCategories = [
            'Medical Equipment', 'Medical Supplies', 'Diagnostic Equipment', 'Lab Equipment',
            'Pharmacy Equipment', 'Medications', 'Office Equipment', 'IT Equipment',
            'Furniture', 'Vehicles', 'Other'
        ];

        return match($role) {
            'admin', 'it_staff' => $allCategories,
            'doctor', 'nurse' => ['Medical Equipment', 'Medical Supplies', 'Diagnostic Equipment'],
            'pharmacist' => ['Medical Supplies', 'Pharmacy Equipment', 'Medications'], // Medications ONLY for pharmacists
            'laboratorist' => ['Lab Equipment', 'Diagnostic Equipment', 'Medical Supplies'], // NO medications for laboratorists
            'receptionist' => ['Office Equipment', 'IT Equipment'],
            default => []
        };
    }

    public function getStaffForAssignment()
    {
        try {
            return $this->db->table('staff')
                ->select('staff_id, first_name, last_name, role')
                ->where('status', 'Active')
                ->orderBy('first_name')
                ->get()->getResultArray();
        } catch (\Exception $e) {
            log_message('error', 'ResourceService::getStaffForAssignment - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get medications expiring within specified days
     */
    public function getExpiringMedications($daysAhead = 30)
    {
        try {
            return $this->resourceModel->getExpiringMedications($daysAhead);
        } catch (\Exception $e) {
            log_message('error', 'ResourceService::getExpiringMedications - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get expired medications
     */
    public function getExpiredMedications()
    {
        try {
            return $this->resourceModel->getExpiredMedications();
        } catch (\Exception $e) {
            log_message('error', 'ResourceService::getExpiredMedications - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get resources with low stock (quantity < 20)
     * @param int $threshold Threshold value (default 20)
     * @param string|null $role User role for filtering
     * @return array
     */
    public function getLowStockResources($threshold = 20, $role = null)
    {
        try {
            return $this->resourceModel->getLowStockResources($threshold, $role);
        } catch (\Exception $e) {
            log_message('error', 'ResourceService::getLowStockResources - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get medication resources (category = 'Medications') with optional search filter.
     */
    public function getMedications(?string $search = null): array
    {
        try {
            $builder = $this->db->table('resources')
                ->where('category', 'Medications');

            if ($search) {
                $builder->like('equipment_name', $search);
            }

            return $builder
                ->select('id, equipment_name, quantity, status, price')
                ->orderBy('equipment_name', 'ASC')
                ->get()
                ->getResultArray();
        } catch (\Exception $e) {
            log_message('error', 'ResourceService::getMedications - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Create a stock transaction record
     * @param int $resourceId Resource ID
     * @param string $type Transaction type (stock_in, stock_out)
     * @param int $quantity Quantity of items
     * @param string $resourceName Name of the resource
     * @param int $createdBy User ID who created the transaction
     * @param float|null $purchaseCost Optional purchase cost - if provided, will create an expense transaction
     */
    private function createStockTransaction($resourceId, $type, $quantity, $resourceName, $createdBy, $purchaseCost = null)
    {
        try {
            // Check if transactions table exists and supports stock transactions
            if (!$this->db->tableExists('transactions')) {
                return;
            }

            $transactionModel = new TransactionModel();
            $transactionId = $transactionModel->generateTransactionId();

            // For stock_in with a known purchase cost, record the *per-unit* purchase cost on this row
            // $purchaseCost here is the total cost (unit cost * quantity) passed from createResource.
            $stockAmount = null;
            if ($type === 'stock_in' && $purchaseCost !== null && $purchaseCost > 0) {
                $unitCost = $quantity > 0 ? ($purchaseCost / $quantity) : $purchaseCost;
                $stockAmount = (float) $unitCost; // 1 product = 1 price (unit purchase cost)
            }

            $transactionData = [
                'transaction_id' => $transactionId,
                'type' => $type,
                'category' => 'Stock Management',
                'amount' => $stockAmount, // Used by Purchase Amount column for stock_in when available
                'quantity' => $quantity,
                'description' => ucfirst(str_replace('_', ' ', $type)) . ': ' . $quantity . ' unit(s) of ' . $resourceName,
                'resource_id' => $resourceId,
                'payment_status' => 'completed', // Stock transactions are always completed
                'transaction_date' => date('Y-m-d'),
                'transaction_time' => date('H:i:s'),
                'created_by' => $createdBy,
                'notes' => 'Automatic stock transaction from resource management'
            ];

            // Skip validation for stock transactions (amount is optional)
            $transactionModel->skipValidation(true);
            $transactionModel->insert($transactionData);
            $transactionModel->skipValidation(false);

            // If purchase cost is provided, create an expense transaction
            if ($purchaseCost !== null && $purchaseCost > 0 && $type === 'stock_in') {
                $this->createStockPurchaseExpense($resourceId, $quantity, $resourceName, $purchaseCost, $createdBy);
            }

        } catch (\Exception $e) {
            // Log error but don't fail the resource operation
            log_message('error', 'ResourceService::createStockTransaction - ' . $e->getMessage());
        }
    }

    /**
     * Create an expense transaction for stock purchase
     * @param int $resourceId Resource ID
     * @param int $quantity Quantity purchased
     * @param string $resourceName Name of the resource
     * @param float $purchaseCost Total purchase cost (unit cost * quantity)
     * @param int $createdBy User ID who created the transaction
     */
    private function createStockPurchaseExpense($resourceId, $quantity, $resourceName, $purchaseCost, $createdBy)
    {
        try {
            // Check if transactions table exists
            if (!$this->db->tableExists('transactions')) {
                return;
            }

            $transactionModel = new TransactionModel();
            $transactionId = $transactionModel->generateTransactionId();

            // Calculate unit cost from total cost
            $unitCost = $quantity > 0 ? $purchaseCost / $quantity : $purchaseCost;

            $expenseData = [
                'transaction_id' => $transactionId,
                'type' => 'expense',
                'category' => 'Stock Purchase',
                'amount' => (float)$purchaseCost, // Total amount
                'quantity' => $quantity,
                'description' => 'Purchase of ' . $quantity . ' unit(s) of ' . $resourceName . ' @ ₱' . number_format($unitCost, 2) . ' per unit',
                'resource_id' => $resourceId,
                'payment_status' => 'completed',
                'transaction_date' => date('Y-m-d'),
                'transaction_time' => date('H:i:s'),
                'created_by' => $createdBy,
                'notes' => 'Purchase cost: ₱' . number_format($unitCost, 2) . ' per unit. Total: ₱' . number_format($purchaseCost, 2)
            ];

            $transactionModel->skipValidation(false);
            $transactionModel->insert($expenseData);

            log_message('debug', "ResourceService::createStockPurchaseExpense - Created expense transaction for resource {$resourceId}: Unit cost ₱{$unitCost}, Total ₱{$purchaseCost}");

        } catch (\Exception $e) {
            // Log error but don't fail the stock transaction
            log_message('error', 'ResourceService::createStockPurchaseExpense - ' . $e->getMessage());
        }
    }

}