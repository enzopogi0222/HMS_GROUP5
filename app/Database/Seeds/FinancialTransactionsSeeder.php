<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class FinancialTransactionsSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'user_id'           => 1,
                'type'              => 'Income',
                'category_id'       => 1, // Patient Consultations
                'amount'            => 2500.00,
                'description'       => 'Consultation fees from multiple patients',
                'transaction_date'  => date('Y-m-d'),
                'created_at'        => date('Y-m-d H:i:s'),
            ],
            [
                'user_id'           => 1,
                'type'              => 'Income',
                'category_id'       => 2, // Laboratory Services
                'amount'            => 1800.00,
                'description'       => 'Lab tests and diagnostic services',
                'transaction_date'  => date('Y-m-d', strtotime('-1 day')),
                'created_at'        => date('Y-m-d H:i:s'),
            ],
            [
                'user_id'           => 2,
                'type'              => 'Expense',
                'category_id'       => 11, // Salaries & Wages
                'amount'            => 15000.00,
                'description'       => 'Monthly staff salaries',
                'transaction_date'  => date('Y-m-d', strtotime('-2 days')),
                'created_at'        => date('Y-m-d H:i:s'),
            ],
            [
                'user_id'           => 1,
                'type'              => 'Income',
                'category_id'       => 3, // Pharmacy Sales
                'amount'            => 3200.50,
                'description'       => 'Medicine sales to patients',
                'transaction_date'  => date('Y-m-d'),
                'created_at'        => date('Y-m-d H:i:s'),
            ],
            [
                'user_id'           => 2,
                'type'              => 'Expense',
                'category_id'       => 12, // Medical Supplies
                'amount'            => 4500.00,
                'description'       => 'Purchase of surgical supplies',
                'transaction_date'  => date('Y-m-d', strtotime('-3 days')),
                'created_at'        => date('Y-m-d H:i:s'),
            ],
            [
                'user_id'           => 1,
                'type'              => 'Income',
                'category_id'       => 4, // Insurance Payments
                'amount'            => 8500.00,
                'description'       => 'Insurance claim settlement',
                'transaction_date'  => date('Y-m-d', strtotime('-1 day')),
                'created_at'        => date('Y-m-d H:i:s'),
            ],
            [
                'user_id'           => 2,
                'type'              => 'Expense',
                'category_id'       => 13, // Utilities
                'amount'            => 2800.00,
                'description'       => 'Monthly electricity and water bills',
                'transaction_date'  => date('Y-m-d', strtotime('-5 days')),
                'created_at'        => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('financial_transactions')->insertBatch($data);
    }
}
