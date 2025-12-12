<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPrimaryDoctorToPatient extends Migration
{
    public function up()
    {
        // Add the linking column and index first
        $fields = [
            'primary_doctor_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'patient_type',
            ],
        ];
        $this->forge->addColumn('patient', $fields);

        // Add an index on the new column
        $this->db->query('CREATE INDEX idx_patient_primary_doctor_id ON patient (primary_doctor_id)');

        // Add the foreign key constraint (use raw SQL to ensure compatibility when altering existing table)
        $this->db->query('ALTER TABLE patient
            ADD CONSTRAINT fk_patient_doctor
            FOREIGN KEY (primary_doctor_id) REFERENCES doctor(doctor_id)
            ON UPDATE CASCADE ON DELETE SET NULL');
    }

    public function down()
    {
        // Drop the foreign key first if it exists, then the index and column
        // Try-catch to be resilient if constraint/index naming differs in some environments
        try { $this->db->query('ALTER TABLE patient DROP FOREIGN KEY fk_patient_doctor'); } catch (\Throwable $e) {}
        try { $this->db->query('DROP INDEX idx_patient_primary_doctor_id ON patient'); } catch (\Throwable $e) {}
        $this->forge->dropColumn('patient', 'primary_doctor_id');
    }
}
