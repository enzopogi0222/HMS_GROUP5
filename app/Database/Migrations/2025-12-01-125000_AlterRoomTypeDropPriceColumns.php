<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterRoomTypeDropPriceColumns extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('room_type')) {
            // Drop price-related columns if they exist
            if ($db->fieldExists('base_daily_rate', 'room_type')) {
                $this->forge->dropColumn('room_type', 'base_daily_rate');
            }

            if ($db->fieldExists('base_hourly_rate', 'room_type')) {
                $this->forge->dropColumn('room_type', 'base_hourly_rate');
            }

            if ($db->fieldExists('additional_facility_charge', 'room_type')) {
                $this->forge->dropColumn('room_type', 'additional_facility_charge');
            }
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('room_type')) {
            // Recreate the dropped columns with original definitions if they don't exist
            $fields = [];

            if (! $db->fieldExists('base_daily_rate', 'room_type')) {
                $fields['base_daily_rate'] = [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => false,
                    'default'    => 0.00,
                ];
            }

            if (! $db->fieldExists('base_hourly_rate', 'room_type')) {
                $fields['base_hourly_rate'] = [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => true,
                ];
            }

            if (! $db->fieldExists('additional_facility_charge', 'room_type')) {
                $fields['additional_facility_charge'] = [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => true,
                ];
            }

            if (! empty($fields)) {
                $this->forge->addColumn('room_type', $fields);
            }
        }
    }
}
