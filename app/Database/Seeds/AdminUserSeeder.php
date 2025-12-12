<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        // Resolve shared IDs from reference tables
        $adminDeptId  = $this->getDepartmentId($db, 'Administration');
        $nurseDeptId  = $this->getDepartmentId($db, 'Nursing');
        $receptDeptId = $this->getDepartmentId($db, 'Reception');

        $adminRoleId  = $this->getRoleId($db, 'admin');
        $nurseRoleId  = $this->getRoleId($db, 'nurse');
        $receptRoleId = $this->getRoleId($db, 'receptionist');

        // 1) Ensure an Admin staff exists
        $staff = $db->table('staff')->where('email', 'admin@hospital.com')->get()->getRowArray();

        if (!$staff) {
            $db->table('staff')->insert([
                'employee_id' => 'EMP001',
                'department_id' => $adminDeptId,
                'first_name'  => 'Admin',
                'last_name'   => 'User',
                'gender'      => 'male',
                'dob'         => '1980-01-01',
                'contact_no'  => '1234567890',
                'email'       => 'admin@hospital.com',
                'address'     => 'Hospital Address',
                'role_id'     => $adminRoleId,
                'date_joined' => date('Y-m-d'),
            ]);

            $staffId = (int) $db->insertID();
        } else {
            $staffId = (int) $staff['staff_id'];
        }

        // 2) Upsert the admin user
        $existing = $db->table('users')->where('username', 'admin')->get()->getRowArray();

        $userData = [
            'staff_id'   => $staffId,
            'email'      => 'admin@hospital.com',
            'first_name' => 'Admin',
            'last_name'  => 'User',
            'username'   => 'admin',
            'password'   => password_hash('admin123', PASSWORD_DEFAULT),
            'role_id'    => $adminRoleId,
            'status'     => 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $db->table('users')->where('username', 'admin')->update($userData);
        } else {
            $db->table('users')->insert($userData);
        }

        // 3) Create Nurse staff and user accounts
        $this->createNurseAccounts($db);

        // 4) Create Receptionist staff and user account
        $this->createReceptionistAccount($db);
    }

    private function createNurseAccounts($db)
    {
        $nurseDeptId = $this->getDepartmentId($db, 'Nursing');
        $nurseRoleId = $this->getRoleId($db, 'nurse');

        // Create Senior Nurse staff
        $nurseStaff = $db->table('staff')->where('email', 'nurse.senior@hospital.com')->get()->getRowArray();

        if (!$nurseStaff) {
            $db->table('staff')->insert([
                'employee_id' => 'NUR001',
                'department_id' => $nurseDeptId,
                'first_name'  => 'Sarah',
                'last_name'   => 'Johnson',
                'gender'      => 'female',
                'dob'         => '1985-03-15',
                'contact_no'  => '2345678901',
                'email'       => 'nurse.senior@hospital.com',
                'address'     => 'Hospital Staff Quarters',
                'role_id'     => $nurseRoleId,
                'date_joined' => date('Y-m-d'),
            ]);

            $nurseStaffId = (int) $db->insertID();
        } else {
            $nurseStaffId = (int) $nurseStaff['staff_id'];
        }

        // Create Senior Nurse user
        $existingNurse = $db->table('users')->where('username', 'nurse')->get()->getRowArray();

        $nurseUserData = [
            'staff_id'   => $nurseStaffId,
            'email'      => 'nurse.senior@hospital.com',
            'first_name' => 'Sarah',
            'last_name'  => 'Johnson',
            'username'   => 'nurse',
            'password'   => password_hash('nurse123', PASSWORD_DEFAULT),
            'role_id'    => $nurseRoleId,
            'status'     => 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if ($existingNurse) {
            $db->table('users')->where('username', 'nurse')->update($nurseUserData);
        } else {
            $db->table('users')->insert($nurseUserData);
        }

        // Create Junior Nurse staff
        $juniorNurseStaff = $db->table('staff')->where('email', 'nurse.junior@hospital.com')->get()->getRowArray();

        if (!$juniorNurseStaff) {
            $db->table('staff')->insert([
                'employee_id' => 'NUR002',
                'department_id' => $nurseDeptId,
                'first_name'  => 'Michael',
                'last_name'   => 'Chen',
                'gender'      => 'male',
                'dob'         => '1990-07-22',
                'contact_no'  => '3456789012',
                'email'       => 'nurse.junior@hospital.com',
                'address'     => 'Hospital Staff Quarters',
                'role_id'     => $nurseRoleId,
                'date_joined' => date('Y-m-d'),
            ]);

            $juniorNurseStaffId = (int) $db->insertID();
        } else {
            $juniorNurseStaffId = (int) $juniorNurseStaff['staff_id'];
        }

        // Create Junior Nurse user
        $existingJuniorNurse = $db->table('users')->where('username', 'nurse2')->get()->getRowArray();

        $juniorNurseUserData = [
            'staff_id'   => $juniorNurseStaffId,
            'email'      => 'nurse.junior@hospital.com',
            'first_name' => 'Michael',
            'last_name'  => 'Chen',
            'username'   => 'nurse2',
            'password'   => password_hash('nurse123', PASSWORD_DEFAULT),
            'role_id'    => $nurseRoleId,
            'status'     => 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if ($existingJuniorNurse) {
            $db->table('users')->where('username', 'nurse2')->update($juniorNurseUserData);
        } else {
            $db->table('users')->insert($juniorNurseUserData);
        }
    }

    private function createReceptionistAccount($db)
    {
        $receptDeptId = $this->getDepartmentId($db, 'Reception');
        $receptRoleId = $this->getRoleId($db, 'receptionist');

        // Create Receptionist staff
        $receptionistStaff = $db->table('staff')->where('email', 'receptionist@hospital.com')->get()->getRowArray();

        if (!$receptionistStaff) {
            $db->table('staff')->insert([
                'employee_id' => 'REC001',
                'department_id' => $receptDeptId,
                'first_name'  => 'Maria',
                'last_name'   => 'Santos',
                'gender'      => 'female',
                'dob'         => '1990-05-15',
                'contact_no'  => '09123456789',
                'email'       => 'receptionist@hospital.com',
                'address'     => 'Hospital Reception',
                'role_id'     => $receptRoleId,
                'date_joined' => date('Y-m-d'),
            ]);

            $receptionistStaffId = (int) $db->insertID();
        } else {
            $receptionistStaffId = (int) $receptionistStaff['staff_id'];
        }

        // Create Receptionist user
        $existingReceptionist = $db->table('users')->where('username', 'receptionist')->get()->getRowArray();

        $receptionistUserData = [
            'staff_id'   => $receptionistStaffId,
            'email'      => 'receptionist@hospital.com',
            'first_name' => 'Maria',
            'last_name'  => 'Santos',
            'username'   => 'receptionist',
            'password'   => password_hash('receptionist123', PASSWORD_DEFAULT),
            'role_id'    => $receptRoleId,
            'status'     => 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if ($existingReceptionist) {
            $db->table('users')->where('username', 'receptionist')->update($receptionistUserData);
        } else {
            $db->table('users')->insert($receptionistUserData);
        }
    }

    private function getDepartmentId($db, string $name): ?int
    {
        $row = $db->table('department')->select('department_id')->where('name', $name)->get()->getRowArray();
        return $row ? (int) $row['department_id'] : null;
    }

    private function getRoleId($db, string $slug): ?int
    {
        $row = $db->table('roles')->select('role_id')->where('slug', $slug)->get()->getRowArray();
        return $row ? (int) $row['role_id'] : null;
    }
}
