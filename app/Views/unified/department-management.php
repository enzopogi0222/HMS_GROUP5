<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= base_url() ?>">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <meta name="csrf-hash" content="<?= csrf_hash() ?>">
    <meta name="user-role" content="<?= esc($userRole ?? 'admin') ?>">
    <title><?= esc($title ?? 'Department Management') ?> - HMS</title>

    <link rel="stylesheet" href="<?= base_url('assets/css/common.css') ?>" />
    <!-- Reuse the same unified styling as patient management for uniformity -->
    <link rel="stylesheet" href="<?= base_url('assets/css/unified/patient-management.css') ?>" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />

    <?php
      // Avoid notices if not passed
      $departmentStats = $departmentStats ?? [];
    ?>
</head>

<body class="<?= esc($userRole ?? 'admin') ?>">

<?= $this->include('template/header') ?>

<?= $this->include('unified/components/notification', [
    'id' => 'departmentsNotification',
    'dismissFn' => 'dismissDepartmentsNotification()'
]) ?>

<div class="main-container">
    <?= $this->include('unified/components/sidebar') ?>

    <main class="content" role="main">

        <h1 class="page-title">
            <i class="fas fa-building"></i>
            <?= esc($title ?? 'Department Management') ?>
        </h1>

        <div class="page-actions">
            <button type="button" class="btn btn-primary" id="addDepartmentBtn" aria-label="Add New Department"><i class="fas fa-plus" aria-hidden="true"></i> Add Department</button>
        </div>

        <br />

        <?php if (in_array($userRole ?? '', ['admin', 'doctor'])): ?>
        <!-- Overview cards, same style as other modules -->
        <div class="dashboard-overview" role="region" aria-label="Department Overview Cards">
            <div class="overview-card" tabindex="0">
                <div class="card-header-modern">
                    <div class="card-icon-modern blue">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="card-info">
                        <h3 class="card-title-modern">Total Departments</h3>
                        <p class="card-subtitle">Active hospital units</p>
                    </div>
                </div>
                <div class="card-metrics">
                    <div class="metric">
                        <div class="metric-value blue">
                            <?= $departmentStats['total_departments'] ?? 0 ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overview-card" tabindex="0">
                <div class="card-header-modern">
                    <div class="card-icon-modern purple">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="card-info">
                        <h3 class="card-title-modern">Staff Coverage</h3>
                        <p class="card-subtitle">Departments with assigned heads</p>
                    </div>
                </div>
                <div class="card-metrics">
                    <div class="metric">
                        <div class="metric-value blue">
                            <?= $departmentStats['with_heads'] ?? 0 ?>
                        </div>
                        <div class="metric-label">With Head</div>
                    </div>
                    <div class="metric">
                        <div class="metric-value green">
                            <?= $departmentStats['without_heads'] ?? 0 ?>
                        </div>
                        <div class="metric-label">Unassigned</div>
                    </div>
                </div>
            </div>

            <div class="overview-card" tabindex="0">
                <div class="card-header-modern">
                    <div class="card-icon-modern orange">
                        <i class="fas fa-notes-medical"></i>
                    </div>
                    <div class="card-info">
                        <h3 class="card-title-modern">Specialties</h3>
                        <p class="card-subtitle">Departments with sub-specialties</p>
                    </div>
                </div>
                <div class="card-metrics">
                    <div class="metric">
                        <div class="metric-value orange">
                            <?= $departmentStats['with_specialties'] ?? 0 ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filters and Search -->
        <div class="controls-section" style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div class="filters-section" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                <div class="filter-group" style="margin: 0;">
                    <label for="searchDepartment" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                        <i class="fas fa-search"></i> Search
                    </label>
                    <input type="text" id="searchDepartment" class="form-control" placeholder="Search departments..." autocomplete="off">
                </div>
                <div class="filter-group" style="margin: 0;">
                    <button type="button" id="clearFiltersDepartment" class="btn btn-secondary" style="width: 100%;">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>
        </div>

        <!-- Departments table (same visual style as patient table) -->
        <div class="patient-table-container">
            <div class="table-header">
                <h3>Departments</h3>
            </div>
            <div class="table-responsive">
                <table class="table" id="departmentsTable" aria-describedby="departmentsTableCaption">
                    <thead>
                        <tr>
                            <th scope="col">Department</th>
                            <th scope="col">Head</th>
                            <th scope="col">Staff Count</th>
                            <th scope="col">Services / Specialties</th>
                            <th scope="col">Status</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="departmentsTableBody">
                        <!-- Placeholder row while backend/JS is pending -->
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem;">
                                <i class="fas fa-spinner fa-spin"
                                   style="font-size: 2rem; color: #ccc; margin-bottom: 1rem;"
                                   aria-hidden="true"></i>
                                <p>Loading departments...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?= $this->include('unified/modals/add-department-modal', [
    'departmentHeads' => $departmentHeads ?? [],
    'specialties' => $specialties ?? []
]) ?>

<?= $this->include('unified/modals/view-department-modal') ?>

<script src="<?= base_url('assets/js/unified/modals/shared/department-modal-utils.js') ?>"></script>
<script src="<?= base_url('assets/js/unified/modals/add-department-modal.js') ?>"></script>
<script src="<?= base_url('assets/js/unified/modals/view-department-modal.js') ?>"></script>
<script src="<?= base_url('assets/js/unified/department-management.js') ?>"></script>

<?php if (session()->getFlashdata('success') || session()->getFlashdata('error')): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showDepartmentsNotification(
                '<?= esc(session()->getFlashdata('success') ?: session()->getFlashdata('error'), 'js') ?>',
                '<?= session()->getFlashdata('success') ? 'success' : 'error' ?>'
            );
        });
    </script>
<?php endif; ?>

</body>
</html>