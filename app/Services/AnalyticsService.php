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
     * Generate a specific report based on type and filters
     */
    public function generateReport(string $reportType, string $userRole, ?int $userId = null, array $filters = []): array
    {
        if (empty($reportType)) {
            return [
                'success' => false,
                'message' => 'Report type is required',
            ];
        }

        try {
            $dateRange = $this->getDateRange($filters);
            $reportData = [];

            switch ($reportType) {
                case 'patient_summary':
                    $reportData = $this->getPatientAnalytics($dateRange);
                    break;

                case 'appointment_summary':
                    $reportData = $this->getAppointmentAnalytics($dateRange);
                    break;

                case 'financial_summary':
                    $reportData = $this->getFinancialAnalytics($dateRange);
                    break;

                case 'lab_summary':
                    $reportData = $this->getLabAnalytics($dateRange);
                    break;

                case 'prescription_summary':
                    $reportData = $this->getPrescriptionAnalytics($dateRange);
                    break;

                case 'staff_performance':
                    $reportData = $this->getStaffAnalytics($dateRange);
                    break;

                case 'room_utilization':
                    $reportData = $this->getRoomAnalytics($dateRange);
                    break;

                case 'doctor_performance':
                    // For doctor performance reports we need the current doctor id
                    if ($userRole !== 'doctor' || !$userId) {
                        return [
                            'success' => false,
                            'message' => 'Doctor performance report is only available for logged-in doctors',
                        ];
                    }
                    $reportData = $this->getDoctorAnalytics($userId, $filters);
                    break;

                default:
                    return [
                        'success' => false,
                        'message' => 'Unsupported report type: ' . $reportType,
                    ];
            }

            return [
                'success' => true,
                'message' => 'Report generated successfully',
                'report' => [
                    'type' => $reportType,
                    'filters' => $dateRange,
                    'data' => $reportData,
                ],
            ];
        } catch (\Throwable $e) {
            log_message('error', 'AnalyticsService::generateReport error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to generate report. Please try again later.',
            ];
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
     * Normalize date range filters for analytics queries
     */
    private function getDateRange(array $filters): array
    {
        $endDate = $filters['end_date'] ?? date('Y-m-d');
        $startDate = $filters['start_date'] ?? date('Y-m-d', strtotime('-30 days'));

        return [
            'start' => $startDate,
            'end'   => $endDate,
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
                // Treat both 'active' and 'Active' as active to match existing data
                $totalStaff = $this->db->table('staff')
                    ->groupStart()
                        ->where('status', 'active')
                        ->orWhere('status', 'Active')
                    ->groupEnd()
                    ->countAllResults();
            } else {
                $totalStaff = $this->db->table('staff')->countAllResults();
            }
            
            $staffByRole = [];
            if ($this->db->fieldExists('role', 'staff')) {
                $query = $this->db->table('staff')->select('role, COUNT(*) as count');
                if ($this->db->fieldExists('status', 'staff')) {
                    // Same case-insensitive handling as above
                    $query->groupStart()
                        ->where('status', 'active')
                        ->orWhere('status', 'Active')
                    ->groupEnd();
                }
                $staffByRole = $query->groupBy('role')
                    ->get()
                    ->getResultArray();
            }

            $staffByDepartment = [];
            if ($this->db->fieldExists('department', 'staff')) {
                $query = $this->db->table('staff')->select('department, COUNT(*) as count');
                if ($this->db->fieldExists('status', 'staff')) {
                    // Same case-insensitive handling as above
                    $query->groupStart()
                        ->where('status', 'active')
                        ->orWhere('status', 'Active')
                    ->groupEnd();
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
            // Prefer ordered_at if present, otherwise fall back to created_at
            $dateColumn = $this->db->fieldExists('ordered_at', 'lab_orders') ? 'ordered_at' : 'created_at';

            $totalOrders = $this->db->table('lab_orders')
                ->where('DATE(' . $dateColumn . ') >=', $dateRange['start'])
                ->where('DATE(' . $dateColumn . ') <=', $dateRange['end'])
                ->countAllResults();

            $ordersByStatus = $this->db->table('lab_orders')
                ->select('status, COUNT(*) as count')
                ->where('DATE(' . $dateColumn . ') >=', $dateRange['start'])
                ->where('DATE(' . $dateColumn . ') <=', $dateRange['end'])
                ->groupBy('status')
                ->get()
                ->getResultArray();

            // Get orders by test category if available
            $ordersByCategory = [];
            if ($this->db->tableExists('lab_tests')) {
                $ordersByCategory = $this->db->table('lab_orders lo')
                    ->select('lt.category, COUNT(*) as count')
                    ->join('lab_tests lt', 'lt.test_code = lo.test_code', 'left')
                    ->where('DATE(lo.' . $dateColumn . ') >=', $dateRange['start'])
                    ->where('DATE(lo.' . $dateColumn . ') <=', $dateRange['end'])
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
            // Use date_issued when available, otherwise fall back to created_at
            $dateColumn = $this->db->fieldExists('date_issued', 'prescriptions') ? 'date_issued' : 'created_at';

            $totalPrescriptions = $this->db->table('prescriptions')
                ->where('DATE(' . $dateColumn . ') >=', $dateRange['start'])
                ->where('DATE(' . $dateColumn . ') <=', $dateRange['end'])
                ->countAllResults();

            $prescriptionsByStatus = $this->db->table('prescriptions')
                ->select('status, COUNT(*) as count')
                ->where('DATE(' . $dateColumn . ') >=', $dateRange['start'])
                ->where('DATE(' . $dateColumn . ') <=', $dateRange['end'])
                ->groupBy('status')
                ->get()
                ->getResultArray();

            $prescriptionsByPriority = [];
            if ($this->db->fieldExists('priority', 'prescriptions')) {
                $prescriptionsByPriority = $this->db->table('prescriptions')
                    ->select('priority, COUNT(*) as count')
                    ->where('DATE(' . $dateColumn . ') >=', $dateRange['start'])
                    ->where('DATE(' . $dateColumn . ') <=', $dateRange['end'])
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
        // Align with actual room assignment table
        if (!$this->db->tableExists('room_assignment')) {
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
                    ->whereIn('status', ['available', 'occupied'])
                    ->countAllResults();
            }

            // Get currently occupied rooms based on active room assignments
            $occupiedRooms = $this->db->table('room_assignment')
                ->where('status', 'active')
                ->where('date_out', null)
                ->countAllResults();

            $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 2) : 0;

            // Get rooms by type via join with rooms table
            $roomsByType = [];
            if ($this->db->tableExists('rooms')) {
                $roomsByType = $this->db->table('room_assignment ra')
                    ->select('r.room_type, COUNT(*) as count')
                    ->join('rooms r', 'r.room_id = ra.room_id', 'inner')
                    ->where('ra.status', 'active')
                    ->where('ra.date_out', null)
                    ->groupBy('r.room_type')
                    ->get()
                    ->getResultArray();
            }

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