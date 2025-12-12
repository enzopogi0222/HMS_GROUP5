<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateShiftTable extends Migration
{
    public function up()
    {
        // If table already exists, skip creation to avoid errors
        $db = \Config\Database::connect();
        if ($db->tableExists('doctor_shift')) {
            return;
        }
        $this->forge->addField([
            'shift_id' => [
                'type'           => 'INT',
                'auto_increment' => true,
                'unsigned'       => true,
            ],
            'doctor_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
            'shift_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'shift_start' => [
                'type' => 'TIME',
                'null' => false,
            ],
            'shift_end' => [
                'type' => 'TIME',
                'null' => false,
            ],
            'department' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
             'status' => [
                'type'       => 'ENUM',
                'constraint' => ['Scheduled', 'Completed', 'Cancelled'],
                'default'    => 'Scheduled',
            ],
        ]);
        $this->forge->addKey('shift_id', true);
        $this->forge->addForeignKey('doctor_id', 'doctor', 'doctor_id', 'CASCADE', 'CASCADE');
        // Create with IF NOT EXISTS
        $this->forge->createTable('doctor_shift', true);
    }

    public function down()
    {
         // Drop with IF EXISTS
         $this->forge->dropTable('doctor_shift', true);
    }
}
