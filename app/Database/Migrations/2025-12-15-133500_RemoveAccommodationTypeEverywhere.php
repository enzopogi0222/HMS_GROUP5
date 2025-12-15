<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveAccommodationTypeEverywhere extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // Drop FK + column room_type.accommodation_type_id if present
        if ($db->tableExists('room_type') && $db->fieldExists('accommodation_type_id', 'room_type')) {
            try {
                $db->query('ALTER TABLE room_type DROP FOREIGN KEY fk_room_type_accommodation_type');
            } catch (\Throwable $e) {
                // ignore
            }

            try {
                $db->query('ALTER TABLE room_type DROP INDEX idx_room_type_accommodation_type_id');
            } catch (\Throwable $e) {
                // ignore
            }

            try {
                $this->forge->dropColumn('room_type', 'accommodation_type_id');
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // Drop room_type.accommodation_type (string) if present
        if ($db->tableExists('room_type') && $db->fieldExists('accommodation_type', 'room_type')) {
            try {
                $this->forge->dropColumn('room_type', 'accommodation_type');
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // Drop room.accommodation_type if present
        if ($db->tableExists('room') && $db->fieldExists('accommodation_type', 'room')) {
            try {
                $this->forge->dropColumn('room', 'accommodation_type');
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // Drop accommodation_types table if present
        if ($db->tableExists('accommodation_types')) {
            try {
                $this->forge->dropTable('accommodation_types', true);
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }

    public function down()
    {
        // No down migration: dropping columns/tables is destructive.
    }
}
