<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBaseDailyRateToRoomType extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (!$db->tableExists('room_type')) {
            return;
        }

        // Add base_daily_rate column if it doesn't exist
        if (!$db->fieldExists('base_daily_rate', 'room_type')) {
            $this->forge->addColumn('room_type', [
                'base_daily_rate' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => false,
                    'default'    => 0.00,
                    'after'      => 'accommodation_type',
                ],
            ]);
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if (!$db->tableExists('room_type')) {
            return;
        }

        // Drop base_daily_rate column if it exists
        if ($db->fieldExists('base_daily_rate', 'room_type')) {
            $this->forge->dropColumn('room_type', 'base_daily_rate');
        }
    }
}
