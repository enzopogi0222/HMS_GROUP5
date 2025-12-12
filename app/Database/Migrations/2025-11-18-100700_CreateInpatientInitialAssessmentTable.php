<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInpatientInitialAssessmentTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'assessment_id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'admission_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
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
            'spo2' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
            ],
            'level_of_consciousness' => [
                'type'       => 'ENUM',
                'constraint' => ['Alert', 'Semi-conscious', 'Unconscious'],
                'null'       => true,
            ],
            'pain_level' => [
                'type' => 'INT',
                'null' => true,
            ],
            'initial_findings' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'remarks' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => true,
                'default' => null,
            ],
        ]);

        $this->forge->addKey('assessment_id', true);
        $this->forge->createTable('inpatient_initial_assessment');

        $db = \Config\Database::connect();
        $db->query('ALTER TABLE inpatient_initial_assessment ENGINE=InnoDB');
        $db->query('ALTER TABLE inpatient_initial_assessment MODIFY created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');

        if ($db->tableExists('inpatient_admissions') && $db->fieldExists('admission_id', 'inpatient_admissions')) {
            try {
                $db->query('ALTER TABLE inpatient_initial_assessment ADD CONSTRAINT fk_inpatient_initial_assessment_admission FOREIGN KEY (admission_id) REFERENCES inpatient_admissions(admission_id) ON DELETE CASCADE ON UPDATE CASCADE');
            } catch (\Throwable $e) {
                // ignore malformed constraints
            }
        }
    }

    public function down()
    {
        $this->forge->dropTable('inpatient_initial_assessment');
    }
}
