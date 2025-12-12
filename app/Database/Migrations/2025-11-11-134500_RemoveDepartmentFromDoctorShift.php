<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveDepartmentFromDoctorShift extends Migration
{
    public function up()
    {
        // Check if the department column exists before dropping it
        if ($this->db->fieldExists('department', 'doctor_shift')) {
            $this->forge->dropColumn('doctor_shift', 'department');
        }
    }

    public function down()
    {
        // Add the department column back if rolling back
        $this->forge->addColumn('doctor_shift', [
            'department' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'doctor_id'
            ]
        ]);
    }
}
