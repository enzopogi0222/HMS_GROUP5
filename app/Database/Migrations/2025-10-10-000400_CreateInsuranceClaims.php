<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInsuranceClaims extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'ref_no' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'unique'     => true,
            ],
            'patient_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
            ],
            'policy_no' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
            ],
            'claim_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
            ],
            'diagnosis_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'default'    => 'Pending',
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
        $this->forge->createTable('insurance_claims');
    }

    public function down()
    {
        $this->forge->dropTable('insurance_claims');
    }
}
