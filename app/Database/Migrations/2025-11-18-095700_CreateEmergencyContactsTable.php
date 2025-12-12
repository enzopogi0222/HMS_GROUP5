<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmergencyContactsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'contact_id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'patient_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ],
            'relationship' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'contact_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
        ]);

        $this->forge->addKey('contact_id', true);
        $this->forge->addForeignKey('patient_id', 'patients', 'patient_id', 'CASCADE', 'CASCADE', 'fk_emergency_contacts_patient');

        $this->forge->createTable('emergency_contacts');

        $db = \Config\Database::connect();
        $db->query('ALTER TABLE emergency_contacts ENGINE=InnoDB');
    }

    public function down()
    {
        $this->forge->dropTable('emergency_contacts');
    }
}
