<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSellingPriceToResourcesTable extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (!$db->tableExists('resources')) {
            return;
        }

        // Add selling_price column if it doesn't exist
        if (!$db->fieldExists('selling_price', 'resources')) {
            $this->forge->addColumn('resources', [
                'selling_price' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'price',
                    'comment'    => 'Selling price for medications/resources (used for billing)'
                ],
            ]);
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if (!$db->tableExists('resources')) {
            return;
        }

        // Remove selling_price column if it exists
        if ($db->fieldExists('selling_price', 'resources')) {
            $this->forge->dropColumn('resources', 'selling_price');
        }
    }
}
