<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterBedTableDropRates extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('bed')) {
            return;
        }

        // Drop columns only if they exist to avoid errors on re-run
        $fieldsToDrop = ['bed_daily_rate', 'bed_hourly_rate', 'last_cleaned_at'];

        foreach ($fieldsToDrop as $field) {
            if ($db->fieldExists($field, 'bed')) {
                // Using raw SQL for compatibility
                $db->query('ALTER TABLE `bed` DROP COLUMN `' . $field . '`');
            }
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('bed')) {
            return;
        }

        // Recreate the dropped columns if they do not exist
        $forge = \Config\Database::forge();

        $columns = [];

        if (! $db->fieldExists('bed_daily_rate', 'bed')) {
            $columns['bed_daily_rate'] = [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
            ];
        }

        if (! $db->fieldExists('bed_hourly_rate', 'bed')) {
            $columns['bed_hourly_rate'] = [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
            ];
        }

        if (! $db->fieldExists('last_cleaned_at', 'bed')) {
            $columns['last_cleaned_at'] = [
                'type' => 'TIMESTAMP',
                'null' => true,
            ];
        }

        if (! empty($columns)) {
            $forge->addColumn('bed', $columns);
        }
    }
}
