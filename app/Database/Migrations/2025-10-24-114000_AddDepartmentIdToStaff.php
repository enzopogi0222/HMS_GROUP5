<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDepartmentIdToStaff extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // Add department_id to staff if not exists
        if (!$db->fieldExists('department_id', 'staff')) {
            $this->forge->addColumn('staff', [
                'department_id' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'null'     => true,
                    'after'    => 'employee_id',
                ],
            ]);
        }

        // Add index if missing
        $indexExists = $db->query("SHOW INDEX FROM staff WHERE Key_name = 'idx_staff_department_id'")->getResult();
        if (empty($indexExists)) {
            $db->query('ALTER TABLE staff ADD INDEX idx_staff_department_id (department_id)');
        }
        // Use try/catch to avoid error if FK exists
        try {
            $db->query('ALTER TABLE staff ADD CONSTRAINT fk_staff_department FOREIGN KEY (department_id) REFERENCES department(department_id) ON UPDATE CASCADE ON DELETE SET NULL');
        } catch (\Throwable $e) {
            // ignore if already exists
        }

        // Backfill department_id from existing department names if the legacy column exists
        if ($db->fieldExists('department', 'staff')) {
            $db->query('UPDATE staff s LEFT JOIN department d ON d.name = s.department SET s.department_id = d.department_id WHERE s.department IS NOT NULL AND s.department != ""');
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();
        // Drop FK if exists
        try {
            $db->query('ALTER TABLE staff DROP FOREIGN KEY fk_staff_department');
        } catch (\Throwable $e) {
            // ignore
        }
        // Drop index if exists
        try {
            $db->query('ALTER TABLE staff DROP INDEX idx_staff_department_id');
        } catch (\Throwable $e) {
            // ignore
        }
        if ($db->fieldExists('department_id', 'staff')) {
            $this->forge->dropColumn('staff', 'department_id');
        }
    }
}
