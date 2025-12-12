<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveCurrentOccupiedBeds extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        if (! $db->tableExists('room')) {
            return;
        }

        if ($db->fieldExists('current_occupied_beds', 'room')) {
            $this->forge->dropColumn('room', 'current_occupied_beds');
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();
        if (! $db->tableExists('room')) {
            return;
        }

        if (! $db->fieldExists('current_occupied_beds', 'room')) {
            $this->forge->addColumn('room', [
                'current_occupied_beds' => [
                    'type'       => 'INT',
                    'unsigned'   => true,
                    'null'       => false,
                    'default'    => 0,
                    'after'      => 'bed_capacity',
                ],
            ]);
        }
    }
}
