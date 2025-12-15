<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= base_url() ?>">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <meta name="csrf-hash" content="<?= csrf_hash() ?>">
    <meta name="user-role" content="<?= esc($userRole) ?>">
    <title><?= esc($title ?? 'Schedule Management') ?> - HMS</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/common.css') ?>" />
     <link rel="stylesheet" href="<?= base_url('assets/css/unified/shift-management.css') ?>" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
</head>

<?= $this->include('template/header') ?>

<?= $this->include('unified/components/notification', ['id' => 'scheduleNotification', 'dismissFn' => 'dismissScheduleNotification()']) ?>

<div class="main-container">
    <?= $this->include('unified/components/sidebar') ?>

    <main class="content" role="main">
        <h1 class="page-title">
            <i class="fas fa-calendar-alt"></i>
            <?= esc($title ?? 'Schedule Management') ?>
        </h1>
        <div class="page-actions">
            <?php if (in_array($userRole ?? '', ['admin', 'it_staff'])): ?>
                <button type="button" class="btn btn-primary" id="createShiftBtn" aria-label="Create New Shift"><i class="fas fa-plus"></i> Add Schedule</button>
                <button type="button" class="btn btn-secondary" id="exportBtn" aria-label="Export Data"><i class="fas fa-download"></i> Export</button>
            <?php endif; ?>
        </div>

<!-- Schedule View Modal (match view-staff modal styling) -->
<div id="viewShiftModal" class="hms-modal-overlay" aria-hidden="true">
    <div class="hms-modal" role="dialog" aria-modal="true" aria-labelledby="viewScheduleTitle">
        <div class="hms-modal-header">
            <div class="hms-modal-title" id="viewScheduleTitle">
                <i class="fas fa-calendar-check" style="color:#4f46e5"></i>
                Schedule Details
            </div>
            <button type="button" class="btn btn-secondary btn-small" id="closeViewShiftModal" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="hms-modal-body">
            <div class="form-grid">
                <div class="full">
                    <label class="form-label">Doctor</label>
                    <input type="text" id="viewDoctorName" class="form-input" readonly disabled>
                </div>
                <div>
                    <label class="form-label">Weekday</label>
                    <input type="text" id="viewScheduleWeekday" class="form-input" readonly disabled>
                </div>
                <div>
                    <label class="form-label">Time</label>
                    <input type="text" id="viewScheduleSlot" class="form-input" readonly disabled>
                </div>
                <div>
                    <label class="form-label">Status</label>
                    <input type="text" id="viewShiftStatus" class="form-input" readonly disabled>
                </div>
            </div>
        </div>
        <div class="hms-modal-actions">
            <button type="button" class="btn btn-success" id="closeViewShiftBtn">Close</button>
        </div>
    </div>
</div>

        <?php if (in_array($userRole ?? '', ['admin', 'doctor'])): ?>
        <br />

        <div class="dashboard-overview" role="region" aria-label="Dashboard Overview Cards">
                <?php if ($userRole === 'admin' || $userRole === 'it_staff'): ?>
                    <!-- Total Shifts Card -->
                    <div class="overview-card" tabindex="0">
                        <div class="card-header-modern">
                            <div class="card-icon-modern blue"><i class="fas fa-calendar-alt"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Total Schedule</h3>
                                <p class="card-subtitle">All schedules</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value blue"><?= $stats['total_shifts'] ?? 0 ?></div>
                                <div class="metric-label">Total</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value green"><?= $stats['scheduled_shifts'] ?? 0 ?></div>
                                <div class="metric-label">Scheduled</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Today's Shifts Card -->
                    <div class="overview-card" tabindex="0">
                        <div class="card-header-modern">
                            <div class="card-icon-modern orange"><i class="fas fa-calendar-day"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Today's Shifts</h3>
                                <p class="card-subtitle"><?= date('F j, Y') ?></p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value orange"><?= $stats['today_shifts'] ?? 0 ?></div>
                                <div class="metric-label">Today</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value purple"><?= $stats['active_doctors'] ?? 0 ?></div>
                                <div class="metric-label">Active Doctors</div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($userRole === 'doctor'): ?>
                    <!-- My Shifts Card -->
                    <div class="overview-card" tabindex="0">
                        <div class="card-header-modern">
                            <div class="card-icon-modern blue"><i class="fas fa-user-clock"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">My Shifts</h3>
                                <p class="card-subtitle">Personal schedule</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value blue"><?= $stats['my_shifts'] ?? 0 ?></div>
                                <div class="metric-label">Total</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value orange"><?= $stats['today_shifts'] ?? 0 ?></div>
                                <div class="metric-label">Today</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Weekly Overview Card -->
                    <div class="overview-card" tabindex="0">
                        <div class="card-header-modern">
                            <div class="card-icon-modern green"><i class="fas fa-calendar-week"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Weekly Overview</h3>
                                <p class="card-subtitle">This week's schedule</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value green"><?= $stats['week_shifts'] ?? 0 ?></div>
                                <div class="metric-label">This Week</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value purple"><?= $stats['upcoming_shifts'] ?? 0 ?></div>
                                <div class="metric-label">Upcoming</div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($userRole === 'nurse'): ?>
                    <!-- Department Shifts Card -->
                    <div class="overview-card" tabindex="0">
                        <div class="card-header-modern">
                            <div class="card-icon-modern blue"><i class="fas fa-hospital"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Department Shifts</h3>
                                <p class="card-subtitle"><?= esc($stats['department'] ?? 'Your department') ?></p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value blue"><?= $stats['department_shifts'] ?? 0 ?></div>
                                <div class="metric-label">Total</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value orange"><?= $stats['today_shifts'] ?? 0 ?></div>
                                <div class="metric-label">Today</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Schedule Status Card -->
                    <div class="overview-card" tabindex="0">
                        <div class="card-header-modern">
                            <div class="card-icon-modern green"><i class="fas fa-chart-line"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Schedule Status</h3>
                                <p class="card-subtitle">Current status</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value blue">0</div>
                                <div class="metric-label">Total</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value green">0</div>
                                <div class="metric-label">Scheduled</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value purple">0</div>
                                <div class="metric-label">Department</div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- General Shifts Overview -->
                    <div class="overview-card" tabindex="0">
                        <div class="card-header-modern">
                            <div class="card-icon-modern blue"><i class="fas fa-clock"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Shifts Overview</h3>
                                <p class="card-subtitle">General statistics</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value blue">0</div>
                                <div class="metric-label">Total</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value orange">0</div>
                                <div class="metric-label">Today</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Schedule Status -->
                    <div class="overview-card" tabindex="0">
                        <div class="card-header-modern">
                            <div class="card-icon-modern green"><i class="fas fa-tasks"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Schedule Status</h3>
                                <p class="card-subtitle">Current status</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value green">0</div>
                                <div class="metric-label">Scheduled</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Filters and Search -->
        <div class="controls-section" style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div class="filters-section" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                <div class="filter-group" style="margin: 0;">
                    <label for="searchFilter" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                        <i class="fas fa-search"></i> Search
                    </label>
                    <input type="text" id="searchFilter" class="form-control" placeholder="<?= (($userRole ?? '') === 'doctor') ? 'Search by day...' : 'Search by doctor, day...' ?>" autocomplete="off">
                </div>
                <div class="filter-group" style="margin: 0;">
                    <label for="dateFilter" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                        <i class="fas fa-calendar"></i> Date
                    </label>
                    <input type="date" id="dateFilter" class="form-control">
                </div>
                <div class="filter-group" style="margin: 0;">
                    <label for="statusFilter" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                        <i class="fas fa-filter"></i> Status
                    </label>
                    <select id="statusFilter" class="form-control">
                        <option value="">All Status</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <?php if (in_array($userRole ?? '', ['admin', 'it_staff'])): ?>
                <div class="filter-group" style="margin: 0;">
                    <label for="departmentFilter" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                        <i class="fas fa-building"></i> Department
                    </label>
                    <select id="departmentFilter" class="form-control">
                        <option value="">All Departments</option>
                        <!-- Departments will be populated dynamically if needed -->
                    </select>
                </div>
                <?php endif; ?>
                <div class="filter-group" style="margin: 0;">
                    <button type="button" id="clearFilters" class="btn btn-secondary" style="width: 100%;">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>
        </div>

        <div class="shift-table-container">
                <div class="table-header">
                    <h3>Schedule</h3>
                </div>
                <div class="table-responsive">
                    <table class="table" id="shiftsTable" aria-describedby="shiftsTableCaption">
                        <thead>
                            <tr>
                                <?php if (($userRole ?? '') !== 'doctor'): ?>
                                <th scope="col">Doctor</th>
                                <?php endif; ?>
                                <th scope="col">Day</th>
                                <th scope="col">Time</th>
                                <th scope="col">Status</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="shiftsTableBody">
                        </tbody>
                    </table>
                </div>
            </div>  

    </main>
</div>

        <!-- Modals -->
        <?= $this->include('unified/modals/add-shift-modal') ?>
        <?= $this->include('unified/modals/view-shift-modal') ?>
        <?= $this->include('unified/modals/edit-shift-modal') ?>

        <!-- Scripts -->
        <script>
        window.userRole = <?= json_encode($userRole ?? 'admin') ?>;
        </script>
        <script src="<?= base_url('assets/js/unified/modals/shared/shift-modal-utils.js') ?>"></script>
        <script src="<?= base_url('assets/js/unified/modals/add-shift-modal.js') ?>"></script>
        <script src="<?= base_url('assets/js/unified/modals/edit-shift-modal.js') ?>"></script>
        <script src="<?= base_url('assets/js/unified/modals/view-shift-modal.js') ?>"></script>
        <script src="<?= base_url('assets/js/unified/shift-management.js') ?>"></script>
</body>
</html>
