<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class TestDataSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();
        
        // 1. Add test patients
        $patients = [
            [
                'first_name' => 'Juan',
                'last_name' => 'Dela Cruz',
                'date_of_birth' => '1985-05-15',
                'sex' => 'Male',
                'contact_number' => '09123456789',
                'email' => 'juan@example.com',
                'address' => 'Manila, Philippines',
                'created_at' => Time::now()->toDateTimeString()
            ],
            [
                'first_name' => 'Maria',
                'last_name' => 'Santos',
                'date_of_birth' => '1990-08-20',
                'sex' => 'Female',
                'contact_number' => '09187654321',
                'email' => 'maria@example.com',
                'address' => 'Quezon City, Philippines',
                'created_at' => Time::now()->toDateTimeString()
            ]
        ];

        $patientIds = [];
        foreach ($patients as $patient) {
            $db->table('patients')->insert($patient);
            $patientIds[] = $db->insertID();
        }

        echo "Added " . count($patients) . " patients\n";

        // 2. Add test appointments
        $appointments = [
            [
                'patient_id' => $patientIds[0] ?? 1,
                'doctor_id' => 1,
                'appointment_date' => date('Y-m-d'),
                'appointment_time' => '10:00:00',
                'status' => 'completed',
                'created_at' => Time::now()->toDateTimeString()
            ],
            [
                'patient_id' => $patientIds[1] ?? 2,
                'doctor_id' => 1,
                'appointment_date' => date('Y-m-d', strtotime('-1 day')),
                'appointment_time' => '14:00:00',
                'status' => 'completed',
                'created_at' => Time::now()->subDays(1)->toDateTimeString()
            ]
        ];

        $appointmentIds = [];
        foreach ($appointments as $appointment) {
            $db->table('appointments')->insert($appointment);
            $appointmentIds[] = $db->insertID();
        }

        echo "Added " . count($appointments) . " appointments\n";

        // 3. Add test transactions (if table exists)
        if ($db->tableExists('billing_accounts')) {
            $billings = [
                [
                    'patient_id' => $patientIds[0] ?? 1,
                    'total_amount' => 1500.00,
                    'status' => 'paid',
                    'created_at' => Time::now()->toDateTimeString()
                ],
                [
                    'patient_id' => $patientIds[1] ?? 2,
                    'total_amount' => 2000.00,
                    'status' => 'paid',
                    'created_at' => Time::now()->subDays(1)->toDateTimeString()
                ]
            ];

            $billingIds = [];
            foreach ($billings as $billing) {
                $db->table('billing_accounts')->insert($billing);
                $billingIds[] = $db->insertID();
            }

            echo "Added " . count($billings) . " billing records\n";
        } else {
            echo "Billing accounts table not found. Skipping billing data.\n";
        }

        // 4. Add test staff (if needed)
        $staffCount = $db->table('staff')->countAllResults();
        if ($staffCount == 0) {
            $staff = [
                'first_name' => 'John',
                'last_name' => 'Doctor',
                'email' => 'doctor@example.com',
                'role_id' => 2, // Assuming 2 is the role_id for doctors
                'status' => 'active',
                'created_at' => Time::now()->toDateTimeString()
            ];
            $db->table('staff')->insert($staff);
            echo "Added 1 staff member\n";
        }

        echo "Test data seeding completed successfully!\n";
    }
}
