<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBillingAccountsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'billing_id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'admission_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
            'philhealth_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'hmo_provider' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'hmo_approval_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'company_guarantee_letter' => [
                'type'       => 'BOOLEAN',
                'null'       => false,
                'default'    => 0,
            ],
            'payment_method' => [
                'type'       => 'ENUM',
                'constraint' => ['Cash Deposit', 'Insurance', 'Company Billing'],
                'null'       => true,
            ],
            'responsible_person_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'responsible_person_contact' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
        ]);

        $this->forge->addKey('billing_id', true);
        $this->forge->createTable('billing_accounts');

        $db = \Config\Database::connect();
        $db->query('ALTER TABLE billing_accounts ENGINE=InnoDB');

        if ($db->tableExists('inpatient_admissions') && $db->fieldExists('admission_id', 'inpatient_admissions')) {
            try {
                $db->query('ALTER TABLE billing_accounts ADD CONSTRAINT fk_billing_accounts_admission FOREIGN KEY (admission_id) REFERENCES inpatient_admissions(admission_id) ON DELETE CASCADE ON UPDATE CASCADE');
            } catch (\Throwable $e) {
                // ignore malformed constraints
            }
        }
    }

    public function down()
    {
        $this->forge->dropTable('billing_accounts');
    }
}
