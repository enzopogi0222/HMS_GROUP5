<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePharmacyInventoryTable extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // Pharmacy Inventory Table (create only if not exists)
        if (!$db->tableExists('pharmacy_inventory')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'item_code' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'unique' => true,
                ],
                'name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                ],
                'category' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                ],
                'description' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'stock_quantity' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                ],
                'unit' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                ],
                'min_stock_level' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 10,
                ],
                'max_stock_level' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'expiry_date' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'batch_number' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                ],
                'supplier' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'unit_price' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'default' => 0.00,
                ],
                'status' => [
                    'type' => 'ENUM',
                    'constraint' => ['active', 'inactive', 'discontinued'],
                    'default' => 'active',
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey('category');
            // ifNotExists=true to avoid exception in rare race conditions
            $this->forge->createTable('pharmacy_inventory', true);
        }

        // Inventory Transactions Table (create only if not exists)
        if (!$db->tableExists('inventory_transactions')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'inventory_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
                'transaction_type' => [
                    'type' => 'ENUM',
                    'constraint' => ['receive', 'adjust', 'dispense', 'expired'],
                ],
                'quantity_change' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'reason' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_by' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey('inventory_id');
            $this->forge->addForeignKey('inventory_id', 'pharmacy_inventory', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('created_by', 'users', 'user_id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('inventory_transactions', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('inventory_transactions');
        $this->forge->dropTable('pharmacy_inventory');
    }
}
