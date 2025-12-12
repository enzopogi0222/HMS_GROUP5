<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ComprehensivePatientSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        // Resolve patient table name (patient or patients)
        $patientTable = $db->tableExists('patient') ? 'patient' : 'patients';

        echo "Starting comprehensive patient seeder...\n";

        // ===================================================================
        // OUTPATIENT PATIENTS WITH INSURANCE
        // ===================================================================

        $outpatientPatients = [
            [
                'first_name' => 'Maria',
                'middle_name' => 'Santos',
                'last_name' => 'Reyes',
                'gender' => 'Female',
                'date_of_birth' => '1988-05-15',
                'civil_status' => 'Married',
                'contact_no' => '09171234567',
                'contact_number' => '09171234567',
                'email' => 'maria.reyes@example.com',
                'address' => '123 Rizal Street',
                'barangay' => 'Barangay 1',
                'city' => 'Quezon City',
                'province' => 'Metro Manila',
                'zip_code' => '1100',
                'blood_group' => 'A+',
                'patient_type' => 'Outpatient',
                'status' => 'Active',
                'date_registered' => date('Y-m-d', strtotime('-30 days')),
                // Insurance Information
                'insurance_provider' => 'Maxicare',
                'insurance_number' => 'MAX-123456789',
                'insurance_card_number' => 'MAX-123456789',
                'hmo_member_id' => 'MAX-MEM-001',
                'hmo_cardholder_name' => 'Maria Santos Reyes',
                'hmo_coverage_type' => 'Standard',
                'hmo_expiry_date' => date('Y-m-d', strtotime('+1 year')),
                'hmo_contact_person' => 'Maxicare Customer Service',
                'payment_method' => 'HMO',
                'emergency_contact' => 'Juan Reyes',
                'emergency_phone' => '09171234568',
                'medical_notes' => 'Regular check-up patient. No known allergies.',
            ],
            [
                'first_name' => 'John',
                'middle_name' => 'Cruz',
                'last_name' => 'Dela Cruz',
                'gender' => 'Male',
                'date_of_birth' => '1992-08-22',
                'civil_status' => 'Single',
                'contact_no' => '09181234567',
                'contact_number' => '09181234567',
                'email' => 'john.delacruz@example.com',
                'address' => '456 Bonifacio Avenue',
                'barangay' => 'Barangay 2',
                'city' => 'Manila',
                'province' => 'Metro Manila',
                'zip_code' => '1000',
                'blood_group' => 'O+',
                'patient_type' => 'Outpatient',
                'status' => 'Active',
                'date_registered' => date('Y-m-d', strtotime('-20 days')),
                // Insurance Information
                'insurance_provider' => 'PhilHealth',
                'insurance_number' => 'PH-987654321',
                'insurance_card_number' => 'PH-987654321',
                'payment_method' => 'PhilHealth',
                'emergency_contact' => 'Mary Dela Cruz',
                'emergency_phone' => '09181234568',
                'medical_notes' => 'Follow-up for hypertension management.',
            ],
            [
                'first_name' => 'Ana',
                'middle_name' => 'Lopez',
                'last_name' => 'Garcia',
                'gender' => 'Female',
                'date_of_birth' => '1995-03-10',
                'civil_status' => 'Single',
                'contact_no' => '09191234567',
                'contact_number' => '09191234567',
                'email' => 'ana.garcia@example.com',
                'address' => '789 Mabini Street',
                'barangay' => 'Barangay 3',
                'city' => 'Makati City',
                'province' => 'Metro Manila',
                'zip_code' => '1200',
                'blood_group' => 'B+',
                'patient_type' => 'Outpatient',
                'status' => 'Active',
                'date_registered' => date('Y-m-d', strtotime('-15 days')),
                // Insurance Information
                'insurance_provider' => 'Intellicare',
                'insurance_number' => 'INT-456789123',
                'insurance_card_number' => 'INT-456789123',
                'hmo_member_id' => 'INT-MEM-002',
                'hmo_cardholder_name' => 'Ana Lopez Garcia',
                'hmo_coverage_type' => 'Premium',
                'hmo_expiry_date' => date('Y-m-d', strtotime('+6 months')),
                'hmo_approval_code' => 'INT-APP-001',
                'hmo_contact_person' => 'Intellicare Support',
                'payment_method' => 'HMO',
                'emergency_contact' => 'Pedro Garcia',
                'emergency_phone' => '09191234568',
                'medical_notes' => 'Annual physical examination scheduled.',
            ],
        ];

        // Insert outpatient patients and create visits
        foreach ($outpatientPatients as $index => $patientData) {
            // Filter data based on available columns and map field names
            $columns = $db->getFieldNames($patientTable);
            $filteredData = [];
            
            foreach ($patientData as $key => $value) {
                // Map gender/sex and contact_no/contact_number
                if ($key === 'gender' && !in_array('gender', $columns, true) && in_array('sex', $columns, true)) {
                    $filteredData['sex'] = $value;
                } elseif ($key === 'sex' && !in_array('sex', $columns, true) && in_array('gender', $columns, true)) {
                    $filteredData['gender'] = $value;
                } elseif ($key === 'contact_no' && !in_array('contact_no', $columns, true) && in_array('contact_number', $columns, true)) {
                    $filteredData['contact_number'] = $value;
                } elseif ($key === 'contact_number' && !in_array('contact_number', $columns, true) && in_array('contact_no', $columns, true)) {
                    $filteredData['contact_no'] = $value;
                } elseif (in_array($key, $columns, true)) {
                    $filteredData[$key] = $value;
                }
            }

            $db->table($patientTable)->insert($filteredData);
            $patientId = (int) $db->insertID();

            if ($patientId) {
                echo "Created outpatient patient: {$patientData['first_name']} {$patientData['last_name']} (ID: {$patientId})\n";

                // Create outpatient visit
                if ($db->tableExists('outpatient_visits')) {
                    $visitData = [
                        'patient_id' => $patientId,
                        'department' => ['Internal Medicine', 'Cardiology', 'General Medicine'][$index % 3],
                        'assigned_doctor' => ['Dr. Maria Santos', 'Dr. John Cruz', 'Dr. Ana Lopez'][$index % 3],
                        'appointment_datetime' => date('Y-m-d H:i:s', strtotime('-' . (10 - $index * 2) . ' days')),
                        'visit_type' => ['New', 'Follow-up', 'New'][$index % 3],
                        'chief_complaint' => ['Routine check-up', 'Hypertension follow-up', 'Annual physical exam'][$index % 3],
                        'allergies' => ['None', 'Penicillin', 'None'][$index % 3],
                        'existing_conditions' => ['None', 'Hypertension', 'None'][$index % 3],
                        'current_medications' => ['Multivitamins', 'Amlodipine 5mg', 'None'][$index % 3],
                        'blood_pressure' => ['120/80', '140/90', '118/75'][$index % 3],
                        'heart_rate' => ['72', '78', '70'][$index % 3],
                        'respiratory_rate' => ['16', '18', '16'][$index % 3],
                        'temperature' => ['36.8', '37.0', '36.7'][$index % 3],
                        'weight' => ['55', '75', '60'][$index % 3],
                        'height' => ['160', '175', '165'][$index % 3],
                        'payment_type' => ['HMO', 'PhilHealth', 'HMO'][$index % 3],
                    ];

                    $db->table('outpatient_visits')->insert($visitData);
                    $visitId = (int) $db->insertID();

                    // Create insurance claim for outpatient
                    if ($db->tableExists('insurance_claims') && $visitId) {
                        $claimColumns = $db->getFieldNames('insurance_claims');
                        $claimData = [
                            'ref_no' => 'OUT-' . date('Ymd') . '-' . str_pad($visitId, 4, '0', STR_PAD_LEFT),
                            'patient_name' => trim("{$patientData['first_name']} {$patientData['middle_name']} {$patientData['last_name']}"),
                            'policy_no' => $patientData['insurance_number'] ?? 'POL-' . $patientId,
                            'claim_amount' => rand(500, 2000),
                            'diagnosis_code' => 'Z00.00',
                            'notes' => 'Outpatient visit claim',
                            'status' => 'Pending',
                        ];

                        // Add optional fields if they exist
                        if (in_array('patient_id', $claimColumns, true)) {
                            $claimData['patient_id'] = $patientId;
                        }
                        if (in_array('visit_id', $claimColumns, true)) {
                            $claimData['visit_id'] = $visitId;
                        }
                        if (in_array('claim_source', $claimColumns, true)) {
                            $claimData['claim_source'] = 'outpatient';
                        }
                        if (in_array('insurance_provider', $claimColumns, true)) {
                            $claimData['insurance_provider'] = $patientData['insurance_provider'] ?? null;
                        }

                        $claimData = array_filter(
                            $claimData,
                            fn($value, $key) => in_array($key, $claimColumns, true),
                            ARRAY_FILTER_USE_BOTH
                        );

                        $db->table('insurance_claims')->insert($claimData);
                        echo "  - Created outpatient visit and insurance claim\n";
                    }
                }
            }
        }

        // ===================================================================
        // INPATIENT PATIENTS WITH INSURANCE
        // ===================================================================

        $inpatientPatients = [
            [
                'first_name' => 'Roberto',
                'middle_name' => 'Mendoza',
                'last_name' => 'Villanueva',
                'gender' => 'Male',
                'date_of_birth' => '1975-11-20',
                'civil_status' => 'Married',
                'contact_no' => '09201234567',
                'contact_number' => '09201234567',
                'email' => 'roberto.villanueva@example.com',
                'address' => '321 EDSA',
                'barangay' => 'Barangay 4',
                'city' => 'Quezon City',
                'province' => 'Metro Manila',
                'zip_code' => '1105',
                'blood_group' => 'AB+',
                'patient_type' => 'Inpatient',
                'status' => 'Active',
                'date_registered' => date('Y-m-d', strtotime('-5 days')),
                // Insurance Information
                'insurance_provider' => 'Maxicare',
                'insurance_number' => 'MAX-555666777',
                'insurance_card_number' => 'MAX-555666777',
                'hmo_member_id' => 'MAX-MEM-003',
                'hmo_cardholder_name' => 'Roberto Mendoza Villanueva',
                'hmo_coverage_type' => 'Premium',
                'hmo_expiry_date' => date('Y-m-d', strtotime('+1 year')),
                'hmo_approval_code' => 'MAX-APP-003',
                'hmo_contact_person' => 'Maxicare Claims Department',
                'payment_method' => 'HMO',
                'emergency_contact' => 'Maria Villanueva',
                'emergency_phone' => '09201234568',
                'medical_notes' => 'Admitted for pneumonia. Responding well to treatment.',
            ],
            [
                'first_name' => 'Carmen',
                'middle_name' => 'Ramos',
                'last_name' => 'Torres',
                'gender' => 'Female',
                'date_of_birth' => '1980-07-08',
                'civil_status' => 'Married',
                'contact_no' => '09211234567',
                'contact_number' => '09211234567',
                'email' => 'carmen.torres@example.com',
                'address' => '654 Ayala Avenue',
                'barangay' => 'Barangay 5',
                'city' => 'Makati City',
                'province' => 'Metro Manila',
                'zip_code' => '1226',
                'blood_group' => 'A-',
                'patient_type' => 'Inpatient',
                'status' => 'Active',
                'date_registered' => date('Y-m-d', strtotime('-3 days')),
                // Insurance Information
                'insurance_provider' => 'Intellicare',
                'insurance_number' => 'INT-888999000',
                'insurance_card_number' => 'INT-888999000',
                'hmo_member_id' => 'INT-MEM-004',
                'hmo_cardholder_name' => 'Carmen Ramos Torres',
                'hmo_coverage_type' => 'Standard',
                'hmo_expiry_date' => date('Y-m-d', strtotime('+8 months')),
                'hmo_approval_code' => 'INT-APP-004',
                'hmo_contact_person' => 'Intellicare Claims',
                'payment_method' => 'HMO',
                'emergency_contact' => 'Jose Torres',
                'emergency_phone' => '09211234568',
                'medical_notes' => 'Post-operative care. Stable condition.',
            ],
            [
                'first_name' => 'Miguel',
                'middle_name' => 'Fernandez',
                'last_name' => 'Bautista',
                'gender' => 'Male',
                'date_of_birth' => '1965-02-14',
                'civil_status' => 'Widowed',
                'contact_no' => '09221234567',
                'contact_number' => '09221234567',
                'email' => 'miguel.bautista@example.com',
                'address' => '987 Taft Avenue',
                'barangay' => 'Barangay 6',
                'city' => 'Manila',
                'province' => 'Metro Manila',
                'zip_code' => '1004',
                'blood_group' => 'O-',
                'patient_type' => 'Inpatient',
                'status' => 'Active',
                'date_registered' => date('Y-m-d', strtotime('-7 days')),
                // Insurance Information
                'insurance_provider' => 'PhilHealth',
                'insurance_number' => 'PH-111222333',
                'insurance_card_number' => 'PH-111222333',
                'payment_method' => 'PhilHealth',
                'emergency_contact' => 'Luis Bautista',
                'emergency_phone' => '09221234568',
                'medical_notes' => 'Diabetes management and monitoring.',
            ],
        ];

        // Insert inpatient patients and create admissions
        foreach ($inpatientPatients as $index => $patientData) {
            // Filter data based on available columns and map field names
            $columns = $db->getFieldNames($patientTable);
            $filteredData = [];
            
            foreach ($patientData as $key => $value) {
                // Map gender/sex and contact_no/contact_number
                if ($key === 'gender' && !in_array('gender', $columns, true) && in_array('sex', $columns, true)) {
                    $filteredData['sex'] = $value;
                } elseif ($key === 'sex' && !in_array('sex', $columns, true) && in_array('gender', $columns, true)) {
                    $filteredData['gender'] = $value;
                } elseif ($key === 'contact_no' && !in_array('contact_no', $columns, true) && in_array('contact_number', $columns, true)) {
                    $filteredData['contact_number'] = $value;
                } elseif ($key === 'contact_number' && !in_array('contact_number', $columns, true) && in_array('contact_no', $columns, true)) {
                    $filteredData['contact_no'] = $value;
                } elseif (in_array($key, $columns, true)) {
                    $filteredData[$key] = $value;
                }
            }

            $db->table($patientTable)->insert($filteredData);
            $patientId = (int) $db->insertID();

            if ($patientId) {
                echo "Created inpatient patient: {$patientData['first_name']} {$patientData['last_name']} (ID: {$patientId})\n";

                // Create inpatient admission
                if ($db->tableExists('inpatient_admissions')) {
                    // Get available columns for inpatient_admissions table
                    $admissionColumns = $db->getFieldNames('inpatient_admissions');
                    
                    $admissionData = [
                        'patient_id' => $patientId,
                        'admission_datetime' => date('Y-m-d H:i:s', strtotime('-' . (5 - $index) . ' days')),
                        'admission_type' => ['ER', 'Scheduled', 'ER'][$index % 3],
                        'admitting_diagnosis' => ['Pneumonia', 'Post-operative care', 'Diabetes with complications'][$index % 3],
                        'admitting_doctor' => ['Dr. Roberto Mendoza', 'Dr. Carmen Ramos', 'Dr. Miguel Fernandez'][$index % 3],
                        'patient_classification' => ['Medical', 'Surgical', 'Medical'][$index % 3],
                        'consent_signed' => 1,
                    ];
                    
                    // Filter to only include columns that exist
                    $admissionData = array_filter(
                        $admissionData,
                        fn($value, $key) => in_array($key, $admissionColumns, true),
                        ARRAY_FILTER_USE_BOTH
                    );

                    $db->table('inpatient_admissions')->insert($admissionData);
                    $admissionId = (int) $db->insertID();

                    if ($admissionId) {
                        // Create medical history
                        if ($db->tableExists('inpatient_medical_history')) {
                            $historyData = [
                                'admission_id' => $admissionId,
                                'allergies' => ['None', 'Latex', 'Sulfa drugs'][$index % 3],
                                'past_medical_history' => ['Hypertension, Asthma', 'Appendectomy (2015)', 'Diabetes Type 2, Hypertension'][$index % 3],
                                'past_surgical_history' => ['None', 'Appendectomy (2015)', 'Cholecystectomy (2010)'][$index % 3],
                                'family_history' => ['Father: Heart disease', 'Mother: Diabetes', 'Both parents: Diabetes'][$index % 3],
                                'current_medications' => ['Amlodipine 5mg, Salbutamol inhaler', 'Paracetamol 500mg', 'Metformin 500mg BID, Amlodipine 5mg'][$index % 3],
                            ];
                            $db->table('inpatient_medical_history')->insert($historyData);
                        }

                        // Create initial assessment
                        if ($db->tableExists('inpatient_initial_assessment')) {
                            $assessmentData = [
                                'admission_id' => $admissionId,
                                'blood_pressure' => ['130/85', '120/80', '145/90'][$index % 3],
                                'heart_rate' => ['88', '72', '92'][$index % 3],
                                'respiratory_rate' => ['22', '16', '18'][$index % 3],
                                'temperature' => ['38.2', '36.8', '37.1'][$index % 3],
                                'spo2' => ['94', '98', '96'][$index % 3],
                                'level_of_consciousness' => 'Alert',
                                'pain_level' => [4, 2, 3][$index % 3],
                                'initial_findings' => ['Fever, productive cough', 'Post-operative wound healing well', 'Elevated blood glucose'][$index % 3],
                                'remarks' => ['Requires antibiotic therapy', 'Stable post-op', 'Needs insulin adjustment'][$index % 3],
                            ];
                            $db->table('inpatient_initial_assessment')->insert($assessmentData);
                        }

                        // Create room assignment
                        if ($db->tableExists('inpatient_room_assignments')) {
                            $roomData = [
                                'admission_id' => $admissionId,
                                'room_type' => ['Private', 'Semi-private', 'Ward'][$index % 3],
                                'floor_number' => [3, 2, 1][$index % 3],
                                'room_number' => ['301', '205', '101'][$index % 3],
                                'bed_number' => ['A', 'B', 'C'][$index % 3],
                                'daily_rate' => [3500.00, 2500.00, 1500.00][$index % 3],
                            ];
                            $db->table('inpatient_room_assignments')->insert($roomData);
                        }

                        // Create insurance claim for inpatient
                        if ($db->tableExists('insurance_claims')) {
                            $claimColumns = $db->getFieldNames('insurance_claims');
                            $claimData = [
                                'ref_no' => 'INP-' . date('Ymd') . '-' . str_pad($admissionId, 4, '0', STR_PAD_LEFT),
                                'patient_name' => trim("{$patientData['first_name']} {$patientData['middle_name']} {$patientData['last_name']}"),
                                'policy_no' => $patientData['insurance_number'] ?? 'POL-' . $patientId,
                                'claim_amount' => rand(5000, 15000),
                                'diagnosis_code' => ['J18.9', 'Z48.0', 'E11.9'][$index % 3],
                                'notes' => 'Inpatient admission claim',
                                'status' => 'Pending',
                            ];

                            // Add optional fields if they exist
                            if (in_array('patient_id', $claimColumns, true)) {
                                $claimData['patient_id'] = $patientId;
                            }
                            if (in_array('admission_id', $claimColumns, true)) {
                                $claimData['admission_id'] = $admissionId;
                            }
                            if (in_array('claim_source', $claimColumns, true)) {
                                $claimData['claim_source'] = 'inpatient';
                            }
                            if (in_array('insurance_provider', $claimColumns, true)) {
                                $claimData['insurance_provider'] = $patientData['insurance_provider'] ?? null;
                            }

                            $claimData = array_filter(
                                $claimData,
                                fn($value, $key) => in_array($key, $claimColumns, true),
                                ARRAY_FILTER_USE_BOTH
                            );

                            $db->table('insurance_claims')->insert($claimData);
                        }

                        echo "  - Created inpatient admission (ID: {$admissionId}) with medical history, assessment, room assignment, and insurance claim\n";
                    }
                }
            }
        }

        echo "\nComprehensive patient seeder completed successfully!\n";
        echo "Created " . count($outpatientPatients) . " outpatient patients with visits and insurance.\n";
        echo "Created " . count($inpatientPatients) . " inpatient patients with admissions and insurance.\n";
    }
}

