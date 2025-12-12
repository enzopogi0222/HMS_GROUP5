<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLabOrderIdToBillingItems extends Migration
{
    public function up()
    {
        // Add lab_order_id column to billing_items if both table and column are appropriate
        if ($this->db->tableExists('billing_items') && !$this->db->fieldExists('lab_order_id', 'billing_items')) {
            $fields = [
                'lab_order_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                    'unsigned'   => true,
                ],
            ];

            $this->forge->addColumn('billing_items', $fields);

            // Optionally, create an index for faster lookups
            // Use try/catch to avoid hard failures on some drivers
            try {
                $this->db->query('CREATE INDEX IF NOT EXISTS idx_billing_items_lab_order_id ON billing_items (lab_order_id)');
            } catch (\Throwable $e) {
                // Ignore index creation failure, column is the important part
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('billing_items') && $this->db->fieldExists('lab_order_id', 'billing_items')) {
            $this->forge->dropColumn('billing_items', 'lab_order_id');
        }
    }
}
