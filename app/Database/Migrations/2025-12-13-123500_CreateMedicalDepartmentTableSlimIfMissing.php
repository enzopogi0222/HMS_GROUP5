<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMedicalDepartmentTableSlimIfMissing extends Migration
{
    protected string $table = 'medical_department';

    public function up()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists($this->table)) {
            return;
        }

        $this->forge->addField([
            'medical_department_id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'department_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
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

        $this->forge->addKey('medical_department_id', true);
        $this->forge->addKey('department_id', false, true);

        $this->forge->createTable($this->table, true, ['ENGINE' => 'InnoDB']);

        if ($db->tableExists('department')) {
            try {
                $this->db->query(
                    'ALTER TABLE ' . $this->db->escapeString($this->table) .
                    ' ADD CONSTRAINT fk_medical_department_department'
                    . ' FOREIGN KEY (department_id) REFERENCES department(department_id)'
                    . ' ON UPDATE CASCADE ON DELETE CASCADE'
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
            $this->db->query('ALTER TABLE ' . $this->db->escapeString($this->table) . ' DROP FOREIGN KEY fk_medical_department_department');
        } catch (\Throwable $e) {
        }

        $this->forge->dropTable($this->table, true);
    }
}
