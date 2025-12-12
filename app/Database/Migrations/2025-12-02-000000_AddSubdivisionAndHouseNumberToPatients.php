<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSubdivisionAndHouseNumberToPatients extends Migration
{
    public function up()
    {
        // Determine which table exists
        $db = \Config\Database::connect();
        $tableName = $db->tableExists('patients') ? 'patients' : ($db->tableExists('patient') ? 'patient' : null);
        
        if (!$tableName) {
            log_message('warning', 'Neither "patients" nor "patient" table exists. Skipping migration.');
            return;
        }

        // Get existing columns to determine where to place new columns
        $existingColumns = [];
        try {
            $fields = $db->getFieldData($tableName);
            foreach ($fields as $field) {
                $existingColumns[] = $field->name;
            }
        } catch (\Throwable $e) {
            log_message('error', 'Error getting field data: ' . $e->getMessage());
            return;
        }

        // Determine the 'after' column - use the last address-related column that exists
        $afterColumn = 'address'; // Default to after address
        if (in_array('zip_code', $existingColumns)) {
            $afterColumn = 'zip_code';
        } elseif (in_array('barangay', $existingColumns)) {
            $afterColumn = 'barangay';
        } elseif (in_array('city', $existingColumns)) {
            $afterColumn = 'city';
        } elseif (in_array('province', $existingColumns)) {
            $afterColumn = 'province';
        }

        // Check if columns already exist and add missing ones
        $fields = [];
        
        // Add all missing address columns
        $addressFields = [
            'province' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'city' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'barangay' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'subdivision' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'house_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'zip_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
        ];

        // Add fields in order, placing each after the previous one
        $previousField = $afterColumn;
        foreach ($addressFields as $fieldName => $fieldDef) {
            if (!in_array($fieldName, $existingColumns)) {
                $fieldDef['after'] = $previousField;
                $fields[$fieldName] = $fieldDef;
                $previousField = $fieldName;
            } else {
                // If field exists, use it as the anchor for next field
                $previousField = $fieldName;
            }
        }

        if (!empty($fields)) {
            try {
                $this->forge->addColumn($tableName, $fields);
                log_message('info', 'Added address columns to ' . $tableName . ' table: ' . implode(', ', array_keys($fields)));
            } catch (\Throwable $e) {
                log_message('error', 'Error adding columns to ' . $tableName . ': ' . $e->getMessage());
                throw $e;
            }
        } else {
            log_message('info', 'All address columns already exist in ' . $tableName . ' table');
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();
        $tableName = $db->tableExists('patients') ? 'patients' : ($db->tableExists('patient') ? 'patient' : null);
        
        if ($tableName) {
            $columnsToDrop = [];
            
            if ($db->fieldExists('subdivision', $tableName)) {
                $columnsToDrop[] = 'subdivision';
            }
            
            if ($db->fieldExists('house_number', $tableName)) {
                $columnsToDrop[] = 'house_number';
            }
            
            if (!empty($columnsToDrop)) {
                $this->forge->dropColumn($tableName, $columnsToDrop);
            }
        }
    }
}

