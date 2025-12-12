<?php

namespace App\Models;

use CodeIgniter\Model;

class InvoiceModel extends Model
{
    protected $table            = 'invoices';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'invoice_no', 'patient_name', 'service', 'amount', 'due_date', 'status'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'patient_name' => 'required|min_length[2]',
        'service'      => 'required|min_length[2]',
        'amount'       => 'required|decimal',
        'due_date'     => 'permit_empty|valid_date'
    ];
}
