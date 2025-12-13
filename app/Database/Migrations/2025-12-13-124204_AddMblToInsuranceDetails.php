<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMblToInsuranceDetails extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        
        // Check if insurance_details table exists
        if (!$db->tableExists('insurance_details')) {
            echo "Warning: 'insurance_details' table does not exist. Skipping migration.\n";
            return;
        }

        // Check if mbl column already exists
        if ($db->fieldExists('mbl', 'insurance_details')) {
            echo "Column 'mbl' already exists in 'insurance_details' table. Skipping.\n";
            return;
        }

        // Add the mbl column
        $fields = [
            'mbl' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'null'       => true,
                'after'      => 'coverage_type',
            ],
        ];

        $this->forge->addColumn('insurance_details', $fields);
    }

    public function down()
    {
        $db = \Config\Database::connect();
        
        // Check if insurance_details table exists
        if (!$db->tableExists('insurance_details')) {
            return;
        }

        // Check if mbl column exists before dropping
        if ($db->fieldExists('mbl', 'insurance_details')) {
            $this->forge->dropColumn('insurance_details', ['mbl']);
        }
    }
}
