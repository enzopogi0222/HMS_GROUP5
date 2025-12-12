<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Services\AnalyticsService;

class AnalyticsManagement extends BaseController
{
    protected $analyticsService;

    public function __construct()
    {
        $this->analyticsService = new AnalyticsService();
    }

    public function index()
    {
        $session = session();
        $userRole = $session->get('role');
        $userId = $session->get('staff_id');

        if (!$userRole) {
            return redirect()->to('/login');
        }

        $permissions = $this->getPermissions($userRole);
        $analytics = $this->analyticsService->getAnalyticsData($userRole, $userId);

        $data = [
            'title' => $this->getPageTitle($userRole),
            'userRole' => $userRole,
            'permissions' => $permissions,
            'analytics' => $analytics
        ];

        return view('unified/analytics-reports', $data);
    }

    public function getAnalyticsAPI()
    {
        $session = session();
        $userRole = $session->get('role');
        $userId = $session->get('staff_id');

        if (!$userRole) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        $filters = $this->request->getGet();
        $analytics = $this->analyticsService->getAnalyticsData($userRole, $userId, $filters);
        
        return $this->response->setJSON(['success' => true, 'data' => $analytics]);
    }

    public function generateReport()
    {
        $session = session();
        $userRole = $session->get('role');
        $userId = $session->get('staff_id');

        $input = $this->request->getJSON(true) ?? $this->request->getPost();
        $reportType = $input['report_type'] ?? '';
        $filters = $input['filters'] ?? [];

        $result = $this->analyticsService->generateReport($reportType, $userRole, $userId, $filters);
        
        return $this->response->setJSON($result);
    }

    private function getPermissions(string $userRole): array
    {
        $permissions = [
            'admin' => ['view', 'generate_reports', 'export', 'view_all', 'advanced_analytics'],
            'accountant' => ['view', 'generate_reports', 'export', 'view_all', 'financial_analytics'],
            'doctor' => ['view', 'generate_reports', 'view_own'],
            'nurse' => ['view', 'view_department'],
            'receptionist' => ['view', 'basic_reports'],
            'it_staff' => ['view', 'generate_reports', 'export', 'view_all', 'system_analytics']
        ];

        return $permissions[$userRole] ?? ['view'];
    }

    private function getPageTitle(string $userRole): string
    {
        switch ($userRole) {
            case 'admin': return 'Analytics & Reports';
            case 'accountant': return 'Financial Analytics';
            case 'doctor': return 'My Performance Analytics';
            case 'nurse': return 'Department Analytics';
            case 'receptionist': return 'Activity Reports';
            default: return 'Analytics Overview';
        }
    }
}