<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class FixPatientStatus extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'fix:patient-status';
    protected $description = 'Fix patient status and date_registered for all patients';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        
        // Resolve patient table name
        $patientTable = $db->tableExists('patient') ? 'patient' : 'patients';
        
        if (!$db->tableExists($patientTable)) {
            CLI::error("Patient table '{$patientTable}' does not exist.");
            return;
        }

        CLI::write("Fixing patient data...", 'yellow');
        CLI::newLine();

        // Fix Status
        $this->fixStatus($db, $patientTable);
        
        // Fix Date Registered
        $this->fixDateRegistered($db, $patientTable);
        
        CLI::newLine();
        CLI::write("✓ Patient data fix completed!", 'green');
    }

    private function fixStatus($db, $patientTable)
    {
        // Check if status column exists
        if (!$db->fieldExists('status', $patientTable)) {
            CLI::write("Status column does not exist. Skipping status fix.", 'yellow');
            return;
        }

        CLI::write("Fixing patient statuses...", 'yellow');

        // Count patients with NULL, empty, or Inactive status
        $inactiveCount = $db->table($patientTable)
            ->where('status IS NULL', null, false)
            ->orWhere('status', '')
            ->orWhere('status', 'Inactive')
            ->countAllResults(false);

        if ($inactiveCount === 0) {
            CLI::write("  ✓ All patients already have 'Active' status.", 'green');
            return;
        }

        CLI::write("  Found {$inactiveCount} patient(s) with NULL, empty, or Inactive status.", 'yellow');

        // Update all patients to Active
        $updated = $db->table($patientTable)
            ->where('status IS NULL', null, false)
            ->orWhere('status', '')
            ->orWhere('status', 'Inactive')
            ->update(['status' => 'Active']);

        if ($updated) {
            CLI::write("  ✓ Successfully updated {$updated} patient(s) to 'Active' status.", 'green');
        } else {
            CLI::error("  ✗ Failed to update patient statuses.");
        }

        // Verify
        $remaining = $db->table($patientTable)
            ->where('status IS NULL', null, false)
            ->orWhere('status', '')
            ->orWhere('status', 'Inactive')
            ->countAllResults(false);

        if ($remaining === 0) {
            CLI::write("  ✓ All patients now have 'Active' status.", 'green');
        } else {
            CLI::error("  ✗ Warning: {$remaining} patient(s) still have non-Active status.");
        }
    }

    private function fixDateRegistered($db, $patientTable)
    {
        // Check if date_registered column exists
        if (!$db->fieldExists('date_registered', $patientTable)) {
            CLI::write("date_registered column does not exist. Skipping date_registered fix.", 'yellow');
            return;
        }

        CLI::write("Fixing date_registered...", 'yellow');

        // Count patients with NULL or empty date_registered
        $nullDateCount = $db->table($patientTable)
            ->where('date_registered IS NULL', null, false)
            ->orWhere('date_registered', '')
            ->orWhere('date_registered', '0000-00-00')
            ->countAllResults(false);

        if ($nullDateCount === 0) {
            CLI::write("  ✓ All patients already have date_registered.", 'green');
            return;
        }

        CLI::write("  Found {$nullDateCount} patient(s) with NULL or empty date_registered.", 'yellow');

        // Update date_registered: use created_at if available, otherwise use current date
        try {
            // Check if created_at column exists
            $hasCreatedAt = $db->fieldExists('created_at', $patientTable);
            
            if ($hasCreatedAt) {
                // Use created_at date if available
                $db->query("UPDATE {$patientTable} 
                    SET date_registered = DATE(COALESCE(created_at, NOW())) 
                    WHERE date_registered IS NULL 
                    OR date_registered = '' 
                    OR date_registered = '0000-00-00'");
            } else {
                // Use current date
                $db->table($patientTable)
                    ->where('date_registered IS NULL', null, false)
                    ->orWhere('date_registered', '')
                    ->orWhere('date_registered', '0000-00-00')
                    ->update(['date_registered' => date('Y-m-d')]);
            }

            CLI::write("  ✓ Successfully updated {$nullDateCount} patient(s) with date_registered.", 'green');
        } catch (\Throwable $e) {
            CLI::error("  ✗ Failed to update date_registered: " . $e->getMessage());
        }

        // Verify
        $remaining = $db->table($patientTable)
            ->where('date_registered IS NULL', null, false)
            ->orWhere('date_registered', '')
            ->orWhere('date_registered', '0000-00-00')
            ->countAllResults(false);

        if ($remaining === 0) {
            CLI::write("  ✓ All patients now have date_registered.", 'green');
        } else {
            CLI::error("  ✗ Warning: {$remaining} patient(s) still have invalid date_registered.");
        }
    }
}

