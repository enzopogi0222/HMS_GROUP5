<?php

namespace App\Controllers\Unified;

use App\Controllers\BaseController;
use App\Services\DashboardService;

class DashboardController extends BaseController
{
    protected $dashboardService;
    protected $userRole;
    protected $userId;
    protected $staffId;

    public function __construct()
    {
        $this->dashboardService = new DashboardService();
        $this->userRole = session()->get('role');
        $this->userId = session()->get('user_id');
        $this->staffId = session()->get('staff_id');
    }

    /**
     * Main dashboard view
     */
    public function index()
    {
        try {
            // Get role-specific dashboard data
            $dashboardStats = $this->dashboardService->getDashboardStats($this->userRole, $this->staffId);
            $recentActivities = $this->dashboardService->getRecentActivities($this->userRole, $this->staffId);
            $upcomingEvents = $this->dashboardService->getUpcomingEvents($this->userRole, $this->staffId);

            $data = [
                'title' => $this->getDashboardTitle(),
                'userRole' => $this->userRole,
                'dashboardStats' => $dashboardStats,
                'recentActivities' => $recentActivities,
                'upcomingEvents' => $upcomingEvents
            ];

            return view('unified/dashboard', $data);

        } catch (\Exception $e) {
            log_message('error', 'Dashboard error: ' . $e->getMessage());
            
            // Fallback data
            $data = [
                'title' => 'Dashboard',
                'userRole' => $this->userRole ?? 'guest',
                'dashboardStats' => [],
                'recentActivities' => [],
                'upcomingEvents' => []
            ];

            return view('unified/dashboard', $data);
        }
    }

    /**
     * API endpoint for dashboard data
     */
    public function getDashboardData()
    {
        try {
            $stats = $this->dashboardService->getDashboardStats($this->userRole, $this->staffId);
            $activities = $this->dashboardService->getRecentActivities($this->userRole, $this->staffId, 5);
            $events = $this->dashboardService->getUpcomingEvents($this->userRole, $this->staffId, 3);

            return $this->response->setJSON([
                'success' => true,
                'stats' => $stats,
                'recentActivities' => $activities,
                'upcomingEvents' => $events
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Dashboard API error: ' . $e->getMessage());
            
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Failed to load dashboard data'
            ]);
        }
    }

    /**
     * Get system health data (admin only)
     */
    public function getSystemHealth()
    {
        if ($this->userRole !== 'admin') {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Access denied'
            ]);
        }

        try {
            $systemHealth = $this->dashboardService->getSystemHealth();
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $systemHealth
            ]);

        } catch (\Exception $e) {
            log_message('error', 'System health error: ' . $e->getMessage());
            
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Failed to load system health data'
            ]);
        }
    }

    /**
     * Get today's schedule (doctor/nurse)
     */
    public function getTodaySchedule()
    {
        if (!in_array($this->userRole, ['doctor', 'nurse'])) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Access denied'
            ]);
        }

        try {
            $schedule = $this->dashboardService->getTodaySchedule($this->userRole, $this->staffId);
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $schedule
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Today schedule error: ' . $e->getMessage());
            
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Failed to load today schedule'
            ]);
        }
    }

    /**
     * Get role-specific dashboard title
     */
    private function getDashboardTitle()
    {
        $titles = [
            'admin' => 'System Dashboard',
            'doctor' => 'Doctor Dashboard',
            'nurse' => 'Nursing Dashboard',
            'receptionist' => 'Reception Dashboard',
            'accountant' => 'Financial Dashboard',
            'it_staff' => 'IT Dashboard',
            'laboratorist' => 'Laboratory Dashboard',
            'pharmacist' => 'Pharmacy Dashboard'
        ];

        return $titles[$this->userRole] ?? 'Dashboard';
    }

    /**
     * Quick stats API endpoint
     */
    public function getQuickStats()
    {
        try {
            $stats = $this->dashboardService->getQuickStats($this->userRole, $this->staffId);
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Quick stats error: ' . $e->getMessage());
            
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Failed to load quick stats'
            ]);
        }
    }

    /**
     * Update dashboard preferences
     */
    public function updatePreferences()
    {
        try {
            $input = $this->request->getJSON(true);
            
            if (!$input) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Invalid input data'
                ]);
            }

            $result = $this->dashboardService->updateUserPreferences($this->userId, $input);
            
            if ($result) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Preferences updated successfully'
                ]);
            } else {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'Failed to update preferences'
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', 'Update preferences error: ' . $e->getMessage());
            
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Failed to update preferences'
            ]);
        }
    }
}
