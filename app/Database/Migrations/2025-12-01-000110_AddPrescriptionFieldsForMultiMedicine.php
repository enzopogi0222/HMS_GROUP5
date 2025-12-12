<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPrescriptionFieldsForMultiMedicine extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('prescriptions')) {
            return;
        }

        // Add columns used by the new PrescriptionService if they are missing
        $fields = [];

        if (!$this->db->fieldExists('patient_name', 'prescriptions')) {
            $fields['patient_name'] = [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'patient_id',
            ];
        }

        if (!$this->db->fieldExists('days_supply', 'prescriptions')) {
            $fields['days_supply'] = [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'after'      => 'frequency',
            ];
        }

        if (!$this->db->fieldExists('quantity', 'prescriptions')) {
            $fields['quantity'] = [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'after'      => 'days_supply',
            ];
        }

        if (!$this->db->fieldExists('prescriber', 'prescriptions')) {
            $fields['prescriber'] = [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'quantity',
            ];
        }

        if (!$this->db->fieldExists('priority', 'prescriptions')) {
            $fields['priority'] = [
                'type'       => 'ENUM',
                'constraint' => ['routine', 'priority', 'stat'],
                'default'    => 'routine',
                'after'      => 'prescriber',
            ];
        }

        if (!$this->db->fieldExists('created_by', 'prescriptions')) {
            $fields['created_by'] = [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'status',
            ];
        }

        if (!$this->db->fieldExists('dispensed_at', 'prescriptions')) {
            $fields['dispensed_at'] = [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'updated_at',
            ];
        }

        if (!$this->db->fieldExists('dispensed_by', 'prescriptions')) {
            $fields['dispensed_by'] = [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'dispensed_at',
            ];
        }

        if (!$this->db->fieldExists('dispensed_quantity', 'prescriptions')) {
            $fields['dispensed_quantity'] = [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'after'      => 'dispensed_by',
            ];
        }

        if (!empty($fields)) {
            $this->forge->addColumn('prescriptions', $fields);
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('prescriptions')) {
            return;
        }

        $drop = [];
        foreach (['patient_name', 'days_supply', 'quantity', 'prescriber', 'priority', 'created_by', 'dispensed_at', 'dispensed_by', 'dispensed_quantity'] as $col) {
            if ($this->db->fieldExists($col, 'prescriptions')) {
                $drop[] = $col;
            }
        }

        if (!empty($drop)) {
            $this->forge->dropColumn('prescriptions', $drop);
        }
    }
}
