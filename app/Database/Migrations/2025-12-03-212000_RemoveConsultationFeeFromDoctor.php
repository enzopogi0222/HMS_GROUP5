<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveConsultationFeeFromDoctor extends Migration
{
    public function up()
    {
        // Safely drop the consultation_fee column from the doctor table
        $db = \Config\Database::connect();

        if (! $db->tableExists('doctor')) {
            return;
        }

        if ($db->fieldExists('consultation_fee', 'doctor')) {
            $this->forge->dropColumn('doctor', 'consultation_fee');
        }
    }

    public function down()
    {
        // Re-add the consultation_fee column in case of rollback
        $fields = [
            'consultation_fee' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
            ],
        ];

        $this->forge->addColumn('doctor', $fields);
    }
}
