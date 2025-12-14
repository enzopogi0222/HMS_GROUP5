<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStockTransactionsToTransactionsTable extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (!$db->tableExists('transactions')) {
            return;
        }

        // Add quantity field for stock transactions
        if (!$db->fieldExists('quantity', 'transactions')) {
            $this->forge->addColumn('transactions', [
                'quantity' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'amount',
                ],
            ]);
        }

        // Update type ENUM to include stock_in and stock_out
        // Note: MySQL doesn't support direct ENUM modification, so we need to use ALTER TABLE
        try {
            $db->query("ALTER TABLE transactions MODIFY COLUMN type ENUM('payment', 'expense', 'refund', 'adjustment', 'stock_in', 'stock_out') DEFAULT 'payment'");
        } catch (\Exception $e) {
            log_message('error', 'Failed to update transactions type ENUM: ' . $e->getMessage());
        }

        // Make amount nullable for stock transactions (they may not have monetary value)
        if ($db->fieldExists('amount', 'transactions')) {
            try {
                $db->query("ALTER TABLE transactions MODIFY COLUMN amount DECIMAL(10,2) NULL");
            } catch (\Exception $e) {
                log_message('error', 'Failed to make amount nullable: ' . $e->getMessage());
            }
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if (!$db->tableExists('transactions')) {
            return;
        }

        // Remove quantity field
        if ($db->fieldExists('quantity', 'transactions')) {
            $this->forge->dropColumn('transactions', 'quantity');
        }

        // Revert type ENUM (remove stock_in and stock_out)
        try {
            $db->query("ALTER TABLE transactions MODIFY COLUMN type ENUM('payment', 'expense', 'refund', 'adjustment') DEFAULT 'payment'");
        } catch (\Exception $e) {
            log_message('error', 'Failed to revert transactions type ENUM: ' . $e->getMessage());
        }

        // Revert amount to NOT NULL
        if ($db->fieldExists('amount', 'transactions')) {
            try {
                $db->query("ALTER TABLE transactions MODIFY COLUMN amount DECIMAL(10,2) NOT NULL");
            } catch (\Exception $e) {
                log_message('error', 'Failed to revert amount to NOT NULL: ' . $e->getMessage());
            }
        }
    }
}



