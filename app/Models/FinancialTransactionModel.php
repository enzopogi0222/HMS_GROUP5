<?php

namespace App\Models;

use CodeIgniter\Model;

class FinancialTransactionModel extends Model
{
    protected $table            = 'financial_transaction';
    protected $primaryKey       = 'transaction_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'type',
        'category',
        'amount',
        'description',
        'transaction_date',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    // Validation
    protected $validationRules      = [
        'user_id'           => 'required|integer|greater_than[0]',
        'type'              => 'required|in_list[Income,Expense]',
        'category'          => 'required|string|min_length[1]|max_length[255]',
        'amount'            => 'required|numeric|greater_than[0]',
        'transaction_date'  => 'required|valid_date[Y-m-d]',
    ];
    protected $validationMessages   = [
        'user_id' => [
            'required' => 'User is required',
            'integer' => 'User ID must be a valid integer',
            'greater_than' => 'Please select a valid user',
        ],
        'type' => [
            'required' => 'Transaction type is required',
            'in_list' => 'Invalid transaction type',
        ],
        'category' => [
            'required' => 'Category is required',
            'string' => 'Category must be a valid text',
            'min_length' => 'Category cannot be empty',
            'max_length' => 'Category is too long (max 255 characters)',
        ],
        'amount' => [
            'required' => 'Amount is required',
            'numeric' => 'Amount must be a valid number',
            'greater_than' => 'Amount must be greater than 0',
        ],
        'transaction_date' => [
            'required' => 'Transaction date is required',
            'valid_date' => 'Please enter a valid date',
        ],
    ];
    protected $skipValidation       = false;

    /**
     * Get transactions with user and category information
     */
    public function getTransactionsWithDetails($filters = [])
    {
        $builder = $this->select('financial_transaction.*, 
                                 users.username as user_name')
                         ->join('users', 'users.id = financial_transaction.user_id');

        // Apply filters
        if (!empty($filters['type'])) {
            $builder->where('financial_transaction.type', $filters['type']);
        }
        
        if (!empty($filters['category'])) {
            $builder->where('financial_transaction.category', $filters['category']);
        }
        
        if (!empty($filters['user_id'])) {
            $builder->where('financial_transaction.user_id', $filters['user_id']);
        }
        
        if (!empty($filters['date_from'])) {
            $builder->where('financial_transaction.transaction_date >=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $builder->where('financial_transaction.transaction_date <=', $filters['date_to']);
        }
        
        if (!empty($filters['search'])) {
            $builder->groupStart()
                    ->like('financial_transaction.description', $filters['search'])
                    ->orLike('financial_transaction.category', $filters['search'])
                    ->orLike('users.username', $filters['search'])
                    ->groupEnd();
        }

        return $builder->orderBy('financial_transaction.transaction_date DESC, financial_transaction.created_at DESC')
                       ->findAll();
    }

    /**
     * Get financial summary
     */
    public function getFinancialSummary($dateFrom = null, $dateTo = null)
    {
        $builder = $this->builder();
        
        if ($dateFrom) {
            $builder->where('transaction_date >=', $dateFrom);
        }
        
        if ($dateTo) {
            $builder->where('transaction_date <=', $dateTo);
        }

        $totalIncome = $builder->where('type', 'Income')
                               ->selectSum('amount')
                               ->get()
                               ->getRow()
                               ->amount ?? 0;

        $totalExpenses = $builder->where('type', 'Expense')
                                 ->selectSum('amount')
                                 ->get()
                                 ->getRow()
                                 ->amount ?? 0;

        return [
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_total' => $totalIncome - $totalExpenses,
        ];
    }

    /**
     * Get transactions by category for reporting
     */
    public function getTransactionsByCategory($dateFrom = null, $dateTo = null)
    {
        $builder = $this->select('category,
                                 type,
                                 SUM(CASE WHEN type = "Income" THEN amount ELSE 0 END) as income,
                                 SUM(CASE WHEN type = "Expense" THEN amount ELSE 0 END) as expense,
                                 COUNT(transaction_id) as transaction_count')
                         ->groupBy('category, type');
        
        if ($dateFrom) {
            $builder->where('transaction_date >=', $dateFrom);
        }
        
        if ($dateTo) {
            $builder->where('transaction_date <=', $dateTo);
        }

        return $builder->orderBy('category')
                       ->get()
                       ->getResultArray();
    }

    /**
     * Get monthly trend data
     */
    public function getMonthlyTrend($months = 12)
    {
        $builder = $this->select('DATE_FORMAT(transaction_date, "%Y-%m") as month,
                                 SUM(CASE WHEN type = "Income" THEN amount ELSE 0 END) as income,
                                 SUM(CASE WHEN type = "Expense" THEN amount ELSE 0 END) as expense')
                         ->where('transaction_date >=', date('Y-m-d', strtotime("-$months months")))
                         ->groupBy('DATE_FORMAT(transaction_date, "%Y-%m")')
                         ->orderBy('month');

        return $builder->get()->getResultArray();
    }
}
