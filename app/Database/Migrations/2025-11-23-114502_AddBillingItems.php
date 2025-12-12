<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBillingItems extends Migration
{
    public function up()
    {
  
        if (! $this->db->fieldExists('patient_id', 'billing_accounts')) {
            $fields = [
                'patient_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => false,
                    'after'      => 'billing_id', // adjust if needed
                ],
            ];

            $this->forge->addColumn('billing_accounts', $fields);
        }

    
        if (! $this->db->tableExists('billing_items')) {
            $this->forge->addField([
                'item_id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'billing_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => false,
                ],
                'patient_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => false,
                ],
                'appointment_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'prescription_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'description' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => false,
                ],
                'quantity' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => false,
                    'default'    => 1,
                ],
                'unit_price' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => false,
                    'default'    => '0.00',
                ],
                'line_total' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => false,
                    'default'    => '0.00',
                ],
                'created_by_staff_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'created_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('item_id', true);

            // Optional indexes to speed up lookups
            $this->forge->addKey('billing_id');
            $this->forge->addKey('patient_id');
            $this->forge->addKey('appointment_id');
            $this->forge->addKey('prescription_id');

            $this->forge->createTable('billing_items', true);
        }
    }

    public function down()
    {
        // Drop billing_items table
        if ($this->db->tableExists('billing_items')) {
            $this->forge->dropTable('billing_items', true);
        }

        // Remove patient_id from billing_accounts (only if exists)
        if ($this->db->fieldExists('patient_id', 'billing_accounts')) {
            $this->forge->dropColumn('billing_accounts', 'patient_id');
        }
    }
}