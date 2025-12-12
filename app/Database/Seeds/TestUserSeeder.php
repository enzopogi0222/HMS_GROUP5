<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TestUserSeeder extends Seeder
{
    public function run()
    {
        // First, insert a staff member
        $staffData = [
            'employee_id' => 'TEST001',
            'department_id' => null, // Set to null to avoid FK constraint
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'role' => 'admin',
            'date_joined' => date('Y-m-d'),
        ];

        $this->db->table('staff')->insert($staffData);
        $staffId = $this->db->insertID();

        // Then insert a user for that staff member
        $userData = [
            'staff_id' => $staffId,
            'username' => 'testuser',
            'email'    => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'role'     => 'admin',
            'status'   => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->db->table('users')->insert($userData);
        
        echo "Created test staff member and user\n";
    }
}
