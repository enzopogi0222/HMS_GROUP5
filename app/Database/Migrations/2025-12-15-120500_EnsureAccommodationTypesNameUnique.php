<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EnsureAccommodationTypesNameUnique extends Migration
{
    protected string $table = 'accommodation_types';

    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists($this->table)) {
            return;
        }

        // Backfill from existing room/room_type values (best effort)
        try {
            if ($db->tableExists('room') && $db->fieldExists('accommodation_type', 'room')) {
                $rows = $db->table('room')
                    ->select('accommodation_type')
                    ->where('accommodation_type IS NOT NULL', null, false)
                    ->where("TRIM(accommodation_type) != ''", null, false)
                    ->groupBy('accommodation_type')
                    ->get()
                    ->getResultArray();

                foreach ($rows as $row) {
                    $name = trim((string) ($row['accommodation_type'] ?? ''));
                    if ($name === '') {
                        continue;
                    }

                    $exists = $db->table($this->table)->select('id')->where('name', $name)->get()->getRowArray();
                    if (! $exists) {
                        $db->table($this->table)->insert(['name' => $name]);
                    }
                }
            }

            if ($db->tableExists('room_type') && $db->fieldExists('accommodation_type', 'room_type')) {
                $rows = $db->table('room_type')
                    ->select('accommodation_type')
                    ->where('accommodation_type IS NOT NULL', null, false)
                    ->where("TRIM(accommodation_type) != ''", null, false)
                    ->groupBy('accommodation_type')
                    ->get()
                    ->getResultArray();

                foreach ($rows as $row) {
                    $name = trim((string) ($row['accommodation_type'] ?? ''));
                    if ($name === '') {
                        continue;
                    }

                    $exists = $db->table($this->table)->select('id')->where('name', $name)->get()->getRowArray();
                    if (! $exists) {
                        $db->table($this->table)->insert(['name' => $name]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // best effort
        }

        // Ensure UNIQUE constraint on name (best effort)
        try {
            $db->query('ALTER TABLE ' . $db->escapeString($this->table) . ' ADD UNIQUE KEY uq_accommodation_types_name (name)');
        } catch (\Throwable $e) {
            // ignore if already exists or cannot be created
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists($this->table)) {
            return;
        }

        try {
            $db->query('ALTER TABLE ' . $db->escapeString($this->table) . ' DROP INDEX uq_accommodation_types_name');
        } catch (\Throwable $e) {
            // ignore
        }
    }
}
