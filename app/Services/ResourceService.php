<?php

namespace App\Services;

use CodeIgniter\Database\ConnectionInterface;
use App\Libraries\PermissionManager;
use App\Models\ResourceModel;

class ResourceService
{
    protected $db;
    protected $permissionManager;
    protected $resourceModel;

    public function __construct(ConnectionInterface $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
        $this->permissionManager = new PermissionManager();
        $this->resourceModel = new ResourceModel();
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
                'remarks' => trim($data['remarks'] ?? '')
            ];

            // Validate category based on user role
            $allowedCategories = $this->getCategories($role);
            if (!in_array($resourceData['category'], $allowedCategories)) {
                return ['success' => false, 'message' => 'You do not have permission to create resources in this category. Allowed categories: ' . implode(', ', $allowedCategories)];
            }

            // Validate medications require batch number and expiry date
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
            }

            if (!$this->resourceModel->insert($resourceData)) {
                return [
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $this->resourceModel->errors()
                ];
            }

            return [
                'success' => true,
                'message' => 'Resource created successfully',
                'resource_id' => $this->resourceModel->getInsertID()
            ];

        } catch (\Exception $e) {
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
            
            // Check if quantity is being set to 0 and resource is not assigned to staff
            if (isset($updateData['quantity']) && $newQuantity === 0 && !$assignedToStaff) {
                // Automatically delete the resource when stock runs out
                if ($this->resourceModel->delete($resourceId)) {
                    return [
                        'success' => true, 
                        'message' => 'Resource automatically removed - stock is out',
                        'deleted' => true
                    ];
                } else {
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
                return ['success' => false, 'message' => 'No changes provided'];
            }

            if (!$this->resourceModel->update($resourceId, $updateData)) {
                return [
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $this->resourceModel->errors()
                ];
            }

            return ['success' => true, 'message' => 'Resource updated successfully'];

        } catch (\Exception $e) {
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

            return $this->resourceModel->delete($resourceId)
                ? ['success' => true, 'message' => 'Resource deleted successfully']
                : ['success' => false, 'message' => 'Failed to delete resource'];

        } catch (\Exception $e) {
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

}