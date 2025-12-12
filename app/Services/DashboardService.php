<?php

namespace App\Services;

use CodeIgniter\Database\ConnectionInterface;

class DashboardService
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Get role-specific dashboard statistics
     */
    public function getDashboardStats($userRole, $staffId = null)
    {
        try {
            switch ($userRole) {
                case 'admin':
                    return $this->getAdminStats();
                case 'doctor':
                    return $this->getDoctorStats($staffId);
                case 'nurse':
                    return $this->getNurseStats($staffId);
                case 'receptionist':
                    return $this->getReceptionistStats();
                case 'accountant':
                    return $this->getAccountantStats();
                case 'pharmacist':
                    return $this->getPharmacistStats();
                case 'laboratorist':
                    return $this->getLaboratoristStats();
                case 'it_staff':
                    return $this->getITStats();
                default:
                    return $this->getDefaultStats();
            }
        } catch (\Exception $e) {
            log_message('error', 'Dashboard stats error: ' . $e->getMessage());
            return ['_error' => $e->getMessage()];
        }
    }

    /**
     * Admin dashboard statistics
     */
    private function getAdminStats()
    {
        $stats = [];

        // Total patients + trends and types
        if ($this->db->tableExists('patient')) {
            $patientTable = $this->db->table('patient');

            // Overall counts
            $stats['total_patients'] = $patientTable->countAllResults();

            $stats['active_patients'] = $this->db->table('patient')
                ->where('status', 'Active')
                ->countAllResults();

            // Patient type breakdown
            $stats['inpatients'] = $this->db->table('patient')
                ->where('patient_type', 'Inpatient')
                ->countAllResults();

            $stats['outpatients'] = $this->db->table('patient')
                ->where('patient_type', 'Outpatient')
                ->countAllResults();

            $stats['emergency_patients'] = $this->db->table('patient')
                ->where('patient_type', 'Emergency')
                ->countAllResults();

            // Trends: new patients this week and this month
            $today = date('Y-m-d');
            $weekAgo = date('Y-m-d', strtotime('-7 days'));
            $monthAgo = date('Y-m-d', strtotime('-30 days'));

            $stats['weekly_new_patients'] = $this->db->table('patient')
                ->where('date_registered >=', $weekAgo)
                ->where('date_registered <=', $today)
                ->countAllResults();

            $stats['monthly_patients'] = $this->db->table('patient')
                ->where('date_registered >=', $monthAgo)
                ->where('date_registered <=', $today)
                ->countAllResults();
        } else {
            $stats['total_patients'] = 0;
            $stats['active_patients'] = 0;
            $stats['inpatients'] = 0;
            $stats['outpatients'] = 0;
            $stats['emergency_patients'] = 0;
            $stats['weekly_new_patients'] = 0;
            $stats['monthly_patients'] = 0;
        }

        // Staff statistics (defensive in case "role" column does not exist)
        if ($this->db->tableExists('staff')) {
            try {
                $staffTable = $this->db->table('staff');
                $stats['total_staff'] = $staffTable->countAllResults();

                // Only attempt role-based count if column exists
                if ($this->db->fieldExists('role', 'staff')) {
                    $stats['total_doctors'] = $this->db->table('staff')
                        ->where('role', 'doctor')
                        ->countAllResults();
                } else {
                    $stats['total_doctors'] = 0;
                }
            } catch (\Throwable $e) {
                log_message('error', 'DashboardService::getAdminStats staff stats error: ' . $e->getMessage());
                $stats['total_staff'] = $stats['total_staff'] ?? 0;
                $stats['total_doctors'] = 0;
            }
        } else {
            $stats['total_staff'] = 0;
            $stats['total_doctors'] = 0;
        }

        // Appointments (today breakdown)
        $today = date('Y-m-d');
        if ($this->db->tableExists('appointments')) {
            // All appointments today (any status)
            $stats['today_appointments'] = $this->db->table('appointments')
                ->where('appointment_date', $today)
                ->countAllResults();

            // Today by status
            $stats['today_scheduled_appointments'] = $this->db->table('appointments')
                ->where('appointment_date', $today)
                ->where('status', 'scheduled')
                ->countAllResults();

            $stats['today_completed_appointments'] = $this->db->table('appointments')
                ->where('appointment_date', $today)
                ->where('status', 'completed')
                ->countAllResults();

            $stats['today_cancelled_appointments'] = $this->db->table('appointments')
                ->where('appointment_date', $today)
                ->where('status', 'cancelled')
                ->countAllResults();

            // Legacy keys for backward compatibility
            $stats['pending_appointments'] = $this->db->table('appointments')
                ->where('status', 'scheduled')
                ->countAllResults();

            $stats['completed_appointments'] = $stats['today_completed_appointments'];
        } else {
            $stats['today_appointments'] = 0;
            $stats['today_scheduled_appointments'] = 0;
            $stats['today_completed_appointments'] = 0;
            $stats['today_cancelled_appointments'] = 0;
            $stats['pending_appointments'] = 0;
            $stats['completed_appointments'] = 0;
        }
        
        // Users
        if ($this->db->tableExists('users')) {
            $stats['total_users'] = $this->db->table('users')->countAllResults();
        } else {
            $stats['total_users'] = 0;
        }

        // Weekly appointments stats
        if ($this->db->tableExists('appointments')) {
            $stats['weekly_appointments'] = $this->db->table('appointments')
                ->where('appointment_date >=', date('Y-m-d', strtotime('-7 days')))
                ->countAllResults();
        } else {
            $stats['weekly_appointments'] = 0;
        }

        // Bed / capacity statistics (room table)
        if ($this->db->tableExists('room')) {
            $roomBuilder = $this->db->table('room');

            // Total bed capacity across all rooms
            $capacityRow = $roomBuilder
                ->selectSum('bed_capacity', 'total_capacity')
                ->get()
                ->getRow();

            $totalCapacity = $capacityRow && isset($capacityRow->total_capacity)
                ? (int) $capacityRow->total_capacity
                : 0;

            // Occupied beds: sum bed_capacity where room status is occupied
            $occupiedRow = $this->db->table('room')
                ->selectSum('bed_capacity', 'occupied_capacity')
                ->where('status', 'occupied')
                ->get()
                ->getRow();

            $occupiedBeds = $occupiedRow && isset($occupiedRow->occupied_capacity)
                ? (int) $occupiedRow->occupied_capacity
                : 0;

            $stats['bed_capacity_total'] = $totalCapacity;
            $stats['occupied_beds'] = $occupiedBeds;
            $stats['available_beds'] = max($totalCapacity - $occupiedBeds, 0);
        } else {
            $stats['bed_capacity_total'] = 0;
            $stats['occupied_beds'] = 0;
            $stats['available_beds'] = 0;
        }

        // Staff on duty today (based on doctor_shift)
        if ($this->db->tableExists('doctor_shift') && $this->db->tableExists('doctor') && $this->db->tableExists('staff')) {
            try {
                $onDutyRow = $this->db->table('doctor_shift ds')
                    ->join('doctor d', 'd.doctor_id = ds.doctor_id', 'inner')
                    ->join('staff s', 's.staff_id = d.staff_id', 'inner')
                    ->select('COUNT(DISTINCT s.staff_id) as count')
                    ->where('ds.shift_date', $today)
                    ->whereIn('ds.status', ['Scheduled', 'Completed'])
                    ->get()
                    ->getRow();

                $stats['staff_on_duty_today'] = $onDutyRow && isset($onDutyRow->count)
                    ? (int) $onDutyRow->count
                    : 0;
            } catch (\Throwable $e) {
                log_message('error', 'DashboardService::getAdminStats staff_on_duty_today error: ' . $e->getMessage());
                $stats['staff_on_duty_today'] = 0;
            }
        } else {
            $stats['staff_on_duty_today'] = 0;
        }

        return $stats;
    }

    /**
     * Doctor dashboard statistics
     */
    private function getDoctorStats($staffId)
    {
        $stats = [];
        $today = date('Y-m-d');
        $staffId = (int) $staffId; // Ensure it's an integer

        // Initialize schedule stats first (most important for the widget)
        $stats['my_schedule_today'] = 0;
        $stats['my_schedule_total'] = 0;
        $stats['my_schedule_this_week'] = 0;

        // My Schedule stats - calculate these first and wrap in try-catch to ensure they always work
        if (!empty($staffId)) {
            try {
                $todayWeekday = date('N'); // 1 = Monday, 7 = Sunday
                $stats['my_schedule_today'] = $this->db->table('staff_schedule')
                    ->where('staff_id', $staffId)
                    ->where('status', 'active')
                    ->where('weekday', $todayWeekday)
                    ->countAllResults();
                
                $stats['my_schedule_total'] = $this->db->table('staff_schedule')
                    ->where('staff_id', $staffId)
                    ->where('status', 'active')
                    ->countAllResults();
                
                // Get upcoming shifts for this week
                $stats['my_schedule_this_week'] = $this->db->table('staff_schedule')
                    ->where('staff_id', $staffId)
                    ->where('status', 'active')
                    ->whereIn('weekday', [1, 2, 3, 4, 5, 6, 7])
                    ->countAllResults();
            } catch (\Exception $e) {
                log_message('error', 'Schedule stats error: ' . $e->getMessage());
                // Keep default values of 0
            }
        }

        // Other stats - wrap in try-catch to prevent failures from breaking the whole method
        try {
            // Today's appointments
            if ($this->db->tableExists('appointments')) {
                $stats['today_appointments'] = $this->db->table('appointments')
                    ->where('doctor_id', $staffId)
                    ->where('appointment_date', $today)
                    ->countAllResults();

                $stats['completed_today'] = $this->db->table('appointments')
                    ->where('doctor_id', $staffId)
                    ->where('appointment_date', $today)
                    ->where('status', 'completed')
                    ->countAllResults();

                $stats['pending_today'] = $this->db->table('appointments')
                    ->where('doctor_id', $staffId)
                    ->where('appointment_date', $today)
                    ->whereIn('status', ['scheduled', 'in-progress'])
                    ->countAllResults();

                // Weekly stats
                $stats['weekly_appointments'] = $this->db->table('appointments')
                    ->where('doctor_id', $staffId)
                    ->where('appointment_date >=', date('Y-m-d', strtotime('-7 days')))
                    ->countAllResults();
            } else {
                $stats['today_appointments'] = 0;
                $stats['completed_today'] = 0;
                $stats['pending_today'] = 0;
                $stats['weekly_appointments'] = 0;
            }
        } catch (\Exception $e) {
            log_message('error', 'Appointments stats error: ' . $e->getMessage());
            $stats['today_appointments'] = 0;
            $stats['completed_today'] = 0;
            $stats['pending_today'] = 0;
            $stats['weekly_appointments'] = 0;
        }

        try {
            // Patient statistics
            // Check both 'patient' and 'patients' table names
            $patientTable = $this->db->tableExists('patient') ? 'patient' : ($this->db->tableExists('patients') ? 'patients' : null);
            
            if ($patientTable && $this->db->tableExists('doctor')) {
                // Get doctor_id for this staff_id
                $doctorRecord = $this->db->table('doctor')
                    ->select('doctor_id')
                    ->where('staff_id', $staffId)
                    ->get()
                    ->getRowArray();
                
                $doctorId = $doctorRecord['doctor_id'] ?? null;
                
                if ($doctorId) {
                    // Check if primary_doctor_id column exists
                    $hasPrimaryDoctorColumn = $this->db->fieldExists('primary_doctor_id', $patientTable);
                    
                    if ($hasPrimaryDoctorColumn) {
                        $stats['my_patients'] = $this->db->table($patientTable)
                            ->where('primary_doctor_id', $doctorId)
                            ->countAllResults();

                        $stats['new_patients_week'] = $this->db->table($patientTable)
                            ->where('primary_doctor_id', $doctorId)
                            ->where('date_registered >=', date('Y-m-d', strtotime('-7 days')))
                            ->countAllResults();

                        $stats['critical_patients'] = $this->db->table($patientTable)
                            ->where('primary_doctor_id', $doctorId)
                            ->where('patient_type', 'emergency')
                            ->countAllResults();

                        $stats['monthly_patients'] = $this->db->table($patientTable)
                            ->where('primary_doctor_id', $doctorId)
                            ->where('date_registered >=', date('Y-m-d', strtotime('-30 days')))
                            ->countAllResults();
                    } else {
                        // Fallback: no primary_doctor_id column
                        $stats['my_patients'] = 0;
                        $stats['new_patients_week'] = 0;
                        $stats['critical_patients'] = 0;
                        $stats['monthly_patients'] = 0;
                    }
                } else {
                    $stats['my_patients'] = 0;
                    $stats['new_patients_week'] = 0;
                    $stats['critical_patients'] = 0;
                    $stats['monthly_patients'] = 0;
                }
            } else {
                $stats['my_patients'] = 0;
                $stats['new_patients_week'] = 0;
                $stats['critical_patients'] = 0;
                $stats['monthly_patients'] = 0;
            }
        } catch (\Exception $e) {
            log_message('error', 'Patient stats error: ' . $e->getMessage());
            $stats['my_patients'] = 0;
            $stats['new_patients_week'] = 0;
            $stats['critical_patients'] = 0;
            $stats['monthly_patients'] = 0;
        }

        try {
            // Prescriptions statistics
            if ($this->db->tableExists('prescriptions') && $this->db->tableExists('users')) {
                // Check if doctor_id column exists (legacy support)
                $hasDoctorIdColumn = $this->db->fieldExists('doctor_id', 'prescriptions');
                $hasCreatedByColumn = $this->db->fieldExists('created_by', 'prescriptions');
                
                if ($hasDoctorIdColumn && $hasCreatedByColumn) {
                    // Both columns exist - check both doctor_id and created_by
                    // Use a subquery to avoid double counting when both conditions match
                    $baseQuery = $this->db->table('prescriptions p')
                        ->join('users u', 'u.user_id = p.created_by', 'left')
                        ->groupStart()
                            ->where('p.doctor_id', $staffId)
                            ->orWhere('u.staff_id', $staffId)
                        ->groupEnd();
                    
                    $stats['prescriptions_total'] = (clone $baseQuery)->countAllResults();

                    $stats['prescriptions_today'] = (clone $baseQuery)
                        ->where('p.created_at >=', $today . ' 00:00:00')
                        ->where('p.created_at <=', $today . ' 23:59:59')
                        ->countAllResults();

                    // For status filtering, check both old and new status values
                    $stats['prescriptions_active'] = (clone $baseQuery)
                        ->whereIn('p.status', ['active', 'queued', 'verifying', 'ready'])
                        ->countAllResults();

                    $stats['prescriptions_completed'] = (clone $baseQuery)
                        ->whereIn('p.status', ['completed', 'dispensed'])
                        ->countAllResults();
                } elseif ($hasDoctorIdColumn) {
                    // Only doctor_id column exists
                    $stats['prescriptions_total'] = $this->db->table('prescriptions')
                        ->where('doctor_id', $staffId)
                        ->countAllResults();

                    $stats['prescriptions_today'] = $this->db->table('prescriptions')
                        ->where('doctor_id', $staffId)
                        ->where('created_at >=', $today . ' 00:00:00')
                        ->where('created_at <=', $today . ' 23:59:59')
                        ->countAllResults();

                    // For status filtering, check both old and new status values
                    $stats['prescriptions_active'] = $this->db->table('prescriptions')
                        ->where('doctor_id', $staffId)
                        ->whereIn('status', ['active', 'queued', 'verifying', 'ready'])
                        ->countAllResults();

                    $stats['prescriptions_completed'] = $this->db->table('prescriptions')
                        ->where('doctor_id', $staffId)
                        ->whereIn('status', ['completed', 'dispensed'])
                        ->countAllResults();
                } elseif ($hasCreatedByColumn) {
                    // Modern: use created_by (user_id) and join with users to get staff_id
                    $stats['prescriptions_total'] = $this->db->table('prescriptions p')
                        ->join('users u', 'u.user_id = p.created_by', 'inner')
                        ->where('u.staff_id', $staffId)
                        ->countAllResults();

                    $stats['prescriptions_today'] = $this->db->table('prescriptions p')
                        ->join('users u', 'u.user_id = p.created_by', 'inner')
                        ->where('u.staff_id', $staffId)
                        ->where('p.created_at >=', $today . ' 00:00:00')
                        ->where('p.created_at <=', $today . ' 23:59:59')
                        ->countAllResults();

                    // For status filtering, check both 'active' and 'queued' (new status values)
                    $stats['prescriptions_active'] = $this->db->table('prescriptions p')
                        ->join('users u', 'u.user_id = p.created_by', 'inner')
                        ->where('u.staff_id', $staffId)
                        ->whereIn('p.status', ['active', 'queued', 'verifying', 'ready'])
                        ->countAllResults();

                    $stats['prescriptions_completed'] = $this->db->table('prescriptions p')
                        ->join('users u', 'u.user_id = p.created_by', 'inner')
                        ->where('u.staff_id', $staffId)
                        ->whereIn('p.status', ['completed', 'dispensed'])
                        ->countAllResults();
                } else {
                    // No linking column found
                    $stats['prescriptions_total'] = 0;
                    $stats['prescriptions_today'] = 0;
                    $stats['prescriptions_active'] = 0;
                    $stats['prescriptions_completed'] = 0;
                }

                // Keep backward compatibility
                $stats['prescriptions_pending'] = $stats['prescriptions_active'] ?? 0;
            } else {
                $stats['prescriptions_total'] = 0;
                $stats['prescriptions_today'] = 0;
                $stats['prescriptions_active'] = 0;
                $stats['prescriptions_completed'] = 0;
                $stats['prescriptions_pending'] = 0;
            }
        } catch (\Exception $e) {
            log_message('error', 'Prescriptions stats error: ' . $e->getMessage());
            $stats['prescriptions_total'] = 0;
            $stats['prescriptions_today'] = 0;
            $stats['prescriptions_active'] = 0;
            $stats['prescriptions_completed'] = 0;
            $stats['prescriptions_pending'] = 0;
        }

        return $stats;
    }

    /**
     * Nurse dashboard statistics
     */
    private function getNurseStats($staffId)
    {
        $stats = [];

        // Get nurse department
        $nurse = $this->db->table('staff')
            ->select('department')
            ->where('staff_id', $staffId)
            ->get()
            ->getRow();

        $department = $nurse->department ?? null;

        if ($department) {
            // Department patients
            $stats['department_patients'] = $this->db->table('patient p')
                ->join('staff s', 's.staff_id = p.primary_doctor_id', 'left')
                ->where('s.department', $department)
                ->countAllResults();

            $stats['critical_patients'] = $this->db->table('patient p')
                ->join('staff s', 's.staff_id = p.primary_doctor_id', 'left')
                ->where('s.department', $department)
                ->where('p.patient_type', 'emergency')
                ->countAllResults();
        } else {
            $stats['department_patients'] = 0;
            $stats['critical_patients'] = 0;
        }

        // Medications
        $today = date('Y-m-d');
        $stats['medications_due'] = $this->db->table('prescriptions')
            ->where('DATE(next_dose)', $today)
            ->where('status', 'active')
            ->countAllResults();

        $stats['medications_overdue'] = $this->db->table('prescriptions')
            ->where('DATE(next_dose) <', $today)
            ->where('status', 'active')
            ->countAllResults();

        return $stats;
    }

    /**
     * Receptionist dashboard statistics
     */
    private function getReceptionistStats()
    {
        $stats = [];
        $today = date('Y-m-d');

        // Appointments
        $stats['total_appointments'] = $this->db->table('appointments')
            ->where('appointment_date', $today)
            ->countAllResults();

        $stats['scheduled_today'] = $this->db->table('appointments')
            ->where('appointment_date', $today)
            ->where('status', 'scheduled')
            ->countAllResults();

        $stats['cancelled_today'] = $this->db->table('appointments')
            ->where('appointment_date', $today)
            ->where('status', 'cancelled')
            ->countAllResults();

        // Patients
        $stats['new_patients_today'] = $this->db->table('patient')
            ->where('DATE(date_registered)', $today)
            ->countAllResults();

        $stats['total_patients'] = $this->db->table('patient')->countAllResults();

        // Weekly and monthly stats
        $stats['weekly_appointments'] = $this->db->table('appointments')
            ->where('appointment_date >=', date('Y-m-d', strtotime('-7 days')))
            ->countAllResults();

        $stats['monthly_patients'] = $this->db->table('patient')
            ->where('date_registered >=', date('Y-m-d', strtotime('-30 days')))
            ->countAllResults();

        return $stats;
    }

    /**
     * Get recent activities based on user role
     */
    public function getRecentActivities($userRole, $staffId = null, $limit = 10)
    {
        $activities = [];

        try {
            switch ($userRole) {
                case 'admin':
                    $activities = $this->getAdminActivities($limit);
                    break;
                case 'doctor':
                    $activities = $this->getDoctorActivities($staffId, $limit);
                    break;
                case 'nurse':
                    $activities = $this->getNurseActivities($staffId, $limit);
                    break;
                case 'receptionist':
                    $activities = $this->getReceptionistActivities($limit);
                    break;
                default:
                    $activities = $this->getDefaultActivities($limit);
            }
        } catch (\Exception $e) {
            log_message('error', 'Recent activities error: ' . $e->getMessage());
        }

        return $activities;
    }

    /**
     * Get upcoming events based on user role
     */
    public function getUpcomingEvents($userRole, $staffId = null, $limit = 5)
    {
        $events = [];

        try {
            switch ($userRole) {
                case 'doctor':
                    $events = $this->getDoctorUpcomingEvents($staffId, $limit);
                    break;
                case 'nurse':
                    $events = $this->getNurseUpcomingEvents($staffId, $limit);
                    break;
                case 'receptionist':
                    $events = $this->getReceptionistUpcomingEvents($limit);
                    break;
                default:
                    $events = $this->getDefaultUpcomingEvents($limit);
            }
        } catch (\Exception $e) {
            log_message('error', 'Upcoming events error: ' . $e->getMessage());
        }

        return $events;
    }

    /**
     * Get admin recent activities
     */
    private function getAdminActivities($limit)
    {
        // Get recent appointments, patient registrations, staff additions
        $activities = [];

        // Recent appointments
        if ($this->db->tableExists('appointments') && $this->db->tableExists('patient') && $this->db->tableExists('staff')) {
            $appointments = $this->db->table('appointments a')
                ->select('a.created_at, p.first_name, p.last_name, s.first_name as doctor_first_name, s.last_name as doctor_last_name')
                ->join('patient p', 'p.patient_id = a.patient_id')
                ->join('staff s', 's.staff_id = a.doctor_id')
                ->orderBy('a.created_at', 'DESC')
                ->limit(3)
                ->get()
                ->getResultArray();

            foreach ($appointments as $appointment) {
                $activities[] = [
                    'message' => "New appointment scheduled for {$appointment['first_name']} {$appointment['last_name']} with Dr. {$appointment['doctor_first_name']} {$appointment['doctor_last_name']}",
                    'time' => $appointment['created_at'],
                    'icon' => 'fas fa-calendar-plus',
                    'color' => 'blue'
                ];
            }

            $patients = $this->db->table('patient')
                ->select('first_name, last_name, date_registered')
                ->orderBy('date_registered', 'DESC')
                ->limit(2)
                ->get()
                ->getResultArray();

            foreach ($patients as $patient) {
                $activities[] = [
                    'message' => "New patient registered: {$patient['first_name']} {$patient['last_name']}",
                    'time' => $patient['date_registered'],
                    'icon' => 'fas fa-user-plus',
                    'color' => 'green'
                ];
            }
        }

        // Sort by time and limit
        usort($activities, function($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });

        return array_slice($activities, 0, $limit);
    }

    /**
     * Get doctor recent activities
     */
    private function getDoctorActivities($staffId, $limit)
    {
        $activities = [];

        // Recent appointments
        $appointments = $this->db->table('appointments a')
            ->select('a.created_at, a.status, p.first_name, p.last_name')
            ->join('patient p', 'p.patient_id = a.patient_id')
            ->where('a.doctor_id', $staffId)
            ->orderBy('a.created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();

        foreach ($appointments as $appointment) {
            $statusText = ucfirst($appointment['status']);
            $activities[] = [
                'message' => "Appointment {$statusText}: {$appointment['first_name']} {$appointment['last_name']}",
                'time' => $appointment['created_at'],
                'icon' => 'fas fa-calendar-check',
                'color' => $appointment['status'] === 'completed' ? 'green' : 'blue'
            ];
        }

        return $activities;
    }

    /**
     * Get doctor upcoming events (appointments)
     */
    private function getDoctorUpcomingEvents($staffId, $limit)
    {
        $events = [];

        $appointments = $this->db->table('appointments a')
            ->select('a.appointment_date, a.appointment_time, p.first_name, p.last_name, a.appointment_type')
            ->join('patient p', 'p.patient_id = a.patient_id')
            ->where('a.doctor_id', $staffId)
            ->where('a.appointment_date >=', date('Y-m-d'))
            ->where('a.status', 'scheduled')
            ->orderBy('a.appointment_date, a.appointment_time')
            ->limit($limit)
            ->get()
            ->getResultArray();

        foreach ($appointments as $appointment) {
            $events[] = [
                'title' => "{$appointment['appointment_type']} - {$appointment['first_name']} {$appointment['last_name']}",
                'date' => $appointment['appointment_date'],
                'time' => $appointment['appointment_time']
            ];
        }

        return $events;
    }

    /**
     * Placeholder methods for other roles
     */
    private function getAccountantStats() { return []; }
    private function getPharmacistStats() { return []; }
    private function getLaboratoristStats() { return []; }
    private function getITStats() { return []; }
    private function getDefaultStats() { return []; }
    
    private function getNurseActivities($staffId, $limit) { return []; }
    private function getReceptionistActivities($limit) { return []; }
    private function getDefaultActivities($limit) { return []; }
    
    private function getNurseUpcomingEvents($staffId, $limit) { return []; }
    private function getReceptionistUpcomingEvents($limit) { return []; }
    private function getDefaultUpcomingEvents($limit) { return []; }

    /**
     * Get system health data (admin only)
     */
    public function getSystemHealth()
    {
        return [
            'database_status' => 'healthy',
            'server_load' => '45%',
            'memory_usage' => '62%',
            'disk_space' => '78%'
        ];
    }

    /**
     * Get today's schedule
     */
    public function getTodaySchedule($userRole, $staffId)
    {
        $today = date('Y-m-d');
        
        return $this->db->table('appointments a')
            ->select('a.*, p.first_name, p.last_name')
            ->join('patient p', 'p.patient_id = a.patient_id')
            ->where('a.doctor_id', $staffId)
            ->where('a.appointment_date', $today)
            ->orderBy('a.appointment_time')
            ->get()
            ->getResultArray();
    }

    /**
     * Get quick stats
     */
    public function getQuickStats($userRole, $staffId)
    {
        return [
            'weekly_appointments' => 25,
            'monthly_patients' => 150
        ];
    }

    /**
     * Update user preferences
     */
    public function updateUserPreferences($userId, $preferences)
    {
        try {
            return $this->db->table('user_preferences')
                ->replace([
                    'user_id' => $userId,
                    'preferences' => json_encode($preferences),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
        } catch (\Exception $e) {
            log_message('error', 'Update preferences error: ' . $e->getMessage());
            return false;
        }
    }
}
