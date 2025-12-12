<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateInsuranceDetailsTable extends Migration
{
    protected function resolvePatientTable(): ?string
    {
        $db = \Config\Database::connect();
        if ($db->tableExists('patients')) {
            return 'patients';
        }
        if ($db->tableExists('patient')) {
            return 'patient';
        }
        return null;
    }

    public function up()
    {
        $db = \Config\Database::connect();
        $patientTable = $this->resolvePatientTable();

        if (! $patientTable) {
            throw new \RuntimeException('Unable to find patients table to relate insurance details.');
        }

        if ($db->tableExists('insurance_details')) {
            $this->forge->dropTable('insurance_details', true);
        }

        $this->forge->addField([
            'insurance_detail_id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'patient_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => false,
            ],
            'provider' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'membership_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'card_holder_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ],
            'member_type' => [
                'type'       => 'ENUM',
                'constraint' => ['Principal', 'Dependent'],
                'null'       => false,
                'default'    => 'Principal',
            ],
            'relationship' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'plan_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ],
            'coverage_type' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'mbl' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'null'       => true,
            ],
            'pre_existing_coverage' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'start_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'end_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'card_status' => [
                'type'       => 'ENUM',
                'constraint' => ['Active', 'Expired', 'Pending', 'Revoked'],
                'default'    => 'Active',
                'null'       => false,
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

        $this->forge->addKey('insurance_detail_id', true);
        $this->forge->addForeignKey('patient_id', $patientTable, 'patient_id', 'CASCADE', 'CASCADE', 'fk_hmo_patient');
        $this->forge->createTable('insurance_details');

        $db->query('ALTER TABLE insurance_details MODIFY created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP');
        $db->query('ALTER TABLE insurance_details MODIFY updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }

    public function down()
    {
        $db = \Config\Database::connect();
        $patientTable = $this->resolvePatientTable();

        if (! $patientTable) {
            throw new \RuntimeException('Unable to find patients table to recreate previous insurance_details schema.');
        }

        if ($db->tableExists('insurance_details')) {
            $this->forge->dropTable('insurance_details', true);
        }

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
        $this->forge->addForeignKey('patient_id', $patientTable, 'patient_id', 'CASCADE', 'CASCADE', 'fk_insurance_details_patient');
        if ($db->tableExists('inpatient_admissions')) {
            $this->forge->addForeignKey('admission_id', 'inpatient_admissions', 'admission_id', 'CASCADE', 'CASCADE', 'fk_insurance_details_admission');
        }
        if ($db->tableExists('outpatient_visits')) {
            $this->forge->addForeignKey('visit_id', 'outpatient_visits', 'visit_id', 'CASCADE', 'CASCADE', 'fk_insurance_details_visit');
        }
        if ($db->tableExists('insurance_claims')) {
            $this->forge->addForeignKey('insurance_claim_id', 'insurance_claims', 'id', 'SET NULL', 'CASCADE', 'fk_insurance_details_claim');
        }

        $this->forge->createTable('insurance_details');
        $db->query('ALTER TABLE insurance_details ENGINE=InnoDB');
        $db->query('ALTER TABLE insurance_details MODIFY created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
    }
}
