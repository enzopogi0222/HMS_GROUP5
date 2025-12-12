<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInpatientMedicalHistoryTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'history_id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'admission_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
            'allergies' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'past_medical_history' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'past_surgical_history' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'family_history' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'current_medications' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('history_id', true);
        $this->forge->createTable('inpatient_medical_history');

        $db = \Config\Database::connect();
        $db->query('ALTER TABLE inpatient_medical_history ENGINE=InnoDB');

        if ($db->tableExists('inpatient_admissions') && $db->fieldExists('admission_id', 'inpatient_admissions')) {
            try {
                $db->query('ALTER TABLE inpatient_medical_history ADD CONSTRAINT fk_inpatient_med_hist_admission FOREIGN KEY (admission_id) REFERENCES inpatient_admissions(admission_id) ON DELETE CASCADE ON UPDATE CASCADE');
            } catch (\Throwable $e) {
                // ignore malformed constraints
            }
        }
    }

    public function down()
    {
        $this->forge->dropTable('inpatient_medical_history');
    }
}
