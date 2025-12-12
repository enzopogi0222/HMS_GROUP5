<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStatusToPatientsTable extends Migration
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
        if ($db->fieldExists('status', 'patients')) {
            echo "Column 'status' already exists in 'patients' table. Skipping.\n";
            return;
        }

        // Add the status column
        $fields = [
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['Active', 'Inactive'],
                'default'    => 'Active',
                'null'       => false,
                'after'      => 'patient_type',
            ],
        ];
        
        // Try to add after patient_type, if that column doesn't exist, add after created_at
        try {
            $this->forge->addColumn('patients', $fields);
        } catch (\Throwable $e) {
            // If patient_type doesn't exist, add after created_at or at the end
            unset($fields['status']['after']);
            $this->forge->addColumn('patients', $fields);
        }

        // Update all existing patients to 'Active' status
        try {
            $db->table('patients')
                ->where('status IS NULL', null, false)
                ->orWhere('status', '')
                ->update(['status' => 'Active']);
            
            // Also set any NULL values to Active (in case the update didn't catch them)
            $db->query("UPDATE patients SET status = 'Active' WHERE status IS NULL OR status = ''");
            
            echo "Updated existing patients to 'Active' status.\n";
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
            $this->forge->dropColumn('patients', 'status');
        } catch (\Throwable $e) {
            // Column might not exist
        }
    }
}

