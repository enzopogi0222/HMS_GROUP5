<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNonMedicalDepartmentTable extends Migration
{
    protected string $table = 'non_medical_department';

    public function up()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists($this->table)) {
            return;
        }

        $this->forge->addField([
            'non_medical_department_id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'department_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'type' => [
                'type'    => "ENUM('Administrative','Support')",
                'null'    => false,
                'default' => 'Administrative',
            ],
            'floor' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'department_head_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'contact_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'    => "ENUM('Active','Inactive')",
                'null'    => false,
                'default' => 'Active',
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => true,
                'default' => null,
            ],
            'updated_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => true,
                'default' => null,
            ],
        ]);

        $this->forge->addKey('non_medical_department_id', true);
        $this->forge->addKey('department_id');
        $this->forge->addKey('name');
        $this->forge->addKey('type');
        $this->forge->addKey('status');

        $this->forge->createTable($this->table, true, ['ENGINE' => 'InnoDB']);

        if ($db->tableExists('department')) {
            try {
                $this->db->query(
                    'ALTER TABLE ' . $this->db->escapeString($this->table) .
                    ' ADD CONSTRAINT fk_non_medical_department_department'
                    . ' FOREIGN KEY (department_id) REFERENCES department(department_id)'
                    . ' ON UPDATE CASCADE ON DELETE SET NULL'
                );
            } catch (\Throwable $e) {
            }
        }

        $this->db->query('ALTER TABLE ' . $this->db->escapeString($this->table) . ' MODIFY created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
        $this->db->query('ALTER TABLE ' . $this->db->escapeString($this->table) . ' MODIFY updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }

    public function down()
    {
        try {
            $this->db->query('ALTER TABLE ' . $this->db->escapeString($this->table) . ' DROP FOREIGN KEY fk_non_medical_department_department');
        } catch (\Throwable $e) {
        }

        $this->forge->dropTable($this->table, true);
    }
}
