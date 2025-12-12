<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRolesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'role_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => false,
            ],
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => false,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'inactive'],
                'default'    => 'active',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('role_id', true);
        $this->forge->addKey('slug', false, true); // unique
        $this->forge->createTable('roles');

        // Seed default roles (optional but very useful)
        $this->db->table('roles')->insertBatch([
            [
                'name'        => 'Administrator',
                'slug'        => 'admin',
                'description' => 'System administrator',
                'status'      => 'active',
            ],
            [
                'name'        => 'Doctor',
                'slug'        => 'doctor',
                'description' => 'Medical doctor',
                'status'      => 'active',
            ],
            [
                'name'        => 'Nurse',
                'slug'        => 'nurse',
                'description' => 'Nursing staff',
                'status'      => 'active',
            ],
            [
                'name'        => 'Receptionist',
                'slug'        => 'receptionist',
                'description' => 'Front desk staff',
                'status'      => 'active',
            ],
            [
                'name'        => 'Pharmacist',
                'slug'        => 'pharmacist',
                'description' => 'Pharmacy staff',
                'status'      => 'active',
            ],
            [
                'name'        => 'Accountant',
                'slug'        => 'accountant',
                'description' => 'Finance staff',
                'status'      => 'active',
            ],
            [
                'name'        => 'IT Staff',
                'slug'        => 'it_staff',
                'description' => 'IT support staff',
                'status'      => 'active',
            ],
            [
                'name'        => 'Laboratorist',
                'slug'        => 'laboratorist',
                'description' => 'Lab staff',
                'status'      => 'active',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('roles');
    }
}