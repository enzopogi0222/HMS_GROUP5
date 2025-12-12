<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CategoriesSeeder extends Seeder
{
    public function run()
    {
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

        $allCategories = array_merge($incomeCategories, $expenseCategories);

        // Add created_at and updated_at timestamps
        foreach ($allCategories as &$category) {
            $category['created_at'] = date('Y-m-d H:i:s');
            $category['updated_at'] = date('Y-m-d H:i:s');
        }

        $this->db->table('categories')->insertBatch($allCategories);
    }
}
