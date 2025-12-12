<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFinancialTransactionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'transaction_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'type' => [
                'type'       => 'ENUM',
                'constraint' => ['Income', 'Expense'],
                'null'       => false,
            ],
            'category_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => false,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'transaction_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('transaction_id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('type');
        $this->forge->addKey('category_id');
        $this->forge->addKey('transaction_date');

        // Create table without foreign keys first to avoid issues
        $this->forge->createTable('financial_transactions');
    }

    public function down()
    {
        $this->forge->dropTable('financial_transactions');
    }
}
