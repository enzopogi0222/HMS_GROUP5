<?php

namespace App\Controllers;

class TestController extends BaseController
{
    public function doctors()
    {
        echo "<h1>🏥 Doctor Database Test</h1>";
        echo "<style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { background: #f3f4f6; padding: 15px; border-left: 4px solid #3b82f6; margin: 10px 0; }
        .warning { background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 10px 0; }
        table { border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        </style>";

        try {
            $db = \Config\Database::connect();

            echo "<h3>✅ Database Connection: <span class='success'>SUCCESS</span></h3>";
            echo "<p>📊 Database: <strong>" . $db->getDatabase() . "</strong></p>";

            // Check tables
            echo "<h2>🔍 Checking Tables</h2>";
            
            $staffExists = $db->tableExists('staff');
            $doctorExists = $db->tableExists('doctor');
            
            if (!$staffExists) {
                echo "<p class='error'>❌ Staff table doesn't exist</p>";
            } else {
                $staffCount = $db->table('staff')->countAll();
                echo "<p class='success'>✅ Staff table exists ($staffCount records)</p>";
            }

            if (!$doctorExists) {
                echo "<p class='error'>❌ Doctor table doesn't exist</p>";
            } else {
                $doctorCount = $db->table('doctor')->countAll();
                echo "<p class='success'>✅ Doctor table exists ($doctorCount records)</p>";
            }

            if ($staffExists && $doctorExists) {
                echo "<h2>👨‍⚕️ Current Doctors</h2>";
                
                $doctors = $db->table('doctor d')
                    ->select('s.staff_id, s.first_name, s.last_name, s.department, d.specialization')
                    ->join('staff s', 's.staff_id = d.staff_id', 'left')
                    ->get()
                    ->getResultArray();

                if (empty($doctors)) {
                    echo "<p class='warning'>⚠️ No doctors found. Adding sample doctors...</p>";
                    
                    // Add sample doctors
                    $sampleDoctors = [
                        [
                            'first_name' => 'John',
                            'last_name' => 'Smith',
                            'email' => 'john.smith@hospital.com',
                            'phone' => '555-0101',
                            'role' => 'doctor',
                            'designation' => 'MD',
                            'department' => 'Cardiology',
                            'specialization' => 'Cardiology',
                            'license_no' => 'MD12345',
                            'consultation_fee' => 150.00
                        ],
                        [
                            'first_name' => 'Sarah',
                            'last_name' => 'Johnson',
                            'email' => 'sarah.johnson@hospital.com',
                            'phone' => '555-0102',
                            'role' => 'doctor',
                            'designation' => 'MD',
                            'department' => 'Pediatrics',
                            'specialization' => 'Pediatrics',
                            'license_no' => 'MD12346',
                            'consultation_fee' => 120.00
                        ],
                        [
                            'first_name' => 'Michael',
                            'last_name' => 'Brown',
                            'email' => 'michael.brown@hospital.com',
                            'phone' => '555-0103',
                            'role' => 'doctor',
                            'designation' => 'MD',
                            'department' => 'Emergency',
                            'specialization' => 'Emergency Medicine',
                            'license_no' => 'MD12347',
                            'consultation_fee' => 200.00
                        ]
                    ];

                    $addedCount = 0;
                    foreach ($sampleDoctors as $doctorData) {
                        try {
                            // Insert into staff table
                            $staffData = [
                                'first_name' => $doctorData['first_name'],
                                'last_name' => $doctorData['last_name'],
                                'email' => $doctorData['email'],
                                'phone' => $doctorData['phone'],
                                'role' => $doctorData['role'],
                                'designation' => $doctorData['designation'],
                                'department' => $doctorData['department'],
                                'status' => 'Active',
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s')
                            ];

                            $db->table('staff')->insert($staffData);
                            $staffId = $db->insertID();

                            // Insert into doctor table
                            $doctorRecord = [
                                'staff_id' => $staffId,
                                'specialization' => $doctorData['specialization'],
                                'license_no' => $doctorData['license_no'],
                                'consultation_fee' => $doctorData['consultation_fee'],
                                'status' => 'Active'
                            ];

                            $db->table('doctor')->insert($doctorRecord);
                            $addedCount++;
                        } catch (Exception $e) {
                            echo "<p class='error'>❌ Error adding Dr. {$doctorData['first_name']} {$doctorData['last_name']}: " . $e->getMessage() . "</p>";
                        }
                    }
                    
                    echo "<p class='success'>✅ Added $addedCount sample doctors</p>";
                    
                    // Refresh the doctors list
                    $doctors = $db->table('doctor d')
                        ->select('s.staff_id, s.first_name, s.last_name, s.department, d.specialization')
                        ->join('staff s', 's.staff_id = d.staff_id', 'left')
                        ->get()
                        ->getResultArray();
                }

                if (!empty($doctors)) {
                    echo "<table>";
                    echo "<tr><th>Staff ID</th><th>Name</th><th>Department</th><th>Specialization</th></tr>";
                    foreach ($doctors as $doctor) {
                        echo "<tr>";
                        echo "<td>{$doctor['staff_id']}</td>";
                        echo "<td>{$doctor['first_name']} {$doctor['last_name']}</td>";
                        echo "<td>{$doctor['department']}</td>";
                        echo "<td>{$doctor['specialization']}</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
            }

            // Test PatientService
            echo "<h2>🧪 Testing PatientService</h2>";
            try {
                $patientService = new \App\Services\PatientService();
                $availableDoctors = $patientService->getAvailableDoctors();
                echo "<p class='success'>✅ PatientService::getAvailableDoctors() returned " . count($availableDoctors) . " doctors</p>";
            } catch (Exception $e) {
                echo "<p class='error'>❌ PatientService error: " . $e->getMessage() . "</p>";
            }

        } catch (Exception $e) {
            echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
        }
    }
}
