<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRoomPriceHistoryTable extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('room_price_history')) {
            $this->forge->addField([
                'price_history_id' => [
                    'type'           => 'INT',
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'room_id' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'null'     => false,
                ],
                'old_price' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => true,
                ],
                'new_price' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => false,
                ],
                'effective_date' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
                'updated_by' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'null'     => true,
                    'comment'  => 'staff_id of person who made the change',
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

            $this->forge->addKey('price_history_id', true);
            $this->forge->addKey('room_id');
            $this->forge->addKey('effective_date');
            $this->forge->addKey('updated_by');

            $this->forge->createTable('room_price_history', true);

            // Ensure InnoDB and set timestamp defaults
            $db->query('ALTER TABLE room_price_history ENGINE=InnoDB');
            $db->query('ALTER TABLE room_price_history MODIFY created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
            $db->query('ALTER TABLE room_price_history MODIFY updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        }
    }

    public function down()
    {
        $this->forge->dropTable('room_price_history', true);
    }
}
