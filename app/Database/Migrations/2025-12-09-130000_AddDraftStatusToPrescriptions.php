<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDraftStatusToPrescriptions extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        
        // Check if prescriptions table exists
        if (!$db->tableExists('prescriptions')) {
            return;
        }
        
        // Get current status ENUM values
        $fields = $db->getFieldData('prescriptions');
        $statusField = null;
        foreach ($fields as $field) {
            if ($field->name === 'status') {
                $statusField = $field;
                break;
            }
        }
        
        if ($statusField) {
            // Check if 'draft' is already in the ENUM
            $currentType = $statusField->type;
            if (strpos($currentType, 'draft') === false) {
                // Add 'draft' to the ENUM
                // Get current ENUM values from the type string
                preg_match("/ENUM\((.*)\)/", $currentType, $matches);
                if (!empty($matches[1])) {
                    $enumValues = $matches[1];
                    // Add 'draft' to the ENUM
                    $newEnum = $enumValues . ",'draft'";
                    $db->query("ALTER TABLE prescriptions MODIFY COLUMN status ENUM({$newEnum})");
                }
            }
        }
    }

    public function down()
    {
        // Note: Removing 'draft' from ENUM could cause data issues if there are draft prescriptions
        // This is intentionally left as a comment - only remove if you're sure no draft prescriptions exist
        // $db = \Config\Database::connect();
        // $db->query("ALTER TABLE prescriptions MODIFY COLUMN status ENUM('queued', 'verifying', 'ready', 'dispensed', 'cancelled')");
    }
}

