<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterResourcesTable extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // Make 'location' nullable, and only modify 'supplier' if it exists
        $fields = [
            'location' => [
                'name'       => 'location',
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
        ];

        if ($db->fieldExists('supplier', 'resources')) {
            $fields['supplier'] = [
                'name'       => 'supplier',
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ];
        }

        $this->forge->modifyColumn('resources', $fields);
    }

    public function down()
    {
        $db = \Config\Database::connect();

        // Revert 'location' to NOT NULL, and only modify 'supplier' if it exists
        $fields = [
            'location' => [
                'name'       => 'location',
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
        ];

        if ($db->fieldExists('supplier', 'resources')) {
            $fields['supplier'] = [
                'name'       => 'supplier',
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ];
        }

        $this->forge->modifyColumn('resources', $fields);
    }
}
