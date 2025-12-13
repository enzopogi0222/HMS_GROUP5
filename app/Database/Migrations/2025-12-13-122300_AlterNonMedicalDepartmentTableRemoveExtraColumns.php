<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterNonMedicalDepartmentTableRemoveExtraColumns extends Migration
{
    protected string $table = 'non_medical_department';

    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists($this->table)) {
            return;
        }

        $columnsToDrop = [
            'name',
            'code',
            'type',
            'floor',
            'department_head_id',
            'contact_number',
            'description',
            'status',
        ];

        foreach ($columnsToDrop as $column) {
            if ($db->fieldExists($column, $this->table)) {
                try {
                    $this->forge->dropColumn($this->table, $column);
                } catch (\Throwable $e) {
                    // Ignore drop failures to keep migration idempotent across environments
                }
            }
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists($this->table)) {
            return;
        }

        $columnsToAdd = [];

        if (! $db->fieldExists('name', $this->table)) {
            $columnsToAdd['name'] = [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ];
        }

        if (! $db->fieldExists('code', $this->table)) {
            $columnsToAdd['code'] = [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ];
        }

        if (! $db->fieldExists('type', $this->table)) {
            $columnsToAdd['type'] = [
                'type'    => "ENUM('Administrative','Support')",
                'null'    => false,
                'default' => 'Administrative',
            ];
        }

        if (! $db->fieldExists('floor', $this->table)) {
            $columnsToAdd['floor'] = [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ];
        }

        if (! $db->fieldExists('department_head_id', $this->table)) {
            $columnsToAdd['department_head_id'] = [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ];
        }

        if (! $db->fieldExists('contact_number', $this->table)) {
            $columnsToAdd['contact_number'] = [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ];
        }

        if (! $db->fieldExists('description', $this->table)) {
            $columnsToAdd['description'] = [
                'type' => 'TEXT',
                'null' => true,
            ];
        }

        if (! $db->fieldExists('status', $this->table)) {
            $columnsToAdd['status'] = [
                'type'    => "ENUM('Active','Inactive')",
                'null'    => false,
                'default' => 'Active',
            ];
        }

        if (!empty($columnsToAdd)) {
            $this->forge->addColumn($this->table, $columnsToAdd);
        }
    }
}
