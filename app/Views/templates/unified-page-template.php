<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= base_url() ?>">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <meta name="user-role" content="<?= esc($userRole ?? 'admin') ?>">
    <title><?= esc($title ?? 'HMS') ?> - HMS</title>
    <?php include APPPATH . 'Views/template/header.php'; ?>
    <?php
      // Initialize optional filter vars to avoid notices
      $search = $search ?? null;
      $statusFilter = $statusFilter ?? null;
      $typeFilter = $typeFilter ?? null;
    ?>
</head>
<body>
<div class="main-container">
    <!-- Unified Sidebar -->
    <?= $this->include('unified/components/sidebar') ?>

    <main class="content" role="main">
        <h1 class="page-title"><?= esc($title ?? 'Page Title') ?></h1>
        <div class="page-actions">
            <?php if (($permissions['canCreate'] ?? false)): ?>
                <button type="button" class="btn btn-primary" id="addBtn" aria-label="Add New Item">
                    <i class="fas fa-plus"></i> Add New
                </button>
            <?php endif; ?>
            <?php if (in_array($userRole ?? '', ['admin', 'it_staff'])): ?>
                <button type="button" class="btn btn-secondary" id="exportBtn" aria-label="Export Data">
                    <i class="fas fa-download"></i> Export
                </button>
            <?php endif; ?>
        </div>

        <!-- Flash Messages -->
        <?php if (session()->getFlashdata('success') || session()->getFlashdata('error')): ?>
            <div id="flashNotice" role="alert" aria-live="polite" style="
                margin-top: 1rem; padding: 0.75rem 1rem; border-radius: 8px;
                border: 1px solid <?= session()->getFlashdata('success') ? '#86efac' : '#fecaca' ?>;
                background: <?= session()->getFlashdata('success') ? '#dcfce7' : '#fee2e2' ?>;
                color: <?= session()->getFlashdata('success') ? '#166534' : '#991b1b' ?>; display:flex; align-items:center; gap:0.5rem;">
                <i class="fas <?= session()->getFlashdata('success') ? 'fa-check-circle' : 'fa-exclamation-triangle' ?>"></i>
                <span>
                    <?= esc(session()->getFlashdata('success') ?: session()->getFlashdata('error')) ?>
                </span>
                <button type="button" onclick="dismissFlash()" aria-label="Dismiss notification" style="margin-left:auto; background:transparent; border:none; cursor:pointer; color:inherit;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>

        <!-- Error Messages -->
        <?php $errors = session()->get('errors'); ?>
        <?php if (!empty($errors) && is_array($errors)): ?>
            <div role="alert" aria-live="polite" style="margin-top:0.75rem; padding:0.75rem 1rem; border-radius:8px; border:1px solid #fecaca; background:#fee2e2; color:#991b1b;">
                <div style="font-weight:600; margin-bottom:0.25rem;"><i class="fas fa-exclamation-circle"></i> Please fix the following errors:</div>
                <ul style="margin:0; padding-left:1.25rem;">
                    <?php foreach ($errors as $field => $msg): ?>
                        <li><?= esc(is_array($msg) ? implode(', ', $msg) : $msg) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <br />

        <!-- Dashboard Overview Cards -->
        <div class="dashboard-overview" role="region" aria-label="Dashboard Overview Cards">
            <?php if (in_array($userRole ?? '', ['admin', 'it_staff'])): ?>
                <!-- Example Card 1 -->
                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern blue" aria-hidden="true">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Total Items</h3>
                            <p class="card-subtitle">All Items</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value blue"><?= esc($stats['total'] ?? 0) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Example Card 2 -->
                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern purple" aria-hidden="true">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Active Items</h3>
                            <p class="card-subtitle">Currently Active</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value purple"><?= esc($stats['active'] ?? 0) ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Data Table -->
        <div class="data-table">
            <div class="table-header">
                <h3>Data Table</h3>
            </div>
            <table class="table" id="dataTable" aria-describedby="dataTableCaption">
                <thead>
                    <tr>
                        <th scope="col">Item</th>
                        <th scope="col">Type</th>
                        <th scope="col">Status</th>
                        <th scope="col">Date</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody id="dataTableBody">
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 2rem;">
                            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #ccc; margin-bottom: 1rem;" aria-hidden="true"></i>
                            <p>Loading data...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- Example Modal -->
<div id="exampleModal" class="hms-modal-overlay" hidden>
    <div class="hms-modal" role="dialog" aria-modal="true" aria-labelledby="exampleModalTitle">
        <div class="hms-modal-header">
            <div class="hms-modal-title" id="exampleModalTitle">
                <i class="fas fa-plus" style="color:#4f46e5"></i>
                Add New Item
            </div>
            <button type="button" class="btn btn-secondary btn-small" onclick="closeModal()" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="exampleForm">
            <div class="hms-modal-body">
                <div class="form-grid">
                    <div>
                        <label class="form-label" for="item_name">Item Name*</label>
                        <input type="text" id="item_name" name="item_name" class="form-input" required>
                        <small id="err_item_name" style="color:#dc2626"></small>
                    </div>
                    <div>
                        <label class="form-label" for="item_type">Type</label>
                        <select id="item_type" name="item_type" class="form-select">
                            <option value="">Select type...</option>
                            <option value="type1">Type 1</option>
                            <option value="type2">Type 2</option>
                        </select>
                        <small id="err_item_type" style="color:#dc2626"></small>
                    </div>
                    <div class="full">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description" name="description" class="form-input" rows="3"></textarea>
                        <small id="err_description" style="color:#dc2626"></small>
                    </div>
                </div>
            </div>
            <div class="hms-modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" id="saveBtn" class="btn btn-success"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Scripts -->
<script>
function dismissFlash() {
    const flashNotice = document.getElementById('flashNotice');
    if (flashNotice) {
        flashNotice.style.display = 'none';
    }
}

function openModal() {
    const modal = document.getElementById('exampleModal');
    if (modal) {
        modal.classList.add('active');
        modal.removeAttribute('hidden');
    }
}

function closeModal() {
    const modal = document.getElementById('exampleModal');
    if (modal) {
        modal.classList.remove('active');
        modal.setAttribute('hidden', '');
    }
}

// Add your page-specific JavaScript here
document.addEventListener('DOMContentLoaded', function() {
    // Initialize page functionality
});
</script>
</body>
</html>
