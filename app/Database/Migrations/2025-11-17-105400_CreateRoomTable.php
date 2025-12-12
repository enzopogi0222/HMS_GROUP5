<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRoomTable extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('room')) {
            $this->forge->addField([
                'room_id' => [
                    'type'           => 'INT',
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'room_number' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => false,
                ],
                'room_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'room_type_id' => [
                    'type'       => 'INT',
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'floor_number' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
                ],
                'department_id' => [
                    'type'       => 'INT',
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'bed_capacity' => [
                    'type'       => 'INT',
                    'unsigned'   => true,
                    'null'       => false,
                    'default'    => 1,
                ],
                'bed_names' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => false,
                    'default'    => 'available',
                    'comment'    => 'available / occupied / maintenance',
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

            $this->forge->addKey('room_id', true);
            $this->forge->addKey('room_number');
            $this->forge->addKey('room_type_id');
            $this->forge->addKey('department_id');

            $this->forge->createTable('room', true);

            // Ensure InnoDB for FK support and set timestamp defaults / unique constraints
            $db->query('ALTER TABLE room ENGINE=InnoDB');
            $db->query('ALTER TABLE room MODIFY created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
            $db->query('ALTER TABLE room MODIFY updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

            // Unique room number per table
            $db->query('ALTER TABLE room ADD UNIQUE KEY uq_room_number (room_number)');
        }
    }

    public function down()
    {
        $this->forge->dropTable('room', true);
    }
}
