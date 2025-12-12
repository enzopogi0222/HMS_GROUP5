<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdatePrescriptionStatusEnum extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('prescriptions')) {
            // Check current status column type
            $columns = $db->query("SHOW COLUMNS FROM prescriptions WHERE Field = 'status'")->getRowArray();
            
            if ($columns) {
                $currentType = $columns['Type'] ?? '';
                
                // Check if it's the old ENUM with 'active' and 'completed'
                if (strpos($currentType, "'active'") !== false || strpos($currentType, "'completed'") !== false) {
                    // Update the ENUM to include new status values
                    // First, update any existing 'active' values to 'queued'
                    $db->query("UPDATE prescriptions SET status = 'queued' WHERE status = 'active'");
                    
                    // Update any existing 'completed' values to 'dispensed'
                    $db->query("UPDATE prescriptions SET status = 'dispensed' WHERE status = 'completed'");
                    
                    // Now alter the column to use the new ENUM values
                    $db->query("ALTER TABLE prescriptions MODIFY COLUMN status ENUM('queued', 'verifying', 'ready', 'dispensed', 'cancelled') DEFAULT 'queued'");
                } elseif (strpos($currentType, "'queued'") === false) {
                    // If it doesn't have the new values, update it anyway
                    $db->query("ALTER TABLE prescriptions MODIFY COLUMN status ENUM('queued', 'verifying', 'ready', 'dispensed', 'cancelled') DEFAULT 'queued'");
                }
            }
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('prescriptions')) {
            // Revert to old ENUM values
            // First update values back
            $db->query("UPDATE prescriptions SET status = 'active' WHERE status = 'queued'");
            $db->query("UPDATE prescriptions SET status = 'completed' WHERE status = 'dispensed'");
            
            // Revert the column
            $db->query("ALTER TABLE prescriptions MODIFY COLUMN status ENUM('active', 'completed', 'cancelled') DEFAULT 'active'");
        }
    }
}

