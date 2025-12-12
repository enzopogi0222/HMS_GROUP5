<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRoomAssignmentIdToBillingItems extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('billing_items') && !$this->db->fieldExists('room_assignment_id', 'billing_items')) {
            $fields = [
                'room_assignment_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                    'unsigned'   => true,
                ],
            ];

            $this->forge->addColumn('billing_items', $fields);

            try {
                $this->db->query('CREATE INDEX IF NOT EXISTS idx_billing_items_room_assignment_id ON billing_items (room_assignment_id)');
            } catch (\Throwable $e) {
                // Index creation failure is non-fatal; column existence is the main concern.
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('billing_items') && $this->db->fieldExists('room_assignment_id', 'billing_items')) {
            $this->forge->dropColumn('billing_items', 'room_assignment_id');
        }
    }
}
