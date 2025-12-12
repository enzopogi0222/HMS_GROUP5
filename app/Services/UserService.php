<?php

namespace App\Services;

use App\Libraries\PermissionManager;

class UserService
{
    protected $db;
    protected $userBuilder;
    protected $staffBuilder;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->userBuilder = $this->db->table('users');
        $this->staffBuilder = $this->db->table('staff');
    }

    /**
     * Build base user query with all joins
     */
    private function buildUserQuery()
    {
        return $this->db->table('users u')
            ->select('u.*, s.first_name, s.last_name, d.name as department, s.employee_id, rl.slug as role_slug, rl.name as role_name')
            ->join('staff s', 's.staff_id = u.staff_id', 'inner') // Changed to inner join to exclude orphaned users
            ->join('department d', 'd.department_id = s.department_id', 'left')
            ->join('roles rl', 'rl.role_id = u.role_id', 'left');
    }

    /**
     * Get users based on role and permissions
     */
    public function getUsersByRole($userRole, $staffId = null)
    {
        try {
            $builder = $this->buildUserQuery();

            if (in_array($userRole, ['admin', 'it_staff'])) {
                $users = $builder->where('u.status', $userRole === 'admin' ? 'active' : 'inactive')
                    ->orderBy('u.created_at', 'DESC')->get()->getResultArray();
            } elseif (in_array($userRole, ['doctor', 'nurse'])) {
                $info = $this->staffBuilder->where('staff_id', $staffId)->get()->getRowArray();
                $deptId = $info['department_id'] ?? null;
                if ($deptId) {
                    $builder->where('s.department_id', $deptId);
                    if ($userRole === 'nurse') {
                        $builder->whereIn('rl.slug', ['doctor', 'nurse']);
                    }
                    $users = $builder->orderBy('u.created_at', 'DESC')->get()->getResultArray();
                } else {
                    $users = [];
                }
            } else {
                $users = $builder->whereIn('rl.slug', ['doctor', 'receptionist'])
                    ->select('u.user_id, u.username, u.role_id, u.status, rl.slug as role_slug, rl.name as role_name, s.first_name, s.last_name, d.name as department')
                    ->orderBy('s.first_name', 'ASC')->get()->getResultArray();
            }

            return array_map(function($user) {
                $user['role'] = $user['role'] ?? ($user['role_slug'] ?? ($user['role_name'] ?? null));
                $user['full_name'] = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($user['username'] ?? 'Unknown User');
                $user['id'] = $user['user_id'] ?? null;
                return $user;
            }, $users);

        } catch (\Throwable $e) {
            log_message('error', 'UserService getUsersByRole error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Restore a previously deactivated (inactive) user
     */
    public function restoreUser($userId, $userRole)
    {
        if (!PermissionManager::hasPermission($userRole, 'users', 'edit')) {
            throw new \Exception('Insufficient permissions to restore users');
        }

        $existingUser = $this->userBuilder->where('user_id', $userId)->get()->getRowArray();
        if (!$existingUser) {
            throw new \Exception('User not found');
        }

        if (($existingUser['status'] ?? 'active') === 'active') {
            return ['status' => 'success', 'message' => 'User is already active'];
        }

        $result = $this->userBuilder->where('user_id', $userId)->update(['status' => 'active', 'updated_at' => date('Y-m-d H:i:s')]);
        if (!$result) {
            throw new \Exception('Failed to restore user');
        }

        return ['status' => 'success', 'message' => 'User restored successfully'];
    }

    /**
     * Get user statistics based on role
     */
    public function getUserStats($userRole, $staffId = null)
    {
        try {
            if (in_array($userRole, ['admin', 'it_staff'])) {
                // Count only users with valid staff records (using INNER JOIN)
                $totalUsersQuery = $this->db->table('users u')
                    ->join('staff s', 's.staff_id = u.staff_id', 'inner');
                
                $activeUsersQuery = $this->db->table('users u')
                    ->join('staff s', 's.staff_id = u.staff_id', 'inner')
                    ->where('u.status', 'active');
                
                // Count admin users by joining with roles table
                $adminUsersQuery = $this->db->table('users u')
                    ->join('staff s', 's.staff_id = u.staff_id', 'inner')
                    ->join('roles rl', 'rl.role_id = u.role_id', 'inner')
                    ->where('rl.slug', 'admin');
                
                return [
                    'total_users' => $totalUsersQuery->countAllResults(false),
                    'active_users' => $activeUsersQuery->countAllResults(false),
                    'admin_users' => $adminUsersQuery->countAllResults(false),
                    'new_users_month' => $this->db->table('users u')
                        ->join('staff s', 's.staff_id = u.staff_id', 'inner')
                        ->where('u.created_at >=', date('Y-m-01'))
                        ->countAllResults(false),
                ];
            } elseif ($userRole === 'doctor' && $staffId) {
                $doctorInfo = $this->staffBuilder->where('staff_id', $staffId)->get()->getRowArray();
                $department = $doctorInfo['department'] ?? null;
                if ($department) {
                    $deptUsers = $this->db->table('users u')->join('staff s', 's.staff_id = u.staff_id', 'inner')->where('s.department', $department);
                    return [
                        'department_users' => $deptUsers->countAllResults(false),
                        'department_active' => $deptUsers->where('u.status', 'active')->countAllResults(),
                    ];
                }
                return ['department_users' => 0, 'department_active' => 0];
            }
            
            // Default stats for other roles
            $totalUsersQuery = $this->db->table('users u')
                ->join('staff s', 's.staff_id = u.staff_id', 'inner');
            
            $activeUsersQuery = $this->db->table('users u')
                ->join('staff s', 's.staff_id = u.staff_id', 'inner')
                ->where('u.status', 'active');
            
            return [
                'total_users' => $totalUsersQuery->countAllResults(false),
                'active_users' => $activeUsersQuery->countAllResults(false),
            ];
        } catch (\Throwable $e) {
            log_message('error', 'UserService getUserStats error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Create a new user
     */
    public function createUser($data, $userRole)
    {
        if (!PermissionManager::hasPermission($userRole, 'users', 'create')) {
            throw new \Exception('Insufficient permissions to create users');
        }

        if (empty($data['staff_id']) || empty($data['username']) || empty($data['password'])) {
            throw new \Exception('Staff ID, username, and password are required');
        }

        $this->db->transStart();
        try {
            $staffId = (int) $data['staff_id'];
            $staffRow = $this->db->table('staff s')
                ->select('s.*, rl.role_id as resolved_role_id')
                ->join('roles rl', 'rl.role_id = s.role_id', 'left')
                ->where('s.staff_id', $staffId)
                ->get()->getRowArray();

            if (!$staffRow) {
                throw new \Exception('Selected staff member not found');
            }

            $roleId = null;
            if (!empty($staffRow['role_id'])) {
                $roleId = (int) $staffRow['role_id'];
            } elseif (!empty($staffRow['role'])) {
                $roleRow = $this->db->table('roles')->where('slug', $staffRow['role'])->get()->getRowArray();
                if (!$roleRow) {
                    throw new \Exception('Unable to resolve role for selected staff');
                }
                $roleId = (int) $roleRow['role_id'];
            }

            if (empty($roleId)) {
                throw new \Exception('Unable to determine role for selected staff');
            }

            if ($this->userBuilder->where('username', $data['username'])->get()->getRowArray()) {
                throw new \Exception('Username already exists');
            }

            if ($this->userBuilder->where('staff_id', $data['staff_id'])->get()->getRowArray()) {
                throw new \Exception('This staff member already has a user account');
            }

            $userData = [
                'staff_id' => $data['staff_id'],
                'username' => $data['username'],
                'email' => $data['email'] ?? null,
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'role_id' => $roleId,
                'status' => $data['status'] ?? 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if (!$this->userBuilder->insert($userData)) {
                throw new \Exception('Failed to create user');
            }

            $this->db->transComplete();
            if ($this->db->transStatus() === false) {
                throw new \Exception('Transaction failed');
            }

            return ['status' => 'success', 'message' => 'User created successfully', 'user_id' => $this->db->insertID()];
        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /**
     * Update user
     */
    public function updateUser($userId, $data, $userRole)
    {
        if (!PermissionManager::hasPermission($userRole, 'users', 'edit')) {
            throw new \Exception('Insufficient permissions to update users');
        }

        $existingUser = $this->userBuilder->where('user_id', $userId)->get()->getRowArray();
        if (!$existingUser) {
            throw new \Exception('User not found');
        }

        $updateData = [];
        foreach (['username', 'email', 'status'] as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            return ['status' => 'success', 'message' => 'No changes to update'];
        }

        if (isset($updateData['username']) && $updateData['username'] !== $existingUser['username']) {
            if ($this->userBuilder->where('username', $updateData['username'])->where('user_id !=', $userId)->get()->getRowArray()) {
                throw new \Exception('Username already exists');
            }
        }

        $updateData['updated_at'] = date('Y-m-d H:i:s');
        if (!$this->userBuilder->where('user_id', $userId)->update($updateData)) {
            throw new \Exception('Failed to update user');
        }

        return ['status' => 'success', 'message' => 'User updated successfully'];
    }

    /**
     * Delete user
     */
    public function deleteUser($userId, $userRole)
    {
        if (!PermissionManager::hasPermission($userRole, 'users', 'delete')) {
            throw new \Exception('Insufficient permissions to delete users');
        }

        $existingUser = $this->db->table('users u')
            ->select('u.*, rl.slug as role_slug')
            ->join('roles rl', 'rl.role_id = u.role_id', 'left')
            ->where('u.user_id', $userId)
            ->get()->getRowArray();

        if (!$existingUser) {
            throw new \Exception('User not found');
        }

        $existingUserRole = $existingUser['role'] ?? $existingUser['role_slug'] ?? null;
        if ($existingUserRole === 'admin' && $userRole !== 'admin') {
            throw new \Exception('Cannot delete admin users');
        }

        if ($existingUser['status'] === 'inactive') {
            return ['status' => 'success', 'message' => 'User is already inactive'];
        }

        if (!$this->userBuilder->where('user_id', $userId)->update(['status' => 'inactive', 'updated_at' => date('Y-m-d H:i:s')])) {
            throw new \Exception('Failed to deactivate user');
        }

        return ['status' => 'success', 'message' => 'User set to inactive successfully'];
    }

    /**
     * Get single user
     */
    public function getUser($userId, $userRole)
    {
        $user = $this->buildUserQuery()->where('u.user_id', $userId)->get()->getRowArray();
        if (!$user) {
            throw new \Exception('User not found');
        }

        if (!$this->canViewUser($user, $userRole)) {
            throw new \Exception('Insufficient permissions to view this user');
        }

        $user['role'] = $user['role_slug'] ?? ($user['role_name'] ?? null);
        $user['full_name'] = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        $user['id'] = $user['user_id'];
        return $user;
    }

    /**
     * Reset user password
     */
    public function resetPassword($userId, $newPassword, $userRole)
    {
        if (!PermissionManager::hasPermission($userRole, 'users', 'edit')) {
            throw new \Exception('Insufficient permissions to reset passwords');
        }

        if (!$this->userBuilder->where('user_id', $userId)->update([
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
            'updated_at' => date('Y-m-d H:i:s')
        ])) {
            throw new \Exception('Failed to reset password');
        }

        return ['status' => 'success', 'message' => 'Password reset successfully'];
    }

    /**
     * Get available staff for user creation
     */
    public function getAvailableStaff($userRole)
    {
        try {
            $staff = $this->db->table('staff s')
                ->select('s.staff_id, s.first_name, s.last_name, s.employee_id, s.email, d.name AS department, rl.slug AS role_slug, rl.name AS role_name')
                ->join('users u', 'u.staff_id = s.staff_id', 'left')
                ->join('department d', 'd.department_id = s.department_id', 'left')
                ->join('roles rl', 'rl.role_id = s.role_id', 'left')
                ->where('u.staff_id IS NULL')
                ->orderBy('s.first_name', 'ASC')
                ->get()->getResultArray();

            return array_map(function ($s) {
                $s['full_name'] = trim(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? ''));
                $s['role'] = $s['role_slug'] ?? ($s['role_name'] ?? null);
                return $s;
            }, $staff);
        } catch (\Exception $e) {
            log_message('error', 'UserService getAvailableStaff error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if user can view another user
     */
    private function canViewUser($user, $viewerRole)
    {
        return in_array($viewerRole, ['admin', 'it_staff', 'doctor', 'nurse']);
    }
}
