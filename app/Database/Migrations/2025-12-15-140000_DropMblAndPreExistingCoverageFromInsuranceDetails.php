<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropMblAndPreExistingCoverageFromInsuranceDetails extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('insurance_details')) {
            return;
        }

        $fields = $this->db->getFieldData('insurance_details');
        $existingColumns = array_map(static fn($field) => $field->name ?? null, $fields);
        $existingColumns = array_filter($existingColumns);

        $toDrop = [];

        if (in_array('mbl', $existingColumns, true)) {
            $toDrop[] = 'mbl';
        }

        if (in_array('pre_existing_coverage', $existingColumns, true)) {
            $toDrop[] = 'pre_existing_coverage';
        }

        if (empty($toDrop)) {
            return;
        }

        foreach ($toDrop as $col) {
            try {
                $this->forge->dropColumn('insurance_details', $col);
            } catch (\Throwable $e) {
                // Some drivers may fail if the column doesn't exist or constraints differ; ignore safely.
            }
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('insurance_details')) {
            return;
        }

        $fields = $this->db->getFieldData('insurance_details');
        $existingColumns = array_map(static fn($field) => $field->name ?? null, $fields);
        $existingColumns = array_filter($existingColumns);

        $add = [];

        if (! in_array('mbl', $existingColumns, true)) {
            $add['mbl'] = [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
            ];
        }

        if (! in_array('pre_existing_coverage', $existingColumns, true)) {
            $add['pre_existing_coverage'] = [
                'type' => 'TEXT',
                'null' => true,
            ];
        }

        if (empty($add)) {
            return;
        }

        $this->forge->addColumn('insurance_details', $add);
    }
}
