<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPrimaryDoctorToPatientsTable extends Migration
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
        $fields = $db->getFieldData('patients');
        $columnExists = false;
        foreach ($fields as $field) {
            if ($field->name === 'primary_doctor_id') {
                $columnExists = true;
                break;
            }
        }

        if ($columnExists) {
            echo "Column 'primary_doctor_id' already exists in 'patients' table. Skipping.\n";
            return;
        }

        // Add the linking column
        $fields = [
            'primary_doctor_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'patient_type',
            ],
        ];
        
        // Try to add after patient_type, if that column doesn't exist, add at the end
        try {
            $this->forge->addColumn('patients', $fields);
        } catch (\Throwable $e) {
            // If patient_type doesn't exist, add after created_at or at the end
            unset($fields['primary_doctor_id']['after']);
            $this->forge->addColumn('patients', $fields);
        }

        // Add an index on the new column
        try {
            $db->query('CREATE INDEX idx_patients_primary_doctor_id ON patients (primary_doctor_id)');
        } catch (\Throwable $e) {
            // Index might already exist
        }

        // Add the foreign key constraint
        try {
            $db->query('ALTER TABLE patients
                ADD CONSTRAINT fk_patients_doctor
                FOREIGN KEY (primary_doctor_id) REFERENCES doctor(doctor_id)
                ON UPDATE CASCADE ON DELETE SET NULL');
        } catch (\Throwable $e) {
            // Constraint might already exist or doctor table might not exist yet
            echo "Warning: Could not add foreign key constraint: " . $e->getMessage() . "\n";
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();
        
        if (!$db->tableExists('patients')) {
            return;
        }

        // Drop the foreign key first if it exists
        try {
            $db->query('ALTER TABLE patients DROP FOREIGN KEY fk_patients_doctor');
        } catch (\Throwable $e) {
            // Constraint might not exist
        }
        
        // Drop the index
        try {
            $db->query('DROP INDEX idx_patients_primary_doctor_id ON patients');
        } catch (\Throwable $e) {
            // Index might not exist
        }
        
        // Drop the column
        try {
            $this->forge->dropColumn('patients', 'primary_doctor_id');
        } catch (\Throwable $e) {
            // Column might not exist
        }
    }
}

