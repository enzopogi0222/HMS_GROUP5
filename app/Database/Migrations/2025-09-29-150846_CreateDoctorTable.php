<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDoctorTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'doctor_id' =>[
             'type'   => 'INT',
            'auto_increment' => true,
            'unsigned' => true,
            ],
            'staff_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
            'specialization' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'license_no' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['Active', 'Inactive'],
                'default'    => 'Active',
            ],
        ]);
        $this->forge->addKey('doctor_id', true);
        $this->forge->addForeignKey('staff_id', 'staff', 'staff_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('doctor');
    }

    public function down()
    {
         $this->forge->dropTable('doctor');
    }
}
