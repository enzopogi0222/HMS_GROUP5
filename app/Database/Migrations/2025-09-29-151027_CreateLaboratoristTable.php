<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLaboratoristTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'laboratorist_id' => [
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
            'lab_room_no' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            // Create timestamps as NULL first; set DEFAULT/ON UPDATE after create to satisfy strict mode
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

        $this->forge->addKey('laboratorist_id', true);
        $this->forge->addForeignKey('staff_id', 'staff', 'staff_id', 'CASCADE', 'CASCADE', 'fk_laboratorist_staff');

        $this->forge->createTable('laboratorist');

        $db = \Config\Database::connect();
        // Ensure InnoDB for FK support
        $db->query('ALTER TABLE laboratorist ENGINE=InnoDB');
        // Apply DEFAULT CURRENT_TIMESTAMP and ON UPDATE behavior
        $db->query('ALTER TABLE laboratorist MODIFY created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
        $db->query('ALTER TABLE laboratorist MODIFY updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }

    public function down()
    {
        $this->forge->dropTable('laboratorist');
    }
}
