<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AssignDoctorSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        // Resolve patient table name (patient or patients)
        $patientTable = $db->tableExists('patient') ? 'patient' : 'patients';

        echo "Starting doctor assignment seeder...\n\n";

        // Check if required tables exist
        if (!$db->tableExists($patientTable)) {
            echo "Error: Patient table '{$patientTable}' does not exist.\n";
            return;
        }

        if (!$db->tableExists('doctor')) {
            echo "Error: Doctor table does not exist.\n";
            return;
        }

        if (!$db->tableExists('staff')) {
            echo "Error: Staff table does not exist.\n";
            return;
        }

        // Check if primary_doctor_id column exists
        $hasPrimaryDoctorColumn = false;
        try {
            $fields = $db->getFieldData($patientTable);
            foreach ($fields as $field) {
                if ($field->name === 'primary_doctor_id') {
                    $hasPrimaryDoctorColumn = true;
                    break;
                }
            }
        } catch (\Throwable $e) {
            echo "Error checking patient table columns: " . $e->getMessage() . "\n";
            return;
        }

        if (!$hasPrimaryDoctorColumn) {
            echo "Warning: 'primary_doctor_id' column does not exist in '{$patientTable}' table.\n";
            echo "Please run the migration to add this column first.\n";
            return;
        }

        // Get ALL existing doctors from the database (active first, then inactive as fallback)
        // This uses any doctors that exist in the database, regardless of how they were created
        $doctors = $db->table('doctor d')
            ->select('d.doctor_id, d.specialization, d.status, s.first_name, s.last_name')
            ->join('staff s', 's.staff_id = d.staff_id', 'inner')
            ->orderBy('d.status', 'ASC') // Active first, then Inactive
            ->get()
            ->getResultArray();

        if (empty($doctors)) {
            echo "Error: No doctors found in the database.\n";
            echo "Please ensure doctors exist in the 'doctor' table with corresponding 'staff' records.\n";
            return;
        }

        // Filter to active doctors first, but keep inactive as fallback
        $activeDoctors = array_filter($doctors, function($doc) {
            return strtolower($doc['status'] ?? '') === 'active';
        });

        if (empty($activeDoctors)) {
            echo "Warning: No active doctors found. Using inactive doctors as fallback.\n";
            $doctors = array_values($doctors); // Use all doctors (inactive)
        } else {
            $doctors = array_values($activeDoctors); // Use only active doctors
        }

        echo "Found " . count($doctors) . " doctor(s) from existing database:\n";
        foreach ($doctors as $doc) {
            $statusLabel = strtolower($doc['status'] ?? '') === 'active' ? 'Active' : 'Inactive';
            echo "  - Dr. {$doc['first_name']} {$doc['last_name']} ({$doc['specialization']}) [ID: {$doc['doctor_id']}, Status: {$statusLabel}]\n";
        }
        echo "\n";

        // Get all patients without assigned doctors
        $patients = $db->table($patientTable)
            ->select('patient_id, first_name, middle_name, last_name, date_of_birth, primary_doctor_id')
            ->where('primary_doctor_id IS NULL', null, false)
            ->orWhere('primary_doctor_id', 0)
            ->get()
            ->getResultArray();

        if (empty($patients)) {
            echo "No patients found without assigned doctors.\n";
            return;
        }

        echo "Found " . count($patients) . " patient(s) without assigned doctors.\n\n";

        // Separate doctors by specialization type
        $pediatricDoctors = [];
        $adultDoctors = [];
        $generalDoctors = [];

        $pediatricKeywords = ['pediatric', 'pediatrics', 'pediatrician', 'neonatal', 'neonatology'];

        foreach ($doctors as $doctor) {
            $specialization = strtolower($doctor['specialization'] ?? '');
            $isPediatric = false;

            foreach ($pediatricKeywords as $keyword) {
                if (strpos($specialization, $keyword) !== false) {
                    $isPediatric = true;
                    break;
                }
            }

            if ($isPediatric) {
                $pediatricDoctors[] = $doctor;
            } elseif (stripos($specialization, 'general') !== false || stripos($specialization, 'family') !== false) {
                $generalDoctors[] = $doctor;
            } else {
                $adultDoctors[] = $doctor;
            }
        }

        echo "Doctor distribution:\n";
        echo "  - Pediatric doctors: " . count($pediatricDoctors) . "\n";
        echo "  - Adult doctors: " . count($adultDoctors) . "\n";
        echo "  - General practice doctors: " . count($generalDoctors) . "\n";
        echo "\n";

        // Combine general doctors with adult doctors for assignment
        $allAdultDoctors = array_merge($adultDoctors, $generalDoctors);

        // Initialize round-robin counters for even distribution
        $pediatricIndex = 0;
        $adultIndex = 0;
        $generalIndex = 0;
        $allAdultIndex = 0;

        $assigned = 0;
        $skipped = 0;
        $errors = 0;

        // Calculate age and assign doctors
        $currentDate = new \DateTime();
        
        foreach ($patients as $patient) {
            try {
                $patientId = (int) $patient['patient_id'];
                $dateOfBirth = $patient['date_of_birth'] ?? null;

                // Calculate patient age
                $age = null;
                if ($dateOfBirth) {
                    try {
                        $birthDate = new \DateTime($dateOfBirth);
                        $age = $currentDate->diff($birthDate)->y;
                    } catch (\Throwable $e) {
                        echo "Warning: Invalid date of birth for patient ID {$patientId}: {$dateOfBirth}\n";
                    }
                }

                // Select appropriate doctor based on age using round-robin for even distribution
                $selectedDoctor = null;

                if ($age !== null && $age < 18) {
                    // Pediatric patient
                    if (!empty($pediatricDoctors)) {
                        $selectedDoctor = $pediatricDoctors[$pediatricIndex % count($pediatricDoctors)];
                        $pediatricIndex++;
                    } elseif (!empty($generalDoctors)) {
                        // Fallback to general practice if no pediatric doctors
                        $selectedDoctor = $generalDoctors[$generalIndex % count($generalDoctors)];
                        $generalIndex++;
                    } elseif (!empty($allAdultDoctors)) {
                        // Last resort: use any available doctor
                        $selectedDoctor = $allAdultDoctors[$allAdultIndex % count($allAdultDoctors)];
                        $allAdultIndex++;
                    }
                } else {
                    // Adult patient (18+ or age unknown)
                    if (!empty($allAdultDoctors)) {
                        $selectedDoctor = $allAdultDoctors[$allAdultIndex % count($allAdultDoctors)];
                        $allAdultIndex++;
                    } elseif (!empty($generalDoctors)) {
                        $selectedDoctor = $generalDoctors[$generalIndex % count($generalDoctors)];
                        $generalIndex++;
                    } elseif (!empty($pediatricDoctors)) {
                        // Last resort: use any available doctor
                        $selectedDoctor = $pediatricDoctors[$pediatricIndex % count($pediatricDoctors)];
                        $pediatricIndex++;
                    }
                }

                if (!$selectedDoctor) {
                    echo "Warning: No doctor available to assign to patient ID {$patientId}\n";
                    $skipped++;
                    continue;
                }

                // Update patient with assigned doctor
                $updateData = [
                    'primary_doctor_id' => (int) $selectedDoctor['doctor_id']
                ];

                $result = $db->table($patientTable)
                    ->where('patient_id', $patientId)
                    ->update($updateData);

                if ($result) {
                    $patientName = trim(($patient['first_name'] ?? '') . ' ' . ($patient['middle_name'] ?? '') . ' ' . ($patient['last_name'] ?? ''));
                    $doctorName = trim($selectedDoctor['first_name'] . ' ' . $selectedDoctor['last_name']);
                    $ageStr = $age !== null ? " (Age: {$age})" : "";
                    
                    echo "  ✓ Assigned Dr. {$doctorName} ({$selectedDoctor['specialization']}) to patient: {$patientName}{$ageStr}\n";
                    $assigned++;
                } else {
                    echo "  ✗ Failed to assign doctor to patient ID {$patientId}\n";
                    $errors++;
                }

            } catch (\Throwable $e) {
                echo "  ✗ Error assigning doctor to patient ID {$patient['patient_id']}: " . $e->getMessage() . "\n";
                $errors++;
            }
        }

        echo "\n";
        echo "========================================\n";
        echo "Assignment Summary:\n";
        echo "  - Successfully assigned: {$assigned}\n";
        echo "  - Skipped: {$skipped}\n";
        echo "  - Errors: {$errors}\n";
        echo "========================================\n";
    }
}

