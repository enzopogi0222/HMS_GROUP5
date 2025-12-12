<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Look up the Administrator role_id from roles table
        $role = $this->db->table('roles')
            ->where('slug', 'admin')
            ->get()
            ->getRow();

        $adminRoleId = $role ? $role->role_id : null;

        // Create a default staff record for the admin user so the FK constraint is satisfied
        $staffData = [
            'employee_id'  => 'EMP-ADMIN-001',
            'department_id'=> null,
            'first_name'   => 'System',
            'last_name'    => 'Administrator',
            'gender'       => null,
            'dob'          => null,
            'contact_no'   => null,
            'email'        => 'admin@gagni.com',
            'address'      => null,
            'date_joined'  => date('Y-m-d'),
            'role_id'      => $adminRoleId,
        ];

        $this->db->table('staff')->insert($staffData);
        $staffId = $this->db->insertID();

        $data = [
            'staff_id' => $staffId,
            'username' => 'admin',
            'email'    => 'admin@gagni.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role_id'  => $adminRoleId,
        ];

        $this->db->table('users')->insert($data);
    }
}
