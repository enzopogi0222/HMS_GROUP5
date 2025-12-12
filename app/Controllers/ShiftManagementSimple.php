<?php

namespace App\Controllers;

use App\Services\ShiftService;
use App\Libraries\PermissionManager;

class ShiftManagementSimple
{
    protected $shiftService;
    protected $permissionManager;
    protected $userRole;
    protected $staffId;

    public function __construct()
    {
        try {
            log_message('debug', 'ShiftManagementSimple::__construct starting');
            
            // Load required services and libraries
            $this->shiftService = new ShiftService();
            log_message('debug', 'ShiftManagementSimple::__construct - ShiftService created');
            
            $this->permissionManager = new PermissionManager();
            log_message('debug', 'ShiftManagementSimple::__construct - PermissionManager created');
            
            // Get user session data
            $session = session();
            $this->userRole = $session->get('role');
            $this->staffId = $session->get('staff_id');
            
            log_message('debug', 'ShiftManagementSimple::__construct called for user role: ' . $this->userRole);
            
        } catch (\Throwable $e) {
            log_message('error', 'ShiftManagementSimple::__construct error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function index()
    {
        try {
            log_message('debug', 'ShiftManagementSimple::index method called');
            
            // Get role-specific data
            $shifts = $this->shiftService->getShiftsByRole($this->userRole, $this->staffId);
            $stats = $this->shiftService->getShiftStats($this->userRole, $this->staffId);
            $availableStaff = $this->getAvailableStaffForRole();
            
            log_message('debug', 'ShiftManagementSimple::index - availableStaff count: ' . count($availableStaff));
            
            $data = [
                'title' => 'Shift Management',
                'shifts' => $shifts,
                'stats' => $stats,
                'availableStaff' => $availableStaff,
                'userRole' => $this->userRole,
                'permissions' => ['canView' => true, 'canCreate' => true],
                'pageConfig' => ['title' => 'Shift Management'],
                'departments' => [],
                'shiftTypes' => [],
                'roomsWards' => []
            ];

            return view('unified/shift-management', $data);

        } catch (\Throwable $e) {
            log_message('error', 'ShiftManagementSimple::index error: ' . $e->getMessage());
            return redirect()->to('/dashboard')->with('error', 'Failed to load shift management');
        }
    }

    private function getAvailableStaffForRole()
    {
        try {
            return $this->shiftService->getAvailableStaff();
        } catch (\Throwable $e) {
            log_message('error', 'getAvailableStaffForRole error: ' . $e->getMessage());
            return [];
        }
    }
}
