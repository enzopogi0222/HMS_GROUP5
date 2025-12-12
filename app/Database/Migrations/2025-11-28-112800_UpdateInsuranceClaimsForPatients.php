<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateInsuranceClaimsForPatients extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('insurance_claims')) {
            return;
        }

        $fields = [];

        // Generic linkage columns so a claim can belong to either inpatient or outpatient
        if (! $this->db->fieldExists('patient_id', 'insurance_claims')) {
            $fields['patient_id'] = [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'id',
            ];
        }

        if (! $this->db->fieldExists('admission_id', 'insurance_claims')) {
            $fields['admission_id'] = [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'patient_id',
            ];
        }

        if (! $this->db->fieldExists('visit_id', 'insurance_claims')) {
            $fields['visit_id'] = [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'admission_id',
            ];
        }

        if (! $this->db->fieldExists('claim_source', 'insurance_claims')) {
            $fields['claim_source'] = [
                'type'       => 'ENUM',
                'constraint' => ['inpatient', 'outpatient'],
                'null'       => true,
                'after'      => 'visit_id',
            ];
        }

        $insuranceFields = [
            'insurance_provider' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'claim_source',
            ],
            'insurance_card_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'insurance_valid_from' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'insurance_valid_to' => [
                'type' => 'DATE',
                'null' => true,
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
        ];

        foreach ($insuranceFields as $name => $definition) {
            if (! $this->db->fieldExists($name, 'insurance_claims')) {
                $fields[$name] = $definition;
            }
        }

        if (! empty($fields)) {
            $this->forge->addColumn('insurance_claims', $fields);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('insurance_claims')) {
            return;
        }

        $drop = [];
        foreach ([
            'hmo_attachment',
            'hmo_contact_person',
            'hmo_cardholder_name',
            'hmo_approval_code',
            'hmo_member_id',
            'insurance_valid_to',
            'insurance_valid_from',
            'insurance_card_number',
            'insurance_provider',
            'claim_source',
            'visit_id',
            'admission_id',
            'patient_id',
        ] as $field) {
            if ($this->db->fieldExists($field, 'insurance_claims')) {
                $drop[] = $field;
            }
        }

        if (! empty($drop)) {
            $this->forge->dropColumn('insurance_claims', $drop);
        }
    }
}
