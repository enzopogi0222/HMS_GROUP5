<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPriceToResourcesTable extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('resources')) {
            return;
        }

        // Ensure expiry_date exists before attempting to place price after it.
        if (! $db->fieldExists('expiry_date', 'resources')) {
            $this->forge->addColumn('resources', [
                'expiry_date' => [
                    'type'  => 'DATE',
                    'null'  => true,
                    'after' => $db->fieldExists('batch_number', 'resources') ? 'batch_number' : 'location',
                ],
            ]);
        }

        // Add price field for medications
        if (! $db->fieldExists('price', 'resources')) {
            $fields = [
                'price' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'expiry_date',
                ],
            ];
            $this->forge->addColumn('resources', $fields);
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('resources')) {
            return;
        }

        // Remove price field if it exists
        if ($db->fieldExists('price', 'resources')) {
            $this->forge->dropColumn('resources', 'price');
        }

        // Only drop expiry_date if we added it in up() and it is not required elsewhere.
        // Since newer migrations rely on it, keep the column when present.
    }
}

