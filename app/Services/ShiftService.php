<?php

namespace App\Services;

use App\Libraries\PermissionManager;

class ShiftService
{
    protected $db;
    protected $permissionManager;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->permissionManager = new PermissionManager();
    }

    /**
     * Get shifts based on user role and permissions
     */
    public function getShiftsByRole($userRole, $staffId = null, $filters = [])
    {
        try {
            $builder = $this->db->table('doctor_shift ds')
                ->select([
                    'ds.shift_id as id',
                    'ds.doctor_id',
                    'ds.shift_date as date',
                    'ds.shift_start as start',
                    'ds.shift_end as end',
                    'ds.department',
                    'ds.duration_hours',
                    'ds.room_ward',
                    'ds.status',
                    'ds.notes',
                    'ds.created_at',
                    'ds.updated_at',
                    "CONCAT(COALESCE(s.first_name,''),' ',COALESCE(s.last_name,'')) as doctor_name",
                    's.staff_id',
                    's.phone as doctor_phone',
                    's.email as doctor_email'
                ])
                ->join('doctor d', 'd.doctor_id = ds.doctor_id', 'left')
                ->join('staff s', 's.staff_id = d.staff_id', 'left');

            // Apply role-based filtering
            if (in_array($userRole, ['admin', 'it_staff', 'receptionist'])) {
                // See all shifts
            } elseif ($userRole === 'doctor' && $staffId) {
                $builder->where('s.staff_id', $staffId);
            } elseif ($userRole === 'nurse' && $staffId) {
                $userDept = $this->getUserDepartment($staffId);
                if ($userDept) {
                    $builder->where('ds.department', $userDept);
                }
            } else {
                $builder->where('1', '0');
            }

            // Apply additional filters
            if (!empty($filters['date'])) {
                $builder->where('ds.shift_date', $filters['date']);
            }
            
            if (!empty($filters['status'])) {
                $builder->where('ds.status', $filters['status']);
            }
            
            if (!empty($filters['department'])) {
                $builder->where('ds.department', $filters['department']);
            }
            
            if (!empty($filters['doctor_id'])) {
                $builder->where('ds.doctor_id', $filters['doctor_id']);
            }

            if (!empty($filters['date_range'])) {
                $builder->where('ds.shift_date >=', $filters['date_range']['start']);
                $builder->where('ds.shift_date <=', $filters['date_range']['end']);
            }

            // Search functionality
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $builder->groupStart()
                    ->like("CONCAT(s.first_name, ' ', s.last_name)", $search)
                    ->orLike('ds.department', $search)
                    ->orLike('ds.room_ward', $search)
                    ->groupEnd();
            }

            $builder->orderBy('ds.shift_date', 'DESC')
                ->orderBy('ds.shift_start', 'ASC');

            return $builder->get()->getResultArray();

        } catch (\Throwable $e) {
            log_message('error', 'ShiftService::getShiftsByRole error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get shift statistics based on user role
     */
    public function getShiftStats($userRole, $staffId = null)
    {
        try {
            $stats = [];
            $today = date('Y-m-d');
            $thisWeek = [
                'start' => date('Y-m-d', strtotime('monday this week')),
                'end' => date('Y-m-d', strtotime('sunday this week'))
            ];

            if (in_array($userRole, ['admin', 'it_staff'])) {
                $stats = [
                    'total_shifts' => $this->getTotalShifts(),
                    'today_shifts' => $this->getShiftsCount(['date' => $today]),
                    'week_shifts' => $this->getShiftsCount(['date_range' => $thisWeek]),
                    'scheduled_shifts' => $this->getShiftsCount(['status' => 'Scheduled']),
                    'completed_shifts' => $this->getShiftsCount(['status' => 'Completed']),
                    'cancelled_shifts' => $this->getShiftsCount(['status' => 'Cancelled']),
                    'departments' => $this->getDepartmentStats(),
                    'active_doctors' => $this->getActiveDoctorsCount()
                ];
            } elseif ($userRole === 'doctor') {
                $doctorShifts = $this->getShiftsByRole('doctor', $staffId);
                $stats = [
                    'my_shifts' => count($doctorShifts),
                    'today_shifts' => $this->getShiftsCount(['date' => $today], 'doctor', $staffId),
                    'week_shifts' => $this->getShiftsCount(['date_range' => $thisWeek], 'doctor', $staffId),
                    'scheduled_shifts' => $this->getShiftsCount(['status' => 'Scheduled'], 'doctor', $staffId),
                    'completed_shifts' => $this->getShiftsCount(['status' => 'Completed'], 'doctor', $staffId),
                    'upcoming_shifts' => $this->getUpcomingShifts($staffId)
                ];
            } elseif ($userRole === 'nurse') {
                $userDept = $this->getUserDepartment($staffId);
                $stats = [
                    'department_shifts' => $this->getShiftsCount(['department' => $userDept]),
                    'today_shifts' => $this->getShiftsCount(['date' => $today, 'department' => $userDept]),
                    'week_shifts' => $this->getShiftsCount(['date_range' => $thisWeek, 'department' => $userDept]),
                    'scheduled_shifts' => $this->getShiftsCount(['status' => 'Scheduled', 'department' => $userDept]),
                    'department' => $userDept
                ];
            } elseif ($userRole === 'receptionist') {
                $stats = [
                    'total_shifts' => $this->getTotalShifts(),
                    'today_shifts' => $this->getShiftsCount(['date' => $today]),
                    'scheduled_shifts' => $this->getShiftsCount(['status' => 'Scheduled']),
                    'departments' => $this->getDepartmentStats()
                ];
            } else {
                $stats = ['message' => 'No shift access for this role'];
            }

            return $stats;

        } catch (\Throwable $e) {
            log_message('error', 'ShiftService::getShiftStats error: ' . $e->getMessage());
            return [];
        }
    }


    /**
     * Get available staff for shift assignment
     */
    public function getAvailableStaff($date = null, $startTime = null, $endTime = null)
    {
        try {
            $builder = $this->db->table('doctor d')
                ->select('d.doctor_id, s.staff_id, s.first_name, s.last_name, d.specialization')
                ->join('staff s', 's.staff_id = d.staff_id', 'inner')
                ->orderBy('s.first_name', 'ASC');

            if ($date && $startTime && $endTime) {
                $conflictingDoctors = $this->db->table('doctor_shift')
                    ->select('doctor_id')
                    ->where('shift_date', $date)
                    ->where('status !=', 'Cancelled')
                    ->groupStart()
                        ->where('shift_start <', $endTime)
                        ->where('shift_end >', $startTime)
                    ->groupEnd()
                    ->get()
                    ->getResultArray();

                if (!empty($conflictingDoctors)) {
                    $conflictingIds = array_column($conflictingDoctors, 'doctor_id');
                    if (!empty($conflictingIds)) {
                        $builder->whereNotIn('d.doctor_id', $conflictingIds);
                    }
                }
            }

            return $builder->get()->getResultArray();
        } catch (\Throwable $e) {
            log_message('error', 'ShiftService::getAvailableStaff error: ' . $e->getMessage());
            return [];
        }
    }


    // Private helper methods

    private function getTotalShifts()
    {
        return $this->db->table('doctor_shift')->countAllResults();
    }

    private function getShiftsCount($filters = [], $userRole = null, $staffId = null)
    {
        $builder = $this->db->table('doctor_shift ds');
        
        if ($userRole === 'doctor' && $staffId) {
            $builder->join('doctor d', 'd.doctor_id = ds.doctor_id')
                ->join('staff s', 's.staff_id = d.staff_id')
                ->where('s.staff_id', $staffId);
        }

        if (!empty($filters['date'])) {
            $builder->where('ds.shift_date', $filters['date']);
        }
        
        if (!empty($filters['status'])) {
            $builder->where('ds.status', $filters['status']);
        }
        
        if (!empty($filters['department'])) {
            $builder->where('ds.department', $filters['department']);
        }

        if (!empty($filters['date_range'])) {
            $builder->where('ds.shift_date >=', $filters['date_range']['start']);
            $builder->where('ds.shift_date <=', $filters['date_range']['end']);
        }

        return $builder->countAllResults();
    }

    private function getDepartmentStats()
    {
        try {
            return $this->db->table('doctor_shift')
                ->select('department, COUNT(*) as count')
                ->where('department IS NOT NULL')
                ->groupBy('department')
                ->orderBy('count', 'DESC')
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function getActiveDoctorsCount()
    {
        try {
            return $this->db->table('staff')
                ->where('role', 'doctor')
                ->where('status', 'Active')
                ->countAllResults();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function getUpcomingShifts($staffId)
    {
        try {
            return $this->db->table('doctor_shift ds')
                ->join('doctor d', 'd.doctor_id = ds.doctor_id')
                ->join('staff s', 's.staff_id = d.staff_id')
                ->where('s.staff_id', $staffId)
                ->where('ds.shift_date >=', date('Y-m-d'))
                ->where('ds.status', 'Scheduled')
                ->countAllResults();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function getUserDepartment($staffId)
    {
        try {
            $result = $this->db->table('staff')
                ->select('department')
                ->where('staff_id', $staffId)
                ->get()
                ->getRowArray();
            return $result['department'] ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }


}

