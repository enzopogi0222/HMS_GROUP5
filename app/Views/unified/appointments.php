<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= base_url() ?>">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <meta name="csrf-hash" content="<?= csrf_hash() ?>">
    <meta name="user-role" content="<?= esc($userRole ?? 'guest') ?>">
    <title><?= esc($title ?? 'Appointment Management') ?> - HMS</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/common.css') ?>" />
    <link rel="stylesheet" href="<?= base_url('assets/css/unified/appointments.css') ?>" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.css" rel="stylesheet" />
</head>

<?= $this->include('template/header') ?>
<?= $this->include('unified/components/notification', ['id' => 'appointmentsNotification', 'dismissFn' => 'dismissAppointmentNotification()']) ?>

<div class="main-container">
    <?= $this->include('unified/components/sidebar') ?>

    <main class="content" role="main">
        <h1 class="page-title"><i class="fas fa-calendar-alt"></i> <?= esc(match($userRole) { 'admin' => 'System Appointments', 'doctor' => 'My Appointments', 'nurse' => 'Department Appointments', 'receptionist' => 'Appointment Booking', default => 'Appointments' }) ?></h1>
        <div class="page-actions">
            <?php if (in_array($userRole, ['admin', 'doctor', 'receptionist'])): ?>
                <button type="button" class="btn btn-primary" id="scheduleAppointmentBtn" aria-label="Add New Appointment"><i class="fas fa-plus"></i> Add Appointment</button>
            <?php endif; ?>
            <?php if (in_array($userRole ?? '', ['admin', 'doctor'])): ?>
                <button type="button" class="btn btn-secondary" id="exportBtn" aria-label="Export Data"><i class="fas fa-download"></i> Export</button>
            <?php endif; ?>
            <div class="view-toggle" style="display: flex; gap: 0.5rem; margin-left: auto;">
                <button type="button" class="btn btn-secondary view-toggle-btn active" id="tableViewBtn" data-view="table" aria-label="Table View">
                    <i class="fas fa-table"></i> Table
                </button>
                <button type="button" class="btn btn-secondary view-toggle-btn" id="calendarViewBtn" data-view="calendar" aria-label="Calendar View">
                    <i class="fas fa-calendar"></i> Calendar
                </button>
            </div>
        </div>

        <?php if (in_array($userRole ?? '', ['admin', 'doctor'])): ?>
        <br />

        <!-- Statistics Overview -->
        <div class="dashboard-overview" role="region" aria-label="Dashboard Overview Cards">
            <!-- Today's Appointments Card -->
            <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern blue"><i class="fas fa-calendar-day"></i></div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Today's Appointments</h3>
                            <p class="card-subtitle"><?= date('F j, Y') ?></p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value blue"><?= $appointmentStats['today_total'] ?? 0 ?></div>
                            <div class="metric-label">Total</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value green"><?= $appointmentStats['today_completed'] ?? 0 ?></div>
                            <div class="metric-label">Completed</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value orange"><?= $appointmentStats['today_pending'] ?? 0 ?></div>
                            <div class="metric-label">Pending</div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

            <!-- Filters and Search -->
            <div class="controls-section" style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div class="filters-section" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                    <div class="filter-group" style="margin: 0;">
                        <label for="searchAppointment" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                            <i class="fas fa-search"></i> Search
                        </label>
                        <input type="text" id="searchAppointment" class="form-control" placeholder="Search by patient, doctor..." autocomplete="off">
                    </div>
                    <div class="filter-group" style="margin: 0;">
                        <label for="statusFilterAppointment" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                            <i class="fas fa-filter"></i> Status
                        </label>
                        <select id="statusFilterAppointment" class="form-control">
                            <option value="">All Status</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="completed">Completed</option>
                            <option value="in-progress">In Progress</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="no-show">No Show</option>
                        </select>
                    </div>
                    <div class="filter-group" style="margin: 0;">
                        <label for="dateSelector" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                            <i class="fas fa-calendar"></i> Date
                        </label>
                        <input type="date" id="dateSelector" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="filter-group" style="margin: 0;">
                        <button type="button" id="clearFiltersAppointment" class="btn btn-secondary" style="width: 100%;">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                </div>
            </div>

            <!-- Appointments Table View -->
            <div class="patient-table" id="tableView">
                <div class="table-header">
                    <h3 id="scheduleTitle">Today's Schedule - <?= date('F j, Y') ?></h3>
                </div>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <?php if ($userRole === 'admin'): ?>
                                <th>Doctor</th>
                            <?php endif; ?>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="appointmentsTableBody">
                        <?php if (!empty($appointments) && is_array($appointments)): ?>
                            <?php foreach ($appointments as $appointment): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong>
                                                <a href="<?= base_url($userRole . '/patient-management?patient_id=' . ($appointment['patient_id'] ?? '')) ?>" 
                                                   class="patient-link" style="color: #3b82f6; text-decoration: none;">
                                                    <?= esc(($appointment['patient_first_name'] ?? '') . ' ' . ($appointment['patient_last_name'] ?? '')) ?>
                                                </a>
                                            </strong>
                                        </div>
                                    </td>
                                    <?php if ($userRole === 'admin'): ?>
                                    <td>
                                        <div>
                                            <strong>Dr. <?= esc(($appointment['doctor_first_name'] ?? '') . ' ' . ($appointment['doctor_last_name'] ?? '')) ?></strong>
                                            <?php $dept = trim($appointment['doctor_department'] ?? ''); if ($dept !== ''): ?>
                                                <br>
                                                <small><?= esc($dept) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <?php endif; ?>
                                    <td><?= esc($appointment['appointment_type'] ?? 'N/A') ?></td>
                                    <td>
                                        <?php $status = strtolower($appointment['status'] ?? 'scheduled'); $badgeClass = match($status) { 'completed' => 'badge-success', 'in-progress' => 'badge-info', 'cancelled' => 'badge-danger', 'no-show' => 'badge-warning', default => 'badge-info' }; ?>
                                        <span class="badge <?= $badgeClass ?>"><?= esc(ucfirst($status)) ?></span>
                                    </td>
                                    <td>
                                        <?php $apptId = esc($appointment['appointment_id'] ?? 0); $statusLower = strtolower($status); ?>
                                        <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                            <button class="btn btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem;" onclick="viewAppointment(<?= $apptId ?>)"><i class="fas fa-eye"></i> View</button>
                                            <?php if (in_array($userRole, ['doctor', 'admin']) && $statusLower !== 'completed'): ?>
                                                <button class="btn btn-success" style="padding: 0.3rem 0.6rem; font-size: 0.75rem;" onclick="markCompleted(<?= $apptId ?>)"><i class="fas fa-check"></i> Complete</button>
                                            <?php endif; ?>
                                            <?php if (in_array($userRole, ['admin', 'doctor', 'receptionist'])): ?>
                                                <button class="btn btn-warning" style="padding: 0.3rem 0.6rem; font-size: 0.75rem;" onclick="editAppointment(<?= $apptId ?>)"><i class="fas fa-edit"></i> Edit</button>
                                            <?php endif; ?>
                                            <?php if ($userRole === 'admin'): ?>
                                                <button class="btn btn-danger" style="padding: 0.3rem 0.6rem; font-size: 0.75rem;" onclick="deleteAppointment(<?= $apptId ?>)"><i class="fas fa-trash"></i> Delete</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= $userRole === 'admin' ? '5' : '4' ?>" style="text-align: center; padding: 2rem; color: #6b7280;">
                                    <i class="fas fa-calendar-times" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                                    <p>No appointments found for the selected criteria.</p>
                                    <?php if (in_array($userRole, ['admin', 'doctor', 'receptionist'])): ?>
                                        <button class="btn btn-primary" onclick="document.getElementById('scheduleAppointmentBtn').click()">
                                            <i class="fas fa-plus"></i> Schedule New Appointment
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Appointments Calendar View -->
            <div class="patient-table" id="calendarView" style="display: none;">
                <div class="table-header">
                    <h3>Appointment Calendar</h3>
                </div>
                <div id="appointmentsCalendar" style="padding: 1.5rem;"></div>
            </div>
        </main>
    </div>

    <?php $doctors_for_modal = $doctors ?? []; ?>
    <?= $this->include('unified/modals/new-appointment-modal') ?>
    <?= $this->include('unified/modals/edit-appointment-modal') ?>
    <?= $this->include('unified/modals/view-appointment-modal') ?>
    <?= $this->include('unified/modals/assign-room-modal', [
        'roomTypes' => $roomTypes ?? [],
        'departments' => $departments ?? [],
    ]) ?>

    <!-- Billing modal for adding appointment charges -->
    <div id="billingModal" class="modal" aria-hidden="true" hidden>
        <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="billingModalTitle">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="billingModalTitle">
                        <i class="fas fa-file-invoice-dollar"></i> Add Appointment to Bill
                    </h5>
                    <button type="button" class="close" aria-label="Close" onclick="closeBillingModal()">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="billingForm" onsubmit="event.preventDefault(); submitBillingModal();">
                        <input type="hidden" id="billing_appointment_id" name="appointment_id" value="">
                        <div class="form-group">
                            <label for="billing_amount">Consultation Fee <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="billing_amount" name="amount" placeholder="Enter fee (e.g., 500.00)" required>
                            <small class="form-text text-muted">Amount to be added to the patient's billing account for this appointment.</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeBillingModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitBillingModal()" id="billingSubmitBtn">
                        <i class="fas fa-check"></i> Add to Bill
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.js"></script>
    <script src="<?= base_url('assets/js/unified/modals/shared/appointment-modal-utils.js') ?>"></script>
    <script src="<?= base_url('assets/js/unified/modals/add-appointment-modal.js') ?>"></script>
    <script src="<?= base_url('assets/js/unified/modals/edit-appointment-modal.js') ?>"></script>
    <script src="<?= base_url('assets/js/unified/modals/view-appointment-modal.js') ?>"></script>

    <script>
    window.RoomInventory = <?= json_encode($roomInventory ?? [], JSON_HEX_TAG) ?>;
    </script>
    <script src="<?= base_url('assets/js/unified/modals/shared/room-modal-utils.js') ?>"></script>
    <script src="<?= base_url('assets/js/unified/modals/assign-room-modal.js') ?>"></script>

    <script src="<?= base_url('assets/js/unified/appointments.js') ?>"></script>
</body>
</html>
