<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAccommodationTypeToRoomTypeTable extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('room_type')) {
            return;
        }

        if (! $db->fieldExists('accommodation_type', 'room_type')) {
            $this->forge->addColumn('room_type', [
                'accommodation_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'after'      => 'description',
                ],
            ]);
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('room_type')) {
            return;
        }

        if ($db->fieldExists('accommodation_type', 'room_type')) {
            $this->forge->dropColumn('room_type', 'accommodation_type');
        }
    }
}
