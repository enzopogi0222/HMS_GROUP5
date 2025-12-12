<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RecreateInpatientAdmissionsTable extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('patients') && $db->fieldExists('patient_id', 'patients')) {
            try {
                $db->query('ALTER TABLE patients ENGINE=InnoDB');
                $column = $db->query("SHOW COLUMNS FROM patients WHERE Field = 'patient_id'")->getRow();
                if ($column && ! str_contains(strtolower($column->Type), 'unsigned')) {
                    $db->query('ALTER TABLE patients MODIFY patient_id INT UNSIGNED NOT NULL AUTO_INCREMENT');
                }
            } catch (\Throwable $e) {
                // ignore modifier failures
            }
        }

        $this->forge->addField([
            'admission_id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'patient_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
            'admission_datetime' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'admission_type' => [
                'type'       => 'ENUM',
                'constraint' => ['ER', 'Scheduled', 'Transfer'],
                'null'       => true,
            ],
            'admitting_diagnosis' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'admitting_doctor' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'insurance_provider' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'insurance_card_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'insurance_validity' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'patient_classification' => [
                'type'       => 'ENUM',
                'constraint' => ['Medical', 'Surgical', 'Maternity', 'Pediatric', 'Geriatric', 'Infectious', 'Psychiatric', 'Rehabilitation'],
                'null'       => true,
            ],
            'hmo_member_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'hmo_approval_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'hmo_cardholder_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'hmo_coverage_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'hmo_expiry_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'hmo_contact_person' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'hmo_attachment' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'consent_signed' => [
                'type'       => 'BOOLEAN',
                'null'       => false,
                'default'    => 0,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => true,
                'default' => null,
            ],
        ]);

        $this->forge->addKey('admission_id', true);

        $this->forge->createTable('inpatient_admissions');

        $db->query('ALTER TABLE inpatient_admissions ENGINE=InnoDB');
        $db->query('ALTER TABLE inpatient_admissions MODIFY created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');

        if ($db->tableExists('patients') && $db->fieldExists('patient_id', 'patients')) {
            try {
                $db->query('ALTER TABLE inpatient_admissions ADD CONSTRAINT fk_inpatient_admissions_patient FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE ON UPDATE CASCADE');
            } catch (\Throwable $e) {
                // ignore malformed constraints
            }
        }
    }

    public function down()
    {
        $this->forge->dropTable('inpatient_admissions');
    }
}
