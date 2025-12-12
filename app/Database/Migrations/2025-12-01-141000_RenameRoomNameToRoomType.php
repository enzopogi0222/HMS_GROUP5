<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RenameRoomNameToRoomType extends Migration
{
    protected string $table = 'room';

    public function up()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists($this->table)) {
            return;
        }

        if ($db->fieldExists('room_name', $this->table) && ! $db->fieldExists('room_type', $this->table)) {
            $db->query(
                'ALTER TABLE ' . $db->escapeString($this->table) .
                " CHANGE `room_name` `room_type` VARCHAR(100) NULL"
            );
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists($this->table)) {
            return;
        }

        if ($db->fieldExists('room_type', $this->table) && ! $db->fieldExists('room_name', $this->table)) {
            $db->query(
                'ALTER TABLE ' . $db->escapeString($this->table) .
                " CHANGE `room_type` `room_name` VARCHAR(100) NULL"
            );
        }
    }
}
