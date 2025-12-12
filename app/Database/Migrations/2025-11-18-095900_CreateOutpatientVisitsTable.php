<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOutpatientVisitsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'visit_id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'patient_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
            'department' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'assigned_doctor' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'appointment_datetime' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'visit_type' => [
                'type'       => 'ENUM',
                'constraint' => ['New', 'Follow-up', 'Emergency'],
                'null'       => true,
            ],
            'chief_complaint' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'allergies' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'existing_conditions' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'current_medications' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'blood_pressure' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'heart_rate' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
            ],
            'respiratory_rate' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
            ],
            'temperature' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
            ],
            'weight' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
            ],
            'height' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
            ],
            'payment_type' => [
                'type'       => 'ENUM',
                'constraint' => ['Cash', 'HMO', 'PhilHealth', 'Company'],
                'null'       => true,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => true,
                'default' => null,
            ],
        ]);

        $this->forge->addKey('visit_id', true);
        $this->forge->addForeignKey('patient_id', 'patients', 'patient_id', 'CASCADE', 'CASCADE', 'fk_outpatient_visits_patient');

        $this->forge->createTable('outpatient_visits');

        $db = \Config\Database::connect();
        $db->query('ALTER TABLE outpatient_visits ENGINE=InnoDB');
        $db->query('ALTER TABLE outpatient_visits MODIFY created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
    }

    public function down()
    {
        $this->forge->dropTable('outpatient_visits');
    }
}
