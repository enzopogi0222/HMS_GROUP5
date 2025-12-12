<?php

namespace App\Models;

use CodeIgniter\Model;

class InsuranceClaimModel extends Model
{
    protected $table            = 'insurance_claims';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'ref_no', 'patient_name', 'policy_no', 'claim_amount', 'diagnosis_code', 'notes', 'status'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'patient_name'  => 'required|min_length[2]',
        'policy_no'     => 'required|min_length[2]',
        'claim_amount'  => 'required|decimal',
        'diagnosis_code'=> 'permit_empty|min_length[3]'
    ];
}
