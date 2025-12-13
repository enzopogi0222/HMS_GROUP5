<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ===================================================================
// MAIN ROUTES
// ===================================================================

$routes->get('/', 'Home::index');
$routes->get('about', 'Home::about');
$routes->get('contact', 'Home::contact');

// ===================================================================
// AUTHENTICATION ROUTES
// ===================================================================

$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::login');
$routes->get('logout', 'Auth::logout');

// ===================================================================
// UNIFIED DASHBOARD MANAGEMENT
// ===================================================================

// Dashboard Views - Role-specific entry points
$routes->get('admin/dashboard', 'Unified\DashboardController::index', ['filter' => 'roleauth:admin']);
$routes->get('admin', 'Unified\DashboardController::index', ['filter' => 'roleauth:admin']);
$routes->get('accountant/dashboard', 'Unified\DashboardController::index', ['filter' => 'roleauth:accountant']);
$routes->get('doctor/dashboard', 'Unified\DashboardController::index', ['filter' => 'roleauth:doctor']);
$routes->get('it-staff/dashboard', 'Unified\DashboardController::index', ['filter' => 'roleauth:it_staff']);
$routes->get('laboratorist/dashboard', 'Unified\DashboardController::index', ['filter' => 'roleauth:laboratorist']);
$routes->get('nurse/dashboard', 'Unified\DashboardController::index', ['filter' => 'roleauth:nurse']);
$routes->get('pharmacist/dashboard', 'Unified\DashboardController::index', ['filter' => 'roleauth:pharmacist']);
$routes->get('receptionist/dashboard', 'Unified\DashboardController::index', ['filter' => 'roleauth:receptionist']);

// Dashboard API Routes
$routes->get('api/dashboard-data', 'Unified\DashboardController::getDashboardData', ['filter' => 'roleauth:admin,doctor,nurse,receptionist,accountant,it_staff,laboratorist,pharmacist']);
$routes->get('api/quick-stats', 'Unified\DashboardController::getQuickStats', ['filter' => 'roleauth:admin,doctor,nurse,receptionist,accountant,it_staff,laboratorist,pharmacist']);
$routes->get('api/system-health', 'Unified\DashboardController::getSystemHealth', ['filter' => 'roleauth:admin']);
$routes->get('api/today-schedule', 'Unified\DashboardController::getTodaySchedule', ['filter' => 'roleauth:doctor,nurse']);
$routes->post('api/dashboard-preferences', 'Unified\DashboardController::updatePreferences', ['filter' => 'roleauth:admin,doctor,nurse,receptionist,accountant,it_staff,laboratorist,pharmacist']);

// ===================================================================
// UNIFIED USER MANAGEMENT
// ===================================================================

// User Management Views - Role-specific entry points
$routes->get('admin/user-management', 'UserManagement::index', ['filter' => 'roleauth:admin']);
$routes->get('doctor/users', 'UserManagement::index', ['filter' => 'roleauth:doctor']);
$routes->get('it-staff/users', 'UserManagement::index', ['filter' => 'roleauth:it_staff']);
$routes->get('nurse/users', 'UserManagement::index', ['filter' => 'roleauth:nurse']);
$routes->get('receptionist/users', 'UserManagement::index', ['filter' => 'roleauth:receptionist']);

// User Management API Routes
$routes->get('users/api', 'UserManagement::getUsersAPI', ['filter' => 'roleauth:admin,doctor,nurse,receptionist,it_staff']);
$routes->get('users/(:num)', 'UserManagement::getUser/$1', ['filter' => 'roleauth:admin,doctor,nurse,receptionist,it_staff']);
$routes->get('users/available-staff', 'UserManagement::getAvailableStaffAPI', ['filter' => 'roleauth:admin,it_staff']);
$routes->post('users/create', 'UserManagement::create', ['filter' => 'roleauth:admin,it_staff']);
$routes->post('users/update/(:num)', 'UserManagement::update/$1', ['filter' => 'roleauth:admin,it_staff']);
$routes->post('users/reset-password/(:num)', 'UserManagement::resetPassword/$1', ['filter' => 'roleauth:admin,it_staff']);
$routes->delete('users/delete/(:num)', 'UserManagement::delete/$1', ['filter' => 'roleauth:admin,it_staff']);
$routes->post('users/restore/(:num)', 'UserManagement::restore/$1', ['filter' => 'roleauth:admin,it_staff']);

// ===================================================================
// UNIFIED STAFF MANAGEMENT
// ===================================================================

// Staff Management Views - Role-specific entry points
$routes->get('admin/staff-management', 'StaffManagement::index', ['filter' => 'roleauth:admin']);
$routes->get('doctor/staff', 'StaffManagement::index', ['filter' => 'roleauth:doctor']);
$routes->get('it-staff/staff', 'StaffManagement::index', ['filter' => 'roleauth:it_staff']);
$routes->get('nurse/staff', 'StaffManagement::index', ['filter' => 'roleauth:nurse']);
$routes->get('receptionist/staff', 'StaffManagement::index', ['filter' => 'roleauth:receptionist']);

// Staff Management API Routes
$routes->get('staff/api', 'StaffManagement::getStaffAPI', ['filter' => 'roleauth:admin,doctor,nurse,receptionist,it_staff']);
$routes->get('staff/(:num)', 'StaffManagement::getStaff/$1', ['filter' => 'roleauth:admin,doctor,nurse,receptionist,it_staff']);
$routes->get('staff/next-employee-id', 'StaffManagement::getNextEmployeeId', ['filter' => 'roleauth:admin,it_staff']);
$routes->get('staff/departments-by-category', 'StaffManagement::getDepartmentsByCategory', ['filter' => 'roleauth:admin,it_staff']);
$routes->post('staff/create', 'StaffManagement::create', ['filter' => 'roleauth:admin,it_staff']);
$routes->post('staff/update', 'StaffManagement::update', ['filter' => 'roleauth:admin,it_staff']);
$routes->delete('staff/delete/(:num)', 'StaffManagement::delete/$1', ['filter' => 'roleauth:admin,it_staff']);
$routes->get('admin/doctors/api', 'StaffManagement::getDoctorsAPI', ['filter' => 'roleauth:admin']);

// ===================================================================
// RESOURCE MANAGEMENT
// ===================================================================

// Admin routes
$routes->get('admin/resource-management', 'ResourceManagement::index', ['filter' => 'roleauth:admin']);
$routes->get('admin/resource-management/api', 'ResourceManagement::getResourcesAPI', ['filter' => 'roleauth:admin']);
$routes->get('admin/resource-management/export', 'ResourceManagement::export', ['filter' => 'roleauth:admin']);
$routes->get('admin/resource-management/(:num)', 'ResourceManagement::getResource/$1', ['filter' => 'roleauth:admin']);
$routes->post('admin/resource-management/create', 'ResourceManagement::create', ['filter' => 'roleauth:admin']);
$routes->post('admin/resource-management/update', 'ResourceManagement::update', ['filter' => 'roleauth:admin']);
$routes->post('admin/resource-management/delete', 'ResourceManagement::delete', ['filter' => 'roleauth:admin']);

// Pharmacist routes - medications and pharmacy resources only
$routes->get('pharmacist/resource-management', 'ResourceManagement::index', ['filter' => 'roleauth:pharmacist']);
$routes->get('pharmacist/resource-management/api', 'ResourceManagement::getResourcesAPI', ['filter' => 'roleauth:pharmacist']);
$routes->post('pharmacist/resource-management/create', 'ResourceManagement::create', ['filter' => 'roleauth:pharmacist']);
$routes->post('pharmacist/resource-management/update', 'ResourceManagement::update', ['filter' => 'roleauth:pharmacist']);
$routes->post('pharmacist/resource-management/delete', 'ResourceManagement::delete', ['filter' => 'roleauth:pharmacist']);

// Laboratorist routes - lab equipment and supplies only
$routes->get('laboratorist/resource-management', 'ResourceManagement::index', ['filter' => 'roleauth:laboratorist']);
$routes->get('laboratorist/resource-management/api', 'ResourceManagement::getResourcesAPI', ['filter' => 'roleauth:laboratorist']);
$routes->post('laboratorist/resource-management/create', 'ResourceManagement::create', ['filter' => 'roleauth:laboratorist']);
$routes->post('laboratorist/resource-management/update', 'ResourceManagement::update', ['filter' => 'roleauth:laboratorist']);
$routes->post('laboratorist/resource-management/delete', 'ResourceManagement::delete', ['filter' => 'roleauth:laboratorist']);

// ===================================================================
// ROOM MANAGEMENT
// ===================================================================

$routes->get('admin/room-management', 'RoomManagement::index', ['filter' => 'roleauth:admin']);
$routes->get('rooms/api', 'RoomManagement::getRoomsAPI', ['filter' => 'roleauth:admin']);
$routes->get('rooms/(:num)', 'RoomManagement::getRoom/$1', ['filter' => 'roleauth:admin']);
$routes->post('rooms/create', 'RoomManagement::createRoom', ['filter' => 'roleauth:admin']);
$routes->post('rooms/discharge', 'RoomManagement::dischargeRoom', ['filter' => 'roleauth:admin']);
$routes->post('rooms/(:num)/update', 'RoomManagement::updateRoom/$1', ['filter' => 'roleauth:admin']);
$routes->post('rooms/(:num)/delete', 'RoomManagement::deleteRoom/$1', ['filter' => 'roleauth:admin']);

// ===================================================================
// DEPARTMENT MANAGEMENT
// ===================================================================

$routes->get('admin/department-management', 'DepartmentManagement::index', ['filter' => 'roleauth:admin']);
$routes->match(['get','post','options'], 'departments/create', 'Departments::create', ['filter' => 'roleauth:admin,it_staff']);

// ===================================================================
// UNIFIED SCHEDULE MANAGEMENT
// ===================================================================

// Schedule Management Views - Role-specific entry points
$routes->get('admin/schedule', 'ShiftManagement::index', ['filter' => 'roleauth:admin']);
$routes->get('doctor/schedule', 'ShiftManagement::index', ['filter' => 'roleauth:doctor']);
$routes->get('it-staff/schedule', 'ShiftManagement::index', ['filter' => 'roleauth:it_staff']);
$routes->get('nurse/schedule', 'ShiftManagement::index', ['filter' => 'roleauth:nurse']);
$routes->get('receptionist/schedule', 'ShiftManagement::index', ['filter' => 'roleauth:receptionist']);

// Backward-compatible Shift Management URLs
$routes->get('admin/shifts', 'ShiftManagement::index', ['filter' => 'roleauth:admin']);
$routes->get('doctor/shifts', 'ShiftManagement::index', ['filter' => 'roleauth:doctor']);
$routes->get('it-staff/shifts', 'ShiftManagement::index', ['filter' => 'roleauth:it_staff']);
$routes->get('nurse/shifts', 'ShiftManagement::index', ['filter' => 'roleauth:nurse']);
$routes->get('receptionist/shifts', 'ShiftManagement::index', ['filter' => 'roleauth:receptionist']);
$routes->get('unified/shifts', 'ShiftManagement::index', ['filter' => 'roleauth:admin,doctor,it_staff,nurse,receptionist']);

// Schedule Management API Routes
$routes->get('shifts/api', 'ShiftManagement::getShiftsAPI', ['filter' => 'roleauth:admin,doctor,nurse,receptionist,it_staff']);
$routes->get('shifts/(:num)', 'ShiftManagement::getShift/$1', ['filter' => 'roleauth:admin,doctor,nurse,receptionist,it_staff']);
$routes->get('shifts/available-staff', 'ShiftManagement::getAvailableStaffAPI', ['filter' => 'roleauth:admin,it_staff']);
$routes->post('shifts/create', 'ShiftManagement::create', ['filter' => 'roleauth:admin,it_staff']);
$routes->post('shifts/update', 'ShiftManagement::update', ['filter' => 'roleauth:admin,doctor,it_staff']);
$routes->post('shifts/delete', 'ShiftManagement::delete', ['filter' => 'roleauth:admin']);
$routes->post('shifts/(:num)/status', 'ShiftManagement::updateStatus/$1', ['filter' => 'roleauth:admin,doctor,it_staff']);

// ===================================================================
// UNIFIED PATIENT MANAGEMENT
// ===================================================================

// Patient Management Views - Role-specific entry points
$routes->get('admin/patient-management', 'PatientManagement::index', ['filter' => 'roleauth:admin']);
$routes->get('doctor/patient-management', 'PatientManagement::index', ['filter' => 'roleauth:doctor']);
$routes->get('it-staff/patients', 'PatientManagement::index', ['filter' => 'roleauth:it_staff']);
$routes->get('nurse/patients', 'PatientManagement::index', ['filter' => 'roleauth:nurse']);
$routes->get('receptionist/patients', 'PatientManagement::index', ['filter' => 'roleauth:receptionist']);

// Patient Records View
$routes->get('unified/patient-records', 'PatientManagement::patientRecords', ['filter' => 'roleauth:admin,doctor,nurse,pharmacist,laboratorist']);
$routes->get('admin/patient-records', 'PatientManagement::patientRecords', ['filter' => 'roleauth:admin']);
$routes->get('doctor/patient-records', 'PatientManagement::patientRecords', ['filter' => 'roleauth:doctor']);
$routes->get('nurse/patient-records', 'PatientManagement::patientRecords', ['filter' => 'roleauth:nurse']);
$routes->get('pharmacist/patient-records', 'PatientManagement::patientRecords', ['filter' => 'roleauth:pharmacist']);
$routes->get('laboratorist/patient-records', 'PatientManagement::patientRecords', ['filter' => 'roleauth:laboratorist']);

// Patient Management API Routes
// Nurses can only add vital signs - they need minimal view access to select patients and view records
$routes->get('patients/api', 'PatientManagement::getPatientsAPI', ['filter' => 'roleauth:admin,doctor,nurse,receptionist,it_staff']);
$routes->get('patients/(:num)', 'PatientManagement::getPatient/$1', ['filter' => 'roleauth:admin,doctor,nurse,receptionist,it_staff']);
$routes->get('patients/(:num)/records', 'PatientManagement::getPatientRecordsAPI/$1', ['filter' => 'roleauth:admin,doctor,nurse,pharmacist,laboratorist,receptionist,accountant,it_staff']);
$routes->get('patient-management/records/(:num)', 'PatientManagement::getPatientRecordsAPI/$1', ['filter' => 'roleauth:admin,doctor,nurse,pharmacist,laboratorist,receptionist,accountant,it_staff']);
// Only nurses, doctors, and admins can record vital signs
$routes->post('patients/(:num)/vital-signs', 'PatientManagement::recordVitalSigns/$1', ['filter' => 'roleauth:admin,doctor,nurse']);
$routes->post('patient-management/vital-signs', 'PatientManagement::recordVitalSigns', ['filter' => 'roleauth:admin,doctor,nurse']);
$routes->get('patients/doctors', 'PatientManagement::getDoctorsAPI', ['filter' => 'roleauth:admin,doctor,receptionist,it_staff']);
$routes->post('patients/create', 'PatientManagement::createPatient', ['filter' => 'roleauth:admin,receptionist,it_staff']);
$routes->put('patients/(:num)', 'PatientManagement::updatePatient/$1', ['filter' => 'roleauth:admin,doctor,receptionist,it_staff']);
$routes->post('patients/(:num)', 'PatientManagement::updatePatient/$1', ['filter' => 'roleauth:admin,doctor,receptionist,it_staff']);
// Removed nurse from patient status update - nurses can only add vital signs
$routes->post('patients/(:num)/status', 'PatientManagement::updatePatientStatus/$1', ['filter' => 'roleauth:admin,doctor,it_staff']);
$routes->post('patients/(:num)/assign-doctor', 'PatientManagement::assignDoctor/$1', ['filter' => 'roleauth:admin,receptionist,it_staff']);
$routes->delete('patients/(:num)', 'PatientManagement::deletePatient/$1', ['filter' => 'roleauth:admin,it_staff']);

// Geographic Reference Data APIs
$routes->get('api/geo/provinces', 'GeoDataController::provinces', ['filter' => 'roleauth:admin,doctor,nurse,receptionist,it_staff']);
$routes->get('api/geo/cities', 'GeoDataController::cities', ['filter' => 'roleauth:admin,doctor,nurse,receptionist,it_staff']);
$routes->get('api/geo/barangays', 'GeoDataController::barangays', ['filter' => 'roleauth:admin,doctor,nurse,receptionist,it_staff']);

// ===================================================================
// UNIFIED APPOINTMENT MANAGEMENT
// ===================================================================

// Appointment Management Views - Role-specific entry points
$routes->get('admin/appointments', 'AppointmentManagement::index', ['filter' => 'roleauth:admin']);
$routes->get('doctor/appointments', 'AppointmentManagement::index', ['filter' => 'roleauth:doctor']);
$routes->get('receptionist/appointments', 'AppointmentManagement::index', ['filter' => 'roleauth:receptionist']);
$routes->get('accountant/appointments', 'AppointmentManagement::index', ['filter' => 'roleauth:accountant']);

// Appointment Management API Routes
$routes->get('appointments/api', 'AppointmentManagement::getAppointmentsAPI', ['filter' => 'roleauth:admin,doctor,receptionist,accountant']);
$routes->get('appointments/patients', 'AppointmentManagement::getPatientsList', ['filter' => 'roleauth:admin,doctor,receptionist']);
$routes->get('appointments/doctors', 'AppointmentManagement::getDoctorsList', ['filter' => 'roleauth:admin,doctor,receptionist']);
$routes->get('appointments/available-doctors', 'AppointmentManagement::getAvailableDoctorsByDate', ['filter' => 'roleauth:admin,doctor,receptionist']);
$routes->get('appointments/(:num)', 'AppointmentManagement::getAppointment/$1', ['filter' => 'roleauth:admin,doctor,receptionist,accountant']);
$routes->post('appointments/create', 'AppointmentManagement::createAppointment', ['filter' => 'roleauth:admin,doctor,receptionist']);
$routes->put('appointments/(:num)', 'AppointmentManagement::updateAppointment/$1', ['filter' => 'roleauth:admin,doctor,receptionist']);
$routes->post('appointments/(:num)', 'AppointmentManagement::updateAppointment/$1', ['filter' => 'roleauth:admin,doctor,receptionist']);
$routes->post('appointments/(:num)/status', 'AppointmentManagement::updateAppointmentStatus/$1', ['filter' => 'roleauth:admin,doctor']);
$routes->post('appointments/(:num)/bill', 'AppointmentManagement::addToBilling/$1', ['filter' => 'roleauth:admin,accountant']);
$routes->delete('appointments/(:num)', 'AppointmentManagement::deleteAppointment/$1', ['filter' => 'roleauth:admin']);

// ===================================================================
// UNIFIED PRESCRIPTION MANAGEMENT
// ===================================================================

// Prescription Management Views - Role-specific entry points
$routes->get('admin/prescriptions', 'PrescriptionManagement::index', ['filter' => 'roleauth:admin']);
$routes->get('doctor/prescriptions', 'PrescriptionManagement::index', ['filter' => 'roleauth:doctor']);
$routes->get('it-staff/prescriptions', 'PrescriptionManagement::index', ['filter' => 'roleauth:it_staff']);
$routes->get('nurse/prescriptions', 'PrescriptionManagement::index', ['filter' => 'roleauth:nurse']);
$routes->get('pharmacist/prescriptions', 'PrescriptionManagement::index', ['filter' => 'roleauth:pharmacist']);
$routes->get('receptionist/prescriptions', 'PrescriptionManagement::index', ['filter' => 'roleauth:receptionist']);

// Prescription Management API Routes
$routes->get('prescriptions/api', 'PrescriptionManagement::getPrescriptionsAPI', ['filter' => 'roleauth:admin,doctor,nurse,pharmacist,receptionist,it_staff']);
$routes->get('prescriptions/(:num)', 'PrescriptionManagement::getPrescription/$1', ['filter' => 'roleauth:admin,doctor,nurse,pharmacist,receptionist,it_staff']);
$routes->get('prescriptions/available-patients', 'PrescriptionManagement::getAvailablePatientsAPI', ['filter' => 'roleauth:admin,doctor,it_staff']);
$routes->get('prescriptions/available-doctors', 'PrescriptionManagement::getAvailableDoctorsAPI', ['filter' => 'roleauth:admin,nurse']);
$routes->get('prescriptions/available-medications', 'PrescriptionManagement::getAvailableMedicationsAPI', ['filter' => 'roleauth:admin,doctor,pharmacist,it_staff']);
$routes->post('prescriptions/create', 'PrescriptionManagement::create', ['filter' => 'roleauth:admin,doctor,nurse']);
$routes->post('prescriptions/update', 'PrescriptionManagement::update', ['filter' => 'roleauth:admin,doctor,pharmacist,it_staff']);
$routes->post('prescriptions/delete', 'PrescriptionManagement::delete', ['filter' => 'roleauth:admin,doctor']);
$routes->post('prescriptions/(:num)/status', 'PrescriptionManagement::updateStatus/$1', ['filter' => 'roleauth:admin,doctor,pharmacist,it_staff']);
$routes->post('prescriptions/(:num)/bill', 'PrescriptionManagement::addToBilling/$1', ['filter' => 'roleauth:admin,accountant,pharmacist']);

// ===================================================================
// FINANCIAL MANAGEMENT
// ===================================================================

// Financial Management Views - Role-specific entry points
$routes->get('accountant/financial', 'FinancialController::index', ['filter' => 'roleauth:accountant']);
$routes->get('admin/financial-management', 'FinancialController::index', ['filter' => 'roleauth:admin']);
$routes->get('doctor/financial', 'FinancialController::index', ['filter' => 'roleauth:doctor']);
$routes->get('receptionist/financial', 'FinancialController::index', ['filter' => 'roleauth:receptionist']);

// Financial Management API Routes
$routes->get('api/users', 'FinancialController::getUsersAPI');
$routes->get('financial-management', 'FinancialController::index');
$routes->get('financial-management/add', 'FinancialController::addTransaction');
$routes->post('financial-management/add', 'FinancialController::addTransaction');
$routes->get('financial-management/categories', 'FinancialController::getCategoriesByType');
$routes->get('financial-management/transactions', 'FinancialController::getTransactionsAPI', ['filter' => 'roleauth:admin,accountant,it_staff']);
$routes->get('billing/accounts/(:num)', 'FinancialController::getBillingAccount/$1', ['filter' => 'roleauth:admin,accountant']);
$routes->post('financial/billing-accounts/(:num)/paid', 'FinancialController::markBillingAccountPaid/$1', ['filter' => 'roleauth:admin,accountant']);
$routes->post('financial/billing-accounts/(:num)/delete', 'FinancialController::deleteBillingAccount/$1', ['filter' => 'roleauth:admin,accountant']);
$routes->post('financial/billing-items/add', 'FinancialController::addBillingItem', ['filter' => 'roleauth:admin,accountant,receptionist,it_staff']);

// ===================================================================
// ANALYTICS & REPORTS
// ===================================================================

$routes->get('admin/analytics', 'AnalyticsManagement::index', ['filter' => 'roleauth:admin']);
$routes->get('accountant/analytics', 'AnalyticsManagement::index', ['filter' => 'roleauth:accountant']);
$routes->get('doctor/analytics', 'AnalyticsManagement::index', ['filter' => 'roleauth:doctor']);
$routes->get('nurse/analytics', 'AnalyticsManagement::index', ['filter' => 'roleauth:nurse']);
$routes->get('receptionist/analytics', 'AnalyticsManagement::index', ['filter' => 'roleauth:receptionist']);
$routes->get('it-staff/analytics', 'AnalyticsManagement::index', ['filter' => 'roleauth:it_staff']);
$routes->get('analytics/api', 'AnalyticsManagement::getAnalyticsAPI', ['filter' => 'roleauth:admin,accountant,doctor,nurse,receptionist,it_staff']);
$routes->post('analytics/report/generate', 'AnalyticsManagement::generateReport', ['filter' => 'roleauth:admin,accountant,doctor,it_staff']);

// ===================================================================
// UNIFIED LAB MANAGEMENT
// ===================================================================

// Lab Management Views - Role-specific entry points
$routes->get('admin/labs', 'LabManagement::index', ['filter' => 'roleauth:admin']);
$routes->get('doctor/labs', 'LabManagement::index', ['filter' => 'roleauth:doctor']);
$routes->get('laboratorist/labs', 'LabManagement::index', ['filter' => 'roleauth:laboratorist']);
$routes->get('receptionist/labs', 'LabManagement::index', ['filter' => 'roleauth:receptionist']);
$routes->get('unified/labs', 'LabManagement::index', ['filter' => 'roleauth:admin,doctor,laboratorist,receptionist,accountant,it_staff']);

// Lab Management API Routes
$routes->get('labs/api', 'LabManagement::getLabOrdersAPI', ['filter' => 'roleauth:admin,doctor,laboratorist,receptionist,accountant,it_staff']);
$routes->get('labs/(:num)', 'LabManagement::getLabOrder/$1', ['filter' => 'roleauth:admin,doctor,laboratorist,receptionist,accountant,it_staff']);
$routes->get('labs/patients', 'LabManagement::getLabPatientsAPI', ['filter' => 'roleauth:admin,doctor,laboratorist,it_staff']);
$routes->get('labs/tests', 'LabManagement::getLabTestsAPI', ['filter' => 'roleauth:admin,doctor,laboratorist,it_staff']);
$routes->post('labs/tests', 'LabManagement::createLabTest', ['filter' => 'roleauth:admin,it_staff']);
$routes->post('labs/tests/(:num)', 'LabManagement::updateLabTest/$1', ['filter' => 'roleauth:admin,it_staff']);
$routes->delete('labs/tests/(:num)', 'LabManagement::deleteLabTest/$1', ['filter' => 'roleauth:admin,it_staff']);
$routes->post('labs/create', 'LabManagement::createLabOrder', ['filter' => 'roleauth:admin,doctor,it_staff']);
$routes->post('labs/update', 'LabManagement::updateLabOrder', ['filter' => 'roleauth:admin,doctor,it_staff']);
$routes->post('labs/(:num)/status', 'LabManagement::updateStatus/$1', ['filter' => 'roleauth:admin,doctor,laboratorist,it_staff']);
$routes->post('labs/(:num)/bill', 'LabManagement::addToBilling/$1', ['filter' => 'roleauth:admin,accountant']);
$routes->delete('labs/(:num)', 'LabManagement::deleteLabOrder/$1', ['filter' => 'roleauth:admin']);

// ===================================================================
// DEBUG & TEST ROUTES (Development Only)
// ===================================================================

$routes->get('debug', 'DebugController::index');
$routes->get('test-doctors', 'TestController::doctors');
