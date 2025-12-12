<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateAppointmentsPatientFK extends Migration
{
    public function up()
    {
        // 1) Drop old FK to `patient`
        $this->db->query(
            'ALTER TABLE `appointments` 
             DROP FOREIGN KEY `appointments_patient_id_foreign`'
        );

        // 2) Add new FK to `patients`
        $this->db->query(
            'ALTER TABLE `appointments`
             ADD CONSTRAINT `appointments_patient_id_foreign`
             FOREIGN KEY (`patient_id`)
             REFERENCES `patients`(`patient_id`)
             ON DELETE CASCADE
             ON UPDATE CASCADE'
        );
    }

    public function down()
    {
        // Reverse: point back to legacy `patient` table (if you ever roll back)
        $this->db->query(
            'ALTER TABLE `appointments` 
             DROP FOREIGN KEY `appointments_patient_id_foreign`'
        );

        $this->db->query(
            'ALTER TABLE `appointments`
             ADD CONSTRAINT `appointments_patient_id_foreign`
             FOREIGN KEY (`patient_id`)
             REFERENCES `patient`(`patient_id`)
             ON DELETE CASCADE
             ON UPDATE CASCADE'
        );
    }
}