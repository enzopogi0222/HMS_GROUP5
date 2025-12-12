<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateResourcesTable extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('resources')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'equipment_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                ],
                'category' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                ],
                'quantity' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                ],
                'location' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'serial_number' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'remarks' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_at' => [
                    'type'    => 'TIMESTAMP',
                    'null'    => true,
                    'default' => null,
                ],
                'updated_at' => [
                    'type'    => 'TIMESTAMP',
                    'null'    => true,
                    'default' => null,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->createTable('resources', true);

            // Set timestamp defaults for automatic handling
            $db->query('ALTER TABLE resources MODIFY created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
            $db->query('ALTER TABLE resources MODIFY updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        }
    }

    public function down()
    {
        $this->forge->dropTable('resources');
    }
}
