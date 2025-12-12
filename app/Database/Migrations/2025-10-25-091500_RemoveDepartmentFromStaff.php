<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveDepartmentFromStaff extends Migration
{
    public function up()
    {
        // Drop the 'department' column if it exists
        if ($this->db->fieldExists('department', 'staff')) {
            $this->forge->dropColumn('staff', 'department');
        }
    }

    public function down()
    {
        // Recreate the 'department' column if it was removed
        if (!$this->db->fieldExists('department', 'staff')) {
            $this->forge->addColumn('staff', [
                'department' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'after'      => 'address',
                ],
            ]);
        }
    }
}
