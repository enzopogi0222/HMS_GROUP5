<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBloodGroupToPatientsTable extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        
        // Check if patients table exists
        if (!$db->tableExists('patients')) {
            echo "Warning: 'patients' table does not exist. Skipping migration.\n";
            return;
        }

        // Check if column already exists
        if ($db->fieldExists('blood_group', 'patients')) {
            echo "Column 'blood_group' already exists in 'patients' table. Skipping.\n";
            return;
        }

        // Add the blood_group column
        $fields = [
            'blood_group' => [
                'type'       => 'ENUM',
                'constraint' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
                'null'       => true,
                'after'      => 'status',
            ],
        ];
        
        // Try to add after status, if that column doesn't exist, add after date_registered
        try {
            $this->forge->addColumn('patients', $fields);
        } catch (\Throwable $e) {
            // If status doesn't exist, try after date_registered
            try {
                $fields['blood_group']['after'] = 'date_registered';
                $this->forge->addColumn('patients', $fields);
            } catch (\Throwable $e2) {
                // If that doesn't work, add at the end
                unset($fields['blood_group']['after']);
                $this->forge->addColumn('patients', $fields);
            }
        }

        echo "Added 'blood_group' column to 'patients' table.\n";
    }

    public function down()
    {
        $db = \Config\Database::connect();
        
        if (!$db->tableExists('patients')) {
            return;
        }

        // Drop the column
        try {
            $this->forge->dropColumn('patients', 'blood_group');
        } catch (\Throwable $e) {
            // Column might not exist
        }
    }
}

