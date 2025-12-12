<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRoomIdToInpatientRoomAssignments extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (!$db->tableExists('inpatient_room_assignments')) {
            return; // Table doesn't exist, skip
        }

        // Check if room_id column already exists
        if ($db->fieldExists('room_id', 'inpatient_room_assignments')) {
            return; // Column already exists, skip
        }

        // Add room_id column
        $this->forge->addColumn('inpatient_room_assignments', [
            'room_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'admission_id',
            ],
        ]);

        // Populate room_id for existing records by matching room_number and floor_number
        if ($db->tableExists('room')) {
            $assignments = $db->table('inpatient_room_assignments')
                ->select('room_assignment_id, room_number, floor_number')
                ->where('room_id IS NULL')
                ->get()
                ->getResultArray();

            foreach ($assignments as $assignment) {
                $roomNumber = $assignment['room_number'] ?? null;
                $floorNumber = $assignment['floor_number'] ?? null;

                if (!$roomNumber) {
                    continue;
                }

                // Try to find matching room
                $roomBuilder = $db->table('room')
                    ->where('room_number', $roomNumber);

                // Match floor_number if provided
                if ($floorNumber) {
                    $roomBuilder->where('floor_number', $floorNumber);
                }

                $room = $roomBuilder->get()->getRowArray();

                if ($room && isset($room['room_id'])) {
                    // Update assignment with room_id
                    $db->table('inpatient_room_assignments')
                        ->where('room_assignment_id', $assignment['room_assignment_id'])
                        ->update(['room_id' => $room['room_id']]);
                }
            }
        }

        // Add foreign key constraint if room table exists
        if ($db->tableExists('room')) {
            try {
                $db->query('ALTER TABLE inpatient_room_assignments 
                    ADD CONSTRAINT fk_inpatient_room_assign_room 
                    FOREIGN KEY (room_id) REFERENCES room(room_id) 
                    ON DELETE SET NULL ON UPDATE CASCADE');
            } catch (\Throwable $e) {
                // Constraint might already exist or table structure issue
                log_message('warning', 'Could not add foreign key constraint: ' . $e->getMessage());
            }
        }

        // Add index for better query performance
        try {
            $db->query('ALTER TABLE inpatient_room_assignments ADD INDEX idx_room_id (room_id)');
        } catch (\Throwable $e) {
            // Index might already exist
            log_message('debug', 'Could not add index: ' . $e->getMessage());
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if (!$db->tableExists('inpatient_room_assignments')) {
            return;
        }

        // Drop foreign key constraint
        try {
            $db->query('ALTER TABLE inpatient_room_assignments DROP FOREIGN KEY fk_inpatient_room_assign_room');
        } catch (\Throwable $e) {
            // Constraint might not exist
        }

        // Drop column
        if ($db->fieldExists('room_id', 'inpatient_room_assignments')) {
            $this->forge->dropColumn('inpatient_room_assignments', 'room_id');
        }
    }
}

