<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MedicationSeeder extends Seeder
{
    public function run()
    {
        $medications = [
            // Pain Relief & Anti-inflammatory
            [
                'equipment_name' => 'Paracetamol 500mg',
                'category' => 'Medications',
                'quantity' => 500,
                'status' => 'Stock In',
                'location' => 'Pharmacy - Shelf A1',
                'batch_number' => 'PAR-2024-001',
                'expiry_date' => date('Y-m-d', strtotime('+2 years')),
                'serial_number' => null,
                'price' => 5.50,
                'remarks' => 'Common pain reliever and fever reducer',
            ],
            [
                'equipment_name' => 'Ibuprofen 400mg',
                'category' => 'Medications',
                'quantity' => 300,
                'status' => 'Stock In',
                'location' => 'Pharmacy - Shelf A2',
                'batch_number' => 'IBU-2024-015',
                'expiry_date' => date('Y-m-d', strtotime('+18 months')),
                'serial_number' => null,
                'price' => 8.75,
                'remarks' => 'Anti-inflammatory and pain reliever',
            ],
            [
                'equipment_name' => 'Aspirin 100mg',
                'category' => 'Medications',
                'quantity' => 400,
                'status' => 'Stock In',
                'location' => 'Pharmacy - Shelf A3',
                'batch_number' => 'ASP-2024-008',
                'expiry_date' => date('Y-m-d', strtotime('+2 years')),
                'serial_number' => null,
                'price' => 4.25,
                'remarks' => 'Cardiovascular protection and pain relief',
            ],

            // Antibiotics
            [
                'equipment_name' => 'Amoxicillin 500mg',
                'category' => 'Medications',
                'quantity' => 250,
                'status' => 'Stock In',
                'location' => 'Pharmacy - Shelf B1',
                'batch_number' => 'AMX-2024-022',
                'expiry_date' => date('Y-m-d', strtotime('+2 years')),
                'serial_number' => null,
                'price' => 45.00,
                'remarks' => 'Broad-spectrum antibiotic',
            ],
            [
                'equipment_name' => 'Azithromycin 500mg',
                'category' => 'Medications',
                'quantity' => 150,
                'status' => 'Stock In',
                'location' => 'Pharmacy - Shelf B2',
                'batch_number' => 'AZI-2024-011',
                'expiry_date' => date('Y-m-d', strtotime('+2 years')),
                'serial_number' => null,
                'price' => 125.00,
                'remarks' => 'Macrolide antibiotic',
            ],
            [
                'equipment_name' => 'Ciprofloxacin 500mg',
                'category' => 'Medications',
                'quantity' => 200,
                'status' => 'Stock In',
                'location' => 'Pharmacy - Shelf B3',
                'batch_number' => 'CIP-2024-009',
                'expiry_date' => date('Y-m-d', strtotime('+2 years')),
                'serial_number' => null,
                'price' => 85.50,
                'remarks' => 'Fluoroquinolone antibiotic',
            ],

            // Cardiovascular
            [
                'equipment_name' => 'Atenolol 50mg',
                'category' => 'Medications',
                'quantity' => 300,
                'status' => 'Stock In',
                'location' => 'Pharmacy - Shelf C1',
                'batch_number' => 'ATE-2024-014',
                'expiry_date' => date('Y-m-d', strtotime('+2 years')),
                'serial_number' => null,
                'price' => 35.75,
                'remarks' => 'Beta-blocker for hypertension',
            ],
            [
                'equipment_name' => 'Lisinopril 10mg',
                'category' => 'Medications',
                'quantity' => 350,
                'status' => 'Stock In',
                'location' => 'Pharmacy - Shelf C2',
                'batch_number' => 'LIS-2024-018',
                'expiry_date' => date('Y-m-d', strtotime('+2 years')),
                'serial_number' => null,
                'price' => 42.00,
                'remarks' => 'ACE inhibitor for blood pressure',
            ],
            [
                'equipment_name' => 'Amlodipine 5mg',
                'category' => 'Medications',
                'quantity' => 280,
                'status' => 'Stock In',
                'location' => 'Pharmacy - Shelf C3',
                'batch_number' => 'AML-2024-012',
                'expiry_date' => date('Y-m-d', strtotime('+2 years')),
                'serial_number' => null,
                'price' => 38.50,
                'remarks' => 'Calcium channel blocker',
            ],

            // Gastrointestinal
            [
                'equipment_name' => 'Omeprazole 20mg',
                'category' => 'Medications',
                'quantity' => 400,
                'status' => 'Stock In',
                'location' => 'Pharmacy - Shelf D1',
                'batch_number' => 'OME-2024-020',
                'expiry_date' => date('Y-m-d', strtotime('+2 years')),
                'serial_number' => null,
                'price' => 55.00,
                'remarks' => 'Proton pump inhibitor for acid reflux',
            ],
            [
                'equipment_name' => 'Metoclopramide 10mg',
                'category' => 'Medications',
                'quantity' => 250,
                'status' => 'Stock In',
                'location' => 'Pharmacy - Shelf D2',
                'batch_number' => 'MET-2024-007',
                'expiry_date' => date('Y-m-d', strtotime('+2 years')),
                'serial_number' => null,
                'price' => 28.00,
                'remarks' => 'Anti-nausea medication',
            ],
            [
                'equipment_name' => 'Loperamide 2mg',
                'category' => 'Medications',
                'quantity' => 500,
                'status' => 'Stock In',
                'location' => 'Pharmacy - Shelf D3',
                'batch_number' => 'LOP-2024-016',
                'expiry_date' => date('Y-m-d', strtotime('+2 years')),
                'serial_number' => null,
                'price' => 12.50,
                'remarks' => 'Anti-diarrheal medication',
            ],

            // Respiratory
            [
                'equipment_name' => 'Salbutamol Inhaler 100mcg',
                'category' => 'Medications',
                'quantity' => 150,
                'status' => 'Stock In',
                'location' => 'Pharmacy - Shelf E1',
                'batch_number' => 'SAL-2024-013',
                'expiry_date' => date('Y-m-d', strtotime('+18 months')),
                'serial_number' => null,
                'price' => 185.00,
                'remarks' => 'Bronchodilator for asthma',
            ],
            [
                'equipment_name' => 'Montelukast 10mg',
                'category' => 'Medications',
                'quantity' => 200,
                'status' => 'Stock In',
                'location' => 'Pharmacy - Shelf E2',
                'batch_number' => 'MON-2024-019',
                'expiry_date' => date('Y-m-d', strtotime('+2 years')),
                'serial_number' => null,
                'price' => 95.00,
                'remarks' => 'Leukotriene receptor antagonist',
            ],

            // Antidiabetic
            [
                'equipment_name' => 'Metformin 500mg',
                'category' => 'Medications',
                'quantity' => 600,
                'status' => 'Stock In',
                'location' => 'Pharmacy - Shelf F1',
                'batch_number' => 'MET-2024-021',
                'expiry_date' => date('Y-m-d', strtotime('+2 years')),
                'serial_number' => null,
                'price' => 25.00,
                'remarks' => 'First-line treatment for type 2 diabetes',
            ],
            [
                'equipment_name' => 'Glibenclamide 5mg',
                'category' => 'Medications',
                'quantity' => 300,
                'status' => 'Stock In',
                'location' => 'Pharmacy - Shelf F2',
                'batch_number' => 'GLI-2024-010',
                'expiry_date' => date('Y-m-d', strtotime('+2 years')),
                'serial_number' => null,
                'price' => 32.50,
                'remarks' => 'Sulfonylurea for diabetes',
            ],

            // Antihistamines
            [
                'equipment_name' => 'Cetirizine 10mg',
                'category' => 'Medications',
                'quantity' => 450,
                'status' => 'Stock In',
                'location' => 'Pharmacy - Shelf G1',
                'batch_number' => 'CET-2024-017',
                'expiry_date' => date('Y-m-d', strtotime('+2 years')),
                'serial_number' => null,
                'price' => 15.75,
                'remarks' => 'Antihistamine for allergies',
            ],
            [
                'equipment_name' => 'Loratadine 10mg',
                'category' => 'Medications',
                'quantity' => 400,
                'status' => 'Stock In',
                'location' => 'Pharmacy - Shelf G2',
                'batch_number' => 'LOR-2024-006',
                'expiry_date' => date('Y-m-d', strtotime('+2 years')),
                'serial_number' => null,
                'price' => 18.00,
                'remarks' => 'Non-drowsy antihistamine',
            ],

            // Antifungal
            [
                'equipment_name' => 'Fluconazole 150mg',
                'category' => 'Medications',
                'quantity' => 180,
                'status' => 'Stock In',
                'location' => 'Pharmacy - Shelf H1',
                'batch_number' => 'FLU-2024-005',
                'expiry_date' => date('Y-m-d', strtotime('+2 years')),
                'serial_number' => null,
                'price' => 75.00,
                'remarks' => 'Antifungal medication',
            ],
            [
                'equipment_name' => 'Clotrimazole Cream 1%',
                'category' => 'Medications',
                'quantity' => 120,
                'status' => 'Stock In',
                'location' => 'Pharmacy - Shelf H2',
                'batch_number' => 'CLO-2024-004',
                'expiry_date' => date('Y-m-d', strtotime('+18 months')),
                'serial_number' => null,
                'price' => 45.50,
                'remarks' => 'Topical antifungal cream',
            ],

            // Vitamins & Supplements
            [
                'equipment_name' => 'Vitamin D3 1000IU',
                'category' => 'Medications',
                'quantity' => 500,
                'status' => 'Stock In',
                'location' => 'Pharmacy - Shelf I1',
                'batch_number' => 'VITD-2024-003',
                'expiry_date' => date('Y-m-d', strtotime('+2 years')),
                'serial_number' => null,
                'price' => 22.00,
                'remarks' => 'Vitamin D supplement',
            ],
            [
                'equipment_name' => 'Calcium Carbonate 500mg',
                'category' => 'Medications',
                'quantity' => 600,
                'status' => 'Stock In',
                'location' => 'Pharmacy - Shelf I2',
                'batch_number' => 'CAL-2024-002',
                'expiry_date' => date('Y-m-d', strtotime('+2 years')),
                'serial_number' => null,
                'price' => 18.50,
                'remarks' => 'Calcium supplement',
            ],

            // Antidepressants
            [
                'equipment_name' => 'Sertraline 50mg',
                'category' => 'Medications',
                'quantity' => 200,
                'status' => 'Stock In',
                'location' => 'Pharmacy - Shelf J1',
                'batch_number' => 'SER-2024-023',
                'expiry_date' => date('Y-m-d', strtotime('+2 years')),
                'serial_number' => null,
                'price' => 65.00,
                'remarks' => 'SSRI antidepressant',
            ],

            // Anticoagulants
            [
                'equipment_name' => 'Warfarin 5mg',
                'category' => 'Medications',
                'quantity' => 150,
                'status' => 'Stock In',
                'location' => 'Pharmacy - Shelf K1',
                'batch_number' => 'WAR-2024-024',
                'expiry_date' => date('Y-m-d', strtotime('+2 years')),
                'serial_number' => null,
                'price' => 28.75,
                'remarks' => 'Blood thinner - requires monitoring',
            ],

            // Steroids
            [
                'equipment_name' => 'Prednisolone 5mg',
                'category' => 'Medications',
                'quantity' => 300,
                'status' => 'Stock In',
                'location' => 'Pharmacy - Shelf L1',
                'batch_number' => 'PRE-2024-025',
                'expiry_date' => date('Y-m-d', strtotime('+2 years')),
                'serial_number' => null,
                'price' => 35.00,
                'remarks' => 'Corticosteroid for inflammation',
            ],
        ];

        // Add timestamps to all medications
        foreach ($medications as &$medication) {
            $medication['created_at'] = date('Y-m-d H:i:s');
            $medication['updated_at'] = date('Y-m-d H:i:s');
        }

        // Insert all medications
        $this->db->table('resources')->insertBatch($medications);

        echo "Seeded " . count($medications) . " medications successfully.\n";
    }
}

