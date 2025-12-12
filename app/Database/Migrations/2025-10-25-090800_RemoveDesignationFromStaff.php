<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveDesignationFromStaff extends Migration
{
    public function up()
    {
        // Drop the 'designation' column if it exists
        if ($this->db->fieldExists('designation', 'staff')) {
            $this->forge->dropColumn('staff', 'designation');
        }
    }

    public function down()
    {
        // Recreate the 'designation' column if it was removed
        if (!$this->db->fieldExists('designation', 'staff')) {
            $this->forge->addColumn('staff', [
                'designation' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'after'      => 'department',
                ],
            ]);
        }
    }
}
