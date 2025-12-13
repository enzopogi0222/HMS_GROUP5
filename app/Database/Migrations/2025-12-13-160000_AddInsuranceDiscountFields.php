<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddInsuranceDiscountFields extends Migration
{
    public function up()
    {
        // Add discount fields to billing_items table
        if ($this->db->tableExists('billing_items')) {
            $fields = [
                'insurance_discount_percentage' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'null'       => false,
                    'default'    => '0.00',
                    'after'      => 'line_total',
                ],
                'insurance_discount_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => false,
                    'default'    => '0.00',
                    'after'      => 'insurance_discount_percentage',
                ],
                'final_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => false,
                    'default'    => '0.00',
                    'after'      => 'insurance_discount_amount',
                ],
            ];

            $this->forge->addColumn('billing_items', $fields);
        }

        // Create insurance discount rates table
        if (!$this->db->tableExists('insurance_discount_rates')) {
            $this->forge->addField([
                'discount_rate_id' => [
                    'type'           => 'INT',
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'insurance_provider' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => false,
                ],
                'discount_percentage' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'null'       => false,
                    'default'    => '0.00',
                ],
                'coverage_type' => [
                    'type'       => 'ENUM',
                    'constraint' => ['inpatient', 'outpatient', 'both'],
                    'null'       => false,
                    'default'    => 'both',
                ],
                'effective_date' => [
                    'type' => 'DATE',
                    'null' => false,
                    'default' => '2025-01-01',
                ],
                'expiry_date' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'status' => [
                    'type'       => 'ENUM',
                    'constraint' => ['active', 'inactive'],
                    'null'       => false,
                    'default'    => 'active',
                ],
                'created_at' => [
                    'type'    => 'TIMESTAMP',
                    'null'    => true,
                    'default' => null,
                ],
                'updated_at' => [
                    'type'    => 'TIMESTAMP',
                    'null'    => true,
                    'default' => null,
                ],
            ]);

            $this->forge->addKey('discount_rate_id', true);
            $this->forge->addKey('insurance_provider');
            $this->forge->addKey('status');
            $this->forge->createTable('insurance_discount_rates');

            // Set engine and timestamps
            $db = \Config\Database::connect();
            $db->query('ALTER TABLE insurance_discount_rates ENGINE=InnoDB');
            $db->query('ALTER TABLE insurance_discount_rates MODIFY created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
            $db->query('ALTER TABLE insurance_discount_rates MODIFY updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        }
    }

    public function down()
    {
        // Remove discount fields from billing_items table
        if ($this->db->tableExists('billing_items')) {
            $fieldsToRemove = ['insurance_discount_percentage', 'insurance_discount_amount', 'final_amount'];
            
            foreach ($fieldsToRemove as $field) {
                if ($this->db->fieldExists($field, 'billing_items')) {
                    $this->forge->dropColumn('billing_items', $field);
                }
            }
        }

        // Drop insurance discount rates table
        if ($this->db->tableExists('insurance_discount_rates')) {
            $this->forge->dropTable('insurance_discount_rates');
        }
    }
}
