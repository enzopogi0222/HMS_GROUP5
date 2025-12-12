<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Services\UserService;
use App\Libraries\PermissionManager;

class UserManagement extends BaseController
{
    protected $userService;
    protected $userRole;
    protected $staffId;

    public function __construct()
    {
        $this->userService = new UserService();
        $session = session();
        $this->userRole = $session->get('role') ?: 'admin';
        $this->staffId = $session->get('staff_id');
    }

    /**
     * Main unified user management - Role-based
     */
    public function index()
    {
        $data = [
            'title' => $this->getPageTitle($this->userRole),
            'userRole' => $this->userRole,
            'users' => $this->userService->getUsersByRole($this->userRole, $this->staffId),
            'userStats' => $this->userService->getUserStats($this->userRole, $this->staffId),
            'availableStaff' => $this->userService->getAvailableStaff($this->userRole),
            'permissions' => $this->getUserPermissions($this->userRole),
        ];
        return view('unified/user-management', $data);
    }

    public function create()
    {
        try {
            $result = $this->userService->createUser($this->request->getPost() ?: $this->request->getJSON(true) ?? [], $this->userRole);
            return $this->handleResponse($result, 'create');
        } catch (\Exception $e) {
            return $this->handleError($e, 'create');
        }
    }

    public function update($userId = null)
    {
        try {
            $userId = $userId ?? $this->request->getPost('user_id');
            $result = $this->userService->updateUser($userId, $this->request->getPost() ?: $this->request->getJSON(true) ?? [], $this->userRole);
            return $this->handleResponse($result, 'update');
        } catch (\Exception $e) {
            return $this->handleError($e, 'update');
        }
    }

    public function delete($userId = null)
    {
        try {
            $result = $this->userService->deleteUser($userId, $this->userRole);
            return $this->handleResponse($result, 'delete');
        } catch (\Exception $e) {
            return $this->handleError($e, 'delete');
        }
    }

    public function resetPassword($userId = null)
    {
        try {
            // Get userId from URL parameter first, then from request
            $userId = $userId ?? $this->request->getPost('user_id') ?? ($this->request->getJSON(true)['user_id'] ?? null);
            $requestData = $this->request->getPost() ?: $this->request->getJSON(true) ?? [];
            $newPassword = $requestData['new_password'] ?? null;
            
            if (!$userId) {
                throw new \Exception('User ID is required');
            }
            
            if (!$newPassword) {
                throw new \Exception('New password is required');
            }
            
            if (strlen($newPassword) < 6) {
                throw new \Exception('Password must be at least 6 characters long');
            }
            
            $result = $this->userService->resetPassword($userId, $newPassword, $this->userRole);
            return $this->handleResponse($result, 'reset');
        } catch (\Exception $e) {
            return $this->handleError($e, 'reset');
        }
    }

    // API Methods
    public function getUsersAPI()
    {
        try {
            $users = $this->userService->getUsersByRole($this->userRole, $this->staffId);
            return $this->jsonResponse(['status' => 'success', 'data' => $users]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    
    public function getUser($userId = null)
    {
        try {
            $user = $this->userService->getUser($userId, $this->userRole);
            return $this->jsonResponse(['status' => 'success', 'data' => $user]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }
    
    public function getAvailableStaffAPI()
    {
        try {
            $staff = $this->userService->getAvailableStaff($this->userRole);
            return $this->jsonResponse(['status' => 'success', 'data' => $staff]);
        } catch (\Exception $e) {
            return $this->jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // Helper Methods
    private function getPageTitle($userRole)
    {
        return match($userRole) {
            'admin' => 'User Management',
            'it_staff' => 'System User Management',
            'doctor' => 'Department Users',
            default => 'User Directory',
        };
    }
    
    private function getRedirectUrl($userRole)
    {
        return match($userRole) {
            'admin' => 'admin/user-management',
            'it_staff' => 'it-staff/users',
            'doctor' => 'doctor/users',
            default => 'admin/user-management',
        };
    }
    
    private function getUserPermissions($userRole)
    {
        return [
            'canCreate' => PermissionManager::hasPermission($userRole, 'users', 'create'),
            'canEdit' => PermissionManager::hasPermission($userRole, 'users', 'edit'),
            'canDelete' => PermissionManager::hasPermission($userRole, 'users', 'delete'),
            'canView' => PermissionManager::hasPermission($userRole, 'users', 'view'),
            'canResetPassword' => PermissionManager::hasPermission($userRole, 'users', 'edit'),
            'canViewAll' => in_array($userRole, ['admin', 'it_staff']),
        ];
    }

    /**
     * Helper: Check if request is AJAX
     */
    private function isAjaxRequest()
    {
        return $this->request->isAJAX() || 
               $this->request->getHeaderLine('Accept') === 'application/json' ||
               $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Helper: Return JSON response with status code
     */
    private function jsonResponse($data, $statusCode = 200)
    {
        return $this->response->setStatusCode($statusCode)->setJSON($data);
    }

    /**
     * Helper: Handle successful response (AJAX or redirect)
     */
    private function handleResponse($result, $action = '')
    {
        if ($this->isAjaxRequest()) {
            return $this->jsonResponse($result);
        }
        session()->setFlashdata('success', $result['message']);
        return redirect()->to($this->getRedirectUrl($this->userRole));
    }

    /**
     * Helper: Handle error response (AJAX or redirect)
     */
    private function handleError(\Exception $e, $action = '')
    {
        if ($this->isAjaxRequest()) {
            return $this->jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
        session()->setFlashdata('error', $e->getMessage());
        return $action === 'create' ? redirect()->back()->withInput() : redirect()->to($this->getRedirectUrl($this->userRole));
    }
}