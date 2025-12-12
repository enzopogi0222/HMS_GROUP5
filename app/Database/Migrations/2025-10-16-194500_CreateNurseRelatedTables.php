<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNurseRelatedTables extends Migration
{
    public function up()
    {
        // Patient-Nurse Assignment Table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nurse_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'patient_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'assigned_date' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'inactive', 'transferred'],
                'default'    => 'active',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        // Fix FK targets: tables are 'nurse' and 'patient'
        $this->forge->addForeignKey('nurse_id', 'nurse', 'nurse_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('patient_id', 'patient', 'patient_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('patient_nurse');

        // Vital Signs Table
        $this->forge->addField([
            'vital_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'patient_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'nurse_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'temperature' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'blood_pressure_systolic' => [
                'type'       => 'INT',
                'constraint' => 3,
                'null'       => true,
            ],
            'blood_pressure_diastolic' => [
                'type'       => 'INT',
                'constraint' => 3,
                'null'       => true,
            ],
            'pulse_rate' => [
                'type'       => 'INT',
                'constraint' => 3,
                'null'       => true,
            ],
            'respiratory_rate' => [
                'type'       => 'INT',
                'constraint' => 3,
                'null'       => true,
            ],
            'oxygen_saturation' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'height' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
                'comment'    => 'in cm',
            ],
            'weight' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
                'comment'    => 'in kg',
            ],
            'bmi' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'recorded_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('vital_id', true);
        $this->forge->addForeignKey('patient_id', 'patient', 'patient_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('nurse_id', 'nurse', 'nurse_id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('vital_signs');

        // Medication Schedule Table
        $this->forge->addField([
            'schedule_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'patient_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'medication_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'schedule_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'scheduled_time' => [
                'type' => 'TIME',
                'null' => false,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['scheduled', 'administered', 'missed', 'cancelled'],
                'default'    => 'scheduled',
            ],
            'dosage' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'instructions' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('schedule_id', true);
        $this->forge->addForeignKey('patient_id', 'patient', 'patient_id', 'CASCADE', 'CASCADE');
        // Only add FK to medications if the table exists to avoid migration failure
        $db = \Config\Database::connect();
        if ($db->tableExists('medications')) {
            $this->forge->addForeignKey('medication_id', 'medications', 'medication_id', 'CASCADE', 'CASCADE');
        }
        $this->forge->createTable('medication_schedule');

        // Medication Administration Table
        $this->forge->addField([
            'administration_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'schedule_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'administered_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'administered_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('administration_id', true);
        $this->forge->addForeignKey('schedule_id', 'medication_schedule', 'schedule_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('administered_by', 'staff', 'staff_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('medication_administration');

        // Nurse Shift Reports Table
        $this->forge->addField([
            'report_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nurse_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'shift_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'shift_type' => [
                'type'       => 'ENUM',
                'constraint' => ['morning', 'afternoon', 'night'],
                'null'       => false,
            ],
            'patients_seen' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'medications_administered' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'incidents' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'handover_notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('report_id', true);
        $this->forge->addForeignKey('nurse_id', 'nurse', 'nurse_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('nurse_shift_reports');
    }

    public function down()
    {
        $this->forge->dropTable('nurse_shift_reports', true);
        $this->forge->dropTable('medication_administration', true);
        $this->forge->dropTable('medication_schedule', true);
        $this->forge->dropTable('vital_signs', true);
        $this->forge->dropTable('patient_nurse', true);
    }
}
