<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAccommodationTypeToRoomTable extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('room')) {
            return;
        }

        if (! $db->fieldExists('accommodation_type', 'room')) {
            $this->forge->addColumn('room', [
                'accommodation_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'after'      => 'department_id',
                ],
            ]);
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('room')) {
            return;
        }

        if ($db->fieldExists('accommodation_type', 'room')) {
            $this->forge->dropColumn('room', 'accommodation_type');
        }
    }
}
