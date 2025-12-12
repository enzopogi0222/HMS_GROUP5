<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDepartmentIdToStaffV2 extends Migration
{
    public function up()
    {
        // Add department_id if missing
        if (!$this->db->fieldExists('department_id', 'staff')) {
            $this->forge->addColumn('staff', [
                'department_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'employee_id',
                ],
            ]);
        }

        // Add foreign key if possible and not already present
        // We can't easily detect existing FK name generically; attempt with a known name
        $fkAdded = false;
        if ($this->db->tableExists('department')) {
            $this->forge->addForeignKey('department_id', 'department', 'department_id', 'SET NULL', 'CASCADE');
            $fkAdded = true;
        } elseif ($this->db->tableExists('deaprtment')) {
            $this->forge->addForeignKey('department_id', 'deaprtment', 'department_id', 'SET NULL', 'CASCADE');
            $fkAdded = true;
        } elseif ($this->db->tableExists('departments')) {
            $this->forge->addForeignKey('department_id', 'departments', 'department_id', 'SET NULL', 'CASCADE');
            $fkAdded = true;
        }
        // When using addForeignKey after table creation, we must call processIndexes to apply
        if ($fkAdded) {
            $this->forge->processIndexes('staff');
        }
    }

    public function down()
    {
        // Drop FK if it exists, then column
        // Try each possible FK name
        // We don't know the auto-generated FK name; attempt common names, ignore errors
        try { $this->forge->dropForeignKey('staff', 'fk_staff_department'); } catch (\Throwable $e) {}
        try { $this->forge->dropForeignKey('staff', 'fk_staff_deaprtment'); } catch (\Throwable $e) {}
        try { $this->forge->dropForeignKey('staff', 'fk_staff_departments'); } catch (\Throwable $e) {}

        if ($this->db->fieldExists('department_id', 'staff')) {
            $this->forge->dropColumn('staff', 'department_id');
        }
    }
}
