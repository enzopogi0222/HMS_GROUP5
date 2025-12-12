<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdatePrescriptionsTable extends Migration
{
    public function up()
    {
        // Check if prescriptions table already exists
        if ($this->db->tableExists('prescriptions')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'rx_number' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'unique' => true,
            ],
            'patient_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'patient_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'medication' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'dosage' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'frequency' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'days_supply' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'quantity' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'prescriber' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'priority' => [
                'type' => 'ENUM',
                'constraint' => ['routine', 'priority', 'stat'],
                'default' => 'routine',
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['queued', 'verifying', 'ready', 'dispensed', 'cancelled'],
                'default' => 'queued',
            ],
            'created_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'dispensed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'dispensed_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'dispensed_quantity' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        // Don't add separate key for rx_number since unique constraint already creates an index
        
        // Add foreign key constraints
        $this->forge->addForeignKey('patient_id', 'patient', 'patient_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'user_id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('dispensed_by', 'users', 'user_id', 'SET NULL', 'CASCADE');
        
        $this->forge->createTable('prescriptions');
    }

    public function down()
    {
        $this->forge->dropTable('prescriptions');
    }
}
