<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= base_url() ?>">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <meta name="csrf-hash" content="<?= csrf_hash() ?>">
    <meta name="user-role" content="<?= esc($userRole ?? 'guest') ?>">
    <title><?= esc($title ?? 'Lab Management') ?> - HMS</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/common.css') ?>" />
    <link rel="stylesheet" href="<?= base_url('assets/css/unified/prescription-management.css') ?>" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        .lab-tabs {
            display: flex;
            border-bottom: 2px solid #e0e0e0;
            margin-bottom: 20px;
            gap: 0;
        }
        .lab-tab-button {
            padding: 12px 24px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            color: #666;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .lab-tab-button:hover {
            color: #007bff;
            background-color: #f8f9fa;
        }
        .lab-tab-button.active {
            color: #007bff;
            border-bottom-color: #007bff;
            font-weight: 600;
        }
        .lab-tab-content {
            display: none;
        }
        .lab-tab-content.active {
            display: block;
        }
    </style>
</head>
<body>

<?= $this->include('template/header') ?>

<div class="main-container">
    <?= $this->include('unified/components/sidebar') ?>

    <main class="content" role="main">
        <h1 class="page-title">
            <i class="fas fa-flask"></i>
            <?= esc(match($userRole ?? '') {
                'admin' => 'Laboratory Management',
                'doctor' => 'Lab Orders',
                'laboratorist' => 'Lab Worklist',
                'receptionist' => 'Lab Orders Overview',
                'accountant' => 'Billable Lab Orders',
                default => 'Lab Orders'
            }) ?>
        </h1>

        <div class="page-actions">
            <?php if (in_array($userRole ?? '', ['admin', 'doctor', 'it_staff'])): ?>
                <button type="button" class="btn btn-primary" id="createLabOrderBtn"><i class="fas fa-plus"></i> New Lab Order</button>
            <?php endif; ?>
        </div>

        <br />

        <!-- Tabs Navigation -->
        <div class="lab-tabs">
            <button class="lab-tab-button active" data-tab="lab-orders" role="tab" aria-selected="true">
                <i class="fas fa-vials"></i> Lab Orders
            </button>
            <?php if (($userRole ?? '') === 'admin'): ?>
            <button class="lab-tab-button" data-tab="lab-master-list" role="tab" aria-selected="false">
                <i class="fas fa-list"></i> Lab Master List
            </button>
            <?php endif; ?>
        </div>

        <!-- Tab Content: Lab Orders -->
        <div id="tabLabOrders" class="lab-tab-content active">
            <?php if (in_array($userRole ?? '', ['admin', 'doctor'])): ?>
            <div class="dashboard-overview">
                <div class="overview-card">
                    <div class="card-header-modern">
                        <div class="card-icon-modern blue"><i class="fas fa-vials"></i></div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Lab Orders Today</h3>
                            <p class="card-subtitle"><?= date('F j, Y') ?></p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value blue" id="labTotalToday">0</div>
                            <div class="metric-label">Total</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value orange" id="labInProgress">0</div>
                            <div class="metric-label">In Progress</div>
                        </div>
                        <div class="metric">
                            <div class="metric-value green" id="labCompleted">0</div>
                            <div class="metric-label">Completed</div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <br />

            <div class="patient-table">
                <div class="table-header">
                    <h3>Lab Orders</h3>
                    <div style="display:flex; gap:0.5rem; align-items:center;">
                        <select id="labStatusFilter" class="form-control" style="max-width:200px;">
                            <option value="">All Statuses</option>
                            <option value="ordered">Ordered</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <input type="date" id="labDateFilter" class="form-control" value="<?= date('Y-m-d') ?>" />
                        <input type="text" id="labSearch" class="form-control" placeholder="Search patient or test..." />
                        <button class="btn btn-secondary" id="labRefreshBtn"><i class="fas fa-sync"></i> Refresh</button>
                    </div>
                </div>

                <table class="table">
                    <thead>
                        <tr>
                            <th>Ordered At</th>
                            <th>Patient</th>
                            <th>Test</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="labOrdersTableBody">
                        <tr>
                            <td colspan="6" style="text-align:center; padding:1.5rem; color:#6b7280;">
                                Loading lab orders...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab Content: Lab Master List (Admin Only) -->
        <?php if (($userRole ?? '') === 'admin'): ?>
        <div id="tabLabMasterList" class="lab-tab-content">
            <div class="patient-table">
                <div class="table-header">
                    <h3>Manage Lab Tests</h3>
                </div>

                <form id="labTestForm" style="margin-bottom:1rem; display:flex; flex-wrap:wrap; gap:0.5rem;">
                    <input type="hidden" id="labTestId" name="lab_test_id" value="">
                    <input type="text" class="form-control" id="labTestCode" name="test_code" placeholder="Test Code (e.g. CBC)" required style="max-width:150px;">
                    <input type="text" class="form-control" id="labTestName" name="test_name" placeholder="Test Name" required style="min-width:220px;">
                    <input type="number" step="0.01" min="0" class="form-control" id="labTestPrice" name="default_price" placeholder="Price" required style="max-width:120px;">
                    <input type="text" class="form-control" id="labTestCategory" name="category" placeholder="Category" style="max-width:150px;">
                    <select id="labTestStatus" name="status" class="form-control" style="max-width:120px;">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <button type="button" class="btn btn-primary" id="labTestSaveBtn">
                        <i class="fas fa-save"></i> Save
                    </button>
                    <button type="button" class="btn btn-secondary" id="labTestResetBtn">
                        Reset
                    </button>
                </form>

                <table class="table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="labTestsTableBody">
                        <tr>
                            <td colspan="6" style="text-align:center; padding:1.5rem; color:#6b7280;">
                                Loading lab tests...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </main>
</div>

<?= $this->include('unified/modals/add-lab-order-modal') ?>
<?= $this->include('unified/modals/view-lab-order-modal') ?>
<?= $this->include('unified/modals/edit-lab-order-modal') ?>

<script src="<?= base_url('assets/js/unified/modals/shared/lab-modal-utils.js') ?>"></script>
<script src="<?= base_url('assets/js/unified/modals/add-lab-order-modal.js') ?>"></script>
<script src="<?= base_url('assets/js/unified/modals/view-lab-order-modal.js') ?>"></script>
<script src="<?= base_url('assets/js/unified/modals/edit-lab-order-modal.js') ?>"></script>
<script src="<?= base_url('assets/js/unified/lab-management.js') ?>"></script>
</body>
</html>
