<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DoctorShiftTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'shift_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'doctor_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'shift_date' => [
                'type' => 'DATE',
            ],
            'shift_start' => [
                'type' => 'TIME',
            ],
            'shift_end' => [
                'type' => 'TIME',
            ],
            'shift_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'duration_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
            ],
            'room_ward' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['Scheduled', 'Completed', 'Cancelled'],
                'default'    => 'Scheduled',
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

        $this->forge->addKey('shift_id', true);

        // FK to doctor table and key used in code
        $this->forge->addForeignKey('doctor_id', 'doctor', 'doctor_id', 'CASCADE', 'CASCADE');

        // Use the exact table name expected by the app
        $this->forge->createTable('doctor_shift', true);
    }

    public function down()
    {
        $this->forge->dropTable('doctor_shift', true);
    }
}