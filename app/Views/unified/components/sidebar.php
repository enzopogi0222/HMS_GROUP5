<?php
use App\Libraries\PermissionManager;

// Get user role and permissions
$userRole = session()->get('role') ?? 'guest';
$currentUri = uri_string();

// Define module-based navigation items with permission checks
$navigationItems = [];

// Dashboard - always available for authenticated users
$dashboardUrl = $userRole . '/dashboard';
if ($userRole === 'it_staff') {
    $dashboardUrl = 'it-staff/dashboard';
}
$navigationItems[] = ['url' => $dashboardUrl, 'icon' => 'fas fa-tachometer-alt', 'label' => 'Dashboard', 'module' => null];

// Define module mappings with their URLs and icons
$moduleMappings = [
    'patients' => [
        'admin' => ['url' => 'admin/patient-management', 'icon' => 'fas fa-user-injured', 'label' => 'Patient Management'],
        'doctor' => ['url' => 'doctor/patient-management', 'icon' => 'fas fa-user-injured', 'label' => 'Patient Management'],
        'nurse' => ['url' => 'nurse/patients', 'icon' => 'fas fa-users', 'label' => 'Patients'],
        'receptionist' => ['url' => 'receptionist/patients', 'icon' => 'fas fa-users', 'label' => 'Patient Registration'],
        'it_staff' => ['url' => 'it-staff/patients', 'icon' => 'fas fa-user-injured', 'label' => 'Patient Management'],
    ],
    'appointments' => [
        'admin' => ['url' => 'admin/appointments', 'icon' => 'fas fa-calendar-check', 'label' => 'Appointments'],
        'doctor' => ['url' => 'doctor/appointments', 'icon' => 'fas fa-calendar-check', 'label' => 'Appointments'],
        'receptionist' => ['url' => 'receptionist/appointments', 'icon' => 'fas fa-calendar-alt', 'label' => 'Appointments'],
        'accountant' => ['url' => 'accountant/appointments', 'icon' => 'fas fa-calendar-check', 'label' => 'Appointments'],
    ],
    'staff' => [
        'admin' => ['url' => 'admin/staff-management', 'icon' => 'fas fa-user-tie', 'label' => 'Staff Management'],
        'it_staff' => ['url' => 'it-staff/staff', 'icon' => 'fas fa-user-tie', 'label' => 'Staff Management'],
    ],
    'users' => [
        'admin' => ['url' => 'admin/user-management', 'icon' => 'fas fa-users', 'label' => 'User Management'],
        'it_staff' => ['url' => 'it-staff/users', 'icon' => 'fas fa-users', 'label' => 'User Management'],
    ],
    'resources' => [
        'admin' => ['url' => 'admin/resource-management', 'icon' => 'fas fa-hospital', 'label' => 'Resource Management'],
        'doctor' => ['url' => 'admin/resource-management', 'icon' => 'fas fa-hospital', 'label' => 'Resources'],
        'pharmacist' => ['url' => 'pharmacist/resource-management', 'icon' => 'fas fa-pills', 'label' => 'Pharmacy Inventory'],
        'laboratorist' => ['url' => 'laboratorist/resource-management', 'icon' => 'fas fa-flask', 'label' => 'Lab Resources'],
        'it_staff' => ['url' => 'admin/resource-management', 'icon' => 'fas fa-hospital', 'label' => 'Resource Management'],
    ],
    'shifts' => [
        'admin' => ['url' => 'admin/schedule', 'icon' => 'fas fa-calendar-days', 'label' => 'Schedule Management'],
        'doctor' => ['url' => 'doctor/schedule', 'icon' => 'fas fa-calendar-days', 'label' => 'My Schedule'],
        
    ],
    'prescriptions' => [
        'admin' => ['url' => 'admin/prescriptions', 'icon' => 'fas fa-prescription-bottle', 'label' => 'Prescriptions'],
        'doctor' => ['url' => 'doctor/prescriptions', 'icon' => 'fas fa-prescription-bottle', 'label' => 'Prescriptions'],
        'pharmacist' => ['url' => 'pharmacist/prescriptions', 'icon' => 'fas fa-prescription-bottle', 'label' => 'Prescriptions'],
    ],
    'reports' => [
        'admin' => ['url' => 'admin/analytics', 'icon' => 'fas fa-chart-bar', 'label' => 'Analytics & Reports'],
        'pharmacist' => ['url' => 'pharmacist/reports', 'icon' => 'fas fa-chart-bar', 'label' => 'Reports'],
        'it_staff' => ['url' => 'admin/analytics', 'icon' => 'fas fa-chart-bar', 'label' => 'Analytics & Reports'],
    ],
    'billing' => [
        'admin' => ['url' => 'admin/financial-management', 'icon' => 'fas fa-dollar-sign', 'label' => 'Billing Management'],
    ],
    'system' => [
        'admin' => ['url' => 'admin/system-settings', 'icon' => 'fas fa-cogs', 'label' => 'System Settings'],
        'it_staff' => ['url' => 'it-staff/system-settings', 'icon' => 'fas fa-cogs', 'label' => 'System Settings'],
    ],
];

// Additional modules that may not be in PermissionManager but are in the system
$additionalModules = [
    'labs' => [
        'admin' => ['url' => 'admin/labs', 'icon' => 'fas fa-flask', 'label' => 'Labs'],
        'doctor' => ['url' => 'doctor/labs', 'icon' => 'fas fa-flask', 'label' => 'Lab Orders'],
        'laboratorist' => ['url' => 'laboratorist/labs', 'icon' => 'fas fa-flask', 'label' => 'Lab Worklist'],
    ],
    'departments' => [
        'admin' => ['url' => 'admin/department-management', 'icon' => 'fas fa-building', 'label' => 'Department Management'],
    ],
    'rooms' => [
        'admin' => ['url' => 'admin/room-management', 'icon' => 'fas fa-bed', 'label' => 'Room Management'],
    ],
    'financial' => [
        'accountant' => ['url' => 'accountant/financial', 'icon' => 'fas fa-dollar-sign', 'label' => 'Billing Reports'],
    ],
    'patient_records' => [
        'admin' => ['url' => 'unified/patient-records', 'icon' => 'fas fa-folder-open', 'label' => 'Patient Records'],
        'doctor' => ['url' => 'unified/patient-records', 'icon' => 'fas fa-folder-open', 'label' => 'Patient Records (EHR)'],
        'nurse' => ['url' => 'unified/patient-records', 'icon' => 'fas fa-folder-open', 'label' => 'Patient Records'],
    ],
];

// Check permissions and build navigation items
// Special handling for it_staff - only show Dashboard and User Management
if ($userRole === 'it_staff') {
    // Only add User Management for it_staff
    if (isset($moduleMappings['users']['it_staff'])) {
        $navigationItems[] = array_merge($moduleMappings['users']['it_staff'], ['module' => 'users']);
    }
} else {
    // For all other roles, check permissions normally
    foreach ($moduleMappings as $module => $roleMappings) {
        // For nurses, exclude 'view_assigned' from permission checks
        // For other roles, include all view permissions
        // For vital_signs, check for create permission instead
        if ($module === 'vital_signs') {
            $permissionActions = ['create'];
        } elseif ($userRole === 'nurse') {
            // For nurses, only check for 'view' permission, NOT 'view_assigned'
            $permissionActions = ['view', 'view_all', 'view_own'];
        } else {
            $permissionActions = ['view', 'view_all', 'view_assigned', 'view_own'];
        }
        
        if (PermissionManager::hasAnyPermission($userRole, $module, $permissionActions)) {
            if (isset($roleMappings[$userRole])) {
                $navigationItems[] = array_merge($roleMappings[$userRole], ['module' => $module]);
            }
        }
    }
}

// Add additional modules (these may not have explicit permissions in PermissionManager)
// Skip for it_staff - they only see Dashboard and User Management
if ($userRole !== 'it_staff') {
    // Labs - available if user has patients or prescriptions permission (nurses excluded from prescriptions check)
    $labsPermissionActions = ($userRole === 'nurse') ? ['view', 'view_all', 'view_own'] : ['view', 'view_all', 'view_assigned', 'view_own'];
    if ($userRole === 'nurse') {
        // Nurses can access labs if they have patients permission (no prescriptions check)
        if (PermissionManager::hasAnyPermission($userRole, 'patients', $labsPermissionActions)) {
            if (isset($additionalModules['labs'][$userRole])) {
                $navigationItems[] = array_merge($additionalModules['labs'][$userRole], ['module' => 'labs']);
            }
        }
    } else {
        // Other roles can access labs if they have patients or prescriptions permission
        if (PermissionManager::hasAnyPermission($userRole, 'patients', $labsPermissionActions) || 
            PermissionManager::hasAnyPermission($userRole, 'prescriptions', $labsPermissionActions)) {
            if (isset($additionalModules['labs'][$userRole])) {
                $navigationItems[] = array_merge($additionalModules['labs'][$userRole], ['module' => 'labs']);
            }
        }
    }

    // Departments - typically admin only, but check if user has staff or resources permission
    $deptPermissionActions = ($userRole === 'nurse') ? ['view', 'view_all'] : ['view', 'view_all', 'view_assigned'];
    if (PermissionManager::hasAnyPermission($userRole, 'staff', $deptPermissionActions) || 
        PermissionManager::hasAnyPermission($userRole, 'resources', $deptPermissionActions)) {
        if (isset($additionalModules['departments'][$userRole])) {
            $navigationItems[] = array_merge($additionalModules['departments'][$userRole], ['module' => 'departments']);
        }
    }

    // Rooms - typically admin only, but check if user has resources permission
    $roomsPermissionActions = ($userRole === 'nurse') ? ['view', 'view_all'] : ['view', 'view_all', 'view_assigned'];
    if (PermissionManager::hasAnyPermission($userRole, 'resources', $roomsPermissionActions)) {
        if (isset($additionalModules['rooms'][$userRole])) {
            $navigationItems[] = array_merge($additionalModules['rooms'][$userRole], ['module' => 'rooms']);
        }
    }

    // Inventory - for pharmacists (they have prescriptions permission)
    $inventoryPermissionActions = ($userRole === 'nurse') ? ['view', 'view_all', 'fulfill'] : ['view', 'view_all', 'view_assigned', 'fulfill'];
    if (PermissionManager::hasAnyPermission($userRole, 'prescriptions', $inventoryPermissionActions)) {
        if (isset($additionalModules['inventory'][$userRole])) {
            $navigationItems[] = array_merge($additionalModules['inventory'][$userRole], ['module' => 'inventory']);
        }
    }

    // Financial reports - for accountants (they have billing permission)
    if (PermissionManager::hasAnyPermission($userRole, 'billing', ['view', 'create', 'edit', 'process'])) {
        if (isset($additionalModules['financial'][$userRole])) {
            $navigationItems[] = array_merge($additionalModules['financial'][$userRole], ['module' => 'financial']);
        }
    }

    // Patient Records - available if user has patients permission (excluding view_assigned for nurses)
    $patientRecordsPermissionActions = ($userRole === 'nurse') ? ['view', 'view_all', 'view_own'] : ['view', 'view_all', 'view_assigned', 'view_own'];
    if (PermissionManager::hasAnyPermission($userRole, 'patients', $patientRecordsPermissionActions)) {
        if (isset($additionalModules['patient_records'][$userRole])) {
            $navigationItems[] = array_merge($additionalModules['patient_records'][$userRole], ['module' => 'patient_records']);
        }
    }
}
?>

<nav class="sidebar">
    <ul class="nav-menu">
        <?php foreach ($navigationItems as $item): ?>
            <li class="nav-item">
                <a href="<?= base_url($item['url']) ?>" 
                   class="nav-link <?= ($currentUri === $item['url']) ? 'active' : '' ?>">
                    <i class="<?= $item['icon'] ?> nav-icon"></i>
                    <?= $item['label'] ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
