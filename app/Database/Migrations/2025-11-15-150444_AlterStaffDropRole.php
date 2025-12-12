<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterStaffDropRole extends Migration
{
    public function up()
    {
        // This migration's responsibility is now ONLY to drop the legacy ENUM `role` column
        // after the new integer `role_id` column has been created by
        // 2025-11-15-145826_AlterStaffAddRoleId.

        $db     = \Config\Database::connect();
        $fields = $db->getFieldNames('staff');

        // If there is no legacy `role` column, there is nothing to do.
        if (!in_array('role', $fields)) {
            return;
        }

        // Only drop `role` when `role_id` already exists. We deliberately do NOT
        // add `role_id` here to avoid duplicate-column errors; that is handled
        // by the AlterStaffAddRoleId migration.
        if (in_array('role_id', $fields)) {
            $this->forge->dropColumn('staff', 'role');
        }
    }

    public function down()
    {
        // Recreate role ENUM if you ever rollback.
        // Adjust the ENUM values if needed.
        $this->forge->addColumn('staff', [
            'role' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'admin',
                    'doctor',
                    'nurse',
                    'pharmacist',
                    'receptionist',
                    'laboratorist',
                    'it_staff',
                    'accountant',
                ],
                'null'    => true,
                'after'   => 'department_id',
            ],
        ]);
    }
}