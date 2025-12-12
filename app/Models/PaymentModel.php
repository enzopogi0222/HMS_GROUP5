<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentModel extends Model
{
    protected $table            = 'payments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'invoice_no', 'patient_name', 'amount', 'method', 'reference', 'status'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'invoice_no'   => 'required|min_length[3]',
        'amount'       => 'required|decimal',
        'method'       => 'required|in_list[Cash,Credit Card,Check,Insurance]'
    ];
}
