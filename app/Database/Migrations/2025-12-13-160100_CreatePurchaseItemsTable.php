<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePurchaseItemsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'purchase_item_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'purchase_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
                'comment'    => 'Reference to purchases table'
            ],
            'resource_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'Reference to resources table (if applicable)'
            ],
            'item_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
                'comment'    => 'Name of purchased item'
            ],
            'quantity' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
                'default'    => 1,
                'comment'    => 'Quantity purchased'
            ],
            'unit_price' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => false,
                'default'    => 0.00,
                'comment'    => 'Price per unit'
            ],
            'total_price' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => false,
                'default'    => 0.00,
                'comment'    => 'Total price (quantity * unit_price)'
            ],
            'batch_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'comment'    => 'Batch/lot number (for medications)'
            ],
            'expiry_date' => [
                'type' => 'DATE',
                'null' => true,
                'comment' => 'Expiry date (for medications)'
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Additional notes about this item'
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

        $this->forge->addKey('purchase_item_id', true);
        $this->forge->addKey('purchase_id');
        $this->forge->addKey('resource_id');
        $this->forge->addForeignKey('purchase_id', 'purchases', 'purchase_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('purchase_items');
    }

    public function down()
    {
        $this->forge->dropTable('purchase_items');
    }
}
