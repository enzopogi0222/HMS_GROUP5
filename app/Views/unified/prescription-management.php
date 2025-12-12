<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?> - HMS</title>
    <meta name="base-url" content="<?= base_url() ?>">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <meta name="csrf-hash" content="<?= csrf_hash() ?>">
    <meta name="user-role" content="<?= esc($userRole) ?>">
    
    <link rel="stylesheet" href="<?= base_url('assets/css/common.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/unified/prescription-management.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/unified/shift-management.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="<?= esc($userRole) ?>">

    <?= $this->include('template/header') ?>
    <?= $this->include('unified/components/notification', ['id' => 'prescriptionsNotification', 'dismissFn' => 'dismissPrescriptionsNotification()']) ?>
    <div class="main-container">
        <?= $this->include('unified/components/sidebar') ?>

        <main class="content" role="main">
            <h1 class="page-title">
                <i class="fas fa-prescription-bottle-alt"></i>
                <?php
                $pageTitles = [
                    'admin' => 'Prescription Management',
                    'doctor' => 'My Prescriptions',
                    'nurse' => 'Department Prescriptions',
                    'pharmacist' => 'Pharmacy Queue',
                    'receptionist' => 'Prescription Records'
                ];
                echo esc($pageTitles[$userRole] ?? 'Prescriptions');
                ?>
            </h1>
            <div class="page-actions">
                <?php if ($permissions['canCreate']): ?>
                    <button type="button" id="createPrescriptionBtn" class="btn btn-primary" aria-label="Create New Prescription"><i class="fas fa-plus"></i> Add Prescription</button>
                <?php endif; ?>
                <?php if (in_array($userRole ?? '', ['admin', 'it_staff', 'pharmacist'])): ?>
                    <button type="button" class="btn btn-secondary" id="exportBtn" aria-label="Export Data"><i class="fas fa-download"></i> Export</button>
                <?php endif; ?>
            </div>
            
            <?php if (in_array($userRole ?? '', ['admin', 'doctor'])): ?>
            <br />

            <!-- Statistics Overview -->
            <div class="dashboard-overview">
                <?php if ($userRole === 'admin' || $userRole === 'it_staff'): ?>
                    <!-- Total Prescriptions Card -->
                    <div class="overview-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern blue"><i class="fas fa-pills"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Total Prescriptions</h3>
                                <p class="card-subtitle">All medication orders</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value blue"><?= $stats['total_prescriptions'] ?? 0 ?></div>
                                <div class="metric-label">Total</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value green"><?= $stats['active_prescriptions'] ?? 0 ?></div>
                                <div class="metric-label">Active</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Today's Prescriptions Card -->
                    <div class="overview-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern orange"><i class="fas fa-prescription-bottle"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Today's Prescriptions</h3>
                                <p class="card-subtitle"><?= date('F j, Y') ?></p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value orange"><?= $stats['today_prescriptions'] ?? 0 ?></div>
                                <div class="metric-label">Today</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value purple"><?= $stats['pending_prescriptions'] ?? 0 ?></div>
                                <div class="metric-label">Pending</div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($userRole === 'doctor'): ?>
                    <!-- My Prescriptions Card -->
                    <div class="overview-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern blue"><i class="fas fa-clipboard-list"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">My Prescriptions</h3>
                                <p class="card-subtitle">Issued prescriptions</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value blue"><?= $stats['my_prescriptions'] ?? 0 ?></div>
                                <div class="metric-label">Total</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value orange"><?= $stats['today_prescriptions'] ?? 0 ?></div>
                                <div class="metric-label">Today</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Patient Overview Card -->
                    <div class="overview-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern green"><i class="fas fa-check-circle"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Prescription Status</h3>
                                <p class="card-subtitle">Current status</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value green"><?= $stats['active_prescriptions'] ?? 0 ?></div>
                                <div class="metric-label">Active</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value purple"><?= $stats['completed_prescriptions'] ?? 0 ?></div>
                                <div class="metric-label">Completed</div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($userRole === 'pharmacist'): ?>
                    <!-- Prescription Queue Card -->
                    <div class="overview-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern blue"><i class="fas fa-hourglass-half"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Prescription Queue</h3>
                                <p class="card-subtitle">Pending dispensing</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value blue"><?= $stats['pending_prescriptions'] ?? 0 ?></div>
                                <div class="metric-label">Pending</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value red"><?= $stats['stat_prescriptions'] ?? 0 ?></div>
                                <div class="metric-label">STAT</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dispensed Today Card -->
                    <div class="overview-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern green"><i class="fas fa-mortar-pestle"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Dispensed Today</h3>
                                <p class="card-subtitle"><?= date('F j, Y') ?></p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value green"><?= $stats['dispensed_today'] ?? 0 ?></div>
                                <div class="metric-label">Dispensed</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value orange"><?= $stats['ready_to_dispense'] ?? 0 ?></div>
                                <div class="metric-label">Ready</div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($userRole === 'nurse'): ?>
                    <!-- Department Prescriptions Card -->
                    <div class="overview-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern blue"><i class="fas fa-hospital"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Department Prescriptions</h3>
                                <p class="card-subtitle"><?= esc($stats['department'] ?? 'Your department') ?></p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value blue"><?= $stats['department_prescriptions'] ?? 0 ?></div>
                                <div class="metric-label">Total</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value orange"><?= $stats['today_prescriptions'] ?? 0 ?></div>
                                <div class="metric-label">Today</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Status Overview Card -->
                    <div class="overview-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern green"><i class="fas fa-chart-pie"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Status Overview</h3>
                                <p class="card-subtitle">Current status</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value green"><?= $stats['active_prescriptions'] ?? 0 ?></div>
                                <div class="metric-label">Active</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value purple"><?= $stats['pending_prescriptions'] ?? 0 ?></div>
                                <div class="metric-label">Pending</div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- General Prescriptions Overview -->
                    <div class="overview-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern blue"><i class="fas fa-file-medical"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Prescriptions Overview</h3>
                                <p class="card-subtitle">General statistics</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value blue"><?= $stats['total_prescriptions'] ?? 0 ?></div>
                                <div class="metric-label">Total</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value orange"><?= $stats['today_prescriptions'] ?? 0 ?></div>
                                <div class="metric-label">Today</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Filters and Search -->
            <div class="controls-section">
                <div class="filters-section">
                    <div class="filter-group">
                        <label for="dateFilter">Date:</label>
                        <input type="date" id="dateFilter" class="form-input">
                    </div>
                    
                    <div class="filter-group">
                        <label for="statusFilter">Status:</label>
                        <select id="statusFilter" class="form-select">
                            <option value="">All Status</option>
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?= esc($status['status']) ?>"><?= esc(ucfirst($status['status'])) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="searchFilter">Search:</label>
                        <input type="text" id="searchFilter" class="form-input" placeholder="Search prescriptions...">
                    </div>
                    
                    <button type="button" id="clearFilters" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>

            <!-- Prescriptions Table -->
            <div class="prescriptions-table-container">
                <table class="prescriptions-table">
                    <thead>
                        <tr>
                            <th>Prescription ID</th>
                            <th>Patient</th>
                            <th>Medication</th>
                            <th>Frequency</th>
                            <th>Date Issued</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="prescriptionsTableBody">
                        <tr>
                            <td colspan="7" class="loading-row">
                                <i class="fas fa-spinner fa-spin"></i> Loading prescriptions...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Prescription Billing Modal -->
    <div id="prescriptionBillingModal" class="hms-modal-overlay" aria-hidden="true" style="display:none;">
        <div class="hms-modal" role="dialog" aria-modal="true" aria-labelledby="prescriptionBillingTitle">
            <div class="hms-modal-header">
                <div class="hms-modal-title" id="prescriptionBillingTitle">
                    <i class="fas fa-file-invoice-dollar" style="color:#4f46e5"></i>
                    Add Prescription to Billing
                </div>
                <button type="button" class="btn btn-secondary btn-small" onclick="closePrescriptionBillingModal()" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="prescriptionBillingForm">
                <input type="hidden" id="billing_prescription_id" name="prescription_id" value="">
                <div class="hms-modal-body">
                    <p id="billingPrescriptionInfo" style="margin-bottom:0.75rem; font-size:0.9rem; color:#4b5563;"></p>
                    <div class="form-grid">
                        <div>
                            <label class="form-label" for="billing_prescription_amount">Unit Price* (₱)</label>
                            <input id="billing_prescription_amount" name="amount" type="number" class="form-input" min="0.01" step="0.01" required autocomplete="off" placeholder="0.00">
                        </div>
                        <div>
                            <label class="form-label" for="billing_prescription_quantity">Quantity*</label>
                            <input id="billing_prescription_quantity" name="quantity" type="number" class="form-input" min="1" step="1" required autocomplete="off" placeholder="1">
                        </div>
                    </div>
                </div>
                <div class="hms-modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closePrescriptionBillingModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add to Bill</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modals -->
    <?php $modalData = ['statuses' => $statuses ?? [], 'priorities' => $priorities ?? [], 'availablePatients' => $availablePatients ?? [], 'userRole' => $userRole ?? '', 'permissions' => $permissions ?? []]; ?>
    <?= $this->include('unified/modals/add-prescription-modal', $modalData) ?>
    <?= $this->include('unified/modals/view-prescription-modal', $modalData) ?>

    <!-- JavaScript -->
    <script src="<?= base_url('assets/js/unified/modals/shared/prescription-modal-utils.js') ?>"></script>
    <script src="<?= base_url('assets/js/unified/modals/add-prescription-modal.js') ?>"></script>
    <script src="<?= base_url('assets/js/unified/modals/view-prescription-modal.js') ?>"></script>
    <script src="<?= base_url('assets/js/unified/prescription-management.js') ?>"></script>
</body>
</html>
