<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class NormalizeInpatientAdmissionsInsurance extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('inpatient_admissions')) {
            return;
        }

        $fieldsToDrop = [
            'insurance_provider',
            'insurance_card_number',
            'insurance_validity',
            'hmo_member_id',
            'hmo_approval_code',
            'hmo_cardholder_name',
            'hmo_coverage_type',
            'hmo_expiry_date',
            'hmo_contact_person',
            'hmo_attachment',
        ];

        $existingFields = array_map(
            static fn($field) => $field->name,
            $this->db->getFieldData('inpatient_admissions')
        );

        $drop = [];
        foreach ($fieldsToDrop as $field) {
            if (in_array($field, $existingFields, true)) {
                $drop[] = $field;
            }
        }

        if (! empty($drop)) {
            $this->forge->dropColumn('inpatient_admissions', $drop);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('inpatient_admissions')) {
            return;
        }

        $fields = [];

        $fields['insurance_provider'] = [
            'type'       => 'VARCHAR',
            'constraint' => 150,
            'null'       => true,
        ];
        $fields['insurance_card_number'] = [
            'type'       => 'VARCHAR',
            'constraint' => 100,
            'null'       => true,
        ];
        $fields['insurance_validity'] = [
            'type' => 'DATE',
            'null' => true,
        ];
        $fields['hmo_member_id'] = [
            'type'       => 'VARCHAR',
            'constraint' => 150,
            'null'       => true,
        ];
        $fields['hmo_approval_code'] = [
            'type'       => 'VARCHAR',
            'constraint' => 150,
            'null'       => true,
        ];
        $fields['hmo_cardholder_name'] = [
            'type'       => 'VARCHAR',
            'constraint' => 150,
            'null'       => true,
        ];
        $fields['hmo_coverage_type'] = [
            'type'       => 'VARCHAR',
            'constraint' => 150,
            'null'       => true,
        ];
        $fields['hmo_expiry_date'] = [
            'type' => 'DATE',
            'null' => true,
        ];
        $fields['hmo_contact_person'] = [
            'type'       => 'VARCHAR',
            'constraint' => 150,
            'null'       => true,
        ];
        $fields['hmo_attachment'] = [
            'type'       => 'VARCHAR',
            'constraint' => 255,
            'null'       => true,
        ];

        $this->forge->addColumn('inpatient_admissions', $fields);
    }
}
