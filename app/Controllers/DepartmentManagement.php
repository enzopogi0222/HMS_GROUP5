<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Services\DepartmentService;

class DepartmentManagement extends BaseController
{
    protected $departmentService;
    protected $userRole;

    public function __construct()
    {
        $this->departmentService = new DepartmentService();
        $this->userRole = (string) (session()->get('role') ?? 'guest');
    }

    public function index()
    {
        return view('unified/department-management', [
            'title' => 'Department Management',
            'userRole' => $this->userRole,
            'departmentStats' => $this->departmentService->getDepartmentStats(),
            'departmentHeads' => $this->departmentService->getPotentialDepartmentHeads(),
            'specialties' => $this->departmentService->getAvailableSpecialties(),
            'departments' => $this->departmentService->getDepartments(),
        ]);
    }

    /**
     * API endpoint to fetch departments
     */
    public function getDepartmentsAPI()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Not authenticated'])->setStatusCode(401);
        }

        try {
            $departments = $this->departmentService->getDepartments();
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $departments
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'DepartmentManagement::getDepartmentsAPI error: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Failed to fetch departments'
            ])->setStatusCode(500);
        }
    }

    /**
     * API endpoint to fetch department stats
     */
    public function getDepartmentStatsAPI()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Not authenticated'])->setStatusCode(401);
        }

        try {
            $stats = $this->departmentService->getDepartmentStats();
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $stats
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'DepartmentManagement::getDepartmentStatsAPI error: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Failed to fetch department stats'
            ])->setStatusCode(500);
        }
    }

    /**
     * API endpoint to fetch a single department by ID
     */
    public function getDepartment($id)
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Not authenticated'])->setStatusCode(401);
        }

        try {
            $departmentId = (int) $id;
            if (!$departmentId) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Invalid department ID'
                ])->setStatusCode(400);
            }

            $department = $this->departmentService->getDepartmentById($departmentId);
            
            if (!$department) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Department not found'
                ])->setStatusCode(404);
            }

            return $this->response->setJSON([
                'status' => 'success',
                'data' => $department
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'DepartmentManagement::getDepartment error: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Failed to fetch department'
            ])->setStatusCode(500);
        }
    }

    /**
     * API endpoint to update a department
     */
    public function update()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Not authenticated'])->setStatusCode(401);
        }

        try {
            $input = $this->request->getJSON(true) ?? $this->request->getPost();
            $id = $input['id'] ?? $input['department_id'] ?? null;

            if (!$id) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Department ID is required'
                ])->setStatusCode(422);
            }

            $db = \Config\Database::connect();
            if (!$db->tableExists('department')) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Department table not found'
                ])->setStatusCode(500);
            }

            // Check if department exists
            $exists = $db->table('department')
                ->where('department_id', (int)$id)
                ->get()
                ->getRowArray();

            if (!$exists) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Department not found'
                ])->setStatusCode(404);
            }

            // Prepare update data
            $updateData = [];
            $nameColumn = $db->fieldExists('name', 'department') ? 'name' : 
                         ($db->fieldExists('department_name', 'department') ? 'department_name' : null);

            if ($nameColumn && isset($input['name'])) {
                $updateData[$nameColumn] = trim(preg_replace('/\s+/', ' ', (string)$input['name']));
            }

            $optionalFields = [
                'code' => 'code',
                'floor' => 'floor',
                'building' => 'building',
                'contact_number' => 'contact_number',
                'description' => 'description',
                'status' => 'status',
                'type' => 'type',
                'department_head' => 'department_head_id'
            ];

            foreach ($optionalFields as $inputKey => $dbColumn) {
                if (isset($input[$inputKey]) && $db->fieldExists($dbColumn, 'department')) {
                    $value = $input[$inputKey];
                    if ($inputKey === 'department_head') {
                        $updateData[$dbColumn] = !empty($value) ? (int)$value : null;
                    } elseif ($inputKey === 'status') {
                        $normalized = ucfirst(strtolower(trim((string)$value)));
                        $updateData[$dbColumn] = in_array($normalized, ['Active', 'Inactive'], true) ? $normalized : null;
                    } else {
                        $updateData[$dbColumn] = $value;
                    }
                }
            }

            if ($db->fieldExists('updated_at', 'department')) {
                $updateData['updated_at'] = date('Y-m-d H:i:s');
            }

            if (empty($updateData)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'No valid fields to update'
                ])->setStatusCode(422);
            }

            $db->table('department')
                ->where('department_id', (int)$id)
                ->update($updateData);

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Department updated successfully'
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'DepartmentManagement::update error: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Failed to update department'
            ])->setStatusCode(500);
        }
    }

    /**
     * API endpoint to get department heads by category
     */
    public function getDepartmentHeadsByCategory()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Not authenticated'])->setStatusCode(401);
        }

        try {
            $category = strtolower(trim((string) $this->request->getGet('category')));
            
            if (!in_array($category, ['medical', 'non_medical'], true)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Invalid category. Must be "medical" or "non_medical"'
                ])->setStatusCode(400);
            }

            $heads = $this->departmentService->getPotentialDepartmentHeadsByCategory($category);
            
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $heads
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'DepartmentManagement::getDepartmentHeadsByCategory error: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Failed to fetch department heads'
            ])->setStatusCode(500);
        }
    }

    /**
     * API endpoint to delete a department
     */
    public function delete()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Not authenticated'])->setStatusCode(401);
        }

        try {
            $input = $this->request->getJSON(true) ?? $this->request->getPost();
            $id = $input['id'] ?? $input['department_id'] ?? null;

            if (!$id) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Department ID is required'
                ])->setStatusCode(422);
            }

            $db = \Config\Database::connect();
            if (!$db->tableExists('department')) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Department table not found'
                ])->setStatusCode(500);
            }

            // Check if department exists
            $exists = $db->table('department')
                ->where('department_id', (int)$id)
                ->get()
                ->getRowArray();

            if (!$exists) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Department not found'
                ])->setStatusCode(404);
            }

            // Check if department has associated staff
            if ($db->tableExists('staff') && $db->fieldExists('department_id', 'staff')) {
                $staffCount = $db->table('staff')
                    ->where('department_id', (int)$id)
                    ->countAllResults();
                
                if ($staffCount > 0) {
                    return $this->response->setJSON([
                        'status' => 'error',
                        'message' => 'Cannot delete department with assigned staff members'
                    ])->setStatusCode(422);
                }
            }

            // Delete from department table
            $db->table('department')
                ->where('department_id', (int)$id)
                ->delete();

            // Also delete from medical/non-medical department tables if they exist
            if ($db->tableExists('medical_department')) {
                $db->table('medical_department')
                    ->where('department_id', (int)$id)
                    ->delete();
            }

            if ($db->tableExists('non_medical_department')) {
                $db->table('non_medical_department')
                    ->where('department_id', (int)$id)
                    ->delete();
            }

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Department deleted successfully'
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'DepartmentManagement::delete error: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Failed to delete department'
            ])->setStatusCode(500);
        }
    }
}
