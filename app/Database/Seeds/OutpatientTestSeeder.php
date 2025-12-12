<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class OutpatientTestSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        // Resolve patient table name (patient or patients)
        $patientTable = $db->tableExists('patient') ? 'patient' : 'patients';

        // 1. Insert a test outpatient patient (match patients table columns)
        $patientData = [
            'last_name'       => 'Outpatient',
            'first_name'      => 'Test',
            'middle_name'     => 'Sample',
            'date_of_birth'   => '1995-05-15',
            'sex'             => 'Female',
            'civil_status'    => 'Single',
            'contact_number'  => '09181234567',
            'email'           => 'test.outpatient@example.com',
            'address'         => '456 Clinic Street, Barangay Health, Test City',
            'created_at'      => date('Y-m-d H:i:s'),
        ];

        $db->table($patientTable)->insert($patientData);
        $patientId = (int) $db->insertID();

        if (! $patientId) {
            echo "Failed to insert outpatient test patient.\n";
            return;
        }

        // 2. Insert an outpatient visit linked to this patient
        if (! $db->tableExists('outpatient_visits')) {
            echo "Table outpatient_visits does not exist.\n";
            return;
        }

        $visitData = [
            'patient_id'          => $patientId,
            'department'          => 'Internal Medicine',
            'assigned_doctor'     => 'Dr. Sample Doctor',
            'appointment_datetime'=> date('Y-m-d H:i:s', strtotime('+1 day 09:00:00')),
            'visit_type'          => 'New',
            'chief_complaint'     => 'Headache and dizziness',
            'allergies'           => 'None known',
            'existing_conditions' => 'None',
            'current_medications' => 'Multivitamins',
            'blood_pressure'      => '120/80',
            'heart_rate'          => '76',
            'respiratory_rate'    => '18',
            'temperature'         => '36.8',
            'weight'              => '60',
            'height'              => '165',
            'payment_type'        => 'Cash',
        ];

        $db->table('outpatient_visits')->insert($visitData);
        $visitId = (int) $db->insertID();

        if (! $visitId) {
            echo "Failed to insert outpatient visit.\n";
            return;
        }

        if ($db->tableExists('insurance_claims')) {
            $claimData = [
                'ref_no'               => 'OUT-' . str_pad($visitId, 6, '0', STR_PAD_LEFT),
                'patient_name'         => implode(' ', [$patientData['first_name'], $patientData['middle_name'], $patientData['last_name']]),
                'policy_no'            => 'OUT-POL-' . time(),
                'claim_amount'         => 0.00,
                'notes'                => 'Claim generated for outpatient visit',
                'status'               => 'Pending',
                'patient_id'           => $patientId,
                'visit_id'             => $visitId,
                'claim_source'         => 'outpatient',
                'insurance_provider'   => 'Test PPO',
                'insurance_card_number'=> 'OUT-987654321',
                'insurance_valid_from' => date('Y-m-d'),
                'insurance_valid_to'   => date('Y-m-d', strtotime('+1 year')),
                'hmo_member_id'        => 'PPO-' . $patientId,
                'hmo_approval_code'    => 'PPO-APP-' . $visitId,
                'hmo_cardholder_name'  => $patientData['first_name'] . ' ' . $patientData['last_name'],
                'hmo_contact_person'   => 'PPO Representative',
                'hmo_attachment'       => null,
            ];
            $existingColumns = $db->getFieldNames('insurance_claims');
            $claimData = array_filter(
                $claimData,
                fn($value, $key) => in_array($key, $existingColumns, true),
                ARRAY_FILTER_USE_BOTH
            );

            $db->table('insurance_claims')->insert($claimData);
        }

        echo "Outpatient test data seeded successfully. Patient ID: {$patientId}, Visit ID: {$visitId}\n";
    }
}
