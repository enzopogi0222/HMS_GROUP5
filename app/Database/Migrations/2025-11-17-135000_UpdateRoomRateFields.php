<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateRoomRateFields extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        if (! $db->tableExists('room')) {
            return;
        }

        if (! $db->fieldExists('rate_range', 'room')) {
            $this->forge->addColumn('room', [
                'rate_range' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'after'      => 'status',
                ],
            ]);
        }

        if ($db->fieldExists('daily_rate', 'room')) {
            $this->forge->dropColumn('room', 'daily_rate');
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();
        if (! $db->tableExists('room')) {
            return;
        }

        if (! $db->fieldExists('daily_rate', 'room')) {
            $this->forge->addColumn('room', [
                'daily_rate' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => false,
                    'default'    => 0.00,
                    'after'      => 'status',
                ],
            ]);
        }

        if ($db->fieldExists('rate_range', 'room')) {
            $this->forge->dropColumn('room', 'rate_range');
        }
    }
}
