<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBedTable extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('bed')) {
            $this->forge->addField([
                'bed_id' => [
                    'type'           => 'INT',
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'room_id' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'null'     => false,
                ],
                'bed_number' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => false,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => false,
                    'default'    => 'available',
                    'comment'    => 'available / occupied / reserved',
                ],
                'assigned_patient_id' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'null'     => true,
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

            $this->forge->addKey('bed_id', true);
            $this->forge->addKey('room_id');
            $this->forge->addKey('assigned_patient_id');

            $this->forge->createTable('bed', true);

            // Ensure InnoDB and set timestamp defaults
            $db->query('ALTER TABLE bed ENGINE=InnoDB');
            $db->query('ALTER TABLE bed MODIFY created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
            $db->query('ALTER TABLE bed MODIFY updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

            // Unique bed number per room
            $db->query('ALTER TABLE bed ADD UNIQUE KEY uq_room_bed_number (room_id, bed_number)');
        }
    }

    public function down()
    {
        $this->forge->dropTable('bed', true);
    }
}
