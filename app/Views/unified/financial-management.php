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
    <link rel="stylesheet" href="<?= base_url('assets/css/unified/financial-management.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="<?= esc($userRole) ?>">

    <?= $this->include('template/header') ?>

    <?= $this->include('unified/components/notification', [
        'id'       => 'financialNotification',
        'dismissFn'=> 'dismissFinancialNotification()'
    ]) ?>

    <div class="main-container">
        <?= $this->include('unified/components/sidebar') ?>

        <main class="content" role="main">

            <h1 class="page-title">
                <i class="fas fa-dollar-sign"></i>
                <?php
                $pageTitles = [
                    'admin'       => 'Billing Management',
                    'doctor'      => 'My Financial Records',
                    'accountant'  => 'Financial Overview',
                    'receptionist'=> 'Billing & Payments'
                ];
                echo esc($pageTitles[$userRole] ?? 'Billing Management');
                ?>
            </h1>

            <div class="page-actions">
                <button type="button" class="btn btn-primary" id="refreshBillingBtn" aria-label="Refresh Billing Accounts" onclick="window.location.reload();">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <?php if (in_array($userRole ?? '', ['admin','it_staff','accountant'])): ?>
                    <button type="button" class="btn btn-secondary" id="exportBtn" aria-label="Export Data"><i class="fas fa-download"></i> Export</button>
                <?php endif; ?>
            </div>

            <!-- Validation Errors -->
            <?php $errors = session()->get('errors'); ?>
            <?php if (!empty($errors) && is_array($errors)): ?>
                <div role="alert" aria-live="polite" style="margin-top:0.75rem; padding:0.75rem 1rem; border-radius:8px; border:1px solid #fecaca; background:#fee2e2; color:#991b1b;">
                    <strong><i class="fas fa-exclamation-circle"></i> Please fix the following errors:</strong>
                    <ul style="margin:0; padding-left:1.25rem;">
                        <?php foreach ($errors as $field => $msg): ?>
                            <li><?= esc(is_array($msg) ? implode(', ', $msg) : $msg) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <br>

            <?php if (in_array($userRole ?? '', ['admin', 'doctor'])): ?>
            <!-- ============================
                 DASHBOARD CARDS 
            ============================== -->
            <div class="dashboard-overview">

                <?php if (in_array($userRole, ['admin','it_staff','accountant'])): ?>

                    <!-- Total Income -->
                    <div class="overview-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern green"><i class="fas fa-dollar-sign"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Total Income</h3>
                                <p class="card-subtitle">Revenue generated</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value green">₱<?= number_format($stats['total_income'] ?? 0, 2) ?></div>
                                <div class="metric-label">Total</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value blue">₱<?= number_format($stats['monthly_income'] ?? 0, 2) ?></div>
                                <div class="metric-label">This Month</div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Expenses -->
                    <div class="overview-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern red"><i class="fas fa-credit-card"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Total Expenses</h3>
                                <p class="card-subtitle">Money spent</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value red">₱<?= number_format($stats['total_expenses'] ?? 0, 2) ?></div>
                                <div class="metric-label">Total</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value orange">₱<?= number_format($stats['monthly_expenses'] ?? 0, 2) ?></div>
                                <div class="metric-label">This Month</div>
                            </div>
                        </div>
                    </div>

                <?php elseif ($userRole === 'doctor'): ?>

                    <!-- Doctor – My Income -->
                    <div class="overview-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern green"><i class="fas fa-wallet"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">My Income</h3>
                                <p class="card-subtitle">Personal earnings</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value green">₱<?= number_format($stats['my_income'] ?? 0, 2) ?></div>
                                <div class="metric-label">Total</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value blue">₱<?= number_format($stats['monthly_income'] ?? 0, 2) ?></div>
                                <div class="metric-label">This Month</div>
                            </div>
                        </div>
                    </div>

                <?php elseif ($userRole === 'receptionist'): ?>

                    <!-- Billing Queue -->
                    <div class="overview-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern orange"><i class="fas fa-file-invoice-dollar"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Billing Queue</h3>
                                <p class="card-subtitle">Pending payments</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value orange"><?= $stats['pending_bills'] ?? 0 ?></div>
                                <div class="metric-label">Pending</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value red"><?= $stats['overdue_bills'] ?? 0 ?></div>
                                <div class="metric-label">Overdue</div>
                            </div>
                        </div>
                    </div>

                <?php endif; ?>

                <!-- Net Balance -->
                <div class="overview-card">
                    <div class="card-header-modern">
                        <div class="card-icon-modern blue"><i class="fas fa-balance-scale"></i></div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Financial Balance</h3>
                            <p class="card-subtitle">Current status</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value blue">₱<?= number_format($stats['net_balance'] ?? 0, 2) ?></div>
                            <div class="metric-label">Net Balance</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value purple">₱<?= number_format($stats['profit_margin'] ?? 0, 2) ?></div>
                            <div class="metric-label">Profit</div>
                        </div>
                    </div>
                </div>

            </div>
            <?php endif; ?>

            <!-- Search + Filters -->
            <div class="controls-section">
                <div class="filters-section">

                    <div class="filter-group">
                        <label>Date:</label>
                        <input type="date" id="dateFilter" class="form-input">
                    </div>

                    <div class="filter-group">
                        <label>Category:</label>
                        <select id="categoryFilter" class="form-select">
                            <option value="">All Categories</option>
                            <option value="Income">Income</option>
                            <option value="Expense">Expense</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Search:</label>
                        <input type="text" id="searchFilter" class="form-input" placeholder="Search transactions...">
                    </div>

                    <button type="button" id="clearFilters" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </button>

                </div>
            </div>

            <!-- Billing Accounts Table -->
            <div class="financial-table-container">
                <table class="financial-table">
                    <thead>
                        <tr>
                            <th>Billing ID</th>
                            <th>Patient</th>
                            <th>Admission</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody id="financialTableBody">

                        <?php if (!empty($accounts) && is_array($accounts)): ?>
                            <?php 
                            // Debug: log account count
                            log_message('debug', 'Financial management view: Rendering ' . count($accounts) . ' billing accounts');
                            foreach ($accounts as $account): 
                                // Debug: log each account
                                log_message('debug', "Rendering billing account ID: {$account['billing_id']}, Patient ID: {$account['patient_id']}, Patient Name: " . ($account['patient_name'] ?? 'NOT SET'));
                            ?>

                                <tr>
                                    <td><?= esc($account['billing_id']) ?></td>

                                    <td>
                                        <strong><?= esc($account['patient_name'] ?? ($account['first_name'] ?? '') . ' ' . ($account['last_name'] ?? '') ?: 'Patient #' . $account['patient_id']) ?></strong><br>
                                        <small>ID: <?= esc($account['patient_id']) ?></small>
                                    </td>

                                    <td>
                                        <?php if (!empty($account['admission_id'])): ?>
                                            In-Patient (Admission #<?= esc($account['admission_id']) ?>)
                                        <?php else: ?>
                                            OPD / Out-Patient
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php
                                            $status = strtolower($account['status'] ?? 'open');
                                            $label  = ucfirst($status);
                                            $badge  = ($status === 'paid') ? 'paid' : 'open';
                                        ?>
                                        <span class="status-badge <?= $badge ?>">
                                            <?= esc($label) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <button class="btn btn-primary btn-small" data-action="view" data-billing-id="<?= esc($account['billing_id']) ?>" data-patient-name="<?= esc($account['patient_name'] ?? 'Unknown') ?>"><i class="fas fa-eye"></i> View Details</button>
                                        <?php if (in_array($userRole, ['admin','accountant']) && $status !== 'paid'): ?>
                                            <button class="btn btn-success btn-small" data-action="mark-paid" data-billing-id="<?= esc($account['billing_id']) ?>"><i class="fas fa-check-circle"></i> Mark as Paid</button>
                                        <?php endif; ?>
                                        <?php if (in_array($userRole, ['admin','accountant'])): ?>
                                            <button class="btn btn-danger btn-small" data-action="delete" data-billing-id="<?= esc($account['billing_id']) ?>"><i class="fas fa-trash"></i> Delete</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 3rem; color: #6b7280;">
                                    <i class="fas fa-file-invoice-dollar" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem; display: block;"></i>
                                    <p style="margin: 0.5rem 0; font-size: 1rem; font-weight: 500;">No billing accounts found</p>
                                    <p style="margin: 0; font-size: 0.875rem;">Billing accounts will appear here when items are added from appointments, prescriptions, lab orders, or room management.</p>
                                </td>
                            </tr>
                        <?php endif; ?>

                    </tbody>
                </table>
            </div>

        </main>
    </div>

    <?= $this->include('unified/modals/view-billing-account-modal') ?>

    <script src="<?= base_url('assets/js/unified/modals/shared/billing-modal-utils.js') ?>"></script>
    <script src="<?= base_url('assets/js/unified/modals/view-billing-account-modal.js') ?>"></script>
    <script src="<?= base_url('assets/js/unified/financial-management.js') ?>"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Show flash notification if available
            <?php if (session()->getFlashdata('success') || session()->getFlashdata('error')): ?>
                if (typeof showFinancialNotification === 'function') {
                    showFinancialNotification(
                        '<?= esc(session()->getFlashdata('success') ?: session()->getFlashdata('error'), 'js') ?>',
                        '<?= session()->getFlashdata('success') ? 'success' : 'error' ?>'
                    );
                } else {
                    // Fallback if function not loaded yet
                    setTimeout(function() {
                        if (typeof showFinancialNotification === 'function') {
                            showFinancialNotification(
                                '<?= esc(session()->getFlashdata('success') ?: session()->getFlashdata('error'), 'js') ?>',
                                '<?= session()->getFlashdata('success') ? 'success' : 'error' ?>'
                            );
                        }
                    }, 100);
                }
            <?php endif; ?>
            
            // Check URL parameters for notifications
            const urlParams = new URLSearchParams(window.location.search);
            const billingAdded = urlParams.get('billing_added');
            const billingError = urlParams.get('billing_error');
            
            if (billingAdded === 'true' && typeof showFinancialNotification === 'function') {
                showFinancialNotification('Appointment successfully added to billing account!', 'success');
                // Clean URL
                window.history.replaceState({}, document.title, window.location.pathname);
            } else if (billingError && typeof showFinancialNotification === 'function') {
                showFinancialNotification(decodeURIComponent(billingError), 'error');
                // Clean URL
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });
    </script>

</body>
</html>
