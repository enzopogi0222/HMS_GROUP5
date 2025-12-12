<?php

namespace App\Services;

use CodeIgniter\Database\ConnectionInterface;

class AnalyticsService
{
    protected $db;
    protected $patientTable;

    public function __construct(ConnectionInterface $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
        $this->patientTable = $this->resolvePatientTableName();
    }

    /**
     * Resolve patient table name (handles both 'patient' and 'patients')
     */
    private function resolvePatientTableName(): string
    {
        if ($this->db->tableExists('patients')) {
            return 'patients';
        } elseif ($this->db->tableExists('patient')) {
            return 'patient';
        }
        // Default fallback
        return 'patient';
    }

    /**
     * Get analytics data based on user role
     */
    public function getAnalyticsData(string $userRole, int $userId = null, array $filters = []): array
    {
        try {
            switch ($userRole) {
                case 'admin':
                case 'accountant':
                case 'it_staff':
                    return $this->getSystemWideAnalytics($filters);
                case 'doctor':
                    return $this->getDoctorAnalytics($userId, $filters);
                case 'nurse':
                    return $this->getNurseAnalytics($userId, $filters);
                case 'receptionist':
                    return $this->getReceptionistAnalytics($filters);
                default:
                    return $this->getBasicAnalytics();
            }
        } catch (\Exception $e) {
            log_message('error', 'AnalyticsService::getAnalyticsData error: ' . $e->getMessage());
            return $this->getBasicAnalytics();
        }
    }

    /**
     * Get system-wide analytics
     */
    private function getSystemWideAnalytics(array $filters = []): array
    {
        $dateRange = $this->getDateRange($filters);
        
        return [
            'patient_analytics' => $this->getPatientAnalytics($dateRange),
            'appointment_analytics' => $this->getAppointmentAnalytics($dateRange),
            'financial_analytics' => $this->getFinancialAnalytics($dateRange),
            'staff_analytics' => $this->getStaffAnalytics($dateRange),
            'lab_analytics' => $this->getLabAnalytics($dateRange),
            'prescription_analytics' => $this->getPrescriptionAnalytics($dateRange),
            'room_analytics' => $this->getRoomAnalytics($dateRange),
            'resource_analytics' => $this->getResourceAnalytics($dateRange)
        ];
    }

    /**
     * Get doctor-specific analytics
     */
    private function getDoctorAnalytics(int $doctorId, array $filters = []): array
    {
        $dateRange = $this->getDateRange($filters);
        
        return [
            'my_patients' => $this->getDoctorPatientStats($doctorId, $dateRange),
            'my_appointments' => $this->getDoctorAppointmentStats($doctorId, $dateRange),
            'my_revenue' => $this->getDoctorRevenueStats($doctorId, $dateRange),
            'patient_satisfaction' => $this->getDoctorSatisfactionStats($doctorId, $dateRange),
            'monthly_performance' => $this->getDoctorMonthlyPerformance($doctorId, $dateRange)
        ];
    }

    /**
     * Get nurse analytics
     */
    private function getNurseAnalytics(int $nurseId, array $filters = []): array
    {
        $dateRange = $this->getDateRange($filters);
        
        return [
            'patients' => $this->getNursePatientStats($nurseId, $dateRange),
            'medication_tracking' => $this->getMedicationTrackingStats($nurseId, $dateRange),
            'shift_analytics' => $this->getShiftAnalytics($nurseId, $dateRange)
        ];
    }

    /**
     * Get receptionist analytics
     */
    private function getReceptionistAnalytics(array $filters = []): array
    {
        $dateRange = $this->getDateRange($filters);
        
        return [
            'registration_stats' => $this->getRegistrationStats($dateRange),
            'appointment_booking_stats' => $this->getBookingStats($dateRange),
            'daily_activity' => $this->getDailyActivityStats($dateRange)
        ];
    }

    /**
     * Get patient analytics
     */
    private function getPatientAnalytics(array $dateRange): array
    {
        // Use resolved table name
        $patientTable = $this->patientTable;
        
        // Check if date_registered column exists, otherwise use created_at
        $dateColumn = $this->db->fieldExists('date_registered', $patientTable) ? 'date_registered' : 'created_at';
        
        $totalPatients = $this->db->table($patientTable)->countAllResults();
        $newPatients = $this->db->table($patientTable)
            ->where($dateColumn . ' >=', $dateRange['start'])
            ->where($dateColumn . ' <=', $dateRange['end'])
            ->countAllResults();
        
        // Check if status column exists
        $activePatients = 0;
        if ($this->db->fieldExists('status', $patientTable)) {
            $activePatients = $this->db->table($patientTable)
                ->where('status', 'Active')
                ->countAllResults();
        } else {
            // If no status column, count all as active
            $activePatients = $totalPatients;
        }

        // Check if patient_type column exists
        $patientsByType = [];
        if ($this->db->fieldExists('patient_type', $patientTable)) {
            $patientsByType = $this->db->table($patientTable)
                ->select('patient_type, COUNT(*) as count')
                ->groupBy('patient_type')
                ->get()
                ->getResultArray();
        }

        $patientsByAge = $this->db->table($patientTable)
            ->select('
                CASE 
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 18 THEN "Under 18"
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 18 AND 35 THEN "18-35"
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 36 AND 55 THEN "36-55"
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 56 AND 70 THEN "56-70"
                    ELSE "Over 70"
                END as age_group,
                COUNT(*) as count
            ')
            ->groupBy('age_group')
            ->get()
            ->getResultArray();

        return [
            'total_patients' => $totalPatients,
            'new_patients' => $newPatients,
            'active_patients' => $activePatients,
            'patients_by_type' => $patientsByType,
            'patients_by_age' => $patientsByAge
        ];
    }

    /**
     * Get appointment analytics with peak hours
     */
    private function getAppointmentAnalytics(array $dateRange): array
    {
        if (!$this->db->tableExists('appointments')) {
            return [
                'total_appointments' => 0,
                'appointments_by_status' => [],
                'appointments_by_type' => [],
                'daily_appointments' => [],
                'peak_hours' => []
            ];
        }

        try {
            $totalAppointments = $this->db->table('appointments')
                ->where('appointment_date >=', $dateRange['start'])
                ->where('appointment_date <=', $dateRange['end'])
                ->countAllResults();

            $appointmentsByStatus = [];
            if ($this->db->fieldExists('status', 'appointments')) {
                $appointmentsByStatus = $this->db->table('appointments')
                    ->select('status, COUNT(*) as count')
                    ->where('appointment_date >=', $dateRange['start'])
                    ->where('appointment_date <=', $dateRange['end'])
                    ->groupBy('status')
                    ->get()
                    ->getResultArray();
            }

            $appointmentsByType = [];
            if ($this->db->fieldExists('appointment_type', 'appointments')) {
                $appointmentsByType = $this->db->table('appointments')
                    ->select('appointment_type, COUNT(*) as count')
                    ->where('appointment_date >=', $dateRange['start'])
                    ->where('appointment_date <=', $dateRange['end'])
                    ->groupBy('appointment_type')
                    ->get()
                    ->getResultArray();
            }

            $dailyAppointments = [];
            if ($this->db->fieldExists('appointment_date', 'appointments')) {
                $dailyAppointments = $this->db->table('appointments')
                    ->select('DATE(appointment_date) as date, COUNT(*) as count')
                    ->where('appointment_date >=', $dateRange['start'])
                    ->where('appointment_date <=', $dateRange['end'])
                    ->groupBy('DATE(appointment_date)')
                    ->orderBy('date')
                    ->get()
                    ->getResultArray();
            }

            // Get peak hours
            $peakHours = [];
            if ($this->db->fieldExists('appointment_time', 'appointments')) {
                try {
                    $peakHours = $this->db->table('appointments')
                        ->select('HOUR(appointment_time) as hour, COUNT(*) as count')
                        ->where('appointment_date >=', $dateRange['start'])
                        ->where('appointment_date <=', $dateRange['end'])
                        ->groupBy('HOUR(appointment_time)')
                        ->orderBy('count', 'DESC')
                        ->limit(5)
                        ->get()
                        ->getResultArray();
                } catch (\Exception $e) {
                    log_message('error', 'Error getting peak hours: ' . $e->getMessage());
                }
            }

            return [
                'total_appointments' => $totalAppointments,
                'appointments_by_status' => $appointmentsByStatus,
                'appointments_by_type' => $appointmentsByType,
                'daily_appointments' => $dailyAppointments,
                'peak_hours' => $peakHours
            ];
        } catch (\Exception $e) {
            log_message('error', 'Error getting appointment analytics: ' . $e->getMessage());
            return [
                'total_appointments' => 0,
                'appointments_by_status' => [],
                'appointments_by_type' => [],
                'daily_appointments' => [],
                'peak_hours' => []
            ];
        }
    }

    /**
     * Get financial analytics with payment methods
     */
    private function getFinancialAnalytics(array $dateRange): array
    {
        $totalRevenue = 0;
        $revenueByMonth = [];
        $revenueByPaymentMethod = [];
        
        if ($this->db->tableExists('payments')) {
            try {
                $totalRevenue = $this->db->table('payments')
                    ->selectSum('amount')
                    ->where('payment_date >=', $dateRange['start'])
                    ->where('payment_date <=', $dateRange['end'])
                    ->where('status', 'completed')
                    ->get()
                    ->getRow()
                    ->amount ?? 0;

                $revenueByMonth = $this->db->table('payments')
                    ->select('DATE_FORMAT(payment_date, "%Y-%m") as month, SUM(amount) as revenue')
                    ->where('payment_date >=', $dateRange['start'])
                    ->where('payment_date <=', $dateRange['end'])
                    ->where('status', 'completed')
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get()
                    ->getResultArray();

                // Get revenue by payment method
                if ($this->db->fieldExists('payment_method', 'payments')) {
                    $revenueByPaymentMethod = $this->db->table('payments')
                        ->select('payment_method, SUM(amount) as total')
                        ->where('payment_date >=', $dateRange['start'])
                        ->where('payment_date <=', $dateRange['end'])
                        ->where('status', 'completed')
                        ->groupBy('payment_method')
                        ->get()
                        ->getResultArray();
                }
            } catch (\Exception $e) {
                log_message('error', 'Error getting revenue analytics: ' . $e->getMessage());
            }
        }

        $totalExpenses = 0;
        $expensesByCategory = [];
        
        if ($this->db->tableExists('expenses')) {
            try {
                $totalExpenses = $this->db->table('expenses')
                    ->selectSum('amount')
                    ->where('expense_date >=', $dateRange['start'])
                    ->where('expense_date <=', $dateRange['end'])
                    ->get()
                    ->getRow()
                    ->amount ?? 0;

                $expensesByCategory = $this->db->table('expenses')
                    ->select('category, SUM(amount) as total')
                    ->where('expense_date >=', $dateRange['start'])
                    ->where('expense_date <=', $dateRange['end'])
                    ->groupBy('category')
                    ->get()
                    ->getResultArray();
            } catch (\Exception $e) {
                log_message('error', 'Error getting expense analytics: ' . $e->getMessage());
            }
        }

        // Get outstanding bills
        $outstandingBills = 0;
        if ($this->db->tableExists('bills')) {
            try {
                if ($this->db->fieldExists('status', 'bills')) {
                    $outstandingBills = $this->db->table('bills')
                        ->selectSum('total_amount')
                        ->groupStart()
                            ->where('status', 'pending')
                            ->orWhere('status', 'unpaid')
                        ->groupEnd()
                        ->get()
                        ->getRow()
                        ->total_amount ?? 0;
                }
            } catch (\Exception $e) {
                log_message('error', 'Error getting outstanding bills: ' . $e->getMessage());
            }
        }

        return [
            'total_revenue' => (float)$totalRevenue,
            'total_expenses' => (float)$totalExpenses,
            'net_profit' => (float)$totalRevenue - (float)$totalExpenses,
            'revenue_by_month' => $revenueByMonth,
            'expenses_by_category' => $expensesByCategory,
            'revenue_by_payment_method' => $revenueByPaymentMethod,
            'outstanding_bills' => (float)$outstandingBills
        ];
    }

    /**
     * Get staff analytics
     */
    private function getStaffAnalytics(array $dateRange): array
    {
        if (!$this->db->tableExists('staff')) {
            return [
                'total_staff' => 0,
                'active_staff' => 0,
                'staff_by_role' => [],
                'staff_by_department' => []
            ];
        }

        try {
            $totalStaff = 0;
            if ($this->db->fieldExists('status', 'staff')) {
                $totalStaff = $this->db->table('staff')->where('status', 'active')->countAllResults();
            } else {
                $totalStaff = $this->db->table('staff')->countAllResults();
            }
            
            $staffByRole = [];
            if ($this->db->fieldExists('role', 'staff')) {
                $query = $this->db->table('staff')->select('role, COUNT(*) as count');
                if ($this->db->fieldExists('status', 'staff')) {
                    $query->where('status', 'active');
                }
                $staffByRole = $query->groupBy('role')
                    ->get()
                    ->getResultArray();
            }

            $staffByDepartment = [];
            if ($this->db->fieldExists('department', 'staff')) {
                $query = $this->db->table('staff')->select('department, COUNT(*) as count');
                if ($this->db->fieldExists('status', 'staff')) {
                    $query->where('status', 'active');
                }
                $staffByDepartment = $query->groupBy('department')
                    ->get()
                    ->getResultArray();
            }

            return [
                'total_staff' => $totalStaff,
                'active_staff' => $totalStaff,
                'staff_by_role' => $staffByRole,
                'staff_by_department' => $staffByDepartment
            ];
        } catch (\Exception $e) {
            log_message('error', 'Error getting staff analytics: ' . $e->getMessage());
            return [
                'total_staff' => 0,
                'active_staff' => 0,
                'staff_by_role' => [],
                'staff_by_department' => []
            ];
        }
    }

    /**
     * Generate reports
     */
    public function generateReport(string $reportType, string $userRole, int $userId = null, array $filters = []): array
    {
        try {
            switch ($reportType) {
                case 'patient_summary':
                    return $this->generatePatientSummaryReport($filters);
                case 'financial_summary':
                    return $this->generateFinancialSummaryReport($filters);
                case 'appointment_summary':
                    return $this->generateAppointmentSummaryReport($filters);
                case 'staff_performance':
                    return $this->generateStaffPerformanceReport($filters);
                case 'doctor_performance':
                    return $this->generateDoctorPerformanceReport($userId, $filters);
                case 'lab_summary':
                    return $this->generateLabSummaryReport($filters);
                case 'prescription_summary':
                    return $this->generatePrescriptionSummaryReport($filters);
                case 'room_utilization':
                    return $this->generateRoomUtilizationReport($filters);
                default:
                    return ['success' => false, 'message' => 'Invalid report type'];
            }
        } catch (\Exception $e) {
            log_message('error', 'AnalyticsService::generateReport error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error generating report'];
        }
    }

    /**
     * Helper methods
     */
    private function getDateRange(array $filters): array
    {
        $endDate = $filters['end_date'] ?? date('Y-m-d');
        $startDate = $filters['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        
        return [
            'start' => $startDate,
            'end' => $endDate
        ];
    }

    private function getBasicAnalytics(): array
    {
        return [
            'message' => 'Limited analytics access for this role'
        ];
    }

    private function getDoctorPatientStats(int $doctorId, array $dateRange): array
    {
        $patientTable = $this->patientTable;
        $dateColumn = $this->db->fieldExists('date_registered', $patientTable) ? 'date_registered' : 'created_at';
        
        // Check if primary_doctor_id column exists
        $totalPatients = 0;
        $newPatients = 0;
        
        if ($this->db->fieldExists('primary_doctor_id', $patientTable)) {
            $totalPatients = $this->db->table($patientTable)
                ->where('primary_doctor_id', $doctorId)
                ->countAllResults();

            $newPatients = $this->db->table($patientTable)
                ->where('primary_doctor_id', $doctorId)
                ->where($dateColumn . ' >=', $dateRange['start'])
                ->where($dateColumn . ' <=', $dateRange['end'])
                ->countAllResults();
        }

        return [
            'total_patients' => $totalPatients,
            'new_patients' => $newPatients
        ];
    }

    private function getDoctorAppointmentStats(int $doctorId, array $dateRange): array
    {
        if (!$this->db->tableExists('appointments')) {
            return [
                'total_appointments' => 0,
                'completed_appointments' => 0,
                'completion_rate' => 0
            ];
        }

        try {
            $totalAppointments = $this->db->table('appointments')
                ->where('doctor_id', $doctorId)
                ->where('appointment_date >=', $dateRange['start'])
                ->where('appointment_date <=', $dateRange['end'])
                ->countAllResults();

            $completedAppointments = 0;
            if ($this->db->fieldExists('status', 'appointments')) {
                $completedAppointments = $this->db->table('appointments')
                    ->where('doctor_id', $doctorId)
                    ->where('status', 'completed')
                    ->where('appointment_date >=', $dateRange['start'])
                    ->where('appointment_date <=', $dateRange['end'])
                    ->countAllResults();
            }

            return [
                'total_appointments' => $totalAppointments,
                'completed_appointments' => $completedAppointments,
                'completion_rate' => $totalAppointments > 0 ? round(($completedAppointments / $totalAppointments) * 100, 2) : 0
            ];
        } catch (\Exception $e) {
            log_message('error', 'Error getting doctor appointment stats: ' . $e->getMessage());
            return [
                'total_appointments' => 0,
                'completed_appointments' => 0,
                'completion_rate' => 0
            ];
        }
    }

    private function getDoctorRevenueStats(int $doctorId, array $dateRange): array
    {
        $revenue = 0;
        
        if ($this->db->tableExists('payments') && $this->db->tableExists('bills')) {
            try {
                if ($this->db->fieldExists('doctor_id', 'bills')) {
                    $revenue = $this->db->table('payments p')
                        ->join('bills b', 'b.bill_id = p.bill_id', 'inner')
                        ->selectSum('p.amount')
                        ->where('b.doctor_id', $doctorId)
                        ->where('p.payment_date >=', $dateRange['start'])
                        ->where('p.payment_date <=', $dateRange['end'])
                        ->where('p.status', 'completed')
                        ->get()
                        ->getRow()
                        ->amount ?? 0;
                }
            } catch (\Exception $e) {
                log_message('error', 'Error getting doctor revenue: ' . $e->getMessage());
            }
        }

        return [
            'total_revenue' => (float)$revenue
        ];
    }

    private function generatePatientSummaryReport(array $filters): array
    {
        $dateRange = $this->getDateRange($filters);
        
        $data = [
            'report_title' => 'Patient Summary Report',
            'date_range' => $dateRange,
            'generated_at' => date('Y-m-d H:i:s'),
            'data' => $this->getPatientAnalytics($dateRange)
        ];

        return ['success' => true, 'report' => $data];
    }

    private function generateFinancialSummaryReport(array $filters): array
    {
        $dateRange = $this->getDateRange($filters);
        
        $data = [
            'report_title' => 'Financial Summary Report',
            'date_range' => $dateRange,
            'generated_at' => date('Y-m-d H:i:s'),
            'data' => $this->getFinancialAnalytics($dateRange)
        ];

        return ['success' => true, 'report' => $data];
    }

    private function generateAppointmentSummaryReport(array $filters): array
    {
        $dateRange = $this->getDateRange($filters);
        
        $data = [
            'report_title' => 'Appointment Summary Report',
            'date_range' => $dateRange,
            'generated_at' => date('Y-m-d H:i:s'),
            'data' => $this->getAppointmentAnalytics($dateRange)
        ];

        return ['success' => true, 'report' => $data];
    }

    private function generateStaffPerformanceReport(array $filters): array
    {
        $dateRange = $this->getDateRange($filters);
        
        $data = [
            'report_title' => 'Staff Performance Report',
            'date_range' => $dateRange,
            'generated_at' => date('Y-m-d H:i:s'),
            'data' => $this->getStaffAnalytics($dateRange)
        ];

        return ['success' => true, 'report' => $data];
    }

    private function generateDoctorPerformanceReport(int $doctorId, array $filters): array
    {
        $dateRange = $this->getDateRange($filters);
        
        $data = [
            'report_title' => 'Doctor Performance Report',
            'date_range' => $dateRange,
            'generated_at' => date('Y-m-d H:i:s'),
            'data' => $this->getDoctorAnalytics($doctorId, $filters)
        ];

        return ['success' => true, 'report' => $data];
    }

    private function generateLabSummaryReport(array $filters): array
    {
        $dateRange = $this->getDateRange($filters);
        
        $data = [
            'report_title' => 'Lab Test Summary Report',
            'date_range' => $dateRange,
            'generated_at' => date('Y-m-d H:i:s'),
            'data' => $this->getLabAnalytics($dateRange)
        ];

        return ['success' => true, 'report' => $data];
    }

    private function generatePrescriptionSummaryReport(array $filters): array
    {
        $dateRange = $this->getDateRange($filters);
        
        $data = [
            'report_title' => 'Prescription Summary Report',
            'date_range' => $dateRange,
            'generated_at' => date('Y-m-d H:i:s'),
            'data' => $this->getPrescriptionAnalytics($dateRange)
        ];

        return ['success' => true, 'report' => $data];
    }

    private function generateRoomUtilizationReport(array $filters): array
    {
        $dateRange = $this->getDateRange($filters);
        
        $data = [
            'report_title' => 'Room Utilization Report',
            'date_range' => $dateRange,
            'generated_at' => date('Y-m-d H:i:s'),
            'data' => $this->getRoomAnalytics($dateRange)
        ];

        return ['success' => true, 'report' => $data];
    }

    // Missing methods implementation
    private function getDoctorSatisfactionStats(int $doctorId, array $dateRange): array
    {
        // Placeholder implementation - can be enhanced with actual satisfaction data
        return [
            'average_rating' => 4.5,
            'total_reviews' => 0,
            'satisfaction_percentage' => 85
        ];
    }

    private function getDoctorMonthlyPerformance(int $doctorId, array $dateRange): array
    {
        try {
            // Get appointments per month
            $appointmentsPerMonth = [];
            if ($this->db->tableExists('appointments')) {
                $appointmentsPerMonth = $this->db->table('appointments')
                    ->select('DATE_FORMAT(appointment_date, "%Y-%m") as month, COUNT(*) as count')
                    ->where('doctor_id', $doctorId)
                    ->where('appointment_date >=', $dateRange['start'])
                    ->where('appointment_date <=', $dateRange['end'])
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get()
                    ->getResultArray();
            }

            // Get revenue per month
            $revenuePerMonth = [];
            if ($this->db->tableExists('payments') && $this->db->tableExists('bills')) {
                if ($this->db->fieldExists('doctor_id', 'bills')) {
                    $revenuePerMonth = $this->db->table('payments p')
                        ->select('DATE_FORMAT(p.payment_date, "%Y-%m") as month, SUM(p.amount) as revenue')
                        ->join('bills b', 'b.bill_id = p.bill_id', 'inner')
                        ->where('b.doctor_id', $doctorId)
                        ->where('p.payment_date >=', $dateRange['start'])
                        ->where('p.payment_date <=', $dateRange['end'])
                        ->where('p.status', 'completed')
                        ->groupBy('month')
                        ->orderBy('month')
                        ->get()
                        ->getResultArray();
                }
            }

            // Calculate patient growth
            $patientTable = $this->patientTable;
            $dateColumn = $this->db->fieldExists('date_registered', $patientTable) ? 'date_registered' : 'created_at';
            
            $currentPeriodPatients = 0;
            $previousPeriodPatients = 0;
            
            if ($this->db->fieldExists('primary_doctor_id', $patientTable)) {
                $currentPeriodPatients = $this->db->table($patientTable)
                    ->where('primary_doctor_id', $doctorId)
                    ->where($dateColumn . ' >=', $dateRange['start'])
                    ->where($dateColumn . ' <=', $dateRange['end'])
                    ->countAllResults();

                $previousPeriodStart = date('Y-m-d', strtotime($dateRange['start'] . ' -' . (strtotime($dateRange['end']) - strtotime($dateRange['start'])) . ' days'));
                $previousPeriodPatients = $this->db->table($patientTable)
                    ->where('primary_doctor_id', $doctorId)
                    ->where($dateColumn . ' >=', $previousPeriodStart)
                    ->where($dateColumn . ' <', $dateRange['start'])
                    ->countAllResults();
            }

            $patientGrowth = $previousPeriodPatients > 0 
                ? round((($currentPeriodPatients - $previousPeriodPatients) / $previousPeriodPatients) * 100, 2)
                : ($currentPeriodPatients > 0 ? 100 : 0);

            return [
                'appointments_per_month' => $appointmentsPerMonth,
                'revenue_per_month' => $revenuePerMonth,
                'patient_growth' => $patientGrowth,
                'current_period_patients' => $currentPeriodPatients,
                'previous_period_patients' => $previousPeriodPatients
            ];
        } catch (\Exception $e) {
            return [
                'appointments_per_month' => [],
                'revenue_per_month' => [],
                'patient_growth' => 0
            ];
        }
    }

    private function getNursePatientStats(int $nurseId, array $dateRange): array
    {
        // Nurses can see all patients (view_all permission)
        try {
            $patientTable = $this->patientTable;
            
            $totalPatients = $this->db->table($patientTable)->countAllResults();

            $activePatients = 0;
            if ($this->db->fieldExists('status', $patientTable)) {
                $activePatients = $this->db->table($patientTable)
                    ->where('status', 'Active')
                    ->countAllResults();
            } else {
                $activePatients = $totalPatients;
            }

            return [
                'total' => $totalPatients,
                'active' => $activePatients
            ];
        } catch (\Exception $e) {
            return ['total' => 0, 'active' => 0];
        }
    }

    private function getMedicationTrackingStats(int $nurseId, array $dateRange): array
    {
        try {
            // Nurses can see all prescriptions (view_all permission)
            // Count prescriptions by status
            $administered = 0;
            $pending = 0;
            $scheduled = 0;

            if ($this->db->tableExists('prescriptions')) {
                // Get all prescriptions (no department filtering)
                $prescriptions = $this->db->table('prescriptions p')
                    ->where('p.created_at >=', $dateRange['start'])
                    ->where('p.created_at <=', $dateRange['end'])
                    ->get()
                    ->getResultArray();

                foreach ($prescriptions as $prescription) {
                    $status = strtolower($prescription['status'] ?? '');
                    if ($status === 'completed' || $status === 'dispensed') {
                        $administered++;
                    } elseif ($status === 'pending' || $status === 'active') {
                        $pending++;
                    } else {
                        $scheduled++;
                    }
                }
            }

            return [
                'administered' => $administered,
                'pending' => $pending,
                'scheduled' => $scheduled,
                'total' => $administered + $pending + $scheduled
            ];
        } catch (\Exception $e) {
            return [
                'administered' => 0,
                'pending' => 0,
                'scheduled' => 0,
                'total' => 0
            ];
        }
    }

    private function getShiftAnalytics(int $nurseId, array $dateRange): array
    {
        try {
            if (!$this->db->tableExists('shifts')) {
                return [
                    'hours_worked' => 0,
                    'shifts_completed' => 0,
                    'overtime_hours' => 0
                ];
            }

            // Get shifts for the nurse in the date range
            $shifts = $this->db->table('shifts')
                ->where('staff_id', $nurseId)
                ->where('shift_date >=', $dateRange['start'])
                ->where('shift_date <=', $dateRange['end'])
                ->get()
                ->getResultArray();

            $hoursWorked = 0;
            $shiftsCompleted = 0;
            $overtimeHours = 0;

            foreach ($shifts as $shift) {
                if (isset($shift['start_time']) && isset($shift['end_time'])) {
                    $start = strtotime($shift['start_time']);
                    $end = strtotime($shift['end_time']);
                    $hours = ($end - $start) / 3600;
                    $hoursWorked += $hours;
                    
                    if (isset($shift['status']) && $shift['status'] === 'completed') {
                        $shiftsCompleted++;
                    }

                    // Calculate overtime (assuming 8 hours is standard)
                    if ($hours > 8) {
                        $overtimeHours += ($hours - 8);
                    }
                }
            }

            return [
                'hours_worked' => round($hoursWorked, 2),
                'shifts_completed' => $shiftsCompleted,
                'overtime_hours' => round($overtimeHours, 2),
                'total_shifts' => count($shifts)
            ];
        } catch (\Exception $e) {
            return [
                'hours_worked' => 0,
                'shifts_completed' => 0,
                'overtime_hours' => 0
            ];
        }
    }

    private function getRegistrationStats(array $dateRange): array
    {
        try {
            $patientTable = $this->patientTable;
            $dateColumn = $this->db->fieldExists('date_registered', $patientTable) ? 'date_registered' : 'created_at';
            
            $newRegistrations = $this->db->table($patientTable)
                ->where($dateColumn . ' >=', $dateRange['start'])
                ->where($dateColumn . ' <=', $dateRange['end'])
                ->countAllResults();

            $todayRegistrations = $this->db->table($patientTable)
                ->where('DATE(' . $dateColumn . ')', date('Y-m-d'))
                ->countAllResults();

            return [
                'new_registrations' => $newRegistrations,
                'total_today' => $todayRegistrations,
                'period_total' => $newRegistrations
            ];
        } catch (\Exception $e) {
            return ['new_registrations' => 0, 'total_today' => 0];
        }
    }

    private function getBookingStats(array $dateRange): array
    {
        try {
            $bookedToday = $this->db->table('appointments')
                ->where('DATE(created_at)', date('Y-m-d'))
                ->countAllResults();

            $cancelledToday = $this->db->table('appointments')
                ->where('DATE(updated_at)', date('Y-m-d'))
                ->where('status', 'cancelled')
                ->countAllResults();

            return [
                'booked_today' => $bookedToday,
                'cancelled_today' => $cancelledToday,
                'net_bookings' => $bookedToday - $cancelledToday
            ];
        } catch (\Exception $e) {
            return ['booked_today' => 0, 'cancelled_today' => 0];
        }
    }

    private function getDailyActivityStats(array $dateRange): array
    {
        try {
            // Count various activities
            $appointments = $this->db->table('appointments')
                ->where('appointment_date >=', $dateRange['start'])
                ->where('appointment_date <=', $dateRange['end'])
                ->countAllResults();

            $patientTable = $this->patientTable;
            $dateColumn = $this->db->fieldExists('date_registered', $patientTable) ? 'date_registered' : 'created_at';
            
            $registrations = $this->db->table($patientTable)
                ->where($dateColumn . ' >=', $dateRange['start'])
                ->where($dateColumn . ' <=', $dateRange['end'])
                ->countAllResults();

            $labOrders = $this->db->tableExists('lab_orders') 
                ? $this->db->table('lab_orders')
                    ->where('created_at >=', $dateRange['start'])
                    ->where('created_at <=', $dateRange['end'])
                    ->countAllResults()
                : 0;

            // Get peak hour from appointments
            $peakHour = '10:00';
            if ($this->db->tableExists('appointments') && $this->db->fieldExists('appointment_time', 'appointments')) {
                try {
                    $hourlyData = $this->db->table('appointments')
                        ->select('HOUR(appointment_time) as hour, COUNT(*) as count')
                        ->where('appointment_date >=', $dateRange['start'])
                        ->where('appointment_date <=', $dateRange['end'])
                        ->groupBy('HOUR(appointment_time)')
                        ->orderBy('count', 'DESC')
                        ->limit(1)
                        ->get()
                        ->getRow();
                    
                    if ($hourlyData && isset($hourlyData->hour)) {
                        $peakHour = sprintf('%02d:00', $hourlyData->hour);
                    }
                } catch (\Exception $e) {
                    log_message('error', 'Error getting peak hour: ' . $e->getMessage());
                }
            }

            return [
                'total_activities' => $appointments + $registrations + $labOrders,
                'peak_hour' => $peakHour,
                'busiest_day' => date('Y-m-d'),
                'appointments' => $appointments,
                'registrations' => $registrations,
                'lab_orders' => $labOrders
            ];
        } catch (\Exception $e) {
            return [
                'total_activities' => 0,
                'peak_hour' => '10:00',
                'busiest_day' => date('Y-m-d')
            ];
        }
    }

    /**
     * Get lab test analytics
     */
    private function getLabAnalytics(array $dateRange): array
    {
        if (!$this->db->tableExists('lab_orders')) {
            return [
                'total_orders' => 0,
                'orders_by_status' => [],
                'orders_by_category' => [],
                'revenue' => 0
            ];
        }

        try {
            $totalOrders = $this->db->table('lab_orders')
                ->where('created_at >=', $dateRange['start'])
                ->where('created_at <=', $dateRange['end'])
                ->countAllResults();

            $ordersByStatus = $this->db->table('lab_orders')
                ->select('status, COUNT(*) as count')
                ->where('created_at >=', $dateRange['start'])
                ->where('created_at <=', $dateRange['end'])
                ->groupBy('status')
                ->get()
                ->getResultArray();

            // Get orders by test category if available
            $ordersByCategory = [];
            if ($this->db->tableExists('lab_tests')) {
                $ordersByCategory = $this->db->table('lab_orders lo')
                    ->select('lt.category, COUNT(*) as count')
                    ->join('lab_tests lt', 'lt.test_code = lo.test_code', 'left')
                    ->where('lo.created_at >=', $dateRange['start'])
                    ->where('lo.created_at <=', $dateRange['end'])
                    ->groupBy('lt.category')
                    ->get()
                    ->getResultArray();
            }

            // Calculate lab revenue from billing items
            $labRevenue = 0;
            if ($this->db->tableExists('billing_items')) {
                $labRevenue = $this->db->table('billing_items')
                    ->selectSum('unit_price')
                    ->where('item_type', 'lab_test')
                    ->where('created_at >=', $dateRange['start'])
                    ->where('created_at <=', $dateRange['end'])
                    ->get()
                    ->getRow()
                    ->unit_price ?? 0;
            }

            return [
                'total_orders' => $totalOrders,
                'orders_by_status' => $ordersByStatus,
                'orders_by_category' => $ordersByCategory,
                'revenue' => (float)$labRevenue
            ];
        } catch (\Exception $e) {
            return [
                'total_orders' => 0,
                'orders_by_status' => [],
                'orders_by_category' => [],
                'revenue' => 0
            ];
        }
    }

    /**
     * Get prescription analytics
     */
    private function getPrescriptionAnalytics(array $dateRange): array
    {
        if (!$this->db->tableExists('prescriptions')) {
            return [
                'total_prescriptions' => 0,
                'prescriptions_by_status' => [],
                'prescriptions_by_priority' => [],
                'revenue' => 0
            ];
        }

        try {
            $totalPrescriptions = $this->db->table('prescriptions')
                ->where('date_issued >=', $dateRange['start'])
                ->where('date_issued <=', $dateRange['end'])
                ->countAllResults();

            $prescriptionsByStatus = $this->db->table('prescriptions')
                ->select('status, COUNT(*) as count')
                ->where('date_issued >=', $dateRange['start'])
                ->where('date_issued <=', $dateRange['end'])
                ->groupBy('status')
                ->get()
                ->getResultArray();

            $prescriptionsByPriority = [];
            if ($this->db->fieldExists('priority', 'prescriptions')) {
                $prescriptionsByPriority = $this->db->table('prescriptions')
                    ->select('priority, COUNT(*) as count')
                    ->where('date_issued >=', $dateRange['start'])
                    ->where('date_issued <=', $dateRange['end'])
                    ->groupBy('priority')
                    ->get()
                    ->getResultArray();
            }

            // Calculate prescription revenue
            $prescriptionRevenue = 0;
            if ($this->db->tableExists('billing_items')) {
                $prescriptionRevenue = $this->db->table('billing_items')
                    ->selectSum('unit_price')
                    ->where('item_type', 'prescription')
                    ->where('created_at >=', $dateRange['start'])
                    ->where('created_at <=', $dateRange['end'])
                    ->get()
                    ->getRow()
                    ->unit_price ?? 0;
            }

            return [
                'total_prescriptions' => $totalPrescriptions,
                'prescriptions_by_status' => $prescriptionsByStatus,
                'prescriptions_by_priority' => $prescriptionsByPriority,
                'revenue' => (float)$prescriptionRevenue
            ];
        } catch (\Exception $e) {
            return [
                'total_prescriptions' => 0,
                'prescriptions_by_status' => [],
                'prescriptions_by_priority' => [],
                'revenue' => 0
            ];
        }
    }

    /**
     * Get room utilization analytics
     */
    private function getRoomAnalytics(array $dateRange): array
    {
        if (!$this->db->tableExists('inpatient_room_assignments')) {
            return [
                'total_rooms' => 0,
                'occupied_rooms' => 0,
                'occupancy_rate' => 0,
                'rooms_by_type' => []
            ];
        }

        try {
            // Get total rooms if rooms table exists
            $totalRooms = 0;
            if ($this->db->tableExists('rooms')) {
                $totalRooms = $this->db->table('rooms')
                    ->where('status', 'available')
                    ->orWhere('status', 'occupied')
                    ->countAllResults();
            }

            // Get currently occupied rooms
            $occupiedRooms = $this->db->table('inpatient_room_assignments ra')
                ->join('inpatient_admissions a', 'a.admission_id = ra.admission_id', 'inner')
                ->where('a.discharge_date', null)
                ->countAllResults();

            $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 2) : 0;

            // Get rooms by type
            $roomsByType = $this->db->table('inpatient_room_assignments ra')
                ->select('ra.room_type, COUNT(*) as count')
                ->join('inpatient_admissions a', 'a.admission_id = ra.admission_id', 'inner')
                ->where('a.discharge_date', null)
                ->groupBy('ra.room_type')
                ->get()
                ->getResultArray();

            return [
                'total_rooms' => $totalRooms,
                'occupied_rooms' => $occupiedRooms,
                'occupancy_rate' => $occupancyRate,
                'rooms_by_type' => $roomsByType
            ];
        } catch (\Exception $e) {
            return [
                'total_rooms' => 0,
                'occupied_rooms' => 0,
                'occupancy_rate' => 0,
                'rooms_by_type' => []
            ];
        }
    }

    /**
     * Get resource/equipment utilization analytics
     */
    private function getResourceAnalytics(array $dateRange): array
    {
        if (!$this->db->tableExists('resources')) {
            return [
                'total_resources' => 0,
                'resources_by_status' => [],
                'resources_by_category' => []
            ];
        }

        try {
            $totalResources = $this->db->table('resources')->countAllResults();

            $resourcesByStatus = $this->db->table('resources')
                ->select('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->getResultArray();

            $resourcesByCategory = $this->db->table('resources')
                ->select('category, COUNT(*) as count')
                ->groupBy('category')
                ->get()
                ->getResultArray();

            return [
                'total_resources' => $totalResources,
                'resources_by_status' => $resourcesByStatus,
                'resources_by_category' => $resourcesByCategory
            ];
        } catch (\Exception $e) {
            return [
                'total_resources' => 0,
                'resources_by_status' => [],
                'resources_by_category' => []
            ];
        }
    }

}