<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropLegacyColumnsFromRoomAssignment extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('room_assignment')) {
            return;
        }

        // Drop index on assigned_by first (if it exists)
        try {
            $db->query('ALTER TABLE room_assignment DROP INDEX assigned_by');
        } catch (\Throwable $e) {
            // ignore if index does not exist
        }

        $columnsToDrop = [
            'assigned_by',
            'total_hours',
            'room_rate_at_time',
            'bed_rate_at_time',
            'discount',
            'billing_amount',
        ];

        foreach ($columnsToDrop as $col) {
            if ($db->fieldExists($col, 'room_assignment')) {
                try {
                    $this->forge->dropColumn('room_assignment', $col);
                } catch (\Throwable $e) {
                    // ignore if cannot drop (e.g. already dropped)
                }
            }
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('room_assignment')) {
            return;
        }

        $columnsToAdd = [];

        if (! $db->fieldExists('assigned_by', 'room_assignment')) {
            $columnsToAdd['assigned_by'] = [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ];
        }

        if (! $db->fieldExists('total_hours', 'room_assignment')) {
            $columnsToAdd['total_hours'] = [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
            ];
        }

        if (! $db->fieldExists('room_rate_at_time', 'room_assignment')) {
            $columnsToAdd['room_rate_at_time'] = [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => false,
                'default'    => 0.00,
            ];
        }

        if (! $db->fieldExists('bed_rate_at_time', 'room_assignment')) {
            $columnsToAdd['bed_rate_at_time'] = [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
            ];
        }

        if (! $db->fieldExists('discount', 'room_assignment')) {
            $columnsToAdd['discount'] = [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
            ];
        }

        if (! $db->fieldExists('billing_amount', 'room_assignment')) {
            $columnsToAdd['billing_amount'] = [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => false,
                'default'    => 0.00,
            ];
        }

        if (! empty($columnsToAdd)) {
            $this->forge->addColumn('room_assignment', $columnsToAdd);
        }

        // Re-add index on assigned_by if column exists
        if ($db->fieldExists('assigned_by', 'room_assignment')) {
            try {
                $db->query('ALTER TABLE room_assignment ADD INDEX assigned_by (assigned_by)');
            } catch (\Throwable $e) {
                // ignore if it already exists
            }
        }
    }
}
