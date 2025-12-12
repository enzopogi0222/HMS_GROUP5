<?php

namespace App\Models;

use CodeIgniter\Model;

class CategoryModel extends Model
{
    protected $table            = 'categories';
    protected $primaryKey       = 'category_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'type',
        'description',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [
        'name' => 'required|max_length[100]|is_unique[categories,name,category_id,{category_id}]',
        'type' => 'required|in_list[Income,Expense]',
    ];
    protected $validationMessages   = [
        'name' => [
            'required' => 'Category name is required',
            'max_length' => 'Category name cannot exceed 100 characters',
            'is_unique' => 'Category name already exists',
        ],
        'type' => [
            'required' => 'Category type is required',
            'in_list' => 'Invalid category type',
        ],
    ];
    protected $skipValidation       = false;

    /**
     * Get categories by type
     */
    public function getCategoriesByType($type)
    {
        return $this->where('type', $type)
                   ->orderBy('name', 'ASC')
                   ->findAll();
    }

    /**
     * Get all categories grouped by type
     */
    public function getCategoriesGrouped()
    {
        $incomeCategories = $this->getCategoriesByType('Income');
        $expenseCategories = $this->getCategoriesByType('Expense');

        return [
            'Income' => $incomeCategories,
            'Expense' => $expenseCategories,
        ];
    }

    /**
     * Get category with transaction count
     */
    public function getCategoriesWithTransactionCount()
    {
        $builder = $this->select('categories.*, 
                                 COUNT(financial_transactions.transaction_id) as transaction_count,
                                 SUM(financial_transactions.amount) as total_amount')
                         ->join('financial_transactions', 'financial_transactions.category_id = categories.category_id', 'left')
                         ->groupBy('categories.category_id')
                         ->orderBy('categories.type, categories.name');

        return $builder->findAll();
    }
}
