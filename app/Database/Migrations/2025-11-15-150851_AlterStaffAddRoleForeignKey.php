<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterStaffAddRoleForeignKey extends Migration
{
    public function up()
    {
        $this->db->query("
            ALTER TABLE `staff`
            ADD CONSTRAINT `fk_staff_role`
            FOREIGN KEY (`role_id`) REFERENCES `roles`(`role_id`)
            ON UPDATE CASCADE
            ON DELETE RESTRICT
        ");
    }

    public function down()
    {
        $this->db->query("
            ALTER TABLE `staff`
            DROP FOREIGN KEY `fk_staff_role`
        ");
    }
}