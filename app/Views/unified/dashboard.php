<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= base_url() ?>">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <meta name="csrf-hash" content="<?= csrf_hash() ?>">
    <meta name="user-role" content="<?= esc($userRole ?? 'admin') ?>">
    <title><?= esc($title ?? 'Dashboard') ?> - HMS</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/common.css') ?>" />
    <link rel="stylesheet" href="<?= base_url('assets/css/unified/dashboard.css') ?>" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
</head>
<?= $this->include('template/header') ?>

<?= $this->include('unified/components/notification', ['id' => 'dashboardNotification', 'dismissFn' => 'dismissDashboardNotification()']) ?>

<div class="main-container">
    <?= $this->include('unified/components/sidebar') ?>

    <main class="content" role="main">
        <h1 class="page-title">
            <i class="fas fa-tachometer-alt"></i>
            <?= esc($title ?? 'Dashboard') ?>
        </h1>

        <br />

        <!-- Dashboard Overview Cards -->
        <div class="dashboard-overview" role="region" aria-label="Dashboard Overview Cards">
            <?php 
            $stats = $dashboardStats ?? [];
            $userRole = $userRole ?? 'admin';
            
            // Debug: Log stats for doctor role
            if ($userRole === 'doctor') {
                log_message('debug', 'Doctor dashboard - Stats keys: ' . implode(', ', array_keys($stats)));
                log_message('debug', 'Doctor dashboard - Schedule stats: total=' . ($stats['my_schedule_total'] ?? 'missing') . ', today=' . ($stats['my_schedule_today'] ?? 'missing') . ', week=' . ($stats['my_schedule_this_week'] ?? 'missing'));
            }
            
            if ($userRole === 'admin' || $userRole === 'it_staff'): ?>
                <!-- Total Patients Card -->
                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern blue" aria-hidden="true">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Total Patients</h3>
                            <p class="card-subtitle">All registered patients</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value blue"><?= esc($stats['total_patients'] ?? 0) ?></div>
                            <div class="metric-label">Total</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value green"><?= esc($stats['active_patients'] ?? 0) ?></div>
                            <div class="metric-label">Active</div>
                        </div>
                    </div>
                </div>

                <!-- Patient Types Card -->
                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern green" aria-hidden="true">
                            <i class="fas fa-user-injured"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Patient Types</h3>
                            <p class="card-subtitle">By patient category</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value blue"><?= esc($stats['inpatients'] ?? 0) ?></div>
                            <div class="metric-label">Inpatient</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value orange"><?= esc($stats['outpatients'] ?? 0) ?></div>
                            <div class="metric-label">Outpatient</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value red"><?= esc($stats['emergency_patients'] ?? 0) ?></div>
                            <div class="metric-label">Emergency</div>
                        </div>
                    </div>
                </div>

                <!-- Today's Appointments Card -->
                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern purple" aria-hidden="true">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Today's Appointments</h3>
                            <p class="card-subtitle">Appointment status</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value blue"><?= esc($stats['today_appointments'] ?? 0) ?></div>
                            <div class="metric-label">Total</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value green"><?= esc($stats['today_scheduled_appointments'] ?? 0) ?></div>
                            <div class="metric-label">Scheduled</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value orange"><?= esc($stats['today_completed_appointments'] ?? 0) ?></div>
                            <div class="metric-label">Completed</div>
                        </div>
                    </div>
                </div>

                <!-- Staff Statistics Card -->
                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern orange" aria-hidden="true">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Staff</h3>
                            <p class="card-subtitle">Staff members</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value blue"><?= esc($stats['total_staff'] ?? 0) ?></div>
                            <div class="metric-label">Total Staff</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value green"><?= esc($stats['total_doctors'] ?? 0) ?></div>
                            <div class="metric-label">Doctors</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value purple"><?= esc($stats['staff_on_duty_today'] ?? 0) ?></div>
                            <div class="metric-label">On Duty Today</div>
                        </div>
                    </div>
                </div>

                <!-- Bed Capacity Card -->
                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern red" aria-hidden="true">
                            <i class="fas fa-bed"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Bed Capacity</h3>
                            <p class="card-subtitle">Room availability</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value blue"><?= esc($stats['bed_capacity_total'] ?? 0) ?></div>
                            <div class="metric-label">Total Beds</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value red"><?= esc($stats['occupied_beds'] ?? 0) ?></div>
                            <div class="metric-label">Occupied</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value green"><?= esc($stats['available_beds'] ?? 0) ?></div>
                            <div class="metric-label">Available</div>
                        </div>
                    </div>
                </div>

                <!-- Weekly Statistics Card -->
                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern purple" aria-hidden="true">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Weekly Stats</h3>
                            <p class="card-subtitle">Last 7 days</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value blue"><?= esc($stats['weekly_appointments'] ?? 0) ?></div>
                            <div class="metric-label">Appointments</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value green"><?= esc($stats['weekly_new_patients'] ?? 0) ?></div>
                            <div class="metric-label">New Patients</div>
                        </div>
                    </div>
                </div>

            <?php elseif ($userRole === 'doctor'): ?>
                <!-- Doctor Dashboard Cards -->
                <!-- DEBUG: User role is doctor, stats available: <?= isset($stats) ? 'yes' : 'no' ?> -->
                
                <!-- My Schedule Card - MOVED TO TOP FOR TESTING -->
                <?php 
                // Ensure stats exist and have schedule data
                $scheduleTotal = isset($stats['my_schedule_total']) ? (int)$stats['my_schedule_total'] : 0;
                $scheduleToday = isset($stats['my_schedule_today']) ? (int)$stats['my_schedule_today'] : 0;
                $scheduleWeek = isset($stats['my_schedule_this_week']) ? (int)$stats['my_schedule_this_week'] : 0;
                ?>
                <div class="overview-card" tabindex="0" style="cursor: pointer;" onclick="window.location.href='<?= base_url('doctor/schedule') ?>'">
                    <div class="card-header-modern">
                        <div class="card-icon-modern blue" aria-hidden="true">
                            <i class="fas fa-calendar-days"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">My Schedule</h3>
                            <p class="card-subtitle">Work schedule overview</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value blue"><?= esc($scheduleTotal) ?></div>
                            <div class="metric-label">Total Shifts</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value orange"><?= esc($scheduleToday) ?></div>
                            <div class="metric-label">Today</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value green"><?= esc($scheduleWeek) ?></div>
                            <div class="metric-label">This Week</div>
                        </div>
                    </div>
                    <div style="padding: 0.75rem; border-top: 1px solid #e5e7eb; margin-top: 0.5rem;">
                        <a href="<?= base_url('doctor/schedule') ?>" class="btn btn-primary btn-small" style="width: 100%; text-align: center; display: inline-block; text-decoration: none; padding: 0.5rem;">
                            <i class="fas fa-eye"></i> View Full Schedule
                        </a>
                    </div>
                </div>
                
                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern blue" aria-hidden="true">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Today's Appointments</h3>
                            <p class="card-subtitle">Your schedule today</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value blue"><?= esc($stats['today_appointments'] ?? 0) ?></div>
                            <div class="metric-label">Total</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value green"><?= esc($stats['completed_today'] ?? 0) ?></div>
                            <div class="metric-label">Completed</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value orange"><?= esc($stats['pending_today'] ?? 0) ?></div>
                            <div class="metric-label">Pending</div>
                        </div>
                    </div>
                </div>

                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern green" aria-hidden="true">
                            <i class="fas fa-user-injured"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">My Patients</h3>
                            <p class="card-subtitle">Patient statistics</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value blue"><?= esc($stats['my_patients'] ?? 0) ?></div>
                            <div class="metric-label">Total Patients</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value green"><?= esc($stats['new_patients_week'] ?? 0) ?></div>
                            <div class="metric-label">New This Week</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value red"><?= esc($stats['critical_patients'] ?? 0) ?></div>
                            <div class="metric-label">Critical</div>
                        </div>
                    </div>
                </div>

                <!-- My Prescriptions Card -->
                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern blue" aria-hidden="true">
                            <i class="fas fa-prescription-bottle"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">My Prescriptions</h3>
                            <p class="card-subtitle">Issued prescriptions</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value blue"><?= esc($stats['prescriptions_total'] ?? 0) ?></div>
                            <div class="metric-label">Total</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value orange"><?= esc($stats['prescriptions_today'] ?? 0) ?></div>
                            <div class="metric-label">Today</div>
                        </div>
                    </div>
                </div>

                <!-- Prescription Status Card -->
                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern green" aria-hidden="true">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Prescription Status</h3>
                            <p class="card-subtitle">Current status</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value green"><?= esc($stats['prescriptions_active'] ?? 0) ?></div>
                            <div class="metric-label">Active</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value purple"><?= esc($stats['prescriptions_completed'] ?? 0) ?></div>
                            <div class="metric-label">Completed</div>
                        </div>
                    </div>
                </div>

                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern orange" aria-hidden="true">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Weekly Overview</h3>
                            <p class="card-subtitle">Last 7 days</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value blue"><?= esc($stats['weekly_appointments'] ?? 0) ?></div>
                            <div class="metric-label">Appointments</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value green"><?= esc($stats['monthly_patients'] ?? 0) ?></div>
                            <div class="metric-label">Monthly Patients</div>
                        </div>
                    </div>
                </div>

            <?php elseif ($userRole === 'nurse'): ?>
                <!-- Nurse Dashboard Cards -->
                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern blue" aria-hidden="true">
                            <i class="fas fa-hospital"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Patients</h3>
                            <p class="card-subtitle">Patient overview</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value blue"><?= esc($stats['total_patients'] ?? 0) ?></div>
                            <div class="metric-label">Total</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value red"><?= esc($stats['critical_patients'] ?? 0) ?></div>
                            <div class="metric-label">Critical</div>
                        </div>
                    </div>
                </div>

            <?php elseif ($userRole === 'receptionist'): ?>
                <!-- Receptionist Dashboard Cards -->
                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern blue" aria-hidden="true">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Today's Appointments</h3>
                            <p class="card-subtitle">Appointment management</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value blue"><?= esc($stats['total_appointments'] ?? 0) ?></div>
                            <div class="metric-label">Total</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value green"><?= esc($stats['scheduled_today'] ?? 0) ?></div>
                            <div class="metric-label">Scheduled</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value red"><?= esc($stats['cancelled_today'] ?? 0) ?></div>
                            <div class="metric-label">Cancelled</div>
                        </div>
                    </div>
                </div>

                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern green" aria-hidden="true">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Patients</h3>
                            <p class="card-subtitle">Patient registration</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value blue"><?= esc($stats['total_patients'] ?? 0) ?></div>
                            <div class="metric-label">Total</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value green"><?= esc($stats['new_patients_today'] ?? 0) ?></div>
                            <div class="metric-label">New Today</div>
                        </div>
                    </div>
                </div>

                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern purple" aria-hidden="true">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Weekly Overview</h3>
                            <p class="card-subtitle">Last 7 days</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value blue"><?= esc($stats['weekly_appointments'] ?? 0) ?></div>
                            <div class="metric-label">Appointments</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value green"><?= esc($stats['monthly_patients'] ?? 0) ?></div>
                            <div class="metric-label">Monthly Patients</div>
                        </div>
                    </div>
                </div>

            <?php elseif ($userRole === 'pharmacist'): ?>
                <!-- Pharmacist Dashboard Cards -->
                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern blue" aria-hidden="true">
                            <i class="fas fa-prescription-bottle-alt"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Prescriptions</h3>
                            <p class="card-subtitle">Prescription management</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value blue"><?= esc($stats['total_prescriptions'] ?? 0) ?></div>
                            <div class="metric-label">Total</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value orange"><?= esc($stats['pending_prescriptions'] ?? 0) ?></div>
                            <div class="metric-label">Pending</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value green"><?= esc($stats['ready_prescriptions'] ?? 0) ?></div>
                            <div class="metric-label">Ready</div>
                        </div>
                    </div>
                </div>

                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern green" aria-hidden="true">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Dispensed Today</h3>
                            <p class="card-subtitle">Today's activity</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value green"><?= esc($stats['dispensed_today'] ?? 0) ?></div>
                            <div class="metric-label">Dispensed</div>
                        </div>
                    </div>
                </div>

                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern red" aria-hidden="true">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Inventory Alerts</h3>
                            <p class="card-subtitle">Stock management</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value orange"><?= esc($stats['low_stock_items'] ?? 0) ?></div>
                            <div class="metric-label">Low Stock</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value red"><?= esc($stats['expired_items'] ?? 0) ?></div>
                            <div class="metric-label">Expired</div>
                        </div>
                    </div>
                </div>

            <?php elseif ($userRole === 'accountant'): ?>
                <!-- Accountant Dashboard Cards -->
                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern green" aria-hidden="true">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Revenue</h3>
                            <p class="card-subtitle">Financial overview</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value green">₱<?= number_format($stats['total_revenue'] ?? 0, 2) ?></div>
                            <div class="metric-label">Total Revenue</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value blue">₱<?= number_format($stats['today_revenue'] ?? 0, 2) ?></div>
                            <div class="metric-label">Today</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value purple">₱<?= number_format($stats['monthly_revenue'] ?? 0, 2) ?></div>
                            <div class="metric-label">This Month</div>
                        </div>
                    </div>
                </div>

                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern orange" aria-hidden="true">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Billing Accounts</h3>
                            <p class="card-subtitle">Account status</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value orange"><?= esc($stats['pending_bills'] ?? 0) ?></div>
                            <div class="metric-label">Pending</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value green"><?= esc($stats['paid_bills'] ?? 0) ?></div>
                            <div class="metric-label">Paid</div>
                        </div>
                    </div>
                </div>

                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern blue" aria-hidden="true">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Transactions</h3>
                            <p class="card-subtitle">Payment records</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value blue"><?= esc($stats['total_transactions'] ?? 0) ?></div>
                            <div class="metric-label">Total</div>
                        </div>
                    </div>
                </div>

            <?php elseif ($userRole === 'laboratorist'): ?>
                <!-- Laboratorist Dashboard Cards -->
                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern blue" aria-hidden="true">
                            <i class="fas fa-flask"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Lab Orders</h3>
                            <p class="card-subtitle">Test management</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value blue"><?= esc($stats['total_orders'] ?? 0) ?></div>
                            <div class="metric-label">Total</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value orange"><?= esc($stats['pending_orders'] ?? 0) ?></div>
                            <div class="metric-label">Pending</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value yellow"><?= esc($stats['in_progress'] ?? 0) ?></div>
                            <div class="metric-label">In Progress</div>
                        </div>
                    </div>
                </div>

                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern green" aria-hidden="true">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Completed Today</h3>
                            <p class="card-subtitle">Today's activity</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value green"><?= esc($stats['completed_today'] ?? 0) ?></div>
                            <div class="metric-label">Completed</div>
                        </div>
                    </div>
                </div>

                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern red" aria-hidden="true">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Urgent Orders</h3>
                            <p class="card-subtitle">Priority tests</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value red"><?= esc($stats['urgent_orders'] ?? 0) ?></div>
                            <div class="metric-label">Urgent/Stat</div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- Default Dashboard Card -->
                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern blue" aria-hidden="true">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Dashboard</h3>
                            <p class="card-subtitle">Welcome to your dashboard</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value blue">0</div>
                            <div class="metric-label">No Data Available</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Dashboard Content Section -->
        <?php if (!empty($recentActivities) || !empty($upcomingEvents)): ?>
        <div class="dashboard-content">
            <!-- Recent Activities -->
            <?php if (!empty($recentActivities)): ?>
            <div class="dashboard-section">
                <h2 class="section-title">
                    <i class="fas fa-history"></i>
                    Recent Activities
                </h2>
                <div class="activity-feed">
                    <?php foreach ($recentActivities as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon <?= esc($activity['color'] ?? 'info') ?>">
                            <i class="<?= esc($activity['icon'] ?? 'fas fa-circle') ?>"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-message"><?= esc($activity['message'] ?? '') ?></div>
                            <div class="activity-time"><?= esc($activity['time'] ?? '') ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Upcoming Events -->
            <?php if (!empty($upcomingEvents)): ?>
            <div class="dashboard-section">
                <h2 class="section-title">
                    <i class="fas fa-calendar-alt"></i>
                    Upcoming Events
                </h2>
                <div class="activity-feed">
                    <?php foreach ($upcomingEvents as $event): ?>
                    <div class="activity-item">
                        <div class="activity-icon info">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-message"><?= esc($event['title'] ?? '') ?></div>
                            <div class="activity-time">
                                <?= esc($event['date'] ?? '') ?>
                                <?php if (!empty($event['time'])): ?>
                                    at <?= esc($event['time']) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </main>
</div>

<!-- Scripts -->
<script>
function dismissDashboardNotification() {
    const notification = document.getElementById('dashboardNotification');
    if (notification) {
        notification.style.display = 'none';
    }
}

// Auto-refresh dashboard data every 5 minutes
document.addEventListener('DOMContentLoaded', function() {
    // Optional: Add auto-refresh functionality here
    // setInterval(function() {
    //     fetch('/api/dashboard-data')
    //         .then(response => response.json())
    //         .then(data => {
    //             // Update dashboard stats
    //         });
    // }, 300000); // 5 minutes
});
</script>
</body>
</html>

