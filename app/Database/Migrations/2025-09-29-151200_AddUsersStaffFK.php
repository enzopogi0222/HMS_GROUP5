<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUsersStaffFK extends Migration
{
    public function up()
    {
        // Ensure tables exist before adding FK
        // Add foreign key via raw SQL to avoid Forge limitations on ALTER TABLE FKs
        $this->db->query(
            'ALTER TABLE `users` 
             ADD CONSTRAINT `fk_users_staff`
             FOREIGN KEY (`staff_id`) REFERENCES `staff`(`staff_id`)
             ON DELETE CASCADE ON UPDATE CASCADE'
        );
    }

    public function down()
    {
        // Drop the foreign key if it exists
        $this->db->query('ALTER TABLE `users` DROP FOREIGN KEY `fk_users_staff`');
    }
}
