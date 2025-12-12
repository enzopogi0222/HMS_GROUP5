<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterDepartmentTypeEnumAddSupport extends Migration
{
    protected string $table = 'department';

    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists($this->table)) {
            return;
        }

        // Expand ENUM to include 'Support'
        $db->query(
            "ALTER TABLE `{$this->table}` " .
            "MODIFY `type` ENUM('Clinical','Administrative','Emergency','Diagnostic','Support') NOT NULL DEFAULT 'Clinical'"
        );
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists($this->table)) {
            return;
        }

        // Revert ENUM back to original set (without 'Support')
        $db->query(
            "ALTER TABLE `{$this->table}` " .
            "MODIFY `type` ENUM('Clinical','Administrative','Emergency','Diagnostic') NOT NULL DEFAULT 'Clinical'"
        );
    }
}
