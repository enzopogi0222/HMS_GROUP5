<?php

namespace App\Services;

class StaffService
{
    protected $db;
    // Cache table columns to avoid repeated metadata lookups
    private $tableColumnsCache = [];

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Build base staff query with all joins
     */
    private function buildStaffQuery($includeId = false)
    {
        $select = 's.*, s.department_id, dpt.name as department, CONCAT(s.first_name, " ", s.last_name) as full_name, TIMESTAMPDIFF(YEAR, s.dob, CURDATE()) as age, DATE_FORMAT(s.date_joined, "%M %d, %Y") as formatted_date_joined, d.specialization as doctor_specialization, d.license_no as doctor_license_no, n.license_no as nurse_license_no, p.license_no as pharmacist_license_no, l.license_no as laboratorist_license_no, l.specialization as laboratorist_specialization, a.license_no as accountant_license_no, r.desk_no as receptionist_desk_no, i.expertise as it_expertise, s.role_id, rl.slug as role_slug, rl.name as role_name';
        if ($includeId) $select = 's.staff_id as id, ' . $select;
        
        return $this->db->table('staff s')->select($select)
            ->join('department dpt', 'dpt.department_id = s.department_id', 'left')
            ->join('doctor d', 'd.staff_id = s.staff_id', 'left')
            ->join('nurse n', 'n.staff_id = s.staff_id', 'left')
            ->join('pharmacist p', 'p.staff_id = s.staff_id', 'left')
            ->join('laboratorist l', 'l.staff_id = s.staff_id', 'left')
            ->join('accountant a', 'a.staff_id = s.staff_id', 'left')
            ->join('receptionist r', 'r.staff_id = s.staff_id', 'left')
            ->join('it_staff i', 'i.staff_id = s.staff_id', 'left')
            ->join('roles rl', 'rl.role_id = s.role_id', 'left');
    }

    /**
     * Get staff with role-based filtering
     */
    public function getStaffByRole($userRole, $staffId = null, $filters = [])
    {
        try {
            $builder = $this->buildStaffQuery(true);

            // Role-based filtering
            if (in_array($userRole, ['admin', 'it_staff'])) {
                // Admin and IT staff can see all staff
            } elseif (in_array($userRole, ['doctor', 'nurse'])) {
                $info = $this->db->table('staff')->where('staff_id', $staffId)->get()->getRowArray();
                if ($info && !empty($info['department_id'])) {
                    $builder->where('s.department_id', $info['department_id']);
                }
            } elseif ($userRole === 'receptionist') {
                $builder->whereIn('rl.slug', ['doctor', 'nurse']);
            } else {
                $builder->where('s.staff_id', $staffId);
            }

            // Apply additional filters
            if (isset($filters['department'])) {
                // Filter by department name via join
                $builder->where('dpt.name', $filters['department']);
            }
            
            if (!empty($filters['role'])) {
                // Filter by role slug via roles table
                $builder->where('rl.slug', $filters['role']);
            }
            
            if (isset($filters['status'])) {
                $builder->where('s.status', $filters['status']);
            }

            if (isset($filters['search'])) {
                $search = $filters['search'];
                $builder->groupStart()
                    ->like('s.first_name', $search)
                    ->orLike('s.last_name', $search)
                    ->orLike('s.employee_id', $search)
                    ->orLike('s.email', $search)
                    ->orLike('dpt.name', $search)
                    ->groupEnd();
            }

            $staff = $builder->orderBy('s.first_name', 'ASC')
                ->get()
                ->getResultArray();

            // Normalize role field for frontend
            $staff = array_map(function ($row) {
                if (empty($row['role'])) {
                    $row['role'] = $row['role_slug'] ?? ($row['role_name'] ?? null);
                }
                return $row;
            }, $staff);

            return ['success' => true, 'data' => $staff];
        } catch (\Throwable $e) {
            log_message('error', 'Error fetching staff: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to fetch staff', 'data' => []];
        }
    }

    /**
     * Generate next employee_id for a given role slug.
     * Examples: ADM-0001, DOC-0001, NUR-0001, REC-0001
     */
    private function generateEmployeeIdForRole(string $roleSlug): string
    {
        // Map role slug to prefix
        $prefixMap = [
            'admin'        => 'ADM',
            'doctor'       => 'DOC',
            'nurse'        => 'NUR',
            'receptionist' => 'REC',
            'pharmacist'   => 'PHA',
            'laboratorist' => 'LAB',
            'it_staff'     => 'IT',
            'accountant'   => 'ACC',
        ];

        if (!isset($prefixMap[$roleSlug])) {
            throw new \Exception('Unsupported role for employee ID generation: ' . $roleSlug);
        }

        $prefix = $prefixMap[$roleSlug];

        // Find the current maximum sequence for this prefix
        $row = $this->db->table('staff')
            ->select('employee_id')
            ->like('employee_id', $prefix . '-', 'after')
            ->orderBy('employee_id', 'DESC')
            ->get()
            ->getRowArray();

        $nextNumber = 1;

        if ($row && !empty($row['employee_id'])) {
            // Expect format PREFIX-XXXX
            $parts = explode('-', $row['employee_id']);
            if (count($parts) === 2 && is_numeric($parts[1])) {
                $nextNumber = (int) $parts[1] + 1;
            }
        }

        // Pad with 4 digits: 0001, 0002, ...
        $numberPart = str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);

        return $prefix . '-' . $numberPart;
    }

    /**
     * Public helper so controllers can fetch the next employee ID for a role.
     */
    public function getNextEmployeeIdForRole(string $roleSlug): string
    {
        return $this->generateEmployeeIdForRole($roleSlug);
    }

    /**
     * Get single staff member by ID
     */
    public function getStaff($id)
    {
        try {
            $staff = $this->buildStaffQuery()->where('s.staff_id', $id)->get()->getRowArray();

            if (!$staff) {
                return ['success' => false, 'message' => 'Staff member not found'];
            }

            $staff['id'] = $staff['staff_id'];
            return ['success' => true, 'staff' => $staff];
        } catch (\Throwable $e) {
            log_message('error', 'Error fetching staff member: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error'];
        }
    }

    /**
     * Create new staff member
     */
    public function createStaff($input, $userRole)
    {
    $input = $this->normalizeInput($input);

    $dobCheck = $this->validateDobAndAge($input['dob'] ?? null, 18, 100);
    if (!$dobCheck['valid']) {
        return ['success' => false, 'message' => 'Validation failed', 'errors' => ['dob' => $dobCheck['error']]];
    }

    if (empty($input['employee_id']) && !empty($input['role'])) {
        try {
            $input['employee_id'] = $this->generateEmployeeIdForRole($input['role']);
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Failed to generate employee ID: ' . $e->getMessage()];
        }
    }

    $validation = \Config\Services::validation();
    $validation->setRules(
        $this->getValidationRules($input['role'] ?? 'staff'),
        $this->getValidationMessages()
    );
    if (!$validation->run($input)) {
        return ['success' => false, 'message' => 'Validation failed', 'errors' => $validation->getErrors()];
    }

    if ($this->db->table('staff')->where('employee_id', $input['employee_id'])->get()->getRowArray()) {
        return ['success' => false, 'message' => 'Employee ID already exists'];
    }

    try {
        if (!empty($input['department'])) {
            $this->ensureDepartmentExists($input['department']);
            $deptRow = $this->db->table('department')->where('name', $input['department'])->get()->getRowArray();
            $input['department_id'] = $deptRow['department_id'] ?? null;
        } else {
            $input['department_id'] = null;
        }

        $staffData = $this->filterToExistingColumns('staff', $this->prepareStaffData($input));
        $this->db->table('staff')->insert($staffData);

        $dbError = $this->db->error();
        if (!empty($dbError['code'])) {
            log_message('error', 'StaffService::createStaff DB error: ' . json_encode($dbError) . ' data=' . json_encode($staffData));
            return ['success' => false, 'message' => $dbError['message'] ?? 'Failed to create staff member', 'errors' => $dbError];
        }

        $staffId = $this->db->insertID();
        if (!$staffId) {
            log_message('error', 'StaffService::createStaff insertID is zero; data=' . json_encode($staffData));
            return ['success' => false, 'message' => 'Failed to create staff member'];
        }

        $this->insertRoleSpecificData($input['role'], $staffId, $input);
        return ['success' => true, 'message' => 'Staff member created successfully', 'id' => $staffId];
    } catch (\Throwable $e) {
        log_message('error', 'Failed to create staff: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

    /**
     * Update staff member
     */
    public function updateStaff($id, $input, $userRole)
    {
        if (!$this->canEditStaff($id, $userRole)) {
            return ['success' => false, 'message' => 'Permission denied'];
        }
        $input = $this->normalizeInput($input);

        $existingStaff = $this->getStaff($id);
        if (empty($existingStaff['success']) || empty($existingStaff['staff']) || !is_array($existingStaff['staff'])) {
            return $existingStaff;
        }
        $existing = $existingStaff['staff'];
        $input['role'] = $input['role'] ?? ($existing['role'] ?? ($existing['role_slug'] ?? null));
        $input['employee_id'] = $input['employee_id'] ?? ($existing['employee_id'] ?? null);

        // Handle department and department_id
        if (!empty($input['department'])) {
            $this->ensureDepartmentExists($input['department']);
            $deptRow = $this->db->table('department')->where('name', $input['department'])->get()->getRowArray();
            $input['department_id'] = $deptRow['department_id'] ?? ($existing['department_id'] ?? null);
        } elseif (!isset($input['department_id'])) {
            $input['department_id'] = $existing['department_id'] ?? null;
        }

        $validation = \Config\Services::validation();
        $validation->setRules(
            $this->getUpdateValidationRules($input['role'] ?? 'staff', $id),
            $this->getValidationMessages()
        );
        if (!$validation->run($input)) {
            return ['success' => false, 'message' => 'Validation failed', 'errors' => $validation->getErrors()];
        }

        try {
            $staffData = $this->filterToExistingColumns('staff', $this->prepareStaffData($input));
            if (in_array('updated_at', $this->getTableColumns('staff'), true)) {
                $staffData['updated_at'] = date('Y-m-d H:i:s');
            }
            $this->db->table('staff')->where('staff_id', $id)->update($staffData);

            // Keep linked user role in sync with staff role
            $roleId = $staffData['role_id'] ?? null;
            if (empty($roleId) && !empty($input['role'])) {
                $roleRow = $this->db->table('roles')->where('slug', $input['role'])->get()->getRowArray();
                $roleId = $roleRow['role_id'] ?? null;
            }
            if (!empty($roleId)) {
                $this->db->table('users')->where('staff_id', $id)->update(['role_id' => $roleId]);
            }

            return ['success' => true, 'message' => 'Staff member updated successfully'];
        } catch (\Throwable $e) {
            log_message('error', 'Failed to update staff: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Delete staff member
     */
    public function deleteStaff($id, $userRole)
    {
        if ($userRole !== 'admin') {
            return ['success' => false, 'message' => 'Only administrators can delete staff members'];
        }

        try {
            $this->db->transStart();

            $staff = $this->getStaff($id);
            if (!empty($staff['success']) && !empty($staff['staff']) && is_array($staff['staff'])) {
                $role = $staff['staff']['role'] ?? ($staff['staff']['role_slug'] ?? null);
                if (!empty($role)) {
                    $this->deleteRoleSpecificData($role, $id);
                }
            }

            // Delete associated user account if it exists
            $userDeleted = $this->db->table('users')->where('staff_id', $id)->delete();
            if ($userDeleted) {
                log_message('info', "Deleted user account for staff_id: {$id}");
            }

            // Delete the staff member
            $result = $this->db->table('staff')->where('staff_id', $id)->delete();
            $this->db->transComplete();

            if ($this->db->transStatus() === false || !$result) {
                return ['success' => false, 'message' => 'Failed to delete staff member'];
            }

            $message = 'Staff member deleted successfully';
            if ($userDeleted) {
                $message .= ' (associated user account also deleted)';
            }

            return ['success' => true, 'message' => $message];
        } catch (\Throwable $e) {
            log_message('error', 'Failed to delete staff: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Get staff statistics
     */
    public function getStaffStats($userRole = null, $staffId = null)
    {
        $stats = [
            'total_staff' => 0,
            'active_staff' => 0,
            'inactive_staff' => 0,
            'doctors' => 0,
            'nurses' => 0,
            'pharmacists' => 0,
            'receptionists' => 0,
            'laboratorists' => 0,
            'accountants' => 0,
            'it_staff' => 0,
            'new_this_month' => 0,
            'department_staff' => 0,
        ];

        try {
            $builder = $this->db->table('staff');

            // Role-based filtering for stats
            if (in_array($userRole, ['nurse', 'doctor']) && $staffId) {
                $info = $this->db->table('staff')->where('staff_id', $staffId)->get()->getRowArray();
                if ($info && !empty($info['department'])) {
                    $builder->where('department', $info['department']);
                }
            }

            // Total staff
            $stats['total_staff'] = (clone $builder)->countAllResults(false);

            // Active/Inactive
            $stats['active_staff'] = (clone $builder)->where('status', 'active')->countAllResults(false);
            $stats['inactive_staff'] = (clone $builder)->where('status', 'inactive')->countAllResults(false);

            // Role counts
            foreach (['doctors' => 'doctor', 'nurses' => 'nurse', 'pharmacists' => 'pharmacist', 'receptionists' => 'receptionist', 'laboratorists' => 'laboratorist', 'accountants' => 'accountant', 'it_staff' => 'it_staff'] as $key => $role) {
                $stats[$key] = (clone $builder)->where('role', $role)->countAllResults(false);
            }

            // New this month
            $firstDayOfMonth = date('Y-m-01');
            $stats['new_this_month'] = (clone $builder)
                ->where('date_joined >=', $firstDayOfMonth)
                ->countAllResults();

            // Department-specific stats
            if (in_array($userRole, ['nurse', 'doctor']) && $staffId) {
                $userInfo = $this->db->table('staff')->where('staff_id', $staffId)->get()->getRowArray();
                if ($userInfo && !empty($userInfo['department'])) {
                    $stats['department_staff'] = $this->db->table('staff')->where('department', $userInfo['department'])->countAllResults();
                }
            }

        } catch (\Throwable $e) {
            log_message('error', 'Staff stats error: ' . $e->getMessage());
        }

        return $stats;
    }

    // Private helper methods

    private function canCreateStaff($userRole)
    {
        return in_array($userRole, ['admin', 'it_staff']);
    }

    private function canEditStaff($staffId, $userRole)
    {
        return in_array($userRole, ['admin', 'it_staff']);
    }

    /**
     * Normalize incoming input before validation and persistence
     */
    private function normalizeInput(array $input): array
    {
        // Map date_of_birth -> dob if provided
        if (!isset($input['dob']) && isset($input['date_of_birth'])) {
            $input['dob'] = $input['date_of_birth'];
        }

        // Coerce gender to lowercase when present
        if (isset($input['gender']) && is_string($input['gender'])) {
            $input['gender'] = strtolower(trim($input['gender']));
        }

        // Backward compatibility: map 'designation' -> 'role' if role is missing
        if (!isset($input['role']) && isset($input['designation'])) {
            $input['role'] = is_string($input['designation']) ? strtolower(trim($input['designation'])) : $input['designation'];
        }

        // Trim common string fields
        foreach (['employee_id','first_name','last_name','email','department','address'] as $k) {
            if (isset($input[$k]) && is_string($input[$k])) {
                $input[$k] = trim($input[$k]);
            }
        }

        // Normalize dates to Y-m-d if parseable
        foreach (['dob', 'date_joined'] as $dateField) {
            if (!empty($input[$dateField])) {
                $ts = strtotime($input[$dateField]);
                $input[$dateField] = $ts !== false ? date('Y-m-d', $ts) : null;
                if ($input[$dateField] === null) unset($input[$dateField]);
            }
        }

        return $input;
    }

    private function getValidationRules($role, $isUpdate = false, $excludeId = null)
    {
        $baseRules = [
            'employee_id' => $isUpdate ? 'permit_empty|min_length[3]|max_length[255]|is_unique[staff.employee_id,staff_id,' . $excludeId . ']' : 'required|min_length[3]|max_length[255]|is_unique[staff.employee_id]',
            'first_name' => 'required|min_length[2]|max_length[100]',
            'last_name' => 'permit_empty|max_length[100]',
            'gender' => 'permit_empty|in_list[male,female,other,Male,Female,Other]',
            'dob' => 'required|valid_date',
            'contact_no' => 'permit_empty|regex_match[/^09\d{9}$/]',
            'email' => 'permit_empty|valid_email',
            'address' => 'permit_empty',
            'department' => 'permit_empty|max_length[255]',
            'role' => $isUpdate ? 'permit_empty|in_list[admin,doctor,nurse,pharmacist,receptionist,laboratorist,accountant,it_staff]' : 'required|in_list[admin,doctor,nurse,pharmacist,receptionist,laboratorist,accountant,it_staff]',
            'date_joined' => 'permit_empty|valid_date',
        ];

        $roleRules = [
            'doctor' => ['doctor_specialization' => ($isUpdate ? 'permit_empty' : 'required') . '|max_length[100]', 'doctor_license_no' => 'permit_empty|max_length[50]', 'doctor_consultation_fee' => 'permit_empty|decimal'],
            'nurse' => ['nurse_license_no' => ($isUpdate ? 'permit_empty' : 'required') . '|max_length[100]'],
            'pharmacist' => ['pharmacist_license_no' => ($isUpdate ? 'permit_empty' : 'required') . '|max_length[100]'],
            'laboratorist' => ['laboratorist_license_no' => ($isUpdate ? 'permit_empty' : 'required') . '|max_length[100]', 'laboratorist_specialization' => 'permit_empty|max_length[150]', 'lab_room_no' => 'permit_empty|max_length[50]'],
            'accountant' => ['accountant_license_no' => ($isUpdate ? 'permit_empty' : 'required') . '|max_length[100]'],
            'receptionist' => ['receptionist_desk_no' => 'permit_empty|max_length[50]'],
            'it_staff' => ['it_expertise' => 'permit_empty|max_length[150]'],
        ];

        if (isset($roleRules[$role])) {
            $baseRules = array_merge($baseRules, $roleRules[$role]);
            if ($role === 'doctor' && !$isUpdate) {
                $baseRules['doctor_specialization'] = 'required|min_length[2]|max_length[100]';
            }
        }

        return $baseRules;
    }

    private function getValidationMessages(): array
    {
        return [
            'contact_no' => [
                'regex_match' => 'Contact number must start with 09 and be exactly 11 digits.',
            ],
        ];
    }

    private function getUpdateValidationRules($role, $excludeId)
    {
        return $this->getValidationRules($role, true, $excludeId);
    }

    private function prepareStaffData($input)
    {
        // Determine role_id from explicit input or from role slug
        $roleId = null;
        if (!empty($input['role_id'])) {
            $roleId = (int) $input['role_id'];
        } elseif (!empty($input['role'])) {
            $role = $this->db->table('roles')->where('slug', $input['role'])->get()->getRowArray();
            $roleId = $role['role_id'] ?? null;
        }

        return [
            'employee_id' => $input['employee_id'] ?? null, 
            'first_name' => $input['first_name'] ?? null,
            'last_name' => $input['last_name'] ?? null,
            'gender' => isset($input['gender']) ? strtolower($input['gender']) : null,
            // Accept both 'dob' and 'date_of_birth' from the form
            'dob' => $input['dob'] ?? ($input['date_of_birth'] ?? null),
            'contact_no' => $input['contact_no'] ?? null,
            'email' => $input['email'] ?? null,
            'address' => $input['address'] ?? null,
            'department' => $input['department'] ?? null,
            'department_id' => $input['department_id'] ?? null,
            'role' => $input['role'] ?? null,
            'role_id' => $roleId,
            'date_joined' => $input['date_joined'] ?? date('Y-m-d'),
            // Note: 'status' and timestamp columns are not present in current migration; do not include
        ];
    }

    private function insertRoleSpecificData($role, $staffId, $input)
    {
        try {
            $roleData = [
                'doctor' => ['table' => 'doctor', 'data' => ['specialization' => $input['doctor_specialization'] ?? $input['specialization'] ?? 'General', 'license_no' => $input['doctor_license_no'] ?? null]],
                'admin' => ['table' => 'admin', 'data' => ['username' => $input['username'] ?? ($input['email'] ?? $input['employee_id'] ?? ('admin_' . $staffId)), 'password' => password_hash($input['password'] ?? ($input['employee_id'] ?? ('Adm' . $staffId . '!')), PASSWORD_DEFAULT)]],
                'nurse' => ['table' => 'nurse', 'data' => ['license_no' => $input['nurse_license_no'] ?? null, 'specialization' => $input['nurse_specialization'] ?? null]],
                'pharmacist' => ['table' => 'pharmacist', 'data' => ['license_no' => $input['pharmacist_license_no'] ?? null, 'specialization' => $input['pharmacist_specialization'] ?? null]],
                'laboratorist' => ['table' => 'laboratorist', 'data' => ['license_no' => $input['laboratorist_license_no'], 'specialization' => $input['laboratorist_specialization'] ?? null, 'lab_room_no' => $input['lab_room_no'] ?? null]],
                'accountant' => ['table' => 'accountant', 'data' => ['license_no' => $input['accountant_license_no'] ?? null]],
                'receptionist' => ['table' => 'receptionist', 'data' => ['desk_no' => $input['receptionist_desk_no'] ?? null]],
                'it_staff' => ['table' => 'it_staff', 'data' => ['expertise' => $input['it_expertise'] ?? null]],
            ];

            if (isset($roleData[$role])) {
                $this->db->table($roleData[$role]['table'])->insert(array_merge(['staff_id' => $staffId], $roleData[$role]['data']));
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Role-specific insert skipped for staff_id ' . $staffId . ': ' . $e->getMessage());
        }
    }

    private function deleteRoleSpecificData($role, $staffId)
    {
        $tables = ['doctor', 'nurse', 'pharmacist', 'laboratorist', 'accountant', 'receptionist', 'it_staff', 'admin'];
        if (in_array($role, $tables)) {
            $this->db->table($role === 'it_staff' ? 'it_staff' : $role)->where('staff_id', $staffId)->delete();
        }
    }

    private function ensureDepartmentExists($name)
    {
        $dept = trim((string)$name);
        if ($dept === '') {
            return;
        }
        $exists = $this->db->table('department')->where('name', $dept)->get()->getRowArray();
        if (!$exists) {
            $this->db->table('department')->insert([
                'name' => $dept,
            ]);
        }
    }

    /**
     * Get list of columns for a table, with in-memory cache
     */
    private function getTableColumns(string $table): array
    {
        if (isset($this->tableColumnsCache[$table])) {
            return $this->tableColumnsCache[$table];
        }

        $columns = [];
        try {
            if (method_exists($this->db, 'getFieldNames')) {
                $columns = $this->db->getFieldNames($table) ?? [];
            }
            if (empty($columns) && method_exists($this->db, 'getFieldData')) {
                $fields = $this->db->getFieldData($table) ?? [];
                foreach ($fields as $f) {
                    if (is_object($f) && isset($f->name)) {
                        $columns[] = $f->name;
                    }
                }
            }
        } catch (\Throwable $e) {
            // If metadata lookup fails, default to no columns
            $columns = [];
        }

        return $this->tableColumnsCache[$table] = $columns;
    }

    /**
     * Filter associative array to keys that match actual table columns
     */
    private function filterToExistingColumns(string $table, array $data): array
    {
        $columns = $this->getTableColumns($table);
        if (empty($columns)) {
            return $data; // If we cannot detect columns, fail open to avoid dropping data silently
        }
        return array_intersect_key($data, array_flip($columns));
    }
    private function validateDobAndAge(?string $dobString, int $minAge = 18, int $maxAge = 100): array
    {
        if (empty($dobString)) {
            return ['valid' => false, 'error' => 'Date of birth is required'];
        }
        try {
            $dob = new \DateTime($dobString);
            $today = new \DateTime('today');
            if ($dob > $today) {
                return ['valid' => false, 'error' => 'Date of birth cannot be in the future'];
            }
            $age = $dob->diff($today)->y;
            if ($age < $minAge) {
                return ['valid' => false, 'error' => 'Staff member must be at least ' . $minAge . ' years old'];
            }
            if ($age > $maxAge) {
                return ['valid' => false, 'error' => 'Please check the date of birth (age seems too high)'];
            }
            return ['valid' => true, 'age' => $age];
        } catch (\Throwable $e) {
            return ['valid' => false, 'error' => 'Invalid date of birth'];
        }
    }

}
