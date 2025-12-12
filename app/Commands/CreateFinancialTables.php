<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CreateFinancialTables extends BaseCommand
{
    protected $group = 'Database';
    protected $name = 'create:financial-tables';
    protected $description = 'Create financial management tables manually';
    
    public function run(array $params = [])
    {
        $db = \Config\Database::connect();
        
        // Create categories table
        $createCategoriesSQL = "
        CREATE TABLE IF NOT EXISTS categories (
            category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            type ENUM('Income', 'Expense') NOT NULL,
            description TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX idx_type (type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        // Create financial_transactions table
        $createFinancialTransactionsSQL = "
        CREATE TABLE IF NOT EXISTS financial_transactions (
            transaction_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            type ENUM('Income', 'Expense') NOT NULL,
            category_id INT UNSIGNED NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            description TEXT NULL,
            transaction_date DATE NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_type (type),
            INDEX idx_category_id (category_id),
            INDEX idx_transaction_date (transaction_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        try {
            CLI::write('Creating categories table...', 'green');
            $db->query($createCategoriesSQL);
            CLI::write('✓ Categories table created successfully!', 'green');
            
            CLI::write('Creating financial_transactions table...', 'green');
            $db->query($createFinancialTransactionsSQL);
            CLI::write('✓ Financial transactions table created successfully!', 'green');
            
            CLI::write("\n🎉 Financial management database setup complete!", 'cyan');
            
        } catch (\Exception $e) {
            CLI::error('❌ Error: ' . $e->getMessage());
        }
    }
}
