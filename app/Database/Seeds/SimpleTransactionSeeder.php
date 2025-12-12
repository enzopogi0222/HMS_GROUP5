<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SimpleTransactionSeeder extends Seeder
{
    public function run()
    {
        // Simple test data with minimal fields
        $data = [
            [
                'transaction_id'    => 'TXN20250001',
                'type'              => 'payment',
                'amount'            => 500.00,
                'payment_status'    => 'completed',
                'transaction_date'  => date('Y-m-d'),
                'transaction_time'  => date('H:i:s'),
            ],
        ];

        try {
            $this->db->table('transactions')->insert($data);
            echo "Simple transaction inserted successfully!\n";
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}
