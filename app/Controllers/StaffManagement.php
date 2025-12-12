<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Services\StaffService;

class StaffManagement extends BaseController
{
    protected $db;
    protected $builder;
    protected $staffService;
    protected $userRole;
    protected $staffId;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->builder = $this->db->table('staff');
        $this->staffService = new StaffService();

        // Get user session data
        $session = session();
        $this->userRole = $session->get('role');
        $this->staffId = $session->get('staff_id');
    }

    // Fetch a single staff member as JSON (for modals)
    public function getStaff($id = null)
    {
        $id = (int)($id ?? 0);
        if ($id <= 0) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Invalid staff ID'], 400);
        }

        try {
            $result = $this->staffService->getStaff($id);
            if (!$result['success']) {
                return $this->jsonResponse(['status' => 'error', 'message' => $result['message'] ?? 'Staff not found'], 404);
            }
            return $this->jsonResponse(['status' => 'success', 'data' => $result['staff']]);
        } catch (\Throwable $e) {
            log_message('error', 'Failed to fetch staff for modal: ' . $e->getMessage());
            return $this->jsonResponse(['status' => 'error', 'message' => 'Failed to load staff details'], 500);
        }
    }

    /**
     * Main unified staff management - Role-based
     */
    public function index()
    {
        // Get staff data using service
        $staffResult = $this->staffService->getStaffByRole($this->userRole, $this->staffId);
        $stats = $this->staffService->getStaffStats($this->userRole, $this->staffId);
        
        // Load departments from department table for dynamic dropdowns (id + name)
        $departments = $this->db->table('department')
            ->select('department_id, name')
            ->orderBy('name','ASC')
            ->get()->getResultArray();

        $data = [
            'title' => $this->getPageTitle(),
            'userRole' => $this->userRole,
            'staff' => $staffResult['data'] ?? [],
            'staffStats' => $stats,
            'permissions' => $this->getStaffPermissions($this->userRole),
            'total_staff' => count($staffResult['data'] ?? []),
            'departments' => $departments,
        ];

        return view('unified/staff-management', $data);
    }

    public function create()
    {
        if ($this->request->getMethod() !== 'POST') {
            return view('admin/add-staff', ['title' => 'Add Staff']);
        }

        $input = $this->request->getPost() ?: $this->request->getJSON(true) ?: [];
        $isAjax = $this->isAjaxRequest();
        $result = $this->staffService->createStaff($input, $this->userRole);

        if ($isAjax) {
            $payload = $result['success']
                ? ['status' => 'success', 'message' => $result['message'] ?? 'Created', 'id' => $result['id'] ?? null]
                : ['status' => 'error', 'message' => $result['message'] ?? 'Failed', 'errors' => $result['errors'] ?? null];
            return $this->jsonResponse($payload, $result['success'] ? 200 : 422);
        }

        session()->setFlashdata($result['success'] ? 'success' : 'error', $result['message']);
        if (!$result['success'] && isset($result['errors'])) {
            session()->setFlashdata('errors', $result['errors']);
        }
        return redirect()->to(base_url('admin/staff-management'));
    }


    public function update($id = null)
    {
        $jsonInput = $this->request->getJSON(true);
        $id = (int) ($id ?? ($this->request->getPost('staff_id') ?? ($jsonInput['staff_id'] ?? 0)));
        if ($id <= 0) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Invalid staff ID'], 400);
        }
        
        $result = $this->staffService->updateStaff($id, $this->request->getPost() ?: $jsonInput ?? [], $this->userRole);
        $statusCode = $result['success'] ? 200 : (($result['message'] === 'Permission denied') ? 403 : 422);
        return $this->jsonResponse($result, $statusCode);
    }

    public function delete($id = null)
    {
        $id = (int) ($id ?? $this->request->getPost('staff_id') ?? $this->request->getGet('staff_id'));
        $isAjax = $this->isAjaxRequest();

        if ($id <= 0) {
            if ($isAjax) {
                return $this->jsonResponse(['status' => 'error', 'message' => 'Invalid staff ID'], 400);
            }
            session()->setFlashdata('error', 'Invalid staff ID.');
            return redirect()->to(base_url('admin/staff-management'));
        }

        $result = $this->staffService->deleteStaff($id, $this->userRole);

        if ($isAjax) {
            $statusCode = $result['success'] ? 200 : (($result['message'] ?? '') === 'Permission denied' ? 403 : 422);
            return $this->jsonResponse([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'] ?? ($result['success'] ? 'Deleted' : 'Failed to delete staff'),
            ], $statusCode);
        }

        session()->setFlashdata($result['success'] ? 'success' : 'error', $result['message']);
        return redirect()->to(base_url('admin/staff-management'));
    }

    public function view($id = null)
    {
        if (!$id) {
            return redirect()->to(base_url('admin/staff-management'));
        }

        $staff = $this->builder->where('staff_id', $id)->get()->getRowArray();
        if (!$staff) {
            session()->setFlashdata('error', 'Staff member not found.');
            return redirect()->to(base_url('admin/staff-management'));
        }

        return view('admin/view-staff', ['title' => 'Staff Details', 'staff' => $staff]);
    }

    // API Methods
    public function getStaffAPI()
    {
        try {
            $filters = array_filter([
                'department' => $this->request->getGet('department'),
                'role' => $this->request->getGet('role'),
                'status' => $this->request->getGet('status'),
                'search' => $this->request->getGet('search'),
            ], fn($v) => !empty($v));
            
            $result = $this->staffService->getStaffByRole($this->userRole, $this->staffId, $filters);
            return $this->jsonResponse([
                'status' => $result['success'] ? 'success' : 'error',
                'data' => $result['data'] ?? [],
                'message' => $result['message'] ?? null,
            ], $result['success'] ? 200 : 500);
        } catch (\Throwable $e) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Failed to load staff'], 500);
        }
    }

    public function getDoctorsAPI()
    {
        try {
            $rows = $this->db->table('doctor d')
                ->select('d.doctor_id, s.staff_id, s.first_name, s.last_name, s.department, d.specialization, d.status')
                ->join('staff s', 's.staff_id = d.staff_id', 'left')
                ->orderBy('s.first_name', 'ASC')
                ->get()->getResultArray();
        
            $data = array_map(fn($r) => array_merge($r, ['name' => trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''))]), $rows);
            return $this->jsonResponse(['status' => 'success', 'data' => $data]);
        } catch (\Throwable $e) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Failed to load doctors'], 500);
        }
    }

    /**
     * Get next auto-generated employee_id for a given role slug.
     * Used by frontend to auto-fill IDs like DOC-0001, NUR-0001, etc.
     */
    public function getNextEmployeeId()
    {
        $role = $this->request->getGet('role');
        if (empty($role)) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Role is required'], 400);
        }

        try {
            $employeeId = $this->staffService->getNextEmployeeIdForRole($role);
            return $this->jsonResponse(['status' => 'success', 'employee_id' => $employeeId, 'role' => $role]);
        } catch (\Throwable $e) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Failed to generate employee ID'], 500);
        }
    }

    // ===================================================================
    // UNIFIED STAFF MANAGEMENT HELPER METHODS
    // ===================================================================

    /**
     * Get page title based on user role
     */
    private function getPageTitle()
    {
        return match($this->userRole) {
            'admin' => 'Staff Management',
            'doctor' => 'Department Team',
            'nurse' => 'Department Staff',
            'receptionist' => 'Staff Directory',
            default => 'Staff Information',
        };
    }

    /**
     * Get staff permissions based on user role
     */
    private function getStaffPermissions($userRole)
    {
        $admin = $userRole === 'admin';
        return [
            'canCreate' => $admin,
            'canEdit' => $admin,
            'canDelete' => $admin,
            'canView' => in_array($userRole, ['admin', 'doctor', 'nurse', 'receptionist']),
            'canViewSalary' => in_array($userRole, ['admin', 'accountant']),
            'canManageSchedule' => in_array($userRole, ['admin', 'doctor']),
            'canViewDepartment' => in_array($userRole, ['admin', 'doctor', 'nurse']),
            'canExport' => $admin,
        ];
    }

    /**
     * Helper: Check if request is AJAX
     */
    private function isAjaxRequest()
    {
        return $this->request->isAJAX() || 
               $this->request->getHeaderLine('Accept') === 'application/json' ||
               strtoupper($this->request->getMethod()) === 'DELETE' ||
               $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Helper: Return JSON response with status code
     */
    private function jsonResponse($data, $statusCode = 200)
    {
        return $this->response->setStatusCode($statusCode)->setJSON($data);
    }
}