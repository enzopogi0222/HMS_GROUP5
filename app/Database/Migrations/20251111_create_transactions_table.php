<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTransactionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'transaction_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
            ],
            'type' => [
                'type'       => 'ENUM',
                'constraint' => ['payment', 'expense', 'refund', 'adjustment'],
                'default'    => 'payment',
            ],
            'category' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => false,
            ],
            'description' => [
                'type'       => 'TEXT',
                'null'       => true,
            ],
            'patient_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'appointment_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'resource_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'payment_method' => [
                'type'       => 'ENUM',
                'constraint' => ['cash', 'credit_card', 'debit_card', 'bank_transfer', 'insurance', 'other'],
                'null'       => true,
            ],
            'payment_status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'completed', 'failed', 'refunded', 'cancelled'],
                'default'    => 'pending',
            ],
            'reference_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'transaction_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'transaction_time' => [
                'type' => 'TIME',
                'null' => false,
            ],
            'notes' => [
                'type'       => 'TEXT',
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('transaction_id', false, true);
        $this->forge->addKey('type');
        $this->forge->addKey('payment_status');
        $this->forge->addKey('transaction_date');
        $this->forge->addKey('patient_id');
        $this->forge->addKey('appointment_id');
        $this->forge->addKey('resource_id');
        $this->forge->addKey('created_by');

        // Create table without foreign keys first
        $this->forge->createTable('transactions');

        // Add foreign keys only if the referenced tables exist
        if ($this->db->tableExists('patients')) {
            $this->db->query('ALTER TABLE transactions ADD CONSTRAINT fk_transactions_patient_id 
                              FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL ON UPDATE SET NULL');
        }
        
        if ($this->db->tableExists('appointments')) {
            $this->db->query('ALTER TABLE transactions ADD CONSTRAINT fk_transactions_appointment_id 
                              FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL ON UPDATE SET NULL');
        }
        
        if ($this->db->tableExists('resources')) {
            $this->db->query('ALTER TABLE transactions ADD CONSTRAINT fk_transactions_resource_id 
                              FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE SET NULL ON UPDATE SET NULL');
        }
        
        if ($this->db->tableExists('users')) {
            $this->db->query('ALTER TABLE transactions ADD CONSTRAINT fk_transactions_created_by 
                              FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE SET NULL');
        }
    }

    public function down()
    {
        // Drop foreign keys first if they exist
        $this->db->query('ALTER TABLE transactions DROP FOREIGN KEY IF EXISTS fk_transactions_patient_id');
        $this->db->query('ALTER TABLE transactions DROP FOREIGN KEY IF EXISTS fk_transactions_appointment_id');
        $this->db->query('ALTER TABLE transactions DROP FOREIGN KEY IF EXISTS fk_transactions_resource_id');
        $this->db->query('ALTER TABLE transactions DROP FOREIGN KEY IF EXISTS fk_transactions_created_by');
        
        $this->forge->dropTable('transactions');
    }
}
