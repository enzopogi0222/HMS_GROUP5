k<?php

/**
 * Sidebar Helper Functions
 * Helper functions for unified sidebar integration
 */

if (!function_exists('render_unified_sidebar')) {
    /**
     * Render the unified sidebar component
     * 
     * @return string
     */
    function render_unified_sidebar()
    {
        return view('unified/components/sidebar');
    }
}

if (!function_exists('get_sidebar_for_role')) {
    /**
     * Get appropriate sidebar based on user role
     * Returns unified sidebar for all roles
     * 
     * @param string $userRole
     * @return string
     */
    function get_sidebar_for_role($userRole = null)
    {
        // Always return unified sidebar
        return view('unified/components/sidebar');
    }
}

if (!function_exists('is_sidebar_item_visible')) {
    /**
     * Check if a sidebar item should be visible for the current user role
     * 
     * @param string $item
     * @param string $userRole
     * @return bool
     */
    function is_sidebar_item_visible($item, $userRole = null)
    {
        if (!$userRole) {
            $userRole = session()->get('role') ?? 'guest';
        }

        $itemPermissions = [
            'dashboard' => ['admin', 'doctor', 'nurse', 'receptionist', 'pharmacist', 'laboratorist', 'accountant', 'it_staff'],
            'patients' => ['admin', 'doctor', 'nurse', 'receptionist', 'it_staff'],
            'appointments' => ['admin', 'doctor', 'nurse', 'receptionist'],
            'staff' => ['admin', 'doctor', 'nurse', 'receptionist', 'it_staff'],
            'users' => ['admin', 'doctor', 'nurse', 'receptionist', 'it_staff'],
            'medical_records' => ['admin', 'doctor', 'nurse'],
            'prescriptions' => ['admin', 'doctor', 'pharmacist', 'nurse'],
            'laboratory' => ['admin', 'doctor', 'laboratorist', 'nurse'],
            'pharmacy' => ['admin', 'pharmacist', 'doctor'],
            'billing' => ['admin', 'accountant', 'receptionist'],
            'reports' => ['admin', 'doctor', 'accountant', 'it_staff'],
            'settings' => ['admin', 'it_staff'],
            'resources' => ['admin', 'it_staff']
        ];

        return isset($itemPermissions[$item]) && in_array($userRole, $itemPermissions[$item]);
    }
}

if (!function_exists('get_sidebar_url_for_role')) {
    /**
     * Get the appropriate URL for a sidebar item based on user role
     * 
     * @param string $item
     * @param string $userRole
     * @return string
     */
    function get_sidebar_url_for_role($item, $userRole = null)
    {
        if (!$userRole) {
            $userRole = session()->get('role') ?? 'admin';
        }

        $urlMappings = [
            'dashboard' => [
                'admin' => 'admin/dashboard',
                'doctor' => 'doctor/dashboard',
                'nurse' => 'nurse/dashboard',
                'receptionist' => 'receptionist/dashboard',
                'pharmacist' => 'pharmacist/dashboard',
                'laboratorist' => 'laboratorist/dashboard',
                'accountant' => 'accountant/dashboard',
                'it_staff' => 'it-staff/dashboard'
            ],
            'patients' => [
                'admin' => 'admin/patient-management',
                'doctor' => 'doctor/patients',
                'nurse' => 'nurse/patients',
                'receptionist' => 'receptionist/patients',
                'it_staff' => 'it-staff/patients'
            ],
            'appointments' => [
                'admin' => 'admin/appointments',
                'doctor' => 'doctor/appointments',
                'nurse' => 'nurse/appointments',
                'receptionist' => 'receptionist/appointments'
            ],
            'staff' => [
                'admin' => 'admin/staff-management',
                'doctor' => 'doctor/staff',
                'nurse' => 'nurse/staff',
                'receptionist' => 'receptionist/staff',
                'it_staff' => 'it-staff/staff'
            ],
            'users' => [
                'admin' => 'admin/user-management',
                'doctor' => 'doctor/users',
                'nurse' => 'nurse/users',
                'receptionist' => 'receptionist/users',
                'it_staff' => 'it-staff/users'
            ]
        ];

        if (isset($urlMappings[$item][$userRole])) {
            return $urlMappings[$item][$userRole];
        }

        // Default fallback
        return $item;
    }
}
