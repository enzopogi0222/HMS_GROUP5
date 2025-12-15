<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ResourceAndRoomSeeder extends Seeder
{
    public function run()
    {
        $db = $this->db;

        // Seed medications/resources
        if ($db->tableExists('resources')) {
            $this->seedMedications($db);
        } else {
            echo "[ResourceAndRoomSeeder] 'resources' table does not exist. Skipping medications seeding.\n";
        }

        // Seed room types and rooms
        if ($db->tableExists('room')) {
            $this->seedRoomTypes($db);
            $this->seedRooms($db);
        } else {
            echo "[ResourceAndRoomSeeder] 'room' table does not exist. Skipping room seeding.\n";
        }
    }

    private function seedMedications($db)
    {
        $now = date('Y-m-d H:i:s');

        $medications = [
            [
                'equipment_name' => 'Paracetamol 500mg Tablet',
                'category'       => 'Medications',
                'quantity'       => 500,
                'status'         => 'Stock In',
                'location'       => 'Main Pharmacy - Shelf A1',
                'batch_number'   => 'PAR-'.date('Y').'A',
                'expiry_date'    => date('Y-m-d', strtotime('+18 months')),
                'serial_number'  => null,
                'price'          => 5.50,
                'remarks'        => 'Common analgesic and antipyretic.',
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'equipment_name' => 'Amoxicillin 500mg Capsule',
                'category'       => 'Medications',
                'quantity'       => 250,
                'status'         => 'Stock In',
                'location'       => 'Main Pharmacy - Shelf B2',
                'batch_number'   => 'AMX-'.date('Y').'B',
                'expiry_date'    => date('Y-m-d', strtotime('+2 years')),
                'serial_number'  => null,
                'price'          => 45.00,
                'remarks'        => 'Broad-spectrum antibiotic.',
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'equipment_name' => 'Normal Saline 1L IV Bag',
                'category'       => 'Medications',
                'quantity'       => 120,
                'status'         => 'Stock In',
                'location'       => 'Central Supply - Rack C1',
                'batch_number'   => 'NS-'.date('Y').'C',
                'expiry_date'    => date('Y-m-d', strtotime('+1 year')),
                'serial_number'  => null,
                'price'          => 65.00,
                'remarks'        => 'IV fluid for hydration.',
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'equipment_name' => 'Insulin 100IU/mL Vial',
                'category'       => 'Medications',
                'quantity'       => 80,
                'status'         => 'Stock In',
                'location'       => 'Refrigerated Storage - Pharmacy',
                'batch_number'   => 'INS-'.date('Y').'D',
                'expiry_date'    => date('Y-m-d', strtotime('+9 months')),
                'serial_number'  => null,
                'price'          => 320.00,
                'remarks'        => 'For diabetes management. Keep refrigerated.',
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
        ];

        // Avoid duplicate batches based on batch_number
        $existing = $db->table('resources')
            ->select('batch_number')
            ->whereIn('batch_number', array_column($medications, 'batch_number'))
            ->get()->getResultArray();

        $existingBatches = array_column($existing, 'batch_number');

        $toInsert = array_values(array_filter($medications, function ($item) use ($existingBatches) {
            return !in_array($item['batch_number'], $existingBatches, true);
        }));

        if (empty($toInsert)) {
            echo "[ResourceAndRoomSeeder] Medications already seeded.\n";
            return;
        }

        $db->table('resources')->insertBatch($toInsert);
        echo "[ResourceAndRoomSeeder] Inserted ".count($toInsert)." medication resource(s).\n";
    }

    private function seedRoomTypes($db)
    {
        if (!$db->tableExists('room_type')) {
            echo "[ResourceAndRoomSeeder] 'room_type' table does not exist. Skipping room type seeding.\n";
            return;
        }

        $roomTypes = [
            [
                'type_name'       => 'Ward',
                'description'     => 'General ward with multiple beds.',
                'base_daily_rate' => 1500.00,
            ],
            [
                'type_name'       => 'Semi-Private',
                'description'     => 'Room with 2 beds.',
                'base_daily_rate' => 2500.00,
            ],
            [
                'type_name'       => 'Private',
                'description'     => 'Single occupancy room.',
                'base_daily_rate' => 3500.00,
            ],
            [
                'type_name'       => 'ICU',
                'description'     => 'Intensive Care Unit room.',
                'base_daily_rate' => 5000.00,
            ],
        ];

        $existing = $db->table('room_type')
            ->select('type_name')
            ->get()->getResultArray();

        $existingNames = array_column($existing, 'type_name');
        $hasRateField  = $db->fieldExists('base_daily_rate', 'room_type');

        $toInsert = [];
        foreach ($roomTypes as $type) {
            if (in_array($type['type_name'], $existingNames, true)) {
                continue;
            }
            if (!$hasRateField) {
                unset($type['base_daily_rate']);
            }
            $toInsert[] = $type;
        }

        if (!empty($toInsert)) {
            $db->table('room_type')->insertBatch($toInsert);
            echo "[ResourceAndRoomSeeder] Inserted ".count($toInsert)." room type(s).\n";
        } else {
            echo "[ResourceAndRoomSeeder] Room types already seeded.\n";
        }
    }

    private function seedRooms($db)
    {
        // Load room types and departments
        $roomTypeMap = [];
        if ($db->tableExists('room_type')) {
            $types = $db->table('room_type')->select('room_type_id, type_name')->get()->getResultArray();
            foreach ($types as $t) {
                $roomTypeMap[strtolower($t['type_name'])] = $t['room_type_id'];
            }
        }

        $deptMap = [];
        if ($db->tableExists('department')) {
            $depts = $db->table('department')->select('department_id, name')->get()->getResultArray();
            foreach ($depts as $d) {
                $deptMap[strtolower($d['name'])] = $d['department_id'];
            }
        }

        $rooms = [
            [
                'room_number'   => 'WARD-201',
                'room_type'     => 'Ward',
                'room_type_id'  => $roomTypeMap['ward'] ?? null,
                'floor_number'  => '2',
                'department_id' => $deptMap['inpatient department'] ?? null,
                'bed_capacity'  => 4,
                'bed_names'     => json_encode(['Bed A', 'Bed B', 'Bed C', 'Bed D']),
                'status'        => 'available',
            ],
            [
                'room_number'   => 'WARD-202',
                'room_type'     => 'Ward',
                'room_type_id'  => $roomTypeMap['ward'] ?? null,
                'floor_number'  => '2',
                'department_id' => $deptMap['inpatient department'] ?? null,
                'bed_capacity'  => 4,
                'bed_names'     => json_encode(['Bed A', 'Bed B', 'Bed C', 'Bed D']),
                'status'        => 'available',
            ],
            [
                'room_number'   => 'PRIV-301',
                'room_type'     => 'Private',
                'room_type_id'  => $roomTypeMap['private'] ?? null,
                'floor_number'  => '3',
                'department_id' => $deptMap['internal medicine'] ?? null,
                'bed_capacity'  => 1,
                'bed_names'     => json_encode(['Private Bed']),
                'status'        => 'available',
            ],
            [
                'room_number'   => 'ICU-401',
                'room_type'     => 'ICU',
                'room_type_id'  => $roomTypeMap['icu'] ?? null,
                'floor_number'  => '4',
                'department_id' => $deptMap['internal medicine'] ?? null,
                'bed_capacity'  => 1,
                'bed_names'     => json_encode(['ICU Bed 1']),
                'status'        => 'available',
            ],
        ];

        $existing = $db->table('room')
            ->select('room_number')
            ->whereIn('room_number', array_column($rooms, 'room_number'))
            ->get()->getResultArray();

        $existingNumbers = array_column($existing, 'room_number');

        $toInsert = array_values(array_filter($rooms, function ($room) use ($existingNumbers) {
            return !in_array($room['room_number'], $existingNumbers, true);
        }));

        if (empty($toInsert)) {
            echo "[ResourceAndRoomSeeder] Rooms already seeded.\n";
            return;
        }

        $db->table('room')->insertBatch($toInsert);
        echo "[ResourceAndRoomSeeder] Inserted ".count($toInsert)." room(s).\n";
    }
}
