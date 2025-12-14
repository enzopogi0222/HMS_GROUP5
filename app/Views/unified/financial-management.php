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
                    'accountant'  => 'Billing Overview',
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

            <!-- Tabs -->
            <div class="financial-tabs" style="display: flex; border-bottom: 2px solid #e5e7eb; margin-bottom: 1.5rem; gap: 0;">
                <button class="financial-tab-button active" data-tab="billing-accounts">
                    <i class="fas fa-file-invoice-dollar"></i> Billing Accounts
                </button>
                <button class="financial-tab-button" data-tab="transactions">
                    <i class="fas fa-exchange-alt"></i> Transactions
                </button>
            </div>

            <!-- Billing Accounts Tab Content -->
            <div id="tabBillingAccounts" class="financial-tab-content active">
            <?php if (in_array($userRole ?? '', ['admin', 'it_staff', 'accountant', 'doctor', 'receptionist'])): ?>
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
                                <div class="metric-label">TOTAL</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value blue">₱<?= number_format($stats['monthly_income'] ?? 0, 2) ?></div>
                                <div class="metric-label">THIS MONTH</div>
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
                                <div class="metric-label">TOTAL</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value orange">₱<?= number_format($stats['monthly_expenses'] ?? 0, 2) ?></div>
                                <div class="metric-label">THIS MONTH</div>
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

                <?php if (in_array($userRole, ['admin','it_staff','accountant'])): ?>
                <!-- Financial Balance -->
                <div class="overview-card">
                    <div class="card-header-modern">
                        <div class="card-icon-modern purple"><i class="fas fa-balance-scale"></i></div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Financial Balance</h3>
                            <p class="card-subtitle">Current status</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value purple">₱<?= number_format($stats['net_balance'] ?? 0, 2) ?></div>
                            <div class="metric-label">NET BALANCE</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value purple">₱<?= number_format($stats['profit_margin'] ?? 0, 2) ?></div>
                            <div class="metric-label">PROFIT</div>
                        </div>
                    </div>
                </div>

                <!-- Pending Billing Accounts -->
                <div class="overview-card">
                    <div class="card-header-modern">
                        <div class="card-icon-modern orange"><i class="fas fa-file-invoice-dollar"></i></div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Billing Accounts</h3>
                            <p class="card-subtitle">Account status</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value orange"><?= esc($stats['pending_billing_accounts'] ?? 0) ?></div>
                            <div class="metric-label">PENDING</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value red"><?= esc($stats['overdue_billing_accounts'] ?? 0) ?></div>
                            <div class="metric-label">OVERDUE</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value blue"><?= esc($stats['total_billing_accounts'] ?? 0) ?></div>
                            <div class="metric-label">TOTAL</div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

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
                        <input type="text" id="searchFilter" class="form-input" placeholder="Search billing accounts...">
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
            </div>

            <!-- Transactions Tab Content -->
            <div id="tabTransactions" class="financial-tab-content">
                <!-- Transaction Filters -->
                <div class="controls-section" style="margin-bottom: 1rem;">
                    <div class="filters-section">
                        <div class="filter-group">
                            <label>Transaction Type:</label>
                            <select id="transactionTypeFilter" class="form-select">
                                <option value="">All Types</option>
                                <option value="stock_in">Stock In</option>
                                <option value="stock_out">Stock Out</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Payment Status:</label>
                            <select id="transactionStatusFilter" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                                <option value="failed">Failed</option>
                                <option value="refunded">Refunded</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Date From:</label>
                            <input type="date" id="transactionDateFrom" class="form-input">
                        </div>

                        <div class="filter-group">
                            <label>Date To:</label>
                            <input type="date" id="transactionDateTo" class="form-input">
                        </div>

                        <div class="filter-group">
                            <label>Search:</label>
                            <input type="text" id="transactionSearch" class="form-input" placeholder="Search transactions...">
                        </div>

                        <button type="button" id="clearTransactionFilters" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                </div>

                <!-- Transactions Table -->
                <div class="financial-table-container">
                    <table class="financial-table">
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Date & Time</th>
                                <th>Type</th>
                                <th>Patient/Resource</th>
                                <th>Amount/Quantity</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="transactionsTableBody">
                            <?php if (!empty($transactions) && is_array($transactions)): ?>
                                <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td><?= esc($transaction['transaction_id'] ?? 'N/A') ?></td>
                                        <td>
                                            <?= esc($transaction['transaction_date'] ?? 'N/A') ?><br>
                                            <small style="color: #6b7280;"><?= esc($transaction['transaction_time'] ?? '') ?></small>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= strtolower($transaction['type'] ?? '') ?>">
                                                <?= esc(ucfirst($transaction['type'] ?? 'N/A')) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $isStockTransaction = in_array($transaction['type'] ?? '', ['stock_in', 'stock_out']);
                                            if ($isStockTransaction && !empty($transaction['resource_name'])): ?>
                                                <strong><?= esc($transaction['resource_name']) ?></strong>
                                            <?php elseif (!empty($transaction['patient_first_name']) || !empty($transaction['patient_last_name'])): ?>
                                                <?= esc(($transaction['patient_first_name'] ?? '') . ' ' . ($transaction['patient_last_name'] ?? '')) ?>
                                            <?php elseif (!empty($transaction['patient_id'])): ?>
                                                Patient #<?= esc($transaction['patient_id']) ?>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($isStockTransaction): ?>
                                                <strong style="color: <?= ($transaction['type'] ?? '') === 'stock_out' ? '#ef4444' : '#10b981' ?>;">
                                                    <?= ($transaction['type'] ?? '') === 'stock_out' ? '-' : '+' ?><?= esc($transaction['quantity'] ?? 0) ?> unit(s)
                                                </strong>
                                            <?php else: ?>
                                                <strong style="color: <?= ($transaction['type'] ?? '') === 'expense' ? '#ef4444' : '#10b981' ?>;">
                                                    <?= ($transaction['type'] ?? '') === 'expense' ? '-' : '+' ?>₱<?= number_format($transaction['amount'] ?? 0, 2) ?>
                                                </strong>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($isStockTransaction): ?>
                                                N/A
                                            <?php else: ?>
                                                <?= esc(ucfirst(str_replace('_', ' ', $transaction['payment_method'] ?? 'N/A'))) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= strtolower($transaction['payment_status'] ?? 'pending') ?>">
                                                <?= esc(ucfirst($transaction['payment_status'] ?? 'Pending')) ?>
                                            </span>
                                        </td>
                                        <td><?= esc($transaction['description'] ?? 'N/A') ?></td>
                                        <td>
                                            <div class="action-buttons" style="display: flex; gap: 0.5rem;">
                                                <button class="btn btn-primary btn-small btn-view-transaction" 
                                                        data-transaction-id="<?= esc($transaction['transaction_id'] ?? '') ?>"
                                                        data-transaction-type="<?= esc($transaction['type'] ?? '') ?>"
                                                        title="View Details">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                                <?php if (in_array($userRole, ['admin', 'accountant'])): ?>
                                                    <button class="btn btn-danger btn-small btn-delete-transaction" 
                                                            data-transaction-id="<?= esc($transaction['transaction_id'] ?? '') ?>"
                                                            data-transaction-type="<?= esc($transaction['type'] ?? '') ?>"
                                                            title="Delete Transaction">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 3rem; color: #6b7280;">
                                        <i class="fas fa-exchange-alt" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem; display: block;"></i>
                                        <p style="margin: 0.5rem 0; font-size: 1rem; font-weight: 500;">No transactions found</p>
                                        <p style="margin: 0; font-size: 0.875rem;">Transactions will appear here when payments, expenses, adjustments, or stock movements are recorded.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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
