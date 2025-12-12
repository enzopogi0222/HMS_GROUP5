<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TransactionSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'transaction_id'    => 'TXN' . date('Y') . '0001',
                'type'              => 'payment',
                'category'          => 'Consultation Fee',
                'amount'            => 500.00,
                'description'       => 'General consultation fee for patient',
                'patient_id'        => 1,
                'appointment_id'    => 1,
                'payment_method'    => 'cash',
                'payment_status'    => 'completed',
                'reference_number'  => 'CASH' . date('Ymd') . '001',
                'created_by'        => 1,
                'transaction_date'  => date('Y-m-d'),
                'transaction_time'  => date('H:i:s'),
                'notes'             => 'Patient paid in cash after consultation',
            ],
            [
                'transaction_id'    => 'TXN' . date('Y') . '0002',
                'type'              => 'expense',
                'category'          => 'Medical Supplies',
                'amount'            => 2500.00,
                'description'       => 'Purchase of medical supplies and equipment',
                'resource_id'       => 1,
                'payment_method'    => 'bank_transfer',
                'payment_status'    => 'completed',
                'reference_number'  => 'BANK' . date('Ymd') . '001',
                'created_by'        => 1,
                'transaction_date'  => date('Y-m-d', strtotime('-1 day')),
                'transaction_time'  => date('H:i:s', strtotime('-1 day')),
                'notes'             => 'Monthly supply purchase',
            ],
            [
                'transaction_id'    => 'TXN' . date('Y') . '0003',
                'type'              => 'payment',
                'category'          => 'Lab Test',
                'amount'            => 1200.00,
                'description'       => 'Blood test and X-ray charges',
                'patient_id'        => 2,
                'appointment_id'    => 2,
                'payment_method'    => 'credit_card',
                'payment_status'    => 'completed',
                'reference_number'  => 'CC' . date('Ymd') . '001',
                'created_by'        => 2,
                'transaction_date'  => date('Y-m-d'),
                'transaction_time'  => date('H:i:s', strtotime('-2 hours')),
                'notes'             => 'Patient paid via credit card for lab tests',
            ],
            [
                'transaction_id'    => 'TXN' . date('Y') . '0004',
                'type'              => 'refund',
                'category'          => 'Service Refund',
                'amount'            => 200.00,
                'description'       => 'Refund for cancelled appointment',
                'patient_id'        => 3,
                'appointment_id'    => 3,
                'payment_method'    => 'cash',
                'payment_status'    => 'refunded',
                'reference_number'  => 'REF' . date('Ymd') . '001',
                'created_by'        => 1,
                'transaction_date'  => date('Y-m-d', strtotime('-2 days')),
                'transaction_time'  => date('H:i:s', strtotime('-2 days')),
                'notes'             => 'Refund processed due to appointment cancellation',
            ],
            [
                'transaction_id'    => 'TXN' . date('Y') . '0005',
                'type'              => 'payment',
                'category'          => 'Emergency Service',
                'amount'            => 3000.00,
                'description'       => 'Emergency room consultation and treatment',
                'patient_id'        => 4,
                'payment_method'    => 'insurance',
                'payment_status'    => 'completed',
                'reference_number'  => 'INS' . date('Ymd') . '001',
                'created_by'        => 3,
                'transaction_date'  => date('Y-m-d'),
                'transaction_time'  => date('H:i:s', strtotime('-4 hours')),
                'notes'             => 'Insurance claim processed successfully',
            ],
        ];

        $this->db->table('transactions')->insertBatch($data);
    }
}
