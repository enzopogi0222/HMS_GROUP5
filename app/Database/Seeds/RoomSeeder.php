<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run()
    {
        $db = $this->db;
        
        // Check if room table exists
        if (!$db->tableExists('room')) {
            echo "Room table does not exist. Please run migrations first.\n";
            return;
        }

        // First, ensure we have room types
        $this->seedRoomTypes($db);
        
        // Get room types and departments for reference
        $roomTypes = [];
        if ($db->tableExists('room_type')) {
            $roomTypes = $db->table('room_type')
                ->select('room_type_id, type_name')
                ->get()
                ->getResultArray();
        }

        $departments = [];
        if ($db->tableExists('department')) {
            $departments = $db->table('department')
                ->select('department_id, name, floor')
                ->get()
                ->getResultArray();
        }

        // Create a map for easy lookup
        $roomTypeMap = [];
        foreach ($roomTypes as $rt) {
            $roomTypeMap[strtolower($rt['type_name'])] = $rt['room_type_id'];
        }

        $deptMap = [];
        foreach ($departments as $dept) {
            $deptMap[strtolower($dept['name'])] = $dept['department_id'];
        }

        // Define rooms data
        $rooms = [
            // Floor 1 - Emergency & Outpatient
            [
                'room_number' => 'ER-101',
                'room_type' => 'Emergency',
                'room_type_id' => $roomTypeMap['emergency'] ?? null,
                'floor_number' => '1',
                'department_id' => $deptMap['emergency department'] ?? null,
                'accommodation_type' => 'Emergency',
                'bed_capacity' => 1,
                'bed_names' => json_encode(['Emergency Bed 1']),
                'status' => 'available',
            ],
            [
                'room_number' => 'ER-102',
                'room_type' => 'Emergency',
                'room_type_id' => $roomTypeMap['emergency'] ?? null,
                'floor_number' => '1',
                'department_id' => $deptMap['emergency department'] ?? null,
                'accommodation_type' => 'Emergency',
                'bed_capacity' => 1,
                'bed_names' => json_encode(['Emergency Bed 2']),
                'status' => 'available',
            ],
            [
                'room_number' => 'OPD-101',
                'room_type' => 'Consultation',
                'room_type_id' => $roomTypeMap['consultation'] ?? null,
                'floor_number' => '1',
                'department_id' => $deptMap['outpatient department'] ?? null,
                'accommodation_type' => 'Outpatient',
                'bed_capacity' => 1,
                'bed_names' => json_encode(['Consultation Room 1']),
                'status' => 'available',
            ],
            [
                'room_number' => 'OPD-102',
                'room_type' => 'Consultation',
                'room_type_id' => $roomTypeMap['consultation'] ?? null,
                'floor_number' => '1',
                'department_id' => $deptMap['outpatient department'] ?? null,
                'accommodation_type' => 'Outpatient',
                'bed_capacity' => 1,
                'bed_names' => json_encode(['Consultation Room 2']),
                'status' => 'available',
            ],

            // Floor 2 - General Wards
            [
                'room_number' => '201',
                'room_type' => 'Ward',
                'room_type_id' => $roomTypeMap['ward'] ?? null,
                'floor_number' => '2',
                'department_id' => $deptMap['inpatient department'] ?? null,
                'accommodation_type' => 'Ward',
                'bed_capacity' => 4,
                'bed_names' => json_encode(['Bed A', 'Bed B', 'Bed C', 'Bed D']),
                'status' => 'available',
            ],
            [
                'room_number' => '202',
                'room_type' => 'Ward',
                'room_type_id' => $roomTypeMap['ward'] ?? null,
                'floor_number' => '2',
                'department_id' => $deptMap['inpatient department'] ?? null,
                'accommodation_type' => 'Ward',
                'bed_capacity' => 4,
                'bed_names' => json_encode(['Bed A', 'Bed B', 'Bed C', 'Bed D']),
                'status' => 'available',
            ],
            [
                'room_number' => '203',
                'room_type' => 'Semi-Private',
                'room_type_id' => $roomTypeMap['semi-private'] ?? null,
                'floor_number' => '2',
                'department_id' => $deptMap['internal medicine'] ?? null,
                'accommodation_type' => 'Semi-Private',
                'bed_capacity' => 2,
                'bed_names' => json_encode(['Bed 1', 'Bed 2']),
                'status' => 'available',
            ],
            [
                'room_number' => '204',
                'room_type' => 'Semi-Private',
                'room_type_id' => $roomTypeMap['semi-private'] ?? null,
                'floor_number' => '2',
                'department_id' => $deptMap['internal medicine'] ?? null,
                'accommodation_type' => 'Semi-Private',
                'bed_capacity' => 2,
                'bed_names' => json_encode(['Bed 1', 'Bed 2']),
                'status' => 'available',
            ],
            [
                'room_number' => '205',
                'room_type' => 'Private',
                'room_type_id' => $roomTypeMap['private'] ?? null,
                'floor_number' => '2',
                'department_id' => $deptMap['internal medicine'] ?? null,
                'accommodation_type' => 'Private',
                'bed_capacity' => 1,
                'bed_names' => json_encode(['Private Bed']),
                'status' => 'available',
            ],
            [
                'room_number' => '206',
                'room_type' => 'Private',
                'room_type_id' => $roomTypeMap['private'] ?? null,
                'floor_number' => '2',
                'department_id' => $deptMap['internal medicine'] ?? null,
                'accommodation_type' => 'Private',
                'bed_capacity' => 1,
                'bed_names' => json_encode(['Private Bed']),
                'status' => 'available',
            ],

            // Floor 3 - Specialized Departments
            [
                'room_number' => '301',
                'room_type' => 'Ward',
                'room_type_id' => $roomTypeMap['ward'] ?? null,
                'floor_number' => '3',
                'department_id' => $deptMap['ob-gyn'] ?? null,
                'accommodation_type' => 'Ward',
                'bed_capacity' => 4,
                'bed_names' => json_encode(['Bed A', 'Bed B', 'Bed C', 'Bed D']),
                'status' => 'available',
            ],
            [
                'room_number' => '302',
                'room_type' => 'Private',
                'room_type_id' => $roomTypeMap['private'] ?? null,
                'floor_number' => '3',
                'department_id' => $deptMap['ob-gyn'] ?? null,
                'accommodation_type' => 'Private',
                'bed_capacity' => 1,
                'bed_names' => json_encode(['Private Bed']),
                'status' => 'available',
            ],
            [
                'room_number' => '303',
                'room_type' => 'Semi-Private',
                'room_type_id' => $roomTypeMap['semi-private'] ?? null,
                'floor_number' => '3',
                'department_id' => $deptMap['general surgery'] ?? null,
                'accommodation_type' => 'Semi-Private',
                'bed_capacity' => 2,
                'bed_names' => json_encode(['Bed 1', 'Bed 2']),
                'status' => 'available',
            ],
            [
                'room_number' => '304',
                'room_type' => 'Semi-Private',
                'room_type_id' => $roomTypeMap['semi-private'] ?? null,
                'floor_number' => '3',
                'department_id' => $deptMap['general surgery'] ?? null,
                'accommodation_type' => 'Semi-Private',
                'bed_capacity' => 2,
                'bed_names' => json_encode(['Bed 1', 'Bed 2']),
                'status' => 'available',
            ],
            [
                'room_number' => '305',
                'room_type' => 'Private',
                'room_type_id' => $roomTypeMap['private'] ?? null,
                'floor_number' => '3',
                'department_id' => $deptMap['cardiology'] ?? null,
                'accommodation_type' => 'Private',
                'bed_capacity' => 1,
                'bed_names' => json_encode(['Private Bed']),
                'status' => 'available',
            ],
            [
                'room_number' => '306',
                'room_type' => 'Private',
                'room_type_id' => $roomTypeMap['private'] ?? null,
                'floor_number' => '3',
                'department_id' => $deptMap['cardiology'] ?? null,
                'accommodation_type' => 'Private',
                'bed_capacity' => 1,
                'bed_names' => json_encode(['Private Bed']),
                'status' => 'available',
            ],

            // Floor 4 - ICU and Specialized Care
            [
                'room_number' => 'ICU-401',
                'room_type' => 'ICU',
                'room_type_id' => $roomTypeMap['icu'] ?? null,
                'floor_number' => '4',
                'department_id' => $deptMap['internal medicine'] ?? null,
                'accommodation_type' => 'ICU',
                'bed_capacity' => 1,
                'bed_names' => json_encode(['ICU Bed 1']),
                'status' => 'available',
            ],
            [
                'room_number' => 'ICU-402',
                'room_type' => 'ICU',
                'room_type_id' => $roomTypeMap['icu'] ?? null,
                'floor_number' => '4',
                'department_id' => $deptMap['internal medicine'] ?? null,
                'accommodation_type' => 'ICU',
                'bed_capacity' => 1,
                'bed_names' => json_encode(['ICU Bed 2']),
                'status' => 'available',
            ],
            [
                'room_number' => 'ICU-403',
                'room_type' => 'ICU',
                'room_type_id' => $roomTypeMap['icu'] ?? null,
                'floor_number' => '4',
                'department_id' => $deptMap['internal medicine'] ?? null,
                'accommodation_type' => 'ICU',
                'bed_capacity' => 1,
                'bed_names' => json_encode(['ICU Bed 3']),
                'status' => 'available',
            ],
            [
                'room_number' => 'ISO-401',
                'room_type' => 'Isolation',
                'room_type_id' => $roomTypeMap['isolation'] ?? null,
                'floor_number' => '4',
                'department_id' => $deptMap['infectious diseases'] ?? null,
                'accommodation_type' => 'Isolation',
                'bed_capacity' => 1,
                'bed_names' => json_encode(['Isolation Bed']),
                'status' => 'available',
            ],
            [
                'room_number' => 'ISO-402',
                'room_type' => 'Isolation',
                'room_type_id' => $roomTypeMap['isolation'] ?? null,
                'floor_number' => '4',
                'department_id' => $deptMap['infectious diseases'] ?? null,
                'accommodation_type' => 'Isolation',
                'bed_capacity' => 1,
                'bed_names' => json_encode(['Isolation Bed']),
                'status' => 'available',
            ],
            [
                'room_number' => '401',
                'room_type' => 'Private',
                'room_type_id' => $roomTypeMap['private'] ?? null,
                'floor_number' => '4',
                'department_id' => $deptMap['oncology'] ?? null,
                'accommodation_type' => 'Private',
                'bed_capacity' => 1,
                'bed_names' => json_encode(['Private Bed']),
                'status' => 'available',
            ],
            [
                'room_number' => '402',
                'room_type' => 'Private',
                'room_type_id' => $roomTypeMap['private'] ?? null,
                'floor_number' => '4',
                'department_id' => $deptMap['oncology'] ?? null,
                'accommodation_type' => 'Private',
                'bed_capacity' => 1,
                'bed_names' => json_encode(['Private Bed']),
                'status' => 'available',
            ],
            [
                'room_number' => '403',
                'room_type' => 'Semi-Private',
                'room_type_id' => $roomTypeMap['semi-private'] ?? null,
                'floor_number' => '4',
                'department_id' => $deptMap['psychiatry'] ?? null,
                'accommodation_type' => 'Semi-Private',
                'bed_capacity' => 2,
                'bed_names' => json_encode(['Bed 1', 'Bed 2']),
                'status' => 'available',
            ],
        ];

        // Get existing rooms to avoid duplicates
        $existingRooms = $db->table('room')
            ->select('room_number')
            ->get()
            ->getResultArray();
        
        $existingRoomNumbers = array_column($existingRooms, 'room_number');

        // Filter out rooms that already exist
        $newRooms = array_filter($rooms, function($room) use ($existingRoomNumbers) {
            return !in_array($room['room_number'], $existingRoomNumbers);
        });

        if (empty($newRooms)) {
            echo "All rooms already exist. No new rooms to insert.\n";
            return;
        }

        // Insert new rooms in batches
        $inserted = $db->table('room')->insertBatch(array_values($newRooms));
        
        if ($inserted) {
            echo "Successfully inserted " . count($newRooms) . " room(s).\n";
        } else {
            echo "Failed to insert rooms.\n";
        }
    }

    /**
     * Seed room types if they don't exist
     */
    private function seedRoomTypes($db)
    {
        if (!$db->tableExists('room_type')) {
            echo "Room type table does not exist. Skipping room type seeding.\n";
            return;
        }

        $roomTypes = [
            [
                'type_name' => 'Ward',
                'description' => 'General ward with multiple beds, typically 4-6 beds per room.',
                'accommodation_type' => 'Ward',
            ],
            [
                'type_name' => 'Semi-Private',
                'description' => 'Room with 2 beds, offering more privacy than a ward.',
                'accommodation_type' => 'Semi-Private',
            ],
            [
                'type_name' => 'Private',
                'description' => 'Single occupancy room with private facilities.',
                'accommodation_type' => 'Private',
            ],
            [
                'type_name' => 'ICU',
                'description' => 'Intensive Care Unit room with specialized monitoring equipment.',
                'accommodation_type' => 'ICU',
            ],
            [
                'type_name' => 'Isolation',
                'description' => 'Isolation room for patients with infectious diseases.',
                'accommodation_type' => 'Isolation',
            ],
            [
                'type_name' => 'Emergency',
                'description' => 'Emergency department treatment room.',
                'accommodation_type' => 'Emergency',
            ],
            [
                'type_name' => 'Consultation',
                'description' => 'Outpatient consultation room.',
                'accommodation_type' => 'Outpatient',
            ],
        ];

        // Get existing room types
        $existingTypes = $db->table('room_type')
            ->select('type_name')
            ->get()
            ->getResultArray();
        
        $existingTypeNames = array_column($existingTypes, 'type_name');

        // Filter out room types that already exist
        $newTypes = array_filter($roomTypes, function($type) use ($existingTypeNames) {
            return !in_array($type['type_name'], $existingTypeNames);
        });

        if (!empty($newTypes)) {
            $inserted = $db->table('room_type')->insertBatch(array_values($newTypes));
            if ($inserted) {
                echo "Successfully inserted " . count($newTypes) . " room type(s).\n";
            }
        }
    }
}

