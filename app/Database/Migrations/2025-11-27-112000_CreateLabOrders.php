<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLabOrders extends Migration
{
    public function up()
    {
        // Only create if it doesn't already exist
        if ($this->db->tableExists('lab_orders')) {
            return;
        }

        $this->forge->addField([
            'lab_order_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'patient_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'doctor_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
                // In this HMS this should match staff.staff_id where role = 'doctor'
            ],
            'appointment_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'test_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'test_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 191,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['ordered', 'in_progress', 'completed', 'cancelled'],
                'default'    => 'ordered',
            ],
            'priority' => [
                'type'       => 'ENUM',
                'constraint' => ['routine', 'urgent', 'stat'],
                'default'    => 'routine',
                'null'       => false,
            ],
            'ordered_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'completed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('lab_order_id', true);
        $this->forge->addKey('patient_id');
        $this->forge->addKey('doctor_id');
        $this->forge->addKey('appointment_id');
        $this->forge->addKey('status');
        $this->forge->addKey('test_code');

        $this->forge->createTable('lab_orders', true);
    }

    public function down()
    {
        if ($this->db->tableExists('lab_orders')) {
            $this->forge->dropTable('lab_orders', true);
        }
    }
}
