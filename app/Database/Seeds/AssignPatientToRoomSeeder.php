<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AssignPatientToRoomSeeder extends Seeder
{
    public function run()
    {
        $db = $this->db;
        
        // Check required tables
        if (!$db->tableExists('patients') && !$db->tableExists('patient')) {
            echo "Patient table does not exist. Please run patient migrations/seeds first.\n";
            return;
        }

        if (!$db->tableExists('room')) {
            echo "Room table does not exist. Please run RoomSeeder first.\n";
            return;
        }

        if (!$db->tableExists('room_assignment')) {
            echo "Room assignment table does not exist. Please run migrations first.\n";
            return;
        }

        $patientTable = $db->tableExists('patients') ? 'patients' : 'patient';

        // Get available patients (prefer inpatients if available)
        $patients = $db->table($patientTable)
            ->select('patient_id, first_name, last_name, patient_type')
            ->orderBy('patient_type', 'DESC') // Inpatients first
            ->orderBy('patient_id', 'ASC')
            ->limit(10)
            ->get()
            ->getResultArray();

        if (empty($patients)) {
            echo "No patients found. Please run patient seeder first.\n";
            return;
        }

        // Get available rooms (status = 'available')
        $availableRooms = $db->table('room')
            ->where('status', 'available')
            ->orderBy('room_id', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($availableRooms)) {
            echo "No available rooms found. Please run RoomSeeder first or free up some rooms.\n";
            return;
        }

        // Get admission IDs if available (for linking to admissions)
        $admissionIds = [];
        if ($db->tableExists('inpatient_admissions')) {
            $admissions = $db->table('inpatient_admissions')
                ->select('admission_id, patient_id')
                ->where('discharge_date IS NULL')
                ->orWhere('discharge_date', '')
                ->get()
                ->getResultArray();
            
            // Create a map of patient_id => admission_id
            foreach ($admissions as $adm) {
                $admissionIds[$adm['patient_id']] = $adm['admission_id'];
            }
        }

        // Get bed IDs if bed table exists
        $bedMap = [];
        if ($db->tableExists('bed')) {
            $beds = $db->table('bed')
                ->select('bed_id, room_id, bed_number, status')
                ->where('status', 'available')
                ->get()
                ->getResultArray();
            
            foreach ($beds as $bed) {
                if (!isset($bedMap[$bed['room_id']])) {
                    $bedMap[$bed['room_id']] = [];
                }
                $bedMap[$bed['room_id']][] = $bed;
            }
        }

        // Get room rates from room_type if available
        $roomRates = [];
        if ($db->tableExists('room_type')) {
            $roomTypes = $db->table('room_type')
                ->select('room_type_id, type_name, base_daily_rate')
                ->get()
                ->getResultArray();
            
            foreach ($roomTypes as $rt) {
                $roomRates[$rt['room_type_id']] = (float)($rt['base_daily_rate'] ?? 0);
            }
        }

        $assignmentsCreated = 0;
        $minAssignments = min(count($patients), count($availableRooms), 5); // Assign at least 5 if possible

        echo "Assigning patients to rooms...\n";

        for ($i = 0; $i < $minAssignments && $i < count($patients) && $i < count($availableRooms); $i++) {
            $patient = $patients[$i];
            $room = $availableRooms[$i];
            $patientId = (int)$patient['patient_id'];
            $roomId = (int)$room['room_id'];

            // Check if patient already has an active room assignment
            $existingAssignment = $db->table('room_assignment')
                ->where('patient_id', $patientId)
                ->where('status', 'active')
                ->countAllResults();

            if ($existingAssignment > 0) {
                echo "  Patient {$patient['first_name']} {$patient['last_name']} already has an active room assignment. Skipping.\n";
                continue;
            }

            // Get bed for this room if available
            $bedId = null;
            if (!empty($bedMap[$roomId])) {
                $availableBed = $bedMap[$roomId][0]; // Get first available bed
                $bedId = (int)$availableBed['bed_id'];
                // Remove from map so it's not reused
                array_shift($bedMap[$roomId]);
            }

            // Get room rate
            $roomRate = 0.00;
            if (!empty($room['room_type_id']) && isset($roomRates[$room['room_type_id']])) {
                $roomRate = $roomRates[$room['room_type_id']];
            } else {
                // Default rates based on room type
                $roomTypeName = strtolower($room['room_type'] ?? $room['type_name'] ?? '');
                $defaultRates = [
                    'private' => 3500.00,
                    'semi-private' => 2500.00,
                    'ward' => 1500.00,
                    'icu' => 5000.00,
                    'isolation' => 3000.00,
                ];
                foreach ($defaultRates as $type => $rate) {
                    if (strpos($roomTypeName, $type) !== false) {
                        $roomRate = $rate;
                        break;
                    }
                }
                if ($roomRate === 0.00) {
                    $roomRate = 2000.00; // Default rate
                }
            }

            // Get admission ID for this patient if available
            $admissionId = $admissionIds[$patientId] ?? null;

            // Create room assignment
            $assignmentData = [
                'patient_id' => $patientId,
                'room_id' => $roomId,
                'bed_id' => $bedId,
                'admission_id' => $admissionId,
                'date_in' => date('Y-m-d H:i:s', strtotime('-' . rand(0, 3) . ' days')), // Random date within last 3 days
                'date_out' => null,
                'total_days' => rand(1, 3),
                'status' => 'active',
            ];

            try {
                $db->table('room_assignment')->insert($assignmentData);
                $assignmentId = (int)$db->insertID();

                // Update room status to 'occupied'
                $db->table('room')
                    ->where('room_id', $roomId)
                    ->update(['status' => 'occupied']);

                // Update bed status if bed was assigned
                if ($bedId && $db->tableExists('bed')) {
                    $db->table('bed')
                        ->where('bed_id', $bedId)
                        ->update(['status' => 'occupied', 'assigned_patient_id' => $patientId]);
                }

                $assignmentsCreated++;
                echo "  ✓ Assigned patient {$patient['first_name']} {$patient['last_name']} (ID: {$patientId}) to room {$room['room_number']} (ID: {$roomId})\n";
                
                if ($bedId) {
                    echo "    - Bed assigned: {$bedId}\n";
                }
                if ($admissionId) {
                    echo "    - Linked to admission: {$admissionId}\n";
                }
                echo "    - Daily rate: ₱" . number_format($roomRate, 2) . "\n";

            } catch (\Throwable $e) {
                echo "  ✗ Failed to assign patient {$patient['first_name']} {$patient['last_name']} to room {$room['room_number']}: " . $e->getMessage() . "\n";
            }
        }

        echo "\n";
        if ($assignmentsCreated > 0) {
            echo "Successfully created {$assignmentsCreated} room assignment(s).\n";
        } else {
            echo "No room assignments were created.\n";
        }
    }
}


