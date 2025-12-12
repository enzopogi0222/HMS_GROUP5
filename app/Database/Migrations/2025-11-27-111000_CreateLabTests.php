<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLabTests extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('lab_tests')) {
            return;
        }

        $this->forge->addField([
            'lab_test_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'test_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'test_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 191,
                'null'       => false,
            ],
            'default_price' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => false,
                'default'    => 500.00,
            ],
            'category' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'inactive'],
                'default'    => 'active',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('lab_test_id', true);
        $this->forge->addUniqueKey('test_code');

        $this->forge->createTable('lab_tests', true);
    }

    public function down()
    {
        if ($this->db->tableExists('lab_tests')) {
            $this->forge->dropTable('lab_tests', true);
        }
    }
}
