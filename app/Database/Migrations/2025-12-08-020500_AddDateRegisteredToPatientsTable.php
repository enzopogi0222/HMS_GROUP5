<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDateRegisteredToPatientsTable extends Migration
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
        if ($db->fieldExists('date_registered', 'patients')) {
            echo "Column 'date_registered' already exists in 'patients' table. Skipping.\n";
            return;
        }

        // Add the date_registered column
        $fields = [
            'date_registered' => [
                'type'    => 'DATE',
                'null'    => true,
                'after'   => 'status',
            ],
        ];
        
        // Try to add after status, if that column doesn't exist, add after created_at
        try {
            $this->forge->addColumn('patients', $fields);
        } catch (\Throwable $e) {
            // If status doesn't exist, add after created_at or at the end
            unset($fields['date_registered']['after']);
            $this->forge->addColumn('patients', $fields);
        }

        // Update all existing patients to use created_at date or current date
        try {
            // Set date_registered to created_at if created_at exists, otherwise use current date
            $db->query("UPDATE patients 
                SET date_registered = DATE(COALESCE(created_at, NOW())) 
                WHERE date_registered IS NULL");
            
            echo "Updated existing patients with date_registered based on created_at or current date.\n";
        } catch (\Throwable $e) {
            echo "Warning: Could not update existing patients: " . $e->getMessage() . "\n";
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();
        
        if (!$db->tableExists('patients')) {
            return;
        }

        // Drop the column
        try {
            $this->forge->dropColumn('patients', 'date_registered');
        } catch (\Throwable $e) {
            // Column might not exist
        }
    }
}

