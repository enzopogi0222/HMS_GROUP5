<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddExpiryDateToResources extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('resources')) {
            return;
        }

        if (! $db->fieldExists('expiry_date', 'resources')) {
            try {
                $this->forge->addColumn('resources', [
                    'expiry_date' => [
                        'type' => 'DATE',
                        'null' => true,
                        'after' => $db->fieldExists('batch_number', 'resources') ? 'batch_number' : 'location',
                    ],
                ]);
            } catch (\Throwable $e) {
                if (! $db->fieldExists('expiry_date', 'resources')) {
                    throw $e;
                }
            }
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('resources') && $db->fieldExists('expiry_date', 'resources')) {
            $this->forge->dropColumn('resources', 'expiry_date');
        }
    }
}
