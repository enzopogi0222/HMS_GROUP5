<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInpatientRoomAssignmentsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'room_assignment_id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'admission_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
            'room_type' => [
                'type'       => 'ENUM',
                'constraint' => ['Ward', 'Semi-Private', 'Private', 'Isolation', 'ICU'],
                'null'       => true,
            ],
            'floor_number' => [
                'type' => 'INT',
                'null' => true,
            ],
            'room_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'bed_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
            ],
            'daily_rate' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
            ],
            'assigned_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => true,
                'default' => null,
            ],
        ]);

        $this->forge->addKey('room_assignment_id', true);
        $this->forge->createTable('inpatient_room_assignments');

        $db = \Config\Database::connect();
        $db->query('ALTER TABLE inpatient_room_assignments ENGINE=InnoDB');
        $db->query('ALTER TABLE inpatient_room_assignments MODIFY assigned_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');

        if ($db->tableExists('inpatient_admissions') && $db->fieldExists('admission_id', 'inpatient_admissions')) {
            try {
                $db->query('ALTER TABLE inpatient_room_assignments ADD CONSTRAINT fk_inpatient_room_assign_admission FOREIGN KEY (admission_id) REFERENCES inpatient_admissions(admission_id) ON DELETE CASCADE ON UPDATE CASCADE');
            } catch (\Throwable $e) {
                // ignore malformed constraints
            }
        }
    }

    public function down()
    {
        $this->forge->dropTable('inpatient_room_assignments');
    }
}
