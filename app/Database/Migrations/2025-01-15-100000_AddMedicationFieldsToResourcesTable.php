<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMedicationFieldsToResourcesTable extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('resources')) {
            // Add batch_number field for medication tracking
            if (!$db->fieldExists('batch_number', 'resources')) {
                // Place after location if supplier doesn't exist, otherwise after supplier
                $afterField = $db->fieldExists('supplier', 'resources') ? 'supplier' : 'location';
                $fields = [
                    'batch_number' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 100,
                        'null'       => true,
                        'after'      => $afterField,
                    ],
                ];
                $this->forge->addColumn('resources', $fields);
            }

            // Add expiry_date field for medication expiration tracking
            if (!$db->fieldExists('expiry_date', 'resources')) {
                $fields = [
                    'expiry_date' => [
                        'type' => 'DATE',
                        'null' => true,
                        'after' => 'batch_number',
                    ],
                ];
                $this->forge->addColumn('resources', $fields);
            }

            // Add serial_number field for individual item tracking (optional)
            if (!$db->fieldExists('serial_number', 'resources')) {
                $fields = [
                    'serial_number' => [
                        'type'       => 'VARCHAR',
                        'constraint' => 100,
                        'null'       => true,
                        'after'      => 'expiry_date',
                    ],
                ];
                $this->forge->addColumn('resources', $fields);
            }
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('resources')) {
            // Remove fields if they exist
            if ($db->fieldExists('serial_number', 'resources')) {
                $this->forge->dropColumn('resources', 'serial_number');
            }
            
            if ($db->fieldExists('expiry_date', 'resources')) {
                $this->forge->dropColumn('resources', 'expiry_date');
            }
            
            if ($db->fieldExists('batch_number', 'resources')) {
                $this->forge->dropColumn('resources', 'batch_number');
            }
        }
    }
}

