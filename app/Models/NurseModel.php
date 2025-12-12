<?php

namespace App\Models;

use CodeIgniter\Model;

class NurseModel extends Model
{
    protected $table = 'nurses';
    protected $primaryKey = 'nurse_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['staff_id', 'department', 'qualification', 'license_number', 'specialization'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Get nurse by staff ID
    public function getNurseByStaffId($staffId)
    {
        // Check if table exists (could be 'nurse' or 'nurses')
        $tableName = null;
        if ($this->db->tableExists('nurse')) {
            $tableName = 'nurse';
        } elseif ($this->db->tableExists('nurses')) {
            $tableName = 'nurses';
        }
        
        if (!$tableName) {
            return null; // Table doesn't exist
        }
        
        // Temporarily set table name if different
        $originalTable = $this->table;
        if ($tableName !== $this->table) {
            $this->table = $tableName;
        }
        
        try {
            $result = $this->where('staff_id', $staffId)->first();
            $this->table = $originalTable; // Restore original
            return $result;
        } catch (\Exception $e) {
            $this->table = $originalTable; // Restore original
            return null;
        }
    }

    // Get assigned patients for a nurse
    public function getAssignedPatients($nurseId, $limit = 10)
    {
        return $this->db->table('patient_nurse')
            ->select('patients.*, patient_nurse.assigned_date')
            ->join('patients', 'patients.patient_id = patient_nurse.patient_id')
            ->where('patient_nurse.nurse_id', $nurseId)
            ->orderBy('patient_nurse.assigned_date', 'DESC')
            ->get($limit)
            ->getResultArray();
    }

    // Get vital signs for a patient
    public function getPatientVitals($patientId, $limit = 5)
    {
        return $this->db->table('vital_signs')
            ->where('patient_id', $patientId)
            ->orderBy('recorded_at', 'DESC')
            ->get($limit)
            ->getResultArray();
    }

    // Record new vital signs
    public function recordVitals($data)
    {
        return $this->db->table('vital_signs')->insert($data);
    }

    // Get medication schedule for patients assigned to nurse
    public function getMedicationSchedule($nurseId, $date = null)
    {
        if ($date === null) {
            $date = date('Y-m-d');
        }

        return $this->db->table('medication_schedule')
            ->select('medication_schedule.*, patients.first_name, patients.last_name')
            ->join('patient_nurse', 'patient_nurse.patient_id = medication_schedule.patient_id')
            ->join('patients', 'patients.patient_id = medication_schedule.patient_id')
            ->where('patient_nurse.nurse_id', $nurseId)
            ->where('medication_schedule.schedule_date', $date)
            ->orderBy('medication_schedule.scheduled_time', 'ASC')
            ->get()
            ->getResultArray();
    }

    // Record medication administration
    public function recordMedication($data)
    {
        return $this->db->table('medication_administration')->insert($data);
    }

    // Create shift report
    public function createShiftReport($data)
    {
        return $this->db->table('nurse_shift_reports')->insert($data);
    }

    // Get shift reports for a nurse
    public function getShiftReports($nurseId, $limit = 10)
    {
        return $this->db->table('nurse_shift_reports')
            ->where('nurse_id', $nurseId)
            ->orderBy('shift_date', 'DESC')
            ->get($limit)
            ->getResultArray();
    }
}
