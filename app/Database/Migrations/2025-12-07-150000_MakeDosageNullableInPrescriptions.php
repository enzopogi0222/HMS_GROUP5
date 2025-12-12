<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MakeDosageNullableInPrescriptions extends Migration
{
    public function up()
    {
        // Make dosage nullable in prescriptions table
        if ($this->db->fieldExists('dosage', 'prescriptions')) {
            $fields = [
                'dosage' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
            ];
            $this->forge->modifyColumn('prescriptions', $fields);
        }

        // Make dosage nullable in prescription_items table
        if ($this->db->tableExists('prescription_items') && $this->db->fieldExists('dosage', 'prescription_items')) {
            $fields = [
                'dosage' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
            ];
            $this->forge->modifyColumn('prescription_items', $fields);
        }
    }

    public function down()
    {
        // Revert dosage to NOT NULL in prescriptions table
        if ($this->db->fieldExists('dosage', 'prescriptions')) {
            $fields = [
                'dosage' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => false,
                ],
            ];
            $this->forge->modifyColumn('prescriptions', $fields);
        }

        // Revert dosage to NOT NULL in prescription_items table
        if ($this->db->tableExists('prescription_items') && $this->db->fieldExists('dosage', 'prescription_items')) {
            $fields = [
                'dosage' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => false,
                ],
            ];
            $this->forge->modifyColumn('prescription_items', $fields);
        }
    }
}

