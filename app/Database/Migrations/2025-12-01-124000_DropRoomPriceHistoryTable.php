<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropRoomPriceHistoryTable extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('room_price_history')) {
            $this->forge->dropTable('room_price_history', true);
        }
    }

    public function down()
    {
        // No-op: table will not be recreated
    }
}
