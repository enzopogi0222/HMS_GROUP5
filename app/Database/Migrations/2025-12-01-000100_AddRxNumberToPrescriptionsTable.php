<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRxNumberToPrescriptionsTable extends Migration
{
    public function up()
    {
        // Only proceed if prescriptions table exists
        if (!$this->db->tableExists('prescriptions')) {
            return;
        }

        // Add rx_number column if it does not exist yet
        if (!$this->db->fieldExists('rx_number', 'prescriptions')) {
            $fields = [
                'rx_number' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'unique'     => true,
                    'after'      => 'id',
                ],
            ];

            $this->forge->addColumn('prescriptions', $fields);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('prescriptions') && $this->db->fieldExists('rx_number', 'prescriptions')) {
            $this->forge->dropColumn('prescriptions', 'rx_number');
        }
    }
}
