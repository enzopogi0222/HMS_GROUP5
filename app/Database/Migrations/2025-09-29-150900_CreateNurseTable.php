<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNurseTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'nurse_id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'staff_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
            'license_no' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'specialization' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'ward_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
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

        $this->forge->addKey('nurse_id', true);
        $this->forge->addForeignKey('staff_id', 'staff', 'staff_id', 'CASCADE', 'CASCADE', 'fk_nurse_staff');

        $this->forge->createTable('nurse');

        $db = \Config\Database::connect();
        // Ensure InnoDB for FK
        $db->query('ALTER TABLE nurse ENGINE=InnoDB');
        // Apply DEFAULT CURRENT_TIMESTAMP and ON UPDATE behavior
        $db->query('ALTER TABLE nurse MODIFY created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
        $db->query('ALTER TABLE nurse MODIFY updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }

    public function down()
    {
        $this->forge->dropTable('nurse');
    }
}
