<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRoomAssignmentTable extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('room_assignment')) {
            $this->forge->addField([
                'assignment_id' => [
                    'type'           => 'INT',
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'patient_id' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'null'     => false,
                ],
                'room_id' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'null'     => false,
                ],
                'bed_id' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'null'     => true,
                ],
                'admission_id' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'null'     => true,
                ],
                'assigned_by' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'null'     => true,
                    'comment'  => 'staff_id of assigning staff',
                ],
                'date_in' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
                'date_out' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'total_days' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'null'     => true,
                ],
                'total_hours' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => true,
                ],
                'room_rate_at_time' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => false,
                    'default'    => 0.00,
                ],
                'bed_rate_at_time' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => true,
                ],
                'discount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => true,
                    'comment'    => 'manual or insurance-based discount',
                ],
                'billing_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => false,
                    'default'    => 0.00,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => false,
                    'default'    => 'active',
                    'comment'    => 'active / completed',
                ],
                // Timestamps as NULL first; defaults set via raw SQL after create
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

            $this->forge->addKey('assignment_id', true);
            $this->forge->addKey('patient_id');
            $this->forge->addKey('room_id');
            $this->forge->addKey('bed_id');
            $this->forge->addKey('admission_id');
            $this->forge->addKey('assigned_by');
            $this->forge->addKey('status');

            $this->forge->createTable('room_assignment', true);

            // Ensure InnoDB and set timestamp defaults
            $db->query('ALTER TABLE room_assignment ENGINE=InnoDB');
            $db->query('ALTER TABLE room_assignment MODIFY created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
            $db->query('ALTER TABLE room_assignment MODIFY updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        }
    }

    public function down()
    {
        $this->forge->dropTable('room_assignment', true);
    }
}
