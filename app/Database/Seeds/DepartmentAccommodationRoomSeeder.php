<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Seeder;

class DepartmentAccommodationRoomSeeder extends Seeder
{
    public function run()
    {
        $db = $this->db;

        if (! $db->tableExists('room')) {
            echo "Room table does not exist. Please run migrations first.\n";
            return;
        }

        if (! $db->tableExists('department')) {
            echo "Department table does not exist. Please seed departments first.\n";
            return;
        }

        if (! $db->tableExists('room_type')) {
            echo "Room type table does not exist. Please run migrations first.\n";
            return;
        }

        $this->ensureDefaultRoomTypes($db);

        $departments = $db->table('department')
            ->select('department_id, name, code, floor, status')
            ->where('status', 'Active')
            ->orderBy('department_id', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($departments)) {
            echo "No active departments found.\n";
            return;
        }

        $roomTypes = $db->table('room_type')
            ->select('room_type_id, type_name')
            ->get()
            ->getResultArray();

        if (empty($roomTypes)) {
            echo "No room types found.\n";
            return;
        }

        $existing = $db->table('room')->select('room_number')->get()->getResultArray();
        $existingRoomNumbers = array_fill_keys(array_map('strval', array_column($existing, 'room_number')), true);

        $hasRoomNameColumn = $db->fieldExists('room_name', 'room');

        $defaultsByRoomTypeName = [
            'Ward' => ['rooms_per_department' => 3, 'bed_capacity' => 4, 'bed_names' => ['Bed A', 'Bed B', 'Bed C', 'Bed D']],
            'Semi-Private' => ['rooms_per_department' => 2, 'bed_capacity' => 2, 'bed_names' => ['Bed 1', 'Bed 2']],
            'Private' => ['rooms_per_department' => 2, 'bed_capacity' => 1, 'bed_names' => ['Private Bed']],
            'ICU' => ['rooms_per_department' => 1, 'bed_capacity' => 1, 'bed_names' => ['ICU Bed']],
            'Isolation' => ['rooms_per_department' => 1, 'bed_capacity' => 1, 'bed_names' => ['Isolation Bed']],
        ];

        $toInsert = [];

        foreach ($departments as $dept) {
            $deptId = (int) ($dept['department_id'] ?? 0);
            if (! $deptId) {
                continue;
            }

            $deptCode = trim((string) ($dept['code'] ?? ''));
            if ($deptCode === '') {
                $deptName = trim((string) ($dept['name'] ?? ''));
                $deptCode = strtoupper(preg_replace('/[^A-Z0-9]/i', '', substr($deptName, 0, 6)));
                if ($deptCode === '') {
                    $deptCode = 'DEPT' . $deptId;
                }
            }

            $floorNumber = (string) ($dept['floor'] ?? '');
            if ($floorNumber === '') {
                $floorNumber = '1';
            }

            foreach ($roomTypes as $rt) {
                $roomTypeId = (int) ($rt['room_type_id'] ?? 0);
                $typeName = trim((string) ($rt['type_name'] ?? ''));

                if (! $roomTypeId || $typeName === '') {
                    continue;
                }

                if (! isset($defaultsByRoomTypeName[$typeName])) {
                    continue;
                }

                $spec = $defaultsByRoomTypeName[$typeName];
                $count = (int) ($spec['rooms_per_department'] ?? 0);
                if ($count <= 0) {
                    continue;
                }

                $typeCode = strtoupper(preg_replace('/[^A-Z0-9]/i', '', substr($typeName, 0, 4)));
                if ($typeCode === '') {
                    $typeCode = 'TYPE' . $roomTypeId;
                }

                for ($i = 1; $i <= $count; $i++) {
                    $capacity = (int) ($spec['bed_capacity'] ?? 1);
                    $floorCode = strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) $floorNumber));
                    if ($floorCode === '') {
                        $floorCode = '1';
                    }

                    // Include floor and capacity in room_number for easier identification.
                    // Example: F2-CARD-PRIV-C1-01
                    $roomNumber = 'F' . $floorCode . '-' . $deptCode . '-' . $typeCode . '-C' . $capacity . '-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT);

                    if (isset($existingRoomNumbers[$roomNumber])) {
                        continue;
                    }

                    $row = [
                        'room_number' => $roomNumber,
                        'room_type_id' => $roomTypeId,
                        'floor_number' => $floorNumber,
                        'department_id' => $deptId,
                        'bed_capacity' => $capacity,
                        'bed_names' => json_encode($spec['bed_names'] ?? []),
                        'status' => 'available',
                    ];

                    if ($hasRoomNameColumn) {
                        $row['room_name'] = 'Floor ' . (string) $floorNumber . ' - ' . $typeName . ' (' . $capacity . ' bed)' . ' - ' . (string) ($dept['name'] ?? '');
                    }

                    $toInsert[] = $row;
                    $existingRoomNumbers[$roomNumber] = true;
                }
            }
        }

        if (empty($toInsert)) {
            echo "No new rooms to insert.\n";
            return;
        }

        $batchSize = 200;
        $chunks = array_chunk($toInsert, $batchSize);

        foreach ($chunks as $chunk) {
            $db->table('room')->insertBatch($chunk);
        }

        echo "Inserted " . count($toInsert) . " room(s).\n";
    }

    private function ensureDefaultRoomTypes(BaseConnection $db): void
    {
        $existing = $db->table('room_type')->select('type_name')->get()->getResultArray();
        $existingNames = array_fill_keys(array_map('strval', array_column($existing, 'type_name')), true);

        $hasBaseDailyRate = $db->fieldExists('base_daily_rate', 'room_type');

        $defaults = [
            ['type_name' => 'Ward', 'description' => 'General ward', 'base_daily_rate' => 1500.00],
            ['type_name' => 'Semi-Private', 'description' => 'Semi-private room', 'base_daily_rate' => 2500.00],
            ['type_name' => 'Private', 'description' => 'Private room', 'base_daily_rate' => 3500.00],
            ['type_name' => 'ICU', 'description' => 'Intensive care unit', 'base_daily_rate' => 5000.00],
            ['type_name' => 'Isolation', 'description' => 'Isolation room', 'base_daily_rate' => 3000.00],
        ];

        $toInsert = [];
        foreach ($defaults as $row) {
            if (isset($existingNames[$row['type_name']])) {
                continue;
            }

            if (! $hasBaseDailyRate) {
                unset($row['base_daily_rate']);
            }

            $toInsert[] = $row;
        }

        if (! empty($toInsert)) {
            $db->table('room_type')->insertBatch($toInsert);
        }
    }
}
