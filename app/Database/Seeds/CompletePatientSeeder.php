<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CompletePatientSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        // Resolve patient table name (patient or patients)
        $patientTable = $db->tableExists('patient') ? 'patient' : 'patients';

        echo "Starting complete patient seeder with all form fields...\n\n";

        // ===================================================================
        // OUTPATIENT PATIENTS WITH ALL DETAILS
        // ===================================================================

        $outpatientPatients = [
            [
                // Personal Information
                'first_name' => 'Maria',
                'middle_name' => 'Santos',
                'last_name' => 'Reyes',
                'date_of_birth' => '1988-05-15',
                'gender' => 'Female',
                'sex' => 'Female',
                'civil_status' => 'Married',
                'phone' => '09171234567',
                'contact_number' => '09171234567',
                'contact_no' => '09171234567',
                'email' => 'maria.reyes@example.com',
                
                // Address Information
                'address' => '123 Rizal Street',
                'house_number' => '123',
                'subdivision' => 'Green Meadows Subdivision',
                'barangay' => 'Barangay 1',
                'city' => 'Quezon City',
                'province' => 'Metro Manila',
                'zip_code' => '1100',
                
                // Medical Information
                'blood_group' => 'A+',
                'patient_type' => 'Outpatient',
                'status' => 'Active',
                'date_registered' => date('Y-m-d', strtotime('-30 days')),
                
                // Emergency Contact
                'emergency_contact_name' => 'Juan Reyes',
                'emergency_contact' => 'Juan Reyes',
                'emergency_contact_relationship' => 'Spouse',
                'emergency_contact_phone' => '09171234568',
                'emergency_phone' => '09171234568',
                
                // Insurance Information
                'insurance_provider' => 'Maxicare',
                'insurance_number' => 'MAX-123456789',
                'insurance_card_number' => 'MAX-123456789',
                'insurance_validity' => date('Y-m-d', strtotime('+1 year')),
                'hmo_member_id' => 'MAX-MEM-001',
                'hmo_approval_code' => 'MAX-APP-001',
                'hmo_cardholder_name' => 'Maria Santos Reyes',
                'hmo_coverage_type' => 'Standard',
                'hmo_expiry_date' => date('Y-m-d', strtotime('+1 year')),
                'hmo_contact_person' => 'Maxicare Customer Service',
                'payment_type' => 'HMO',
                'payment_method' => 'HMO',
                
                // Medical Notes
                'medical_notes' => 'Regular check-up patient. No known allergies. Follow-up scheduled in 3 months.',
            ],
            [
                // Personal Information
                'first_name' => 'John',
                'middle_name' => 'Cruz',
                'last_name' => 'Dela Cruz',
                'date_of_birth' => '1992-08-22',
                'gender' => 'Male',
                'sex' => 'Male',
                'civil_status' => 'Single',
                'phone' => '09181234567',
                'contact_number' => '09181234567',
                'contact_no' => '09181234567',
                'email' => 'john.delacruz@example.com',
                
                // Address Information
                'address' => '456 Bonifacio Avenue',
                'house_number' => '456',
                'subdivision' => 'Sunset Village',
                'barangay' => 'Barangay 2',
                'city' => 'Manila',
                'province' => 'Metro Manila',
                'zip_code' => '1000',
                
                // Medical Information
                'blood_group' => 'O+',
                'patient_type' => 'Outpatient',
                'status' => 'Active',
                'date_registered' => date('Y-m-d', strtotime('-20 days')),
                
                // Emergency Contact
                'emergency_contact_name' => 'Mary Dela Cruz',
                'emergency_contact' => 'Mary Dela Cruz',
                'emergency_contact_relationship' => 'Sibling',
                'emergency_contact_phone' => '09181234568',
                'emergency_phone' => '09181234568',
                
                // Insurance Information
                'insurance_provider' => 'PhilHealth',
                'insurance_number' => 'PH-987654321',
                'insurance_card_number' => 'PH-987654321',
                'insurance_validity' => date('Y-m-d', strtotime('+1 year')),
                'payment_type' => 'PhilHealth',
                'payment_method' => 'PhilHealth',
                
                // Medical Notes
                'medical_notes' => 'Follow-up for hypertension management. Blood pressure well-controlled with medication.',
            ],
            [
                // Personal Information
                'first_name' => 'Ana',
                'middle_name' => 'Lopez',
                'last_name' => 'Garcia',
                'date_of_birth' => '1995-03-10',
                'gender' => 'Female',
                'sex' => 'Female',
                'civil_status' => 'Single',
                'phone' => '09191234567',
                'contact_number' => '09191234567',
                'contact_no' => '09191234567',
                'email' => 'ana.garcia@example.com',
                
                // Address Information
                'address' => '789 Mabini Street',
                'house_number' => '789',
                'subdivision' => 'Crystal Heights',
                'barangay' => 'Barangay 3',
                'city' => 'Makati City',
                'province' => 'Metro Manila',
                'zip_code' => '1200',
                
                // Medical Information
                'blood_group' => 'B+',
                'patient_type' => 'Outpatient',
                'status' => 'Active',
                'date_registered' => date('Y-m-d', strtotime('-15 days')),
                
                // Emergency Contact
                'emergency_contact_name' => 'Pedro Garcia',
                'emergency_contact' => 'Pedro Garcia',
                'emergency_contact_relationship' => 'Parent',
                'emergency_contact_phone' => '09191234568',
                'emergency_phone' => '09191234568',
                
                // Insurance Information
                'insurance_provider' => 'Intellicare',
                'insurance_number' => 'INT-456789123',
                'insurance_card_number' => 'INT-456789123',
                'insurance_validity' => date('Y-m-d', strtotime('+6 months')),
                'hmo_member_id' => 'INT-MEM-002',
                'hmo_approval_code' => 'INT-APP-002',
                'hmo_cardholder_name' => 'Ana Lopez Garcia',
                'hmo_coverage_type' => 'Premium',
                'hmo_expiry_date' => date('Y-m-d', strtotime('+6 months')),
                'hmo_contact_person' => 'Intellicare Support',
                'payment_type' => 'HMO',
                'payment_method' => 'HMO',
                
                // Medical Notes
                'medical_notes' => 'Annual physical examination scheduled. Patient in good health.',
            ],
        ];

        // Insert outpatient patients and create visits
        foreach ($outpatientPatients as $index => $patientData) {
            $patientId = $this->insertPatient($db, $patientTable, $patientData);
            
            if ($patientId) {
                echo "✓ Created outpatient patient: {$patientData['first_name']} {$patientData['last_name']} (ID: {$patientId})\n";

                // Create emergency contact
                $this->createEmergencyContact($db, $patientId, $patientData);

                // Create outpatient visit
                $visitData = [
                    'patient_id' => $patientId,
                    'department' => ['Internal Medicine', 'Cardiology', 'General Medicine'][$index % 3],
                    'assigned_doctor' => ['Dr. Maria Santos', 'Dr. John Cruz', 'Dr. Ana Lopez'][$index % 3],
                    'appointment_datetime' => date('Y-m-d H:i:s', strtotime('-' . (10 - $index * 2) . ' days')),
                    'visit_type' => ['New', 'Follow-up', 'New'][$index % 3],
                    'chief_complaint' => [
                        'Routine check-up and general health assessment',
                        'Hypertension follow-up - monitoring blood pressure control',
                        'Annual physical examination and preventive health screening'
                    ][$index % 3],
                    'allergies' => ['None known', 'Penicillin - causes rash', 'None known'][$index % 3],
                    'existing_conditions' => [
                        'None',
                        'Hypertension (diagnosed 2020)',
                        'None'
                    ][$index % 3],
                    'current_medications' => [
                        'Multivitamins daily',
                        'Amlodipine 5mg once daily',
                        'None'
                    ][$index % 3],
                    'blood_pressure' => ['120/80', '140/90', '118/75'][$index % 3],
                    'heart_rate' => ['72', '78', '70'][$index % 3],
                    'respiratory_rate' => ['16', '18', '16'][$index % 3],
                    'temperature' => ['36.8', '37.0', '36.7'][$index % 3],
                    'weight' => ['55', '75', '60'][$index % 3],
                    'height' => ['160', '175', '165'][$index % 3],
                    'payment_type' => ['HMO', 'PhilHealth', 'HMO'][$index % 3],
                ];

                if ($db->tableExists('outpatient_visits')) {
                    $db->table('outpatient_visits')->insert($visitData);
                    $visitId = (int) $db->insertID();
                    echo "  → Created outpatient visit (ID: {$visitId})\n";
                }
            }
        }

        echo "\n";

        // ===================================================================
        // INPATIENT PATIENTS WITH ALL DETAILS
        // ===================================================================

        $inpatientPatients = [
            [
                // Personal Information
                'first_name' => 'Roberto',
                'middle_name' => 'Mendoza',
                'last_name' => 'Villanueva',
                'date_of_birth' => '1975-11-20',
                'gender' => 'Male',
                'sex' => 'Male',
                'civil_status' => 'Married',
                'phone' => '09201234567',
                'contact_number' => '09201234567',
                'contact_no' => '09201234567',
                'email' => 'roberto.villanueva@example.com',
                
                // Address Information
                'address' => '321 EDSA',
                'house_number' => '321',
                'subdivision' => 'Metro Heights',
                'barangay' => 'Barangay 4',
                'city' => 'Quezon City',
                'province' => 'Metro Manila',
                'zip_code' => '1105',
                
                // Medical Information
                'blood_group' => 'AB+',
                'patient_type' => 'Inpatient',
                'status' => 'Active',
                'date_registered' => date('Y-m-d', strtotime('-5 days')),
                
                // Emergency Contact
                'emergency_contact_name' => 'Maria Villanueva',
                'emergency_contact' => 'Maria Villanueva',
                'emergency_contact_relationship' => 'Spouse',
                'emergency_contact_phone' => '09201234568',
                'emergency_phone' => '09201234568',
                
                // Insurance Information
                'insurance_provider' => 'Maxicare',
                'insurance_number' => 'MAX-555666777',
                'insurance_card_number' => 'MAX-555666777',
                'insurance_validity' => date('Y-m-d', strtotime('+1 year')),
                'hmo_member_id' => 'MAX-MEM-003',
                'hmo_approval_code' => 'MAX-APP-003',
                'hmo_cardholder_name' => 'Roberto Mendoza Villanueva',
                'hmo_coverage_type' => 'Premium',
                'hmo_expiry_date' => date('Y-m-d', strtotime('+1 year')),
                'hmo_contact_person' => 'Maxicare Claims Department',
                'payment_type' => 'HMO',
                'payment_method' => 'HMO',
                
                // Medical Notes
                'medical_notes' => 'Admitted for pneumonia. Responding well to antibiotic treatment. Vital signs stable.',
            ],
            [
                // Personal Information
                'first_name' => 'Carmen',
                'middle_name' => 'Ramos',
                'last_name' => 'Torres',
                'date_of_birth' => '1980-07-08',
                'gender' => 'Female',
                'sex' => 'Female',
                'civil_status' => 'Married',
                'phone' => '09211234567',
                'contact_number' => '09211234567',
                'contact_no' => '09211234567',
                'email' => 'carmen.torres@example.com',
                
                // Address Information
                'address' => '654 Ayala Avenue',
                'house_number' => '654',
                'subdivision' => 'Ayala Heights',
                'barangay' => 'Barangay 5',
                'city' => 'Makati City',
                'province' => 'Metro Manila',
                'zip_code' => '1226',
                
                // Medical Information
                'blood_group' => 'A-',
                'patient_type' => 'Inpatient',
                'status' => 'Active',
                'date_registered' => date('Y-m-d', strtotime('-3 days')),
                
                // Emergency Contact
                'emergency_contact_name' => 'Jose Torres',
                'emergency_contact' => 'Jose Torres',
                'emergency_contact_relationship' => 'Spouse',
                'emergency_contact_phone' => '09211234568',
                'emergency_phone' => '09211234568',
                
                // Insurance Information
                'insurance_provider' => 'Intellicare',
                'insurance_number' => 'INT-888999000',
                'insurance_card_number' => 'INT-888999000',
                'insurance_validity' => date('Y-m-d', strtotime('+8 months')),
                'hmo_member_id' => 'INT-MEM-004',
                'hmo_approval_code' => 'INT-APP-004',
                'hmo_cardholder_name' => 'Carmen Ramos Torres',
                'hmo_coverage_type' => 'Standard',
                'hmo_expiry_date' => date('Y-m-d', strtotime('+8 months')),
                'hmo_contact_person' => 'Intellicare Claims',
                'payment_type' => 'HMO',
                'payment_method' => 'HMO',
                
                // Medical Notes
                'medical_notes' => 'Post-operative care following appendectomy. Wound healing well. Stable condition.',
            ],
            [
                // Personal Information
                'first_name' => 'Miguel',
                'middle_name' => 'Fernandez',
                'last_name' => 'Bautista',
                'date_of_birth' => '1965-02-14',
                'gender' => 'Male',
                'sex' => 'Male',
                'civil_status' => 'Widowed',
                'phone' => '09221234567',
                'contact_number' => '09221234567',
                'contact_no' => '09221234567',
                'email' => 'miguel.bautista@example.com',
                
                // Address Information
                'address' => '987 Taft Avenue',
                'house_number' => '987',
                'subdivision' => 'Taft Residences',
                'barangay' => 'Barangay 6',
                'city' => 'Manila',
                'province' => 'Metro Manila',
                'zip_code' => '1004',
                
                // Medical Information
                'blood_group' => 'O-',
                'patient_type' => 'Inpatient',
                'status' => 'Active',
                'date_registered' => date('Y-m-d', strtotime('-7 days')),
                
                // Emergency Contact
                'emergency_contact_name' => 'Luis Bautista',
                'emergency_contact' => 'Luis Bautista',
                'emergency_contact_relationship' => 'Child',
                'emergency_contact_phone' => '09221234568',
                'emergency_phone' => '09221234568',
                
                // Insurance Information
                'insurance_provider' => 'PhilHealth',
                'insurance_number' => 'PH-111222333',
                'insurance_card_number' => 'PH-111222333',
                'insurance_validity' => date('Y-m-d', strtotime('+1 year')),
                'payment_type' => 'PhilHealth',
                'payment_method' => 'PhilHealth',
                
                // Medical Notes
                'medical_notes' => 'Diabetes management and monitoring. Blood glucose levels being stabilized with insulin therapy.',
            ],
        ];

        // Insert inpatient patients and create admissions
        foreach ($inpatientPatients as $index => $patientData) {
            $patientId = $this->insertPatient($db, $patientTable, $patientData);
            
            if ($patientId) {
                echo "✓ Created inpatient patient: {$patientData['first_name']} {$patientData['last_name']} (ID: {$patientId})\n";

                // Create emergency contact
                $this->createEmergencyContact($db, $patientId, $patientData);

                // Create inpatient admission
                if ($db->tableExists('inpatient_admissions')) {
                    $admissionColumns = $db->getFieldNames('inpatient_admissions');
                    
                    $admissionData = [
                        'patient_id' => $patientId,
                        'admission_datetime' => date('Y-m-d H:i:s', strtotime('-' . (5 - $index) . ' days')),
                        'admission_type' => ['ER', 'Scheduled', 'ER'][$index % 3],
                        'admitting_diagnosis' => [
                            'Community-acquired pneumonia, right lower lobe',
                            'Post-operative care - Appendectomy',
                            'Type 2 Diabetes Mellitus with complications, uncontrolled hyperglycemia'
                        ][$index % 3],
                        'admitting_doctor' => ['Dr. Roberto Mendoza', 'Dr. Carmen Ramos', 'Dr. Miguel Fernandez'][$index % 3],
                        'patient_classification' => ['Medical', 'Surgical', 'Medical'][$index % 3],
                        'consent_signed' => 1,
                    ];
                    
                    // Add insurance fields if they exist
                    if (in_array('insurance_provider', $admissionColumns, true)) {
                        $admissionData['insurance_provider'] = $patientData['insurance_provider'] ?? null;
                    }
                    if (in_array('insurance_card_number', $admissionColumns, true)) {
                        $admissionData['insurance_card_number'] = $patientData['insurance_card_number'] ?? null;
                    }
                    if (in_array('insurance_validity', $admissionColumns, true)) {
                        $admissionData['insurance_validity'] = $patientData['insurance_validity'] ?? null;
                    }
                    if (in_array('hmo_member_id', $admissionColumns, true)) {
                        $admissionData['hmo_member_id'] = $patientData['hmo_member_id'] ?? null;
                    }
                    if (in_array('hmo_approval_code', $admissionColumns, true)) {
                        $admissionData['hmo_approval_code'] = $patientData['hmo_approval_code'] ?? null;
                    }
                    if (in_array('hmo_cardholder_name', $admissionColumns, true)) {
                        $admissionData['hmo_cardholder_name'] = $patientData['hmo_cardholder_name'] ?? null;
                    }
                    if (in_array('hmo_coverage_type', $admissionColumns, true)) {
                        $admissionData['hmo_coverage_type'] = $patientData['hmo_coverage_type'] ?? null;
                    }
                    if (in_array('hmo_expiry_date', $admissionColumns, true)) {
                        $admissionData['hmo_expiry_date'] = $patientData['hmo_expiry_date'] ?? null;
                    }
                    if (in_array('hmo_contact_person', $admissionColumns, true)) {
                        $admissionData['hmo_contact_person'] = $patientData['hmo_contact_person'] ?? null;
                    }
                    
                    // Filter to only include columns that exist
                    $admissionData = array_filter(
                        $admissionData,
                        fn($value, $key) => in_array($key, $admissionColumns, true),
                        ARRAY_FILTER_USE_BOTH
                    );

                    $db->table('inpatient_admissions')->insert($admissionData);
                    $admissionId = (int) $db->insertID();

                    if ($admissionId) {
                        echo "  → Created inpatient admission (ID: {$admissionId})\n";

                        // Create medical history
                        if ($db->tableExists('inpatient_medical_history')) {
                            $historyData = [
                                'admission_id' => $admissionId,
                                'allergies' => [
                                    'None known',
                                    'Latex - causes contact dermatitis',
                                    'Sulfa drugs - causes rash'
                                ][$index % 3],
                                'past_medical_history' => [
                                    'Hypertension (diagnosed 2010), Asthma (childhood)',
                                    'Appendectomy (2015), No other significant medical history',
                                    'Diabetes Type 2 (diagnosed 2015), Hypertension (diagnosed 2012)'
                                ][$index % 3],
                                'past_surgical_history' => [
                                    'None',
                                    'Appendectomy (2015)',
                                    'Cholecystectomy (2010)'
                                ][$index % 3],
                                'family_history' => [
                                    'Father: Heart disease, Mother: Hypertension',
                                    'Mother: Diabetes, Father: No significant history',
                                    'Both parents: Diabetes, Maternal grandmother: Heart disease'
                                ][$index % 3],
                                'current_medications' => [
                                    'Amlodipine 5mg once daily, Salbutamol inhaler PRN',
                                    'Paracetamol 500mg every 6 hours as needed',
                                    'Metformin 500mg twice daily, Amlodipine 5mg once daily, Insulin glargine 20 units at bedtime'
                                ][$index % 3],
                            ];
                            $db->table('inpatient_medical_history')->insert($historyData);
                            echo "    → Created medical history\n";
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
                                'initial_findings' => [
                                    'Fever, productive cough with yellow sputum, decreased breath sounds on right lower lobe',
                                    'Post-operative wound healing well, minimal serous drainage, no signs of infection',
                                    'Elevated blood glucose (280 mg/dL), polyuria, polydipsia, fatigue'
                                ][$index % 3],
                                'remarks' => [
                                    'Requires antibiotic therapy, oxygen support, chest physiotherapy',
                                    'Stable post-op, continue wound care, advance diet as tolerated',
                                    'Needs insulin adjustment, blood glucose monitoring, diabetic diet'
                                ][$index % 3],
                            ];
                            $db->table('inpatient_initial_assessment')->insert($assessmentData);
                            echo "    → Created initial assessment\n";
                        }

                        // Create room assignment
                        if ($db->tableExists('inpatient_room_assignments')) {
                            $roomData = [
                                'admission_id' => $admissionId,
                                'room_type' => ['Private', 'Semi-Private', 'Ward'][$index % 3],
                                'floor_number' => [3, 2, 1][$index % 3],
                                'room_number' => ['301', '205', '101'][$index % 3],
                                'bed_number' => ['A', 'B', 'C'][$index % 3],
                                'daily_rate' => [3500.00, 2500.00, 1500.00][$index % 3],
                            ];
                            $db->table('inpatient_room_assignments')->insert($roomData);
                            echo "    → Created room assignment\n";
                        }
                    }
                }
            }
        }

        echo "\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "Complete patient seeder finished successfully!\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "Created " . count($outpatientPatients) . " outpatient patients with all details\n";
        echo "Created " . count($inpatientPatients) . " inpatient patients with all details\n";
        echo "═══════════════════════════════════════════════════════════════\n";
    }

    /**
     * Insert patient data with field mapping
     */
    private function insertPatient($db, $patientTable, $patientData)
    {
        $columns = $db->getFieldNames($patientTable);
        $filteredData = [];
        
        foreach ($patientData as $key => $value) {
            // Map gender/sex
            if ($key === 'gender' && !in_array('gender', $columns, true) && in_array('sex', $columns, true)) {
                $filteredData['sex'] = $value;
            } elseif ($key === 'sex' && !in_array('sex', $columns, true) && in_array('gender', $columns, true)) {
                $filteredData['gender'] = $value;
            }
            // Map contact fields
            elseif ($key === 'phone' && !in_array('phone', $columns, true)) {
                if (in_array('contact_number', $columns, true)) {
                    $filteredData['contact_number'] = $value;
                } elseif (in_array('contact_no', $columns, true)) {
                    $filteredData['contact_no'] = $value;
                }
            }
            // Include field if it exists in table
            elseif (in_array($key, $columns, true)) {
                $filteredData[$key] = $value;
            }
        }

        $db->table($patientTable)->insert($filteredData);
        return (int) $db->insertID();
    }

    /**
     * Create emergency contact record
     */
    private function createEmergencyContact($db, $patientId, $patientData)
    {
        if (!$db->tableExists('emergency_contacts')) {
            return;
        }

        $contactData = [
            'patient_id' => $patientId,
            'name' => $patientData['emergency_contact_name'] ?? $patientData['emergency_contact'] ?? 'N/A',
            'relationship' => $patientData['emergency_contact_relationship'] ?? 'Other',
            'contact_number' => $patientData['emergency_contact_phone'] ?? $patientData['emergency_phone'] ?? 'N/A',
        ];

        $db->table('emergency_contacts')->insert($contactData);
    }
}

