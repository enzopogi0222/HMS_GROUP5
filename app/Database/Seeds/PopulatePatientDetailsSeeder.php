<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PopulatePatientDetailsSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        // Resolve patient table name
        $patientTable = $db->tableExists('patient') ? 'patient' : 'patients';

        echo "Starting patient details population seeder...\n\n";

        // Check if required tables exist
        if (!$db->tableExists($patientTable)) {
            echo "Error: Patient table '{$patientTable}' does not exist.\n";
            return;
        }

        // Check if blood_group column exists
        $hasBloodGroup = false;
        try {
            $fields = $db->getFieldData($patientTable);
            foreach ($fields as $field) {
                if ($field->name === 'blood_group') {
                    $hasBloodGroup = true;
                    break;
                }
            }
        } catch (\Throwable $e) {
            echo "Warning: Could not check for blood_group column: " . $e->getMessage() . "\n";
        }

        // Build select query based on available columns
        $selectFields = ['patient_id', 'first_name', 'middle_name', 'last_name', 'primary_doctor_id'];
        if ($hasBloodGroup) {
            $selectFields[] = 'blood_group';
        }

        // Get all patients
        $patients = $db->table($patientTable)
            ->select(implode(', ', $selectFields))
            ->get()
            ->getResultArray();

        if (empty($patients)) {
            echo "No patients found in the database.\n";
            return;
        }

        echo "Found " . count($patients) . " patient(s).\n\n";

        // Common blood groups distribution
        $bloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        
        // Weighted distribution (most common blood types)
        $weightedBloodGroups = [
            'O+', 'O+', 'O+', 'O+',  // 40% O+
            'A+', 'A+', 'A+',        // 30% A+
            'B+', 'B+',              // 20% B+
            'AB+',                   // 5% AB+
            'O-',                    // 4% O-
            'A-',                    // 1% A-
            'B-',                    // Rare
            'AB-',                   // Rare
        ];

        $bloodGroupUpdated = 0;
        $doctorAssigned = 0;
        $doctorChecked = 0;

        foreach ($patients as $patient) {
            $patientId = (int) $patient['patient_id'];
            $updates = [];
            $updated = false;

            // Fix Blood Group if missing (only if column exists)
            if ($hasBloodGroup) {
                if (empty($patient['blood_group']) || $patient['blood_group'] === 'N/A' || $patient['blood_group'] === null) {
                    // Use weighted random selection for more realistic distribution
                    $selectedBloodGroup = $weightedBloodGroups[array_rand($weightedBloodGroups)];
                    $updates['blood_group'] = $selectedBloodGroup;
                    $updated = true;
                    $bloodGroupUpdated++;
                    
                    $patientName = trim(($patient['first_name'] ?? '') . ' ' . ($patient['middle_name'] ?? '') . ' ' . ($patient['last_name'] ?? ''));
                    echo "  → Patient: {$patientName} - Added blood group: {$selectedBloodGroup}\n";
                }
            }

            // Check and fix Assigned Doctor if missing
            if (empty($patient['primary_doctor_id']) || $patient['primary_doctor_id'] == 0) {
                // Get available active doctors
                $doctors = $db->table('doctor d')
                    ->select('d.doctor_id, d.specialization, s.first_name, s.last_name')
                    ->join('staff s', 's.staff_id = d.staff_id', 'inner')
                    ->where('d.status', 'Active')
                    ->get()
                    ->getResultArray();

                if (!empty($doctors)) {
                    // Select a random doctor
                    $selectedDoctor = $doctors[array_rand($doctors)];
                    $updates['primary_doctor_id'] = (int) $selectedDoctor['doctor_id'];
                    $updated = true;
                    $doctorAssigned++;
                    
                    $patientName = trim(($patient['first_name'] ?? '') . ' ' . ($patient['middle_name'] ?? '') . ' ' . ($patient['last_name'] ?? ''));
                    $doctorName = trim($selectedDoctor['first_name'] . ' ' . $selectedDoctor['last_name']);
                    echo "  → Patient: {$patientName} - Assigned doctor: Dr. {$doctorName}\n";
                } else {
                    echo "  ⚠ Patient ID {$patientId}: No active doctors available for assignment.\n";
                }
            } else {
                // Verify the assigned doctor exists
                $doctorExists = $db->table('doctor')
                    ->where('doctor_id', $patient['primary_doctor_id'])
                    ->countAllResults() > 0;
                
                if (!$doctorExists) {
                    echo "  ⚠ Patient ID {$patientId}: Assigned doctor (ID: {$patient['primary_doctor_id']}) does not exist. Removing assignment.\n";
                    $updates['primary_doctor_id'] = null;
                    $updated = true;
                } else {
                    $doctorChecked++;
                }
            }

            // Update patient if there are changes
            if ($updated && !empty($updates)) {
                $result = $db->table($patientTable)
                    ->where('patient_id', $patientId)
                    ->update($updates);

                if (!$result) {
                    echo "  ✗ Failed to update patient ID {$patientId}\n";
                }
            }
        }

        echo "\n";
        echo "========================================\n";
        echo "Population Summary:\n";
        echo "  - Blood groups added/updated: {$bloodGroupUpdated}\n";
        echo "  - Doctors assigned: {$doctorAssigned}\n";
        echo "  - Existing doctor assignments verified: {$doctorChecked}\n";
        echo "========================================\n";
    }
}

