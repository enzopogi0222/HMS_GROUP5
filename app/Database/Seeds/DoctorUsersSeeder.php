<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DoctorUsersSeeder extends Seeder
{
    public function run()
    {
        $db = $this->db;
        
        // Check if required tables exist
        if (!$db->tableExists('staff') || !$db->tableExists('doctor') || !$db->tableExists('users') || !$db->tableExists('roles')) {
            echo "Required tables do not exist. Please run migrations first.\n";
            return;
        }

        // Get doctor role_id
        $doctorRole = $db->table('roles')
            ->where('slug', 'doctor')
            ->get()
            ->getRowArray();
        
        if (!$doctorRole) {
            echo "Doctor role not found. Please ensure roles are seeded first.\n";
            return;
        }
        
        $doctorRoleId = (int) $doctorRole['role_id'];

        // Get departments for mapping specializations
        $departments = $db->table('department')
            ->select('department_id, name')
            ->get()
            ->getResultArray();
        
        $departmentMap = [];
        foreach ($departments as $dept) {
            $departmentMap[strtolower($dept['name'])] = $dept['department_id'];
        }

        // Define doctors with their specializations
        // Each specialization gets one doctor
        $doctors = [
            [
                'first_name' => 'John',
                'last_name' => 'Smith',
                'specialization' => 'Internal Medicine',
                'department' => 'Internal Medicine',
                'email' => 'john.smith@hospital.com',
                'username' => 'dr.smith',
                'employee_id' => 'DOC-IM-001',
                'license_no' => 'MD-IM-001',
            ],
            [
                'first_name' => 'Sarah',
                'last_name' => 'Johnson',
                'specialization' => 'Pediatrics',
                'department' => 'Pediatrics',
                'email' => 'sarah.johnson@hospital.com',
                'username' => 'dr.johnson',
                'employee_id' => 'DOC-PED-001',
                'license_no' => 'MD-PED-001',
            ],
            [
                'first_name' => 'Michael',
                'last_name' => 'Williams',
                'specialization' => 'OB-GYN',
                'department' => 'OB-GYN',
                'email' => 'michael.williams@hospital.com',
                'username' => 'dr.williams',
                'employee_id' => 'DOC-OBGYN-001',
                'license_no' => 'MD-OBGYN-001',
            ],
            [
                'first_name' => 'Emily',
                'last_name' => 'Brown',
                'specialization' => 'General Surgery',
                'department' => 'General Surgery',
                'email' => 'emily.brown@hospital.com',
                'username' => 'dr.brown',
                'employee_id' => 'DOC-SUR-001',
                'license_no' => 'MD-SUR-001',
            ],
            [
                'first_name' => 'David',
                'last_name' => 'Jones',
                'specialization' => 'Orthopedics',
                'department' => 'Orthopedics',
                'email' => 'david.jones@hospital.com',
                'username' => 'dr.jones',
                'employee_id' => 'DOC-ORTHO-001',
                'license_no' => 'MD-ORTHO-001',
            ],
            [
                'first_name' => 'Jennifer',
                'last_name' => 'Garcia',
                'specialization' => 'Cardiology',
                'department' => 'Cardiology',
                'email' => 'jennifer.garcia@hospital.com',
                'username' => 'dr.garcia',
                'employee_id' => 'DOC-CARD-001',
                'license_no' => 'MD-CARD-001',
            ],
            [
                'first_name' => 'Robert',
                'last_name' => 'Miller',
                'specialization' => 'Neurology',
                'department' => 'Neurology',
                'email' => 'robert.miller@hospital.com',
                'username' => 'dr.miller',
                'employee_id' => 'DOC-NEURO-001',
                'license_no' => 'MD-NEURO-001',
            ],
            [
                'first_name' => 'Lisa',
                'last_name' => 'Davis',
                'specialization' => 'Pulmonology',
                'department' => 'Pulmonology',
                'email' => 'lisa.davis@hospital.com',
                'username' => 'dr.davis',
                'employee_id' => 'DOC-PULMO-001',
                'license_no' => 'MD-PULMO-001',
            ],
            [
                'first_name' => 'James',
                'last_name' => 'Rodriguez',
                'specialization' => 'Gastroenterology',
                'department' => 'Gastroenterology',
                'email' => 'james.rodriguez@hospital.com',
                'username' => 'dr.rodriguez',
                'employee_id' => 'DOC-GI-001',
                'license_no' => 'MD-GI-001',
            ],
            [
                'first_name' => 'Patricia',
                'last_name' => 'Martinez',
                'specialization' => 'Dermatology',
                'department' => 'Dermatology',
                'email' => 'patricia.martinez@hospital.com',
                'username' => 'dr.martinez',
                'employee_id' => 'DOC-DERM-001',
                'license_no' => 'MD-DERM-001',
            ],
            [
                'first_name' => 'William',
                'last_name' => 'Hernandez',
                'specialization' => 'Ophthalmology',
                'department' => 'Ophthalmology',
                'email' => 'william.hernandez@hospital.com',
                'username' => 'dr.hernandez',
                'employee_id' => 'DOC-OPHTHA-001',
                'license_no' => 'MD-OPHTHA-001',
            ],
            [
                'first_name' => 'Linda',
                'last_name' => 'Lopez',
                'specialization' => 'ENT',
                'department' => 'ENT',
                'email' => 'linda.lopez@hospital.com',
                'username' => 'dr.lopez',
                'employee_id' => 'DOC-ENT-001',
                'license_no' => 'MD-ENT-001',
            ],
            [
                'first_name' => 'Richard',
                'last_name' => 'Wilson',
                'specialization' => 'Psychiatry',
                'department' => 'Psychiatry',
                'email' => 'richard.wilson@hospital.com',
                'username' => 'dr.wilson',
                'employee_id' => 'DOC-PSY-001',
                'license_no' => 'MD-PSY-001',
            ],
            [
                'first_name' => 'Barbara',
                'last_name' => 'Anderson',
                'specialization' => 'Oncology',
                'department' => 'Oncology',
                'email' => 'barbara.anderson@hospital.com',
                'username' => 'dr.anderson',
                'employee_id' => 'DOC-ONC-001',
                'license_no' => 'MD-ONC-001',
            ],
            [
                'first_name' => 'Joseph',
                'last_name' => 'Thomas',
                'specialization' => 'Infectious Diseases',
                'department' => 'Infectious Diseases',
                'email' => 'joseph.thomas@hospital.com',
                'username' => 'dr.thomas',
                'employee_id' => 'DOC-ID-001',
                'license_no' => 'MD-ID-001',
            ],
            [
                'first_name' => 'Susan',
                'last_name' => 'Jackson',
                'specialization' => 'Endocrinology',
                'department' => 'Endocrinology',
                'email' => 'susan.jackson@hospital.com',
                'username' => 'dr.jackson',
                'employee_id' => 'DOC-ENDO-001',
                'license_no' => 'MD-ENDO-001',
            ],
            [
                'first_name' => 'Thomas',
                'last_name' => 'White',
                'specialization' => 'Urology',
                'department' => 'Urology',
                'email' => 'thomas.white@hospital.com',
                'username' => 'dr.white',
                'employee_id' => 'DOC-URO-001',
                'license_no' => 'MD-URO-001',
            ],
            [
                'first_name' => 'Jessica',
                'last_name' => 'Harris',
                'specialization' => 'Anesthesiology',
                'department' => 'Anesthesiology',
                'email' => 'jessica.harris@hospital.com',
                'username' => 'dr.harris',
                'employee_id' => 'DOC-ANES-001',
                'license_no' => 'MD-ANES-001',
            ],
            [
                'first_name' => 'Charles',
                'last_name' => 'Martin',
                'specialization' => 'Rheumatology',
                'department' => 'Rheumatology',
                'email' => 'charles.martin@hospital.com',
                'username' => 'dr.martin',
                'employee_id' => 'DOC-RHEUM-001',
                'license_no' => 'MD-RHEUM-001',
            ],
            [
                'first_name' => 'Karen',
                'last_name' => 'Thompson',
                'specialization' => 'Emergency Medicine',
                'department' => 'Emergency Department',
                'email' => 'karen.thompson@hospital.com',
                'username' => 'dr.thompson',
                'employee_id' => 'DOC-ER-001',
                'license_no' => 'MD-ER-001',
            ],
            [
                'first_name' => 'Christopher',
                'last_name' => 'Moore',
                'specialization' => 'General Practice',
                'department' => 'Outpatient Department',
                'email' => 'christopher.moore@hospital.com',
                'username' => 'dr.moore',
                'employee_id' => 'DOC-GP-001',
                'license_no' => 'MD-GP-001',
            ],
        ];

        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($doctors as $doctorData) {
            try {
                $db->transStart();

                // Check if doctor with this specialization already exists
                $existingDoctor = $db->table('doctor d')
                    ->join('staff s', 's.staff_id = d.staff_id')
                    ->where('d.specialization', $doctorData['specialization'])
                    ->get()
                    ->getRowArray();

                if ($existingDoctor) {
                    $skipped++;
                    $db->transRollback();
                    echo "Skipped: Doctor with specialization '{$doctorData['specialization']}' already exists.\n";
                    continue;
                }

                // Check if username already exists
                $existingUser = $db->table('users')
                    ->where('username', $doctorData['username'])
                    ->get()
                    ->getRowArray();

                if ($existingUser) {
                    $skipped++;
                    $db->transRollback();
                    echo "Skipped: Username '{$doctorData['username']}' already exists.\n";
                    continue;
                }

                // Get department_id
                $departmentId = null;
                $deptName = strtolower($doctorData['department']);
                if (isset($departmentMap[$deptName])) {
                    $departmentId = $departmentMap[$deptName];
                } else {
                    // Try partial match
                    foreach ($departmentMap as $deptKey => $deptId) {
                        if (strpos($deptKey, $deptName) !== false || strpos($deptName, $deptKey) !== false) {
                            $departmentId = $deptId;
                            break;
                        }
                    }
                }

                // Create staff record
                $staffData = [
                    'employee_id' => $doctorData['employee_id'],
                    'department_id' => $departmentId,
                    'first_name' => $doctorData['first_name'],
                    'last_name' => $doctorData['last_name'],
                    'email' => $doctorData['email'],
                    'role_id' => $doctorRoleId,
                    'date_joined' => date('Y-m-d'),
                ];

                if (!$db->table('staff')->insert($staffData)) {
                    throw new \Exception('Failed to insert staff record');
                }

                $staffId = $db->insertID();

                // Create doctor record
                $doctorRecord = [
                    'staff_id' => $staffId,
                    'specialization' => $doctorData['specialization'],
                    'license_no' => $doctorData['license_no'],
                    'status' => 'Active',
                ];

                if (!$db->table('doctor')->insert($doctorRecord)) {
                    throw new \Exception('Failed to insert doctor record');
                }

                // Create user account
                $userData = [
                    'staff_id' => $staffId,
                    'username' => $doctorData['username'],
                    'email' => $doctorData['email'],
                    'password' => password_hash('doctor123', PASSWORD_DEFAULT), // Default password
                    'role_id' => $doctorRoleId,
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                if (!$db->table('users')->insert($userData)) {
                    throw new \Exception('Failed to insert user record');
                }

                $db->transComplete();

                if ($db->transStatus() === false) {
                    throw new \Exception('Transaction failed');
                }

                $created++;
                echo "Created: Dr. {$doctorData['first_name']} {$doctorData['last_name']} - {$doctorData['specialization']} (Username: {$doctorData['username']}, Password: doctor123)\n";

            } catch (\Exception $e) {
                $db->transRollback();
                $errors++;
                echo "Error creating {$doctorData['specialization']}: " . $e->getMessage() . "\n";
            }
        }

        echo "\n=== Summary ===\n";
        echo "Created: $created doctor(s)\n";
        echo "Skipped: $skipped doctor(s)\n";
        echo "Errors: $errors doctor(s)\n";
        echo "\nDefault password for all doctors: doctor123\n";
    }
}

