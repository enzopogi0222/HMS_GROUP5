<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterUsersAddRoleId extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // Check if column already exists
        $fields = $db->getFieldNames('users');

        // If role_id is already present, skip this migration body entirely to avoid
        // duplicate column errors when rerunning migrations against an existing DB.
        if (in_array('role_id', $fields)) {
            return;
        }

        // 1) Add role_id column only if it does NOT exist
        $this->forge->addColumn('users', [
            'role_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true, // allow null during migration
                'after'      => 'role', // put it after your existing ENUM
            ],
        ]);

        // 2) Backfill role_id based on existing ENUM field `role` and roles.slug
        // This assumes your roles table slugs: admin, doctor, nurse, receptionist, pharmacist,
        // accountant, it_staff, laboratorist
        $sql = "
            UPDATE users u
            JOIN roles r ON r.slug = u.role
            SET u.role_id = r.role_id
        ";
        $db->query($sql);

        // 3) (Optional) Later you can make role_id NOT NULL in another migration
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'role_id');
    }
}