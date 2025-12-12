<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterUsersAddRoleForeignKey extends Migration
{
    public function up()
    {
        $this->db->query("
            ALTER TABLE `users`
            ADD CONSTRAINT `fk_users_role`
            FOREIGN KEY (`role_id`) REFERENCES `roles`(`role_id`)
            ON UPDATE CASCADE
            ON DELETE RESTRICT
        ");
    }

    public function down()
    {
        $this->db->query("
            ALTER TABLE `users`
            DROP FOREIGN KEY `fk_users_role`
        ");
    }
}