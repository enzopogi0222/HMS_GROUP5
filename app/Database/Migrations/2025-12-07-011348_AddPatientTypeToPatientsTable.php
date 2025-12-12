<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPatientTypeToPatientsTable extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        
        // Determine which table name to use
        $tableName = null;
        if ($db->tableExists('patients')) {
            $tableName = 'patients';
        } elseif ($db->tableExists('patient')) {
            $tableName = 'patient';
        }
        
        if (!$tableName) {
            throw new \RuntimeException('Neither "patients" nor "patient" table found');
        }
        
        // Check if column already exists
        if ($db->fieldExists('patient_type', $tableName)) {
            log_message('info', "Column 'patient_type' already exists in table '{$tableName}', skipping migration.");
            return;
        }
        
        // Add patient_type column
        $fields = [
            'patient_type' => [
                'type'       => 'ENUM',
                'constraint' => ['Inpatient', 'Outpatient'],
                'default'    => 'Outpatient',
                'null'       => false,
                'after'      => 'address',
            ],
        ];
        
        $this->forge->addColumn($tableName, $fields);
        
        log_message('info', "Added 'patient_type' column to '{$tableName}' table.");
    }

    public function down()
    {
        $db = \Config\Database::connect();
        
        // Determine which table name to use
        $tableName = null;
        if ($db->tableExists('patients')) {
            $tableName = 'patients';
        } elseif ($db->tableExists('patient')) {
            $tableName = 'patient';
        }
        
        if (!$tableName) {
            log_message('warning', 'Neither "patients" nor "patient" table found, cannot rollback.');
            return;
        }
        
        // Check if column exists before dropping
        if ($db->fieldExists('patient_type', $tableName)) {
            $this->forge->dropColumn($tableName, 'patient_type');
            log_message('info', "Dropped 'patient_type' column from '{$tableName}' table.");
        } else {
            log_message('info', "Column 'patient_type' does not exist in table '{$tableName}', nothing to drop.");
        }
    }
}
