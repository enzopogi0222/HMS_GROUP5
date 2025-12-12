<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateReceptionistTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'receptionist_id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'staff_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
            'desk_no' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'shift_schedule_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
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

        $this->forge->addKey('receptionist_id', true);
        $this->forge->addForeignKey('staff_id', 'staff', 'staff_id', 'CASCADE', 'CASCADE', 'fk_receptionist_staff');

        $this->forge->createTable('receptionist');

        $db = \Config\Database::connect();
        // Ensure InnoDB for FK support
        $db->query('ALTER TABLE receptionist ENGINE=InnoDB');
        // Apply DEFAULT CURRENT_TIMESTAMP and ON UPDATE behavior
        $db->query('ALTER TABLE receptionist MODIFY created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
        $db->query('ALTER TABLE receptionist MODIFY updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }

    public function down()
    {
        $this->forge->dropTable('receptionist');
    }
}
