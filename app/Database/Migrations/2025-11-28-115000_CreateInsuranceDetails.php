<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInsuranceDetails extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'insurance_detail_id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'patient_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
            'admission_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'visit_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'insurance_claim_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'insurance_provider' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'policy_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'coverage_start_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'coverage_end_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'member_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
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
            'coverage_type' => [
                'type'       => 'ENUM',
                'constraint' => ['inpatient', 'outpatient'],
                'null'       => false,
                'default'    => 'inpatient',
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => true,
                'default' => null,
            ],
        ]);

        $this->forge->addKey('insurance_detail_id', true);
        $this->forge->addForeignKey('patient_id', 'patients', 'patient_id', 'CASCADE', 'CASCADE', 'fk_insurance_details_patient');
        $this->forge->addForeignKey('admission_id', 'inpatient_admissions', 'admission_id', 'CASCADE', 'CASCADE', 'fk_insurance_details_admission');
        $this->forge->addForeignKey('visit_id', 'outpatient_visits', 'visit_id', 'CASCADE', 'CASCADE', 'fk_insurance_details_visit');
        $this->forge->addForeignKey('insurance_claim_id', 'insurance_claims', 'id', 'SET NULL', 'CASCADE', 'fk_insurance_details_claim');

        $this->forge->createTable('insurance_details');

        $db = \Config\Database::connect();
        $db->query('ALTER TABLE insurance_details ENGINE=InnoDB');
        $db->query('ALTER TABLE insurance_details MODIFY created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
    }

    public function down()
    {
        $this->forge->dropTable('insurance_details');
    }
}
