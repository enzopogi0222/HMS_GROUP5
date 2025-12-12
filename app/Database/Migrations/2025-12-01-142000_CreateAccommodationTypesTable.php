<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAccommodationTypesTable extends Migration
{
    protected string $table = 'accommodation_types';

    public function up()
    {
        $db = \Config\Database::connect();

        // 1. Create lookup table if it does not exist
        if (! $db->tableExists($this->table)) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => false,
                ],
                'created_at' => [
                    'type'    => 'TIMESTAMP',
                    'null'    => true,
                    'default' => null,
                ],
                'updated_at' => [
                    'type'    => 'TIMESTAMP',
                    'null'    => true,
                    'default' => null,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey('name');

            $this->forge->createTable($this->table, true, ['ENGINE' => 'InnoDB']);

            // Set timestamp defaults
            $db->query('ALTER TABLE ' . $db->escapeString($this->table) . ' MODIFY created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
            $db->query('ALTER TABLE ' . $db->escapeString($this->table) . ' MODIFY updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        }

        // 2. Seed standard accommodation categories if table exists
        if ($db->tableExists($this->table)) {
            $knownTypes = [
                'General Ward / General Accommodation',
                'Intensive / Critical Care Units',
                'Maternity / Obstetrics Accommodation',
                'Pediatric Accommodation',
                'Isolation Accommodation',
                'Surgical / Post-Operative Accommodation',
                'Specialty Units',
            ];

            foreach ($knownTypes as $name) {
                $exists = $db->table($this->table)
                    ->where('name', $name)
                    ->get()
                    ->getRowArray();

                if (! $exists) {
                    $db->table($this->table)->insert(['name' => $name]);
                }
            }
        }

        // 3. Add accommodation_type_id to room_type and connect via FK
        if ($db->tableExists('room_type')) {
            if (! $db->fieldExists('accommodation_type_id', 'room_type')) {
                $this->forge->addColumn('room_type', [
                    'accommodation_type_id' => [
                        'type'     => 'INT',
                        'unsigned' => true,
                        'null'     => true,
                        'after'    => 'accommodation_type',
                    ],
                ]);
            }

            // Try to map existing string accommodation_type values to the lookup table
            if ($db->fieldExists('accommodation_type', 'room_type')) {
                try {
                    // For each known type, set the FK where names match
                    $types = $db->table($this->table)->select('id, name')->get()->getResultArray();
                    foreach ($types as $row) {
                        $id   = (int) $row['id'];
                        $name = $row['name'];

                        $db->table('room_type')
                            ->set('accommodation_type_id', $id)
                            ->where('accommodation_type', $name)
                            ->update();
                    }
                } catch (\Throwable $e) {
                    // Best-effort; ignore mapping failures
                }
            }

            // Add index & foreign key (best effort)
            try {
                $db->query('ALTER TABLE room_type ADD INDEX idx_room_type_accommodation_type_id (accommodation_type_id)');
            } catch (\Throwable $e) {
                // ignore if index already exists
            }

            try {
                $db->query(
                    'ALTER TABLE room_type
                     ADD CONSTRAINT fk_room_type_accommodation_type
                     FOREIGN KEY (accommodation_type_id) REFERENCES ' . $db->escapeString($this->table) . '(id)
                     ON UPDATE CASCADE
                     ON DELETE SET NULL'
                );
            } catch (\Throwable $e) {
                // ignore if FK already exists or cannot be created
            }
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        // Drop FK and column on room_type
        if ($db->tableExists('room_type')) {
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

            if ($db->fieldExists('accommodation_type_id', 'room_type')) {
                $this->forge->dropColumn('room_type', 'accommodation_type_id');
            }
        }

        // Finally drop the lookup table
        $this->forge->dropTable($this->table, true);
    }
}
