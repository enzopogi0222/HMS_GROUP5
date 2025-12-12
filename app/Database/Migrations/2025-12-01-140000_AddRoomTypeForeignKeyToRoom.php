<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRoomTypeForeignKeyToRoom extends Migration
{
    protected string $roomTable = 'room';
    protected string $roomTypeTable = 'room_type';

    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists($this->roomTable) || ! $db->tableExists($this->roomTypeTable)) {
            return;
        }

        // Ensure room_type_id columns exist on both tables
        if (! $db->fieldExists('room_type_id', $this->roomTable) || ! $db->fieldExists('room_type_id', $this->roomTypeTable)) {
            return;
        }

        // Clean up any orphaned room_type_id values in room that don't exist in room_type
        try {
            $db->query(
                'UPDATE ' . $db->escapeString($this->roomTable) . ' r
                 LEFT JOIN ' . $db->escapeString($this->roomTypeTable) . ' t
                   ON r.room_type_id = t.room_type_id
                 SET r.room_type_id = NULL
                 WHERE r.room_type_id IS NOT NULL AND t.room_type_id IS NULL'
            );
        } catch (\Throwable $e) {
            // Ignore clean-up errors to avoid breaking migration; FK may still fail if data invalid
        }

        // Add index and foreign key if not already present
        try {
            // Add index for room_type_id on room if missing
            $db->query(
                'ALTER TABLE ' . $db->escapeString($this->roomTable) . ' ADD INDEX idx_room_room_type_id (room_type_id)'
            );
        } catch (\Throwable $e) {
            // ignore if index already exists
        }

        try {
            $db->query(
                'ALTER TABLE ' . $db->escapeString($this->roomTable) . ' 
                 ADD CONSTRAINT fk_room_room_type
                 FOREIGN KEY (room_type_id) REFERENCES ' . $db->escapeString($this->roomTypeTable) . '(room_type_id)
                 ON UPDATE CASCADE
                 ON DELETE SET NULL'
            );
        } catch (\Throwable $e) {
            // Ignore if the FK already exists or cannot be created
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists($this->roomTable)) {
            return;
        }

        // Drop foreign key and index if they exist
        try {
            $db->query(
                'ALTER TABLE ' . $db->escapeString($this->roomTable) . ' DROP FOREIGN KEY fk_room_room_type'
            );
        } catch (\Throwable $e) {
            // Ignore if the FK does not exist
        }

        try {
            $db->query(
                'ALTER TABLE ' . $db->escapeString($this->roomTable) . ' DROP INDEX idx_room_room_type_id'
            );
        } catch (\Throwable $e) {
            // Ignore if the index does not exist
        }
    }
}
