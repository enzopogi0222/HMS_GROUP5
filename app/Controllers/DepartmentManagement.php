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
            $departments = $this->departmentService->getDepartments();
            $department = array_filter($departments, fn($dept) => ($dept['department_id'] ?? null) == $id);
            
            if (empty($department)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Department not found'
                ])->setStatusCode(404);
            }

            return $this->response->setJSON([
                'status' => 'success',
                'data' => array_values($department)[0]
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'DepartmentManagement::getDepartment error: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Failed to fetch department'
            ])->setStatusCode(500);
        }
    }
}
