<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePurchasesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'purchase_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'purchase_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
                'null'       => false,
                'comment'    => 'Unique purchase order/invoice number'
            ],
            'supplier_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'comment'    => 'Name of supplier/vendor'
            ],
            'supplier_contact' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'comment'    => 'Supplier contact information'
            ],
            'purchase_date' => [
                'type' => 'DATE',
                'null' => false,
                'comment' => 'Date of purchase'
            ],
            'total_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => false,
                'default'    => 0.00,
                'comment'    => 'Total purchase amount'
            ],
            'payment_method' => [
                'type'       => 'ENUM',
                'constraint' => ['cash', 'credit_card', 'debit_card', 'bank_transfer', 'check', 'other'],
                'default'    => 'cash',
                'null'       => false,
            ],
            'payment_status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'paid', 'partial', 'cancelled'],
                'default'    => 'paid',
                'null'       => false,
            ],
            'invoice_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'comment'    => 'Supplier invoice number'
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Additional notes about the purchase'
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
                'comment'    => 'User ID who created the purchase'
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

        $this->forge->addKey('purchase_id', true);
        $this->forge->addKey('purchase_number');
        $this->forge->addKey('purchase_date');
        $this->forge->addKey('supplier_name');
        $this->forge->addKey('created_by');
        $this->forge->createTable('purchases');
    }

    public function down()
    {
        $this->forge->dropTable('purchases');
    }
}
