<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InsuranceDiscountRatesSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();
        
        if (!$db->tableExists('insurance_discount_rates')) {
            return;
        }

        // Clear existing data
        $db->table('insurance_discount_rates')->emptyTable();

        // Insert default insurance discount rates
        $discountRates = [
            [
                'insurance_provider' => 'Maxicare',
                'discount_percentage' => 20.00,
                'coverage_type' => 'both',
                'effective_date' => '2025-01-01',
                'expiry_date' => null,
                'status' => 'active',
            ],
            [
                'insurance_provider' => 'Intellicare',
                'discount_percentage' => 15.00,
                'coverage_type' => 'both',
                'effective_date' => '2025-01-01',
                'expiry_date' => null,
                'status' => 'active',
            ],
            [
                'insurance_provider' => 'Medicard',
                'discount_percentage' => 18.00,
                'coverage_type' => 'both',
                'effective_date' => '2025-01-01',
                'expiry_date' => null,
                'status' => 'active',
            ],
            [
                'insurance_provider' => 'PhilCare',
                'discount_percentage' => 22.00,
                'coverage_type' => 'both',
                'effective_date' => '2025-01-01',
                'expiry_date' => null,
                'status' => 'active',
            ],
            [
                'insurance_provider' => 'PhilHealth',
                'discount_percentage' => 25.00,
                'coverage_type' => 'both',
                'effective_date' => '2025-01-01',
                'expiry_date' => null,
                'status' => 'active',
            ],
            [
                'insurance_provider' => 'Avega',
                'discount_percentage' => 17.00,
                'coverage_type' => 'both',
                'effective_date' => '2025-01-01',
                'expiry_date' => null,
                'status' => 'active',
            ],
            [
                'insurance_provider' => 'Generali Philippines',
                'discount_percentage' => 12.00,
                'coverage_type' => 'both',
                'effective_date' => '2025-01-01',
                'expiry_date' => null,
                'status' => 'active',
            ],
            [
                'insurance_provider' => 'Insular Health Care',
                'discount_percentage' => 16.00,
                'coverage_type' => 'both',
                'effective_date' => '2025-01-01',
                'expiry_date' => null,
                'status' => 'active',
            ],
            [
                'insurance_provider' => 'EastWest Healthcare',
                'discount_percentage' => 14.00,
                'coverage_type' => 'both',
                'effective_date' => '2025-01-01',
                'expiry_date' => null,
                'status' => 'active',
            ],
            [
                'insurance_provider' => 'ValuCare (ValueCare)',
                'discount_percentage' => 13.00,
                'coverage_type' => 'both',
                'effective_date' => '2025-01-01',
                'expiry_date' => null,
                'status' => 'active',
            ],
            [
                'insurance_provider' => 'Caritas Health Shield',
                'discount_percentage' => 19.00,
                'coverage_type' => 'both',
                'effective_date' => '2025-01-01',
                'expiry_date' => null,
                'status' => 'active',
            ],
            [
                'insurance_provider' => 'FortuneCare',
                'discount_percentage' => 15.00,
                'coverage_type' => 'both',
                'effective_date' => '2025-01-01',
                'expiry_date' => null,
                'status' => 'active',
            ],
            [
                'insurance_provider' => 'Kaiser',
                'discount_percentage' => 20.00,
                'coverage_type' => 'both',
                'effective_date' => '2025-01-01',
                'expiry_date' => null,
                'status' => 'active',
            ],
            [
                'insurance_provider' => 'Pacific Cross',
                'discount_percentage' => 18.00,
                'coverage_type' => 'both',
                'effective_date' => '2025-01-01',
                'expiry_date' => null,
                'status' => 'active',
            ],
            [
                'insurance_provider' => 'Asalus Health Care (Healthway / FamilyDOC)',
                'discount_percentage' => 16.00,
                'coverage_type' => 'both',
                'effective_date' => '2025-01-01',
                'expiry_date' => null,
                'status' => 'active',
            ],
        ];

        foreach ($discountRates as $rate) {
            $db->table('insurance_discount_rates')->insert($rate);
        }

        echo "Insurance discount rates seeded successfully.\n";
    }
}
