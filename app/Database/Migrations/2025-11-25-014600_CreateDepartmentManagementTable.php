<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDepartmentManagementTable extends Migration
{
    protected string $table = 'department';

    public function up()
    {
        $this->forge->addField([
            'department_id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
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
                'unique'     => true,
            ],
            'type' => [
                'type' => "ENUM('Clinical','Administrative','Emergency','Diagnostic')",
                'null' => false,
                'default' => 'Clinical',
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
                'type' => "ENUM('Active','Inactive')",
                'null' => false,
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

        $this->forge->addKey('department_id', true);
        $this->forge->addKey('name');
        $this->forge->addKey('type');
        $this->forge->addKey('status');

        $this->forge->createTable($this->table, true, ['ENGINE' => 'InnoDB']);

        // Ensure department_head_id column exists even if table pre-existed
        $db = \Config\Database::connect();
        $table = $this->table;

        if ($db->tableExists($table) && !$db->fieldExists('department_head_id', $table)) {
            $this->forge->addColumn($table, [
                'department_head_id' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'null'     => true,
                ],
            ]);
        }

        // Add foreign key after table creation only if both column and staff table exist
        if ($db->tableExists('staff') && $db->fieldExists('department_head_id', $table)) {
            $this->db->query(
                'ALTER TABLE ' . $this->db->escapeString($table) .
                ' ADD CONSTRAINT fk_department_head_staff
                  FOREIGN KEY (department_head_id) REFERENCES staff(staff_id)
                  ON UPDATE CASCADE ON DELETE SET NULL'
            );
        }

        // Set timestamp defaults after table creation
        $this->db->query('ALTER TABLE ' . $this->db->escapeString($this->table) . ' MODIFY created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
        $this->db->query('ALTER TABLE ' . $this->db->escapeString($this->table) . ' MODIFY updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }

    public function down()
    {
        // Drop FK if it exists to avoid errors
        try {
            $this->db->query('ALTER TABLE ' . $this->db->escapeString($this->table) . ' DROP FOREIGN KEY fk_department_head_staff');
        } catch (\Throwable $e) {
            // Ignore if the constraint does not exist
        }

        $this->forge->dropTable($this->table, true);
    }
}
