<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRoomTypeTable extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('room_type')) {
            $this->forge->addField([
                'room_type_id' => [
                    'type'           => 'INT',
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'type_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => false,
                ],
                'description' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'base_daily_rate' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => false,
                    'default'    => 0.00,
                ],
                'base_hourly_rate' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => true,
                ],
                'additional_facility_charge' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => true,
                ],
                // Timestamps as NULL first; defaults set via raw SQL after create
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

            $this->forge->addKey('room_type_id', true);
            $this->forge->addKey('type_name');

            $this->forge->createTable('room_type', true);

            // Ensure InnoDB and set timestamp defaults
            $db->query('ALTER TABLE room_type ENGINE=InnoDB');
            $db->query('ALTER TABLE room_type MODIFY created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
            $db->query('ALTER TABLE room_type MODIFY updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

            // Unique type_name per table
            $db->query('ALTER TABLE room_type ADD UNIQUE KEY uq_room_type_name (type_name)');
        }
    }

    public function down()
    {
        $this->forge->dropTable('room_type', true);
    }
}
