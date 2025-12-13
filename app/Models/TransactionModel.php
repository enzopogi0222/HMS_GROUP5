<?php

namespace App\Models;

use CodeIgniter\Model;

class TransactionModel extends Model
{
    protected $table            = 'transactions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'transaction_id',
        'type',
        'category',
        'amount',
        'quantity',
        'description',
        'patient_id',
        'appointment_id',
        'resource_id',
        'payment_method',
        'payment_status',
        'reference_number',
        'created_by',
        'transaction_date',
        'transaction_time',
        'notes'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [
        'transaction_id'    => 'required|max_length[50]|is_unique[transactions.transaction_id,id,{id}]',
        'type'              => 'required|in_list[payment,expense,refund,adjustment,stock_in,stock_out]',
        'amount'            => 'permit_empty|numeric|greater_than_equal_to[0]',
        'quantity'           => 'permit_empty|integer|greater_than_equal_to[0]',
        'payment_method'    => 'in_list[cash,credit_card,debit_card,bank_transfer,insurance,other]',
        'payment_status'    => 'in_list[pending,completed,failed,refunded,cancelled]',
        'transaction_date'  => 'required|valid_date[Y-m-d]',
        'transaction_time'  => 'required|valid_date[H:i:s]',
    ];
    protected $validationMessages   = [
        'transaction_id' => [
            'required' => 'Transaction ID is required',
            'is_unique' => 'Transaction ID must be unique',
        ],
        'type' => [
            'required' => 'Transaction type is required',
            'in_list' => 'Invalid transaction type',
        ],
        'amount' => [
            'numeric' => 'Amount must be a valid number',
            'greater_than_equal_to' => 'Amount must be 0 or greater',
        ],
        'quantity' => [
            'integer' => 'Quantity must be a valid integer',
            'greater_than_equal_to' => 'Quantity must be 0 or greater',
        ],
    ];
    protected $skipValidation       = false;

    /**
     * Generate unique transaction ID
     */
    public function generateTransactionId()
    {
        $year = date('Y');
        $prefix = 'TXN' . $year;
        
        $lastTransaction = $this->where('transaction_id LIKE', $prefix . '%')
                                 ->orderBy('transaction_id', 'DESC')
                                 ->first();
        
        if ($lastTransaction) {
            $lastNumber = intval(substr($lastTransaction['transaction_id'], -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }
        
        return $prefix . $newNumber;
    }

    /**
     * Get transactions with filters
     */
    public function getTransactions($filters = [])
    {
        $builder = $this->select('transactions.*, 
                                 p.first_name as patient_first_name, 
                                 p.last_name as patient_last_name,
                                 a.appointment_date,
                                 r.equipment_name as resource_name,
                                 u.username as created_by_username')
                         ->join('patients p', 'p.patient_id = transactions.patient_id', 'left')
                         ->join('appointments a', 'a.id = transactions.appointment_id', 'left')
                         ->join('resources r', 'r.id = transactions.resource_id', 'left')
                         ->join('users u', 'u.user_id = transactions.created_by', 'left');

        // Apply filters
        if (!empty($filters['type'])) {
            $builder->where('transactions.type', $filters['type']);
        }
        
        if (!empty($filters['payment_status'])) {
            $builder->where('transactions.payment_status', $filters['payment_status']);
        }
        
        if (!empty($filters['date_from'])) {
            $builder->where('transactions.transaction_date >=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $builder->where('transactions.transaction_date <=', $filters['date_to']);
        }
        
        if (!empty($filters['search'])) {
            $builder->groupStart()
                    ->like('transactions.transaction_id', $filters['search'])
                    ->orLike('transactions.description', $filters['search'])
                    ->orLike('transactions.reference_number', $filters['search'])
                    ->orLike('p.first_name', $filters['search'])
                    ->orLike('p.last_name', $filters['search'])
                    ->orLike('r.equipment_name', $filters['search'])
                    ->groupEnd();
        }

        return $builder->orderBy('transactions.transaction_date DESC, transactions.transaction_time DESC')
                       ->findAll();
    }

    /**
     * Get transaction summary statistics
     */
    public function getTransactionSummary($dateFrom = null, $dateTo = null)
    {
        $builder = $this->builder();
        
        if ($dateFrom) {
            $builder->where('transaction_date >=', $dateFrom);
        }
        
        if ($dateTo) {
            $builder->where('transaction_date <=', $dateTo);
        }

        $totalRevenue = $builder->where('type', 'payment')
                                ->where('payment_status', 'completed')
                                ->selectSum('amount')
                                ->get()
                                ->getRow()
                                ->amount ?? 0;

        $totalExpenses = $builder->where('type', 'expense')
                                 ->where('payment_status', 'completed')
                                 ->selectSum('amount')
                                 ->get()
                                 ->getRow()
                                 ->amount ?? 0;

        $totalRefunds = $builder->where('type', 'refund')
                                ->where('payment_status', 'refunded')
                                ->selectSum('amount')
                                ->get()
                                ->getRow()
                                ->amount ?? 0;

        $pendingPayments = $builder->where('payment_status', 'pending')
                                   ->selectSum('amount')
                                   ->get()
                                   ->getRow()
                                   ->amount ?? 0;

        return [
            'total_revenue' => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'total_refunds' => $totalRefunds,
            'net_revenue' => $totalRevenue - $totalExpenses - $totalRefunds,
            'pending_payments' => $pendingPayments,
        ];
    }

    /**
     * Get transactions by date range for chart
     */
    public function getTransactionsByDate($dateFrom, $dateTo, $groupBy = 'day')
    {
        $builder = $this->builder();
        $builder->where('transaction_date >=', $dateFrom)
                ->where('transaction_date <=', $dateTo)
                ->where('payment_status', 'completed');

        switch ($groupBy) {
            case 'month':
                $builder->select('DATE_FORMAT(transaction_date, "%Y-%m") as period, 
                                 SUM(CASE WHEN type = "payment" THEN amount ELSE 0 END) as revenue,
                                 SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as expenses,
                                 SUM(CASE WHEN type = "refund" THEN amount ELSE 0 END) as refunds')
                        ->groupBy('DATE_FORMAT(transaction_date, "%Y-%m")')
                        ->orderBy('period');
                break;
            
            case 'week':
                $builder->select('YEARWEEK(transaction_date) as period,
                                 SUM(CASE WHEN type = "payment" THEN amount ELSE 0 END) as revenue,
                                 SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as expenses,
                                 SUM(CASE WHEN type = "refund" THEN amount ELSE 0 END) as refunds')
                        ->groupBy('YEARWEEK(transaction_date)')
                        ->orderBy('period');
                break;
            
            default: // day
                $builder->select('transaction_date as period,
                                 SUM(CASE WHEN type = "payment" THEN amount ELSE 0 END) as revenue,
                                 SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as expenses,
                                 SUM(CASE WHEN type = "refund" THEN amount ELSE 0 END) as refunds')
                        ->groupBy('transaction_date')
                        ->orderBy('period');
        }

        return $builder->get()->getResultArray();
    }
}
