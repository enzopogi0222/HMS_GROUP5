<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SeedFinancialData extends BaseCommand
{
    protected $group = 'Database';
    protected $name = 'seed:financial-data';
    protected $description = 'Seed financial management tables with initial data';
    
    public function run(array $params = [])
    {
        $db = \Config\Database::connect();
        
        // Income categories
        $incomeCategories = [
            ['name' => 'Patient Consultations', 'type' => 'Income', 'description' => 'Income from patient consultation fees'],
            ['name' => 'Laboratory Services', 'type' => 'Income', 'description' => 'Income from lab tests and diagnostics'],
            ['name' => 'Pharmacy Sales', 'type' => 'Income', 'description' => 'Income from medicine and medical supplies sales'],
            ['name' => 'Insurance Payments', 'type' => 'Income', 'description' => 'Payments from insurance companies'],
            ['name' => 'Emergency Services', 'type' => 'Income', 'description' => 'Income from emergency room services'],
            ['name' => 'Surgical Procedures', 'type' => 'Income', 'description' => 'Income from surgical operations'],
            ['name' => 'Room Rentals', 'type' => 'Income', 'description' => 'Income from hospital room admissions'],
            ['name' => 'Other Income', 'type' => 'Income', 'description' => 'Miscellaneous income sources'],
        ];
        
        // Expense categories
        $expenseCategories = [
            ['name' => 'Salaries & Wages', 'type' => 'Expense', 'description' => 'Staff salaries and wages'],
            ['name' => 'Medical Supplies', 'type' => 'Expense', 'description' => 'Purchase of medical supplies and equipment'],
            ['name' => 'Medicines', 'type' => 'Expense', 'description' => 'Pharmacy medicine purchases'],
            ['name' => 'Utilities', 'type' => 'Expense', 'description' => 'Electricity, water, and other utilities'],
            ['name' => 'Rent & Maintenance', 'type' => 'Expense', 'description' => 'Building rent and maintenance costs'],
            ['name' => 'Insurance Premiums', 'type' => 'Expense', 'description' => 'Hospital insurance payments'],
            ['name' => 'Marketing', 'type' => 'Expense', 'description' => 'Marketing and advertising expenses'],
            ['name' => 'Equipment Purchase', 'type' => 'Expense', 'description' => 'Medical equipment purchases'],
            ['name' => 'Training & Development', 'type' => 'Expense', 'description' => 'Staff training programs'],
            ['name' => 'Other Expenses', 'type' => 'Expense', 'description' => 'Miscellaneous expenses'],
        ];
        
        try {
            // Insert categories
            CLI::write('Seeding income categories...', 'green');
            foreach ($incomeCategories as $category) {
                $category['created_at'] = date('Y-m-d H:i:s');
                $category['updated_at'] = date('Y-m-d H:i:s');
                $db->table('categories')->insert($category);
            }
            CLI::write('✓ Income categories seeded successfully!', 'green');
            
            CLI::write('Seeding expense categories...', 'green');
            foreach ($expenseCategories as $category) {
                $category['created_at'] = date('Y-m-d H:i:s');
                $category['updated_at'] = date('Y-m-d H:i:s');
                $db->table('categories')->insert($category);
            }
            CLI::write('✓ Expense categories seeded successfully!', 'green');
            
            // Sample transactions
            CLI::write('Seeding sample transactions...', 'green');
            $sampleTransactions = [
                [
                    'user_id' => 1,
                    'type' => 'Income',
                    'category_id' => 1,
                    'amount' => 2500.00,
                    'description' => 'Consultation fees from multiple patients',
                    'transaction_date' => date('Y-m-d'),
                    'created_at' => date('Y-m-d H:i:s'),
                ],
                [
                    'user_id' => 1,
                    'type' => 'Income',
                    'category_id' => 2,
                    'amount' => 1800.00,
                    'description' => 'Lab tests and diagnostic services',
                    'transaction_date' => date('Y-m-d', strtotime('-1 day')),
                    'created_at' => date('Y-m-d H:i:s'),
                ],
                [
                    'user_id' => 2,
                    'type' => 'Expense',
                    'category_id' => 11,
                    'amount' => 15000.00,
                    'description' => 'Monthly staff salaries',
                    'transaction_date' => date('Y-m-d', strtotime('-2 days')),
                    'created_at' => date('Y-m-d H:i:s'),
                ],
            ];
            
            foreach ($sampleTransactions as $transaction) {
                $db->table('financial_transactions')->insert($transaction);
            }
            
            CLI::write('✓ Sample transactions seeded successfully!', 'green');
            CLI::write("\n🎉 Financial data seeding complete!", 'cyan');
            
        } catch (\Exception $e) {
            CLI::error('❌ Error: ' . $e->getMessage());
        }
    }
}
