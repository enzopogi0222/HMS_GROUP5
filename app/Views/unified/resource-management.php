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
    <link rel="stylesheet" href="<?= base_url('assets/css/unified/resource-management.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="<?= esc($userRole) ?>">

    <?= $this->include('template/header') ?>

    <?= $this->include('unified/components/notification', [
        'id' => 'resourcesNotification',
        'dismissFn' => 'dismissResourcesNotification()'
    ]) ?>

    <!-- Stock and Expiry Notifications -->
    <?php if (!empty($lowStockResources) || !empty($expiredMedications) || !empty($expiringMedications)): ?>
        <div class="expiry-notifications" style="margin: 1rem auto; max-width: 1180px;">
            <?php if (!empty($lowStockResources)): ?>
                <div class="stock-alert low-stock" style="
                    padding: 1rem;
                    margin-bottom: 0.75rem;
                    border-radius: 6px;
                    border: 1px solid #fbbf24;
                    background: #fef3c7;
                    color: #92400e;
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                ">
                    <i class="fas fa-exclamation-triangle" style="font-size: 1.25rem;"></i>
                    <div style="flex: 1;">
                        <strong>Low Stock Alert</strong>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem;">
                            <?= count($lowStockResources) ?> resource(s) have stock below 20:
                            <?php 
                            $lowStockNames = array_slice(array_map(function($r) { 
                                $name = esc($r['equipment_name'] ?? 'Unknown');
                                $quantity = (int)($r['quantity'] ?? 0);
                                return $name . ' (' . $quantity . ' remaining)';
                            }, $lowStockResources), 0, 5);
                            echo implode(', ', $lowStockNames);
                            if (count($lowStockResources) > 5) echo ' and ' . (count($lowStockResources) - 5) . ' more';
                            ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($expiredMedications)): ?>
                <div class="expiry-alert expired" style="
                    padding: 1rem;
                    margin-bottom: 0.75rem;
                    border-radius: 6px;
                    border: 1px solid #fecaca;
                    background: #fee2e2;
                    color: #991b1b;
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                ">
                    <i class="fas fa-exclamation-triangle" style="font-size: 1.25rem;"></i>
                    <div style="flex: 1;">
                        <strong>Expired Medications Alert</strong>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem;">
                            <?= count($expiredMedications) ?> medication(s) have expired:
                            <?php 
                            $expiredNames = array_slice(array_map(function($m) { 
                                return esc($m['equipment_name'] ?? 'Unknown'); 
                            }, $expiredMedications), 0, 5);
                            echo implode(', ', $expiredNames);
                            if (count($expiredMedications) > 5) echo ' and ' . (count($expiredMedications) - 5) . ' more';
                            ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($expiringMedications)): ?>
                <div class="expiry-alert expiring" style="
                    padding: 1rem;
                    margin-bottom: 0.75rem;
                    border-radius: 6px;
                    border: 1px solid #fef3c7;
                    background: #fef9c3;
                    color: #92400e;
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                ">
                    <i class="fas fa-exclamation-circle" style="font-size: 1.25rem;"></i>
                    <div style="flex: 1;">
                        <strong>Expiring Medications Warning</strong>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem;">
                            <?= count($expiringMedications) ?> medication(s) will expire within 30 days:
                            <?php 
                            $expiringNames = array_slice(array_map(function($m) { 
                                $name = esc($m['equipment_name'] ?? 'Unknown');
                                $expiry = !empty($m['expiry_date']) ? date('M d, Y', strtotime($m['expiry_date'])) : 'Unknown';
                                return $name . ' (expires: ' . $expiry . ')';
                            }, $expiringMedications), 0, 3);
                            echo implode(', ', $expiringNames);
                            if (count($expiringMedications) > 3) echo ' and ' . (count($expiringMedications) - 3) . ' more';
                            ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="main-container">
        <?= $this->include('unified/components/sidebar') ?>

        <main class="content" role="main">
            <h1 class="page-title">
                <i class="fas fa-boxes"></i>
                <?= esc(match($userRole ?? '') {
                    'admin' => 'Resource Management',
                    'doctor', 'nurse' => 'Medical Resources',
                    'pharmacist' => 'Pharmacy Resources',
                    'laboratorist' => 'Lab Resources',
                    'receptionist' => 'Office Resources',
                    'it_staff' => 'IT Resource Management',
                    default => 'Resources'
                }) ?>
            </h1>
            <div class="page-actions">
                <?php if (in_array('create', $permissions['resources'] ?? [])): ?>
                    <button type="button" id="addResourceBtn" class="btn btn-primary" aria-label="Add New Resource"><i class="fas fa-plus" aria-hidden="true"></i> Add Resource</button>
                <?php endif; ?>
                <?php if (in_array($userRole ?? '', ['admin', 'it_staff'])): ?>
                    <button type="button" class="btn btn-secondary" id="exportBtn" aria-label="Export Data"><i class="fas fa-download" aria-hidden="true"></i> Export</button>
                <?php endif; ?>
            </div>

            <?php if (in_array($userRole ?? '', ['admin', 'doctor'])): ?>
            <br />

            <!-- Statistics Overview -->
            <div class="dashboard-overview">
                <?php if ($userRole === 'admin' || $userRole === 'it_staff'): ?>
                    <!-- Total Resources Card -->
                    <div class="overview-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern blue"><i class="fas fa-boxes"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Total Resources</h3>
                                <p class="card-subtitle">All equipment & supplies</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value blue"><?= $stats['total_resources'] ?? 0 ?></div>
                                <div class="metric-label">Total</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value green"><?= $stats['stock_in'] ?? 0 ?></div>
                                <div class="metric-label">Stock In</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Resource Status Card -->
                    <div class="overview-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern orange"><i class="fas fa-tools"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Resource Status</h3>
                                <p class="card-subtitle">Current stock status</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value green"><?= $stats['stock_in'] ?? 0 ?></div>
                                <div class="metric-label">Stock In</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value red"><?= $stats['stock_out'] ?? 0 ?></div>
                                <div class="metric-label">Stock Out</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Categories Card -->
                    <div class="overview-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern purple"><i class="fas fa-layer-group"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Categories</h3>
                                <p class="card-subtitle">Resource types</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value purple"><?= $stats['categories'] ?? 0 ?></div>
                                <div class="metric-label">Types</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value orange"><?= $stats['low_quantity'] ?? 0 ?></div>
                                <div class="metric-label">Low Stock</div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($userRole === 'doctor' || $userRole === 'nurse'): ?>
                    <!-- Medical Resources Card -->
                    <div class="overview-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern blue"><i class="fas fa-stethoscope"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Medical Resources</h3>
                                <p class="card-subtitle">Equipment & supplies</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value blue"><?= $stats['total_resources'] ?? 0 ?></div>
                                <div class="metric-label">Total</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value green"><?= $stats['stock_in'] ?? 0 ?></div>
                                <div class="metric-label">Stock In</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Equipment Status Card -->
                    <div class="overview-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern orange"><i class="fas fa-heartbeat"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Equipment Status</h3>
                                <p class="card-subtitle">Current stock status</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value green"><?= $stats['stock_in'] ?? 0 ?></div>
                                <div class="metric-label">Stock In</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value red"><?= $stats['stock_out'] ?? 0 ?></div>
                                <div class="metric-label">Stock Out</div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($userRole === 'pharmacist'): ?>
                    <!-- Pharmacy Resources Card -->
                    <div class="overview-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern blue"><i class="fas fa-pills"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Pharmacy Resources</h3>
                                <p class="card-subtitle">Equipment & supplies</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value blue"><?= $stats['total_resources'] ?? 0 ?></div>
                                <div class="metric-label">Total</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value green"><?= $stats['stock_in'] ?? 0 ?></div>
                                <div class="metric-label">Stock In</div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($userRole === 'laboratorist'): ?>
                    <!-- Lab Resources Card -->
                    <div class="overview-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern blue"><i class="fas fa-microscope"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Lab Resources</h3>
                                <p class="card-subtitle">Equipment & supplies</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value blue"><?= $stats['total_resources'] ?? 0 ?></div>
                                <div class="metric-label">Total</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value green"><?= $stats['stock_in'] ?? 0 ?></div>
                                <div class="metric-label">Stock In</div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($userRole === 'receptionist'): ?>
                    <!-- Office Resources Card -->
                    <div class="overview-card">
                        <div class="card-header-modern">
                            <div class="card-icon-modern blue"><i class="fas fa-desktop"></i></div>
                            <div class="card-info">
                                <h3 class="card-title-modern">Office Resources</h3>
                                <p class="card-subtitle">Equipment & supplies</p>
                            </div>
                        </div>
                        <div class="card-metrics">
                            <div class="metric">
                                <div class="metric-value blue"><?= $stats['total_resources'] ?? 0 ?></div>
                                <div class="metric-label">Total</div>
                            </div>
                            <div class="metric">
                                <div class="metric-value green"><?= $stats['stock_in'] ?? 0 ?></div>
                                <div class="metric-label">Stock In</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Search and Filter Section -->
            <div class="filters-section" style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                    <div class="form-group" style="margin: 0;">
                        <label for="searchResource" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                            <i class="fas fa-search"></i> Search
                        </label>
                        <input type="text" id="searchResource" class="form-control" placeholder="Search by name, location..." autocomplete="off">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label for="filterCategory" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                            <i class="fas fa-filter"></i> Category
                        </label>
                        <select id="filterCategory" class="form-control">
                            <option value="">All Categories</option>
                            <?php foreach ($categories ?? [] as $cat): ?>
                                <option value="<?= esc($cat) ?>"><?= esc($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label for="filterStatus" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                            <i class="fas fa-info-circle"></i> Status
                        </label>
                        <select id="filterStatus" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="Stock In">Stock In</option>
                            <option value="Stock Out">Stock Out</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <button type="button" id="clearFilters" class="btn btn-secondary" style="width: 100%;">
                            <i class="fas fa-times"></i> Clear Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Resources Table -->
            <div class="resources-table-container">
                <table class="resources-table">
                    <thead>
                        <tr>
                            <th>Resource Name</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <th>Location</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="resourcesTableBody">
                        <?php if (!empty($resources) && is_array($resources)): ?>
                            <?php foreach ($resources as $r): ?>
                                <tr>
                                    <td><?= esc($r['equipment_name'] ?? '-') ?></td>
                                    <td><?= esc($r['category'] ?? '-') ?></td>
                                    <td><?= esc($r['quantity'] ?? '-') ?></td>
                                    <td>
                                        <?php
                                        $status = $r['status'] ?? 'Stock In';
                                        $badgeClass = match($status) {
                                            'Stock In' => 'badge-success',
                                            'Stock Out' => 'badge-danger',
                                            default => 'badge-info'
                                        };
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= esc($status) ?></span>
                                    </td>
                                    <td><?= esc($r['location'] ?? '-') ?></td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <button class="btn btn-info btn-small" onclick="viewResource(<?= esc($r['id'] ?? 0) ?>)" aria-label="View Resource">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <?php if (in_array('edit', $permissions['resources'] ?? [])): ?>
                                                <button class="btn btn-warning btn-small" onclick="editResource(<?= esc($r['id'] ?? 0) ?>)" aria-label="Edit Resource">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                            <?php endif; ?>
                                            <?php if (in_array('delete', $permissions['resources'] ?? [])): ?>
                                                <button class="btn btn-danger btn-small" onclick="deleteResource(<?= esc($r['id'] ?? 0) ?>)" aria-label="Delete Resource">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <i class="fas fa-box-open"></i>
                                    <h3>No Resources Found</h3>
                                    <p>Start by adding your first resource</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?= $this->include('unified/modals/add-resource-modal', ['categories' => $categories ?? []]) ?>
            <?= $this->include('unified/modals/view-resource-modal', ['permissions' => $permissions ?? []]) ?>

            <!-- Add Department Modal -->
            <div id="addDepartmentModal" class="hms-modal-overlay" aria-hidden="true">
                <div class="hms-modal" role="dialog" aria-modal="true" aria-labelledby="addDepartmentTitle">
                    <div class="hms-modal-header">
                        <div class="hms-modal-title" id="addDepartmentTitle">
                            <i class="fas fa-building text-primary"></i>
                            Add Department
                        </div>
                        <button type="button" class="btn btn-secondary btn-small" onclick="closeAddDepartmentModal()" aria-label="Close">
                            <i class="fas fa-times"></i>
                        </button>   
                    </div>
                    <form id="addDepartmentForm">
                        <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" />
                        <div class="hms-modal-body">
                            <div class="form-grid">
                                <div class="full">
                                    <label class="form-label" for="dept_name">Department Name*</label>
                                    <input id="dept_name" name="name" type="text" class="form-input" required autocomplete="off" placeholder="e.g., Radiology">
                                    <small id="err_dept_name" style="color:#dc2626"></small>
                                </div>
                                <div class="full">
                                    <label class="form-label" for="dept_description">Description</label>
                                    <textarea id="dept_description" name="description" rows="3" class="form-textarea" autocomplete="off" placeholder="Optional notes..."></textarea>
                                    <small id="err_dept_description" style="color:#dc2626"></small>
                                </div>
                            </div>
                        </div>
                        <div class="hms-modal-actions">
                            <button type="button" class="btn btn-secondary" onclick="closeAddDepartmentModal()">Cancel</button>
                            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save</button>
                        </div>
                    </form>
                </div>
            </div>

            <?= $this->include('unified/modals/edit-resource-modal', ['categories' => $categories ?? []]) ?>

            <script>
                window.HMS = {
                    baseUrl: '<?= rtrim(base_url(), '/') ?>',
                    csrf: { token: '<?= csrf_token() ?>', hash: '<?= csrf_hash() ?>' },
                    resourceManagementBaseUrl: '<?= match($userRole ?? '') {
                        'pharmacist' => 'pharmacist/resource-management',
                        'laboratorist' => 'laboratorist/resource-management',
                        default => 'admin/resource-management'
                    } ?>'
                };
                window.__RESOURCES__ = <?php echo json_encode($resources ?? []); ?>;
            </script>
            <script src="<?= base_url('assets/js/unified/modals/shared/resource-modal-utils.js') ?>"></script>
            <script src="<?= base_url('assets/js/unified/modals/add-resource-modal.js') ?>"></script>
            <script src="<?= base_url('assets/js/unified/modals/edit-resource-modal.js') ?>"></script>
            <script src="<?= base_url('assets/js/unified/modals/view-resource-modal.js') ?>"></script>
            <script src="<?= base_url('assets/js/unified/resource-management.js') ?>"></script>
        </main>
    </div>
</body>
</html>
