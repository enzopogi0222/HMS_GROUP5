<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class LegacyInpatientAdmissionsNoop extends Migration
{
    public function up()
    {
        // No-op: superseded by later migration 2025-11-28-084800_RecreateInpatientAdmissionsTable
        // The inpatient_admissions table is now created and managed by the newer migration.
    }

    public function down()
    {
        $this->forge->dropTable('inpatient_admissions');
    }
}
