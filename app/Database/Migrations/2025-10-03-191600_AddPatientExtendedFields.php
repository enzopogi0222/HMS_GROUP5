<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPatientExtendedFields extends Migration
{
    public function up()
    {
        // Add new columns to align patient table with the form fields
        $fields = [
            'middle_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'first_name',
            ],
            'civil_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'after'      => 'gender',
            ],
            'province' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'address',
            ],
            'city' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'province',
            ],
            'barangay' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'city',
            ],
            'zip_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'after'      => 'barangay',
            ],
            'insurance_provider' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'zip_code',
            ],
            'insurance_card_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'insurance_provider',
            ],
            'payment_method' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'insurance_card_number',
            ],
            'hmo_member_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'payment_method',
            ],
            'hmo_approval_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'hmo_member_id',
            ],
            'hmo_cardholder_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'hmo_approval_code',
            ],
            'hmo_coverage_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'hmo_cardholder_name',
            ],
            'hmo_expiry_date' => [
                'type'       => 'DATE',
                'null'       => true,
                'after'      => 'hmo_coverage_type',
            ],
            'hmo_contact_person' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'hmo_expiry_date',
            ],
            'hmo_attachment' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'hmo_contact_person',
            ],
            'insurance_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'insurance_provider',
            ],
            'patient_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50, // outpatient | inpatient | emergency
                'null'       => true,
                'after'      => 'insurance_number',
            ],
            'medical_notes' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'patient_type',
            ],
        ];

        $this->forge->addColumn('patient', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('patient', [
            'middle_name',
            'civil_status',
            'province',
            'city',
            'barangay',
            'zip_code',
            'insurance_provider',
            'insurance_card_number',
            'payment_method',
            'hmo_member_id',
            'hmo_approval_code',
            'hmo_cardholder_name',
            'hmo_coverage_type',
            'hmo_expiry_date',
            'hmo_contact_person',
            'hmo_attachment',
            'insurance_number',
            'patient_type',
            'medical_notes',
        ]);
    }
}
