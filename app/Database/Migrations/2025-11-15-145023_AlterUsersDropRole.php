<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterUsersDropRole extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        $fields = $db->getFieldNames('users');

        // Only proceed if the role column exists
        if (in_array('role', $fields)) {
            // If role_id exists, it's safe to drop the role column
            if (in_array('role_id', $fields)) {
                $this->forge->dropColumn('users', 'role');
            }
            // If role_id doesn't exist, we'll skip dropping the role column
            // This handles the case where this migration was already run partially
        }
    }

    public function down()
    {
        // Recreate role ENUM if you ever rollback.
        // Adjust the ENUM values if needed.
        $this->forge->addColumn('users', [
            'role' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'admin',
                    'doctor',
                    'nurse',
                    'receptionist',
                    'pharmacist',
                    'accountant',
                    'it_staff',
                    'laboratorist',
                ],
                'default'    => 'admin',
                'after'      => 'password',
            ],
        ]);
    }
}