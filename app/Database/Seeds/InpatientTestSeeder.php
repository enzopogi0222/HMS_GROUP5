<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InpatientTestSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        // Resolve patient table name (patient or patients)
        $patientTable = $db->tableExists('patient') ? 'patient' : 'patients';

        // 1. Insert a test patient (match actual patients table columns)
        $patientData = [
            'last_name'        => 'Patient',
            'first_name'       => 'Test',
            'middle_name'      => 'Inpatient',
            'date_of_birth'    => '1990-01-01',
            'sex'              => 'Male',
            'civil_status'     => 'Single',
            'contact_number'   => '09171234567',
            'email'            => 'test.inpatient@example.com',
            'address'          => '123 Test Street, Test Barangay, Test City',
            'created_at'       => date('Y-m-d H:i:s'),
        ];

        $db->table($patientTable)->insert($patientData);
        $patientId = (int) $db->insertID();

        if (! $patientId) {
            echo "Failed to insert test patient.\n";
            return;
        }

        // 2. Insert inpatient admission
        if (! $db->tableExists('inpatient_admissions')) {
            echo "Table inpatient_admissions does not exist.\n";
            return;
        }

        $admissionData = [
            'patient_id'           => $patientId,
            'admission_datetime'   => date('Y-m-d H:i:s'),
            'admission_type'       => 'ER',
            'admitting_diagnosis'  => 'Test admitting diagnosis',
            'admitting_doctor'     => 'Dr. Test Doctor',
            'patient_classification' => 'Medical',
            'consent_signed'       => 1,
        ];

        $db->table('inpatient_admissions')->insert($admissionData);
        $admissionId = (int) $db->insertID();

        if (! $admissionId) {
            echo "Failed to insert inpatient admission.\n";
            return;
        }

        // 3. Inpatient medical history
        if ($db->tableExists('inpatient_medical_history')) {
            $historyData = [
                'admission_id'        => $admissionId,
                'allergies'           => 'No known drug allergies',
                'past_medical_history'=> 'Hypertension',
                'past_surgical_history'=> 'Appendectomy (2010)',
                'family_history'      => 'Father with diabetes',
                'current_medications' => 'Amlodipine 5mg OD',
            ];

            $db->table('inpatient_medical_history')->insert($historyData);
        }

        // 4. Inpatient initial assessment
        if ($db->tableExists('inpatient_initial_assessment')) {
            $assessmentData = [
                'admission_id'          => $admissionId,
                'blood_pressure'        => '120/80',
                'heart_rate'            => '78',
                'respiratory_rate'      => '16',
                'temperature'           => '37.0',
                'spo2'                  => '98',
                'level_of_consciousness'=> 'Alert',
                'pain_level'            => 2,
                'initial_findings'      => 'Test initial findings',
                'remarks'               => 'Stable on admission',
            ];

            $db->table('inpatient_initial_assessment')->insert($assessmentData);
        }

        // 5. Inpatient room assignment
        if ($db->tableExists('inpatient_room_assignments')) {
            $roomData = [
                'admission_id' => $admissionId,
                'room_type'    => 'Ward',
                'floor_number' => 2,
                'room_number'  => '201',
                'bed_number'   => 'B',
                'daily_rate'   => 1500.00,
            ];

            $db->table('inpatient_room_assignments')->insert($roomData);
        }

        // 6. Insurance claim linked to inpatient admission
        if ($db->tableExists('insurance_claims')) {
            $claimData = [
                'ref_no'              => 'INP-' . str_pad($admissionId, 6, '0', STR_PAD_LEFT),
                'patient_name'        => implode(' ', [$patientData['first_name'], $patientData['middle_name'], $patientData['last_name']]),
                'policy_no'           => 'INP-POL-' . time(),
                'claim_amount'        => 0.00,
                'notes'               => 'Claim generated for inpatient admission',
                'status'              => 'Pending',
                'patient_id'          => $patientId,
                'admission_id'        => $admissionId,
                'claim_source'        => 'inpatient',
                'insurance_provider'  => 'Test HMO',
                'insurance_card_number'=> 'INP-123456789',
                'insurance_valid_from'=> date('Y-m-d'),
                'insurance_valid_to'  => date('Y-m-d', strtotime('+1 year')),
                'hmo_member_id'       => 'HMO-' . $patientId,
                'hmo_approval_code'   => 'HMO-APP-' . $admissionId,
                'hmo_cardholder_name' => $patientData['first_name'] . ' ' . $patientData['last_name'],
                'hmo_contact_person'  => 'HMO Representative',
                'hmo_attachment'      => null,
            ];
            $existingColumns = $db->getFieldNames('insurance_claims');
            $claimData = array_filter(
                $claimData,
                fn($value, $key) => in_array($key, $existingColumns, true),
                ARRAY_FILTER_USE_BOTH
            );

            $db->table('insurance_claims')->insert($claimData);
        }

        echo "Inpatient test data seeded successfully. Patient ID: {$patientId}, Admission ID: {$admissionId}\n";
    }
}
