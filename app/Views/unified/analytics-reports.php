<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= base_url() ?>">
    <meta name="csrf-token" content="<?= csrf_hash() ?>">
    <meta name="user-role" content="<?= esc($userRole) ?>">
    <title><?= esc($title) ?> - HMS</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/common.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/unified/analytics-reports.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Ensure Chart.js is loaded before initializing
        if (typeof Chart === 'undefined') {
            console.error('Chart.js library failed to load');
        }
    </script>
</head>
<body class="<?= esc($userRole) ?>">

    <?php include APPPATH . 'Views/template/header.php'; ?>

    <?= $this->include('unified/components/notification', [
        'id' => 'analyticsNotification',
        'dismissFn' => 'dismissAnalyticsNotification()'
    ]) ?>

    <div class="main-container">
        <?php include APPPATH . 'Views/unified/components/sidebar.php'; ?>

        <main class="content" role="main">
            <h1 class="page-title">
                <i class="fas fa-chart-line"></i>
                <?= esc($title) ?>
            </h1>
            <div class="page-actions">
                <?php if (in_array('generate_reports', $permissions)): ?>
                    <button type="button" class="btn btn-primary" onclick="if(window.AnalyticsManager){window.AnalyticsManager.openReportModal();}">
                        <i class="fas fa-file-pdf"></i> Generate Report
                    </button>
                <?php endif; ?>
                <?php if (in_array('export', $permissions)): ?>
                    <button type="button" class="btn btn-secondary" onclick="if(window.AnalyticsManager){window.AnalyticsManager.exportData();}">
                        <i class="fas fa-download"></i> Export
                    </button>
                <?php endif; ?>
                <button type="button" class="btn btn-secondary" onclick="if(window.AnalyticsManager){window.AnalyticsManager.refreshData();}">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>

            <!-- Filters and Search -->
            <div class="controls-section">
                <div class="filters-section">
                    <div class="filter-group">
                        <label for="dateRange">Date Range:</label>
                        <select id="dateRange" class="form-select">
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month" selected>This Month</option>
                            <option value="quarter">This Quarter</option>
                            <option value="year">This Year</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    
                    <div class="filter-group" id="customDateGroup" style="display: none;">
                        <label for="startDate">Start Date:</label>
                        <input type="date" id="startDate" class="form-input">
                    </div>
                    
                    <div class="filter-group" id="customDateGroup2" style="display: none;">
                        <label for="endDate">End Date:</label>
                        <input type="date" id="endDate" class="form-input">
                    </div>
                    
                    <button type="button" id="applyFilters" class="btn btn-primary" onclick="if(window.AnalyticsManager){window.AnalyticsManager.applyFilters();}">
                        <i class="fas fa-filter"></i> Apply
                    </button>
                </div>
            </div>

            <?php if (in_array($userRole ?? '', ['admin', 'doctor'])): ?>
            <!-- Statistics Overview -->
            <div class="dashboard-overview">
                <?php if ($userRole === 'admin' || $userRole === 'accountant' || $userRole === 'it_staff'): ?>
                    <!-- Total Patients Card -->
                    <div class="overview-card enhanced-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern blue"><i class="fas fa-users"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Total Patients</h3>
                                <p class="card-subtitle">All registered patients</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value"><?= number_format($analytics['patient_analytics']['total_patients'] ?? 0) ?></div>
                                <div class="metric-trend">
                                    <span class="trend-indicator positive">
                                        <i class="fas fa-arrow-up"></i> <?= number_format($analytics['patient_analytics']['new_patients'] ?? 0) ?> new
                                    </span>
                                </div>
                            </div>
                            <div class="metric-breakdown">
                                <div class="breakdown-item">
                                    <span class="breakdown-label">Active</span>
                                    <span class="breakdown-value"><?= number_format($analytics['patient_analytics']['active_patients'] ?? 0) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Appointments Card -->
                    <div class="overview-card enhanced-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern purple"><i class="fas fa-calendar-check"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Total Appointments</h3>
                                <p class="card-subtitle">This period</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value"><?= number_format($analytics['appointment_analytics']['total_appointments'] ?? 0) ?></div>
                                <?php 
                                $appointmentsByStatus = $analytics['appointment_analytics']['appointments_by_status'] ?? [];
                                $completedCount = 0;
                                foreach ($appointmentsByStatus as $status) {
                                    if (strtolower($status['status'] ?? '') === 'completed') {
                                        $completedCount = $status['count'] ?? 0;
                                        break;
                                    }
                                }
                                $completionRate = ($analytics['appointment_analytics']['total_appointments'] ?? 0) > 0 
                                    ? round(($completedCount / ($analytics['appointment_analytics']['total_appointments'] ?? 1)) * 100, 1) 
                                    : 0;
                                ?>
                                <div class="metric-trend">
                                    <span class="trend-indicator <?= $completionRate >= 70 ? 'positive' : 'neutral' ?>">
                                        <i class="fas fa-check-circle"></i> <?= $completionRate ?>% completed
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Revenue Card -->
                    <div class="overview-card enhanced-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern green"><i class="fas fa-dollar-sign"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Total Revenue</h3>
                                <p class="card-subtitle">This period</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value">₱<?= number_format($analytics['financial_analytics']['total_revenue'] ?? 0, 2) ?></div>
                                <div class="metric-trend">
                                    <span class="trend-indicator positive">
                                        <i class="fas fa-arrow-up"></i> Net: ₱<?= number_format($analytics['financial_analytics']['net_profit'] ?? 0, 2) ?>
                                    </span>
                                </div>
                            </div>
                            <?php if (isset($analytics['financial_analytics']['outstanding_bills']) && $analytics['financial_analytics']['outstanding_bills'] > 0): ?>
                            <div class="metric-breakdown">
                                <div class="breakdown-item warning">
                                    <span class="breakdown-label">Outstanding</span>
                                    <span class="breakdown-value">₱<?= number_format($analytics['financial_analytics']['outstanding_bills'], 2) ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Active Staff Card -->
                    <div class="overview-card enhanced-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern orange"><i class="fas fa-user-md"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Active Staff</h3>
                                <p class="card-subtitle">Currently working</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value"><?= number_format($analytics['staff_analytics']['total_staff'] ?? $analytics['staff_analytics']['active_staff'] ?? 0) ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Lab Tests Card -->
                    <?php if (isset($analytics['lab_analytics'])): ?>
                    <div class="overview-card enhanced-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern blue"><i class="fas fa-flask"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Lab Tests</h3>
                                <p class="card-subtitle">Orders this period</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value"><?= number_format($analytics['lab_analytics']['total_orders'] ?? 0) ?></div>
                                <?php 
                                $labStatus = $analytics['lab_analytics']['orders_by_status'] ?? [];
                                $completedLab = 0;
                                foreach ($labStatus as $status) {
                                    if (strtolower($status['status'] ?? '') === 'completed') {
                                        $completedLab = $status['count'] ?? 0;
                                        break;
                                    }
                                }
                                ?>
                                <div class="metric-trend">
                                    <span class="trend-indicator positive">
                                        <i class="fas fa-check"></i> <?= $completedLab ?> completed
                                    </span>
                                </div>
                            </div>
                            <?php if (isset($analytics['lab_analytics']['revenue']) && $analytics['lab_analytics']['revenue'] > 0): ?>
                            <div class="metric-breakdown">
                                <div class="breakdown-item">
                                    <span class="breakdown-label">Revenue</span>
                                    <span class="breakdown-value">₱<?= number_format($analytics['lab_analytics']['revenue'], 2) ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Prescriptions Card -->
                    <?php if (isset($analytics['prescription_analytics'])): ?>
                    <div class="overview-card enhanced-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern green"><i class="fas fa-pills"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Prescriptions</h3>
                                <p class="card-subtitle">Issued this period</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value"><?= number_format($analytics['prescription_analytics']['total_prescriptions'] ?? 0) ?></div>
                            </div>
                            <?php if (isset($analytics['prescription_analytics']['revenue']) && $analytics['prescription_analytics']['revenue'] > 0): ?>
                            <div class="metric-breakdown">
                                <div class="breakdown-item">
                                    <span class="breakdown-label">Revenue</span>
                                    <span class="breakdown-value">₱<?= number_format($analytics['prescription_analytics']['revenue'], 2) ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Room Occupancy Card -->
                    <?php if (isset($analytics['room_analytics'])): ?>
                    <div class="overview-card enhanced-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern purple"><i class="fas fa-bed"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Room Occupancy</h3>
                                <p class="card-subtitle">Current status</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value"><?= number_format($analytics['room_analytics']['occupancy_rate'] ?? 0, 1) ?>%</div>
                                <div class="metric-trend">
                                    <span class="trend-indicator">
                                        <?= number_format($analytics['room_analytics']['occupied_rooms'] ?? 0) ?> / <?= number_format($analytics['room_analytics']['total_rooms'] ?? 0) ?> rooms
                                    </span>
                                </div>
                            </div>
                            <div class="occupancy-bar">
                                <div class="occupancy-fill" style="width: <?= min(100, $analytics['room_analytics']['occupancy_rate'] ?? 0) ?>%"></div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Total Expenses Card -->
                    <?php if (isset($analytics['financial_analytics']['total_expenses'])): ?>
                    <div class="overview-card enhanced-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern red"><i class="fas fa-arrow-down"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Total Expenses</h3>
                                <p class="card-subtitle">This period</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value">₱<?= number_format($analytics['financial_analytics']['total_expenses'] ?? 0, 2) ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Resources Card -->
                    <?php if (isset($analytics['resource_analytics'])): ?>
                    <div class="overview-card enhanced-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern orange"><i class="fas fa-boxes"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Resources</h3>
                                <p class="card-subtitle">Equipment & supplies</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value"><?= number_format($analytics['resource_analytics']['total_resources'] ?? 0) ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php elseif ($userRole === 'doctor'): ?>
                    <!-- My Patients Card -->
                    <div class="overview-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern blue"><i class="fas fa-user-injured"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">My Patients</h3>
                                <p class="card-subtitle">Assigned patients</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value blue"><?= $analytics['my_patients']['total_patients'] ?? 0 ?></div>
                                <div class="metric-label">Total</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value green">+<?= $analytics['my_patients']['new_patients'] ?? 0 ?></div>
                                <div class="metric-label">New</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- My Appointments Card -->
                    <div class="overview-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern purple"><i class="fas fa-calendar-alt"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">My Appointments</h3>
                                <p class="card-subtitle">Performance tracking</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value purple"><?= $analytics['my_appointments']['total_appointments'] ?? 0 ?></div>
                                <div class="metric-label">Total</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value green"><?= $analytics['my_appointments']['completion_rate'] ?? 0 ?>%</div>
                                <div class="metric-label">Completed</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- My Revenue Card -->
                    <div class="overview-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern green"><i class="fas fa-wallet"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">My Revenue</h3>
                                <p class="card-subtitle">This period</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value green">₱<?= number_format($analytics['my_revenue']['total_revenue'] ?? 0, 0) ?></div>
                                <div class="metric-label">Earnings</div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($userRole === 'nurse'): ?>
                    <!-- Patients Card -->
                    <div class="overview-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern blue"><i class="fas fa-hospital"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Patients</h3>
                                <p class="card-subtitle">Patient care</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value blue"><?= $analytics['patients']['total'] ?? 0 ?></div>
                                <div class="metric-label">Total</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value green"><?= $analytics['patients']['active'] ?? 0 ?></div>
                                <div class="metric-label">Active</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Medications Card -->
                    <div class="overview-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern green"><i class="fas fa-pills"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Medications</h3>
                                <p class="card-subtitle">Administration tracking</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value green"><?= $analytics['medication_tracking']['administered'] ?? 0 ?></div>
                                <div class="metric-label">Administered</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value orange"><?= $analytics['medication_tracking']['pending'] ?? 0 ?></div>
                                <div class="metric-label">Pending</div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($userRole === 'receptionist'): ?>
                    <!-- New Registrations Card -->
                    <div class="overview-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern blue"><i class="fas fa-user-plus"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">New Registrations</h3>
                                <p class="card-subtitle">Patient registration</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value blue"><?= $analytics['registration_stats']['new_registrations'] ?? 0 ?></div>
                                <div class="metric-label">Total</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value green"><?= $analytics['registration_stats']['total_today'] ?? 0 ?></div>
                                <div class="metric-label">Today</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Appointments Booked Card -->
                    <div class="overview-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern purple"><i class="fas fa-calendar"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Appointments Booked</h3>
                                <p class="card-subtitle">Today's bookings</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value purple"><?= $analytics['appointment_booking_stats']['booked_today'] ?? 0 ?></div>
                                <div class="metric-label">Booked</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
            <?php endif; ?>

            <!-- Charts Section -->
                <?php if ($userRole === 'admin' || $userRole === 'accountant' || $userRole === 'it_staff'): ?>
                <div class="charts-section">
                    <!-- Revenue Trend Chart -->
                    <div class="chart-container full-width">
                        <div class="chart-header">
                            <div>
                                <h3 class="chart-title">Revenue & Expenses Trend</h3>
                                <p class="chart-subtitle">Monthly financial overview</p>
                            </div>
                            <span class="chart-period">Monthly</span>
                        </div>
                        <div style="position: relative; height: 350px;">
                            <canvas id="revenueTrendChart"></canvas>
                        </div>
                    </div>

                    <!-- Two Column Charts -->
                    <div class="charts-row">
                        <div class="chart-container">
                            <div class="chart-header">
                                <div>
                                    <h3 class="chart-title">Patient Distribution</h3>
                                    <p class="chart-subtitle">By patient type</p>
                                </div>
                            </div>
                            <div style="position: relative; height: 300px;">
                                <canvas id="patientTypeChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Overview Charts -->
                    <div class="charts-row">
                        <div class="chart-container">
                            <div class="chart-header">
                                <div>
                                    <h3 class="chart-title">Expenses by Category</h3>
                                    <p class="chart-subtitle">Category breakdown</p>
                                </div>
                            </div>
                            <div style="position: relative; height: 300px;">
                                <canvas id="expensesChart"></canvas>
                            </div>
                        </div>
                        <div class="chart-container">
                            <div class="chart-header">
                                <div>
                                    <h3 class="chart-title">Payment Methods</h3>
                                    <p class="chart-subtitle">Revenue by payment type</p>
                                </div>
                            </div>
                            <div style="position: relative; height: 300px;">
                                <canvas id="paymentMethodsChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Analytics Charts -->
                    <div class="charts-row">
                        <div class="chart-container">
                            <div class="chart-header">
                                <div>
                                    <h3 class="chart-title">Patient Age Distribution</h3>
                                    <p class="chart-subtitle">By age groups</p>
                                </div>
                            </div>
                            <div style="position: relative; height: 300px;">
                                <canvas id="patientAgeChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Staff & Lab Charts -->
                    <div class="charts-row">
                        <div class="chart-container">
                            <div class="chart-header">
                                <div>
                                    <h3 class="chart-title">Staff Distribution</h3>
                                    <p class="chart-subtitle">By role</p>
                                </div>
                            </div>
                            <div style="position: relative; height: 300px;">
                                <canvas id="staffRoleChart"></canvas>
                            </div>
                        </div>
                        <div class="chart-container">
                            <div class="chart-header">
                                <div>
                                    <h3 class="chart-title">Lab Tests by Category</h3>
                                    <p class="chart-subtitle">Test category breakdown</p>
                                </div>
                            </div>
                            <div style="position: relative; height: 300px;">
                                <canvas id="labCategoryChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Prescription & Resource Charts -->
                    <div class="charts-row">
                        <div class="chart-container">
                            <div class="chart-header">
                                <div>
                                    <h3 class="chart-title">Prescription Status</h3>
                                    <p class="chart-subtitle">Status breakdown</p>
                                </div>
                            </div>
                            <div style="position: relative; height: 300px;">
                                <canvas id="prescriptionStatusChart"></canvas>
                            </div>
                        </div>
                        <div class="chart-container">
                            <div class="chart-header">
                                <div>
                                    <h3 class="chart-title">Resources by Category</h3>
                                    <p class="chart-subtitle">Resource distribution</p>
                                </div>
                            </div>
                            <div style="position: relative; height: 300px;">
                                <canvas id="resourceCategoryChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Room Type Chart -->
                    <?php if (isset($analytics['room_analytics']['rooms_by_type']) && !empty($analytics['room_analytics']['rooms_by_type'])): ?>
                    <div class="chart-container full-width">
                        <div class="chart-header">
                            <div>
                                <h3 class="chart-title">Room Distribution by Type</h3>
                                <p class="chart-subtitle">Current room assignments</p>
                            </div>
                        </div>
                        <div style="position: relative; height: 300px;">
                            <canvas id="roomTypeChart"></canvas>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Peak Hours Chart removed -->

                </div>
                <?php elseif ($userRole === 'doctor'): ?>
                <div class="charts-section">
                    <div class="charts-row">
                        <div class="chart-container">
                            <div class="chart-header">
                                <h3 class="chart-title">Patient Growth</h3>
                            </div>
                            <div style="position: relative; height: 300px;">
                                <canvas id="patientGrowthChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>


        </main>
    </div>

    <!-- Generate Report Modal -->
    <?php if (in_array('generate_reports', $permissions)): ?>
    <div id="reportModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Generate Report</h2>
                <button type="button" class="close-btn" onclick="if(window.AnalyticsManager){window.AnalyticsManager.closeReportModal();}">&times;</button>
            </div>
            <div class="modal-body">
                <form id="reportForm">
                    <div class="form-group">
                        <label>Report Type</label>
                        <select id="reportType" class="form-control" required>
                            <option value="">Select Report Type</option>
                            <option value="patient_summary">Patient Summary</option>
                            <option value="appointment_summary">Appointment Summary</option>
                            <option value="financial_summary">Financial Summary</option>
                            <option value="lab_summary">Lab Test Summary</option>
                            <option value="prescription_summary">Prescription Summary</option>
                            <?php if ($userRole === 'admin' || $userRole === 'it_staff'): ?>
                            <option value="staff_performance">Staff Performance</option>
                            <option value="room_utilization">Room Utilization</option>
                            <?php endif; ?>
                            <?php if ($userRole === 'doctor'): ?>
                            <option value="doctor_performance">My Performance</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date Range</label>
                        <select id="reportDateRange" class="form-control" required>
                            <option value="week">Last 7 Days</option>
                            <option value="month" selected>Last 30 Days</option>
                            <option value="quarter">Last 90 Days</option>
                            <option value="year">Last Year</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Format</label>
                        <select id="reportFormat" class="form-control" required>
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel</option>
                            <option value="csv">CSV</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="if(window.AnalyticsManager){window.AnalyticsManager.closeReportModal();}">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="if(window.AnalyticsManager){window.AnalyticsManager.generateReport();}">
                    <i class="fas fa-file-pdf"></i> Generate
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Pass analytics data to JavaScript
        window.analyticsData = <?= json_encode($analytics) ?>;
        window.userRole = '<?= esc($userRole) ?>';

        function showAnalyticsNotification(message, type) {
            const container = document.getElementById('analyticsNotification');
            const iconEl = document.getElementById('analyticsNotificationIcon');
            const textEl = document.getElementById('analyticsNotificationText');

            if (!container || !iconEl || !textEl) return;

            const isError = type === 'error';
            const isSuccess = type === 'success';

            container.style.border = isError ? '1px solid #fecaca' : '1px solid #bbf7d0';
            container.style.background = isError ? '#fee2e2' : '#ecfdf5';
            container.style.color = isError ? '#991b1b' : '#166534';

            const iconClass = isError ? 'fa-exclamation-triangle' : (isSuccess ? 'fa-check-circle' : 'fa-info-circle');
            iconEl.className = 'fas ' + iconClass;

            textEl.textContent = String(message || '');
            container.style.display = 'flex';
        }

        function dismissAnalyticsNotification() {
            const container = document.getElementById('analyticsNotification');
            if (container) {
                container.style.display = 'none';
            }
        }

        // Pass analytics data from PHP to JavaScript
        window.analyticsData = <?= json_encode($analytics ?? []) ?>;
        
        // Ensure AnalyticsManager is available after script loads
        document.addEventListener('DOMContentLoaded', function() {
            // Wait a bit for the script to load
            setTimeout(function() {
                if (typeof AnalyticsManager === 'undefined' && typeof window.AnalyticsManager === 'undefined') {
                    console.error('AnalyticsManager not loaded. Please check the script path.');
                } else {
                    // Initialize charts with the data we already have
                    if (window.AnalyticsManager && window.analyticsData) {
                        // Trigger chart rendering
                        if (typeof Chart !== 'undefined') {
                            window.AnalyticsManager.renderCharts();
                        } else {
                            // Wait for Chart.js
                            setTimeout(function() {
                                if (typeof Chart !== 'undefined' && window.AnalyticsManager) {
                                    window.AnalyticsManager.renderCharts();
                                }
                            }, 500);
                        }
                    }
                }
            }, 100);
        });
    </script>
    <script src="<?= base_url('assets/js/unified/analytics-reports.js') ?>" defer></script>

    <?php if (session()->getFlashdata('success') || session()->getFlashdata('error')): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                showAnalyticsNotification(
                    '<?= esc(session()->getFlashdata('success') ?: session()->getFlashdata('error'), 'js') ?>',
                    '<?= session()->getFlashdata('success') ? 'success' : 'error' ?>'
                );
            });
        </script>
    <?php endif; ?>
</body>
</html>
