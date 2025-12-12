<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStaffSchedule extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'staff_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'weekday' => [
                'type'     => 'TINYINT',
                'unsigned' => true,
                'null'     => false, // 1 = Monday ... 7 = Sunday
            ],
            'slot' => [
                'type'       => 'ENUM',
                'constraint' => ['morning', 'afternoon', 'night', 'all_day'],
                'null'       => false,
            ],
            'start_time' => [
                'type' => 'TIME',
                'null' => true,
            ],
            'end_time' => [
                'type' => 'TIME',
                'null' => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'inactive'],
                'default'    => 'active',
            ],
            'effective_from' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'effective_to' => [
                'type' => 'DATE',
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
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('staff_id', 'staff', 'staff_id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('staff_schedule', true);
    }

    public function down()
    {
        $this->forge->dropTable('staff_schedule', true);
    }
}
