<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTimestampsToResourcesTable extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('resources')) {
            // Add created_at column if it doesn't exist
            if (!$db->fieldExists('created_at', 'resources')) {
                $fields = [
                    'created_at' => [
                        'type'    => 'TIMESTAMP',
                        'null'    => true,
                        'default' => null,
                        'after'   => 'remarks',
                    ],
                ];
                $this->forge->addColumn('resources', $fields);
                
                // Set default value
                $db->query('ALTER TABLE resources MODIFY created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
            }

            // Add updated_at column if it doesn't exist
            if (!$db->fieldExists('updated_at', 'resources')) {
                $fields = [
                    'updated_at' => [
                        'type'    => 'TIMESTAMP',
                        'null'    => true,
                        'default' => null,
                        'after'   => 'created_at',
                    ],
                ];
                $this->forge->addColumn('resources', $fields);
                
                // Set default value with auto-update
                $db->query('ALTER TABLE resources MODIFY updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
            }
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('resources')) {
            // Remove updated_at column if it exists
            if ($db->fieldExists('updated_at', 'resources')) {
                $this->forge->dropColumn('resources', 'updated_at');
            }
            
            // Remove created_at column if it exists
            if ($db->fieldExists('created_at', 'resources')) {
                $this->forge->dropColumn('resources', 'created_at');
            }
        }
    }
}

