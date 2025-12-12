<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateITstaffTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'it_staff_id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'staff_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
            'expertise' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
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

        $this->forge->addKey('it_staff_id', true);
        $this->forge->addForeignKey('staff_id', 'staff', 'staff_id', 'CASCADE', 'CASCADE', 'fk_itstaff_staff');

        $this->forge->createTable('it_staff');

        $db = \Config\Database::connect();
        // Ensure InnoDB for FK support
        $db->query('ALTER TABLE it_staff ENGINE=InnoDB');
        // Apply DEFAULT CURRENT_TIMESTAMP and ON UPDATE behavior
        $db->query('ALTER TABLE it_staff MODIFY created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
        $db->query('ALTER TABLE it_staff MODIFY updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }

    public function down()
    {
        $this->forge->dropTable('it_staff');
    }
}
