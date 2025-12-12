<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveFieldsFromResourcesTable extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('resources')) {
            // Remove date_acquired column if it exists
            if ($db->fieldExists('date_acquired', 'resources')) {
                $this->forge->dropColumn('resources', 'date_acquired');
            }

            // Remove maintenance_schedule column if it exists
            if ($db->fieldExists('maintenance_schedule', 'resources')) {
                $this->forge->dropColumn('resources', 'maintenance_schedule');
            }

            // Remove supplier column if it exists
            if ($db->fieldExists('supplier', 'resources')) {
                $this->forge->dropColumn('resources', 'supplier');
            }
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('resources')) {
            // Restore date_acquired column
            if (!$db->fieldExists('date_acquired', 'resources')) {
                $fields = [
                    'date_acquired' => [
                        'type' => 'DATE',
                        'null' => true,
                        'after' => 'location',
                    ],
                ];
                $this->forge->addColumn('resources', $fields);
            }

            // Restore maintenance_schedule column
            if (!$db->fieldExists('maintenance_schedule', 'resources')) {
                $fields = [
                    'maintenance_schedule' => [
                        'type' => 'DATE',
                        'null' => true,
                        'after' => 'expiry_date',
                    ],
                ];
                $this->forge->addColumn('resources', $fields);
            }

            // Restore supplier column
            if (!$db->fieldExists('supplier', 'resources')) {
                $fields = [
                    'supplier' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 100,
                        'null'       => true,
                        'after'      => 'location',
                    ],
                ];
                $this->forge->addColumn('resources', $fields);
            }
        }
    }
}

