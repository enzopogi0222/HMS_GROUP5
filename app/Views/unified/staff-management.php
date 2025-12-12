<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= base_url() ?>">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <meta name="user-role" content="<?= esc($userRole ?? 'admin') ?>">
    <title><?= esc($title ?? 'Staff Management') ?> - HMS</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/common.css') ?>" />
     <link rel="stylesheet" href="<?= base_url('assets/css/unified/staff-management.css') ?>" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <?php
      // Initialize optional filter vars to avoid notices
      $search = $search ?? null;
      $roleFilter = $roleFilter ?? null;
      $statusFilter = $statusFilter ?? null;
    ?>
</head>
<?= $this->include('template/header') ?>
<?= $this->include('unified/components/notification', [
    'id' => 'staffNotification',
    'dismissFn' => 'dismissStaffNotification()'
]) ?>

<div class="main-container">
    <!-- Unified Sidebar -->
    <?= $this->include('unified/components/sidebar') ?>

    <main class="content" role="main">
        <h1 class="page-title">
            <i class="fas fa-users-cog"></i>
            <?= esc($title ?? 'Staff Management') ?>
        </h1>
        <div class="page-actions">
            <?php if (($permissions['canCreate'] ?? false) || in_array($userRole ?? '', ['admin', 'it_staff'])): ?>
                <button type="button" class="btn btn-primary" id="addStaffBtn" aria-label="Add New Staff"><i class="fas fa-plus" aria-hidden="true"></i> Add New Staff</button>
            <?php endif; ?>
            <?php if (in_array($userRole ?? '', ['admin', 'it_staff'])): ?>
                <button type="button" class="btn btn-secondary" id="exportBtn" aria-label="Export Data"><i class="fas fa-download" aria-hidden="true"></i> Export</button>
            <?php endif; ?>
        </div>
        
        <?php if (in_array($userRole ?? '', ['admin', 'doctor'])): ?>
        <br />

        <div class="dashboard-overview" role="region" aria-label="Dashboard Overview Cards">
            <?php if (in_array($userRole ?? '', ['admin', 'it_staff'])): ?>
                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern blue"><i class="fas fa-users"></i></div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Total Staff</h3>
                            <p class="card-subtitle">All Staff Members</p>
                        </div>
                    </div>
                    <div class="card-metrics"><div class="metric"><div class="metric-value blue"><?= $staffStats['total_staff'] ?? 0 ?></div></div></div>
                </div>
            <?php elseif ($userRole === 'doctor'): ?>
                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern blue"><i class="fas fa-users"></i></div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Department Staff</h3>
                            <p class="card-subtitle">My Department</p>
                        </div>
                    </div>
                    <div class="card-metrics"><div class="metric"><div class="metric-value blue"><?= $staffStats['department_staff'] ?? 0 ?></div></div></div>
                </div>
                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern purple"><i class="fas fa-user-md"></i></div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Medical Staff</h3>
                            <p class="card-subtitle">Doctors vs Nurses</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric"><div class="metric-value blue"><?= $staffStats['doctors'] ?? 0 ?></div><div class="metric-label">Doctors</div></div>
                        <div class="metric"><div class="metric-value green"><?= $staffStats['nurses'] ?? 0 ?></div><div class="metric-label">Nurses</div></div>
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
                    <input type="text" id="searchFilter" class="form-control" placeholder="Search staff..." autocomplete="off">
                </div>
                <div class="filter-group" style="margin: 0;">
                    <label for="roleFilter" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                        <i class="fas fa-filter"></i> Role
                    </label>
                    <select id="roleFilter" class="form-control">
                        <option value="">All Roles</option>
                        <option value="doctor">Doctor</option>
                        <option value="nurse">Nurse</option>
                        <option value="receptionist">Receptionist</option>
                        <option value="accountant">Accountant</option>
                        <option value="laboratorist">Laboratorist</option>
                        <option value="it_staff">IT Staff</option>
                    </select>
                </div>
                <div class="filter-group" style="margin: 0;">
                    <button type="button" onclick="clearFilters()" class="btn btn-secondary" style="width: 100%;">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>
        </div>

        <div class="staff-table-container">
            <div class="table-header">
                <h3>Staff</h3>
            </div>
            <div class="table-responsive">
                <table class="table" id="staffTable" aria-describedby="staffTableCaption">
                    <thead>
                        <tr>
                            <th scope="col">Staff</th>
                            <th scope="col">Role</th>
                            <th scope="col">Department</th>
                            <th scope="col">Date Joined</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="staffTableBody">
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 2rem;">
                                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #ccc; margin-bottom: 1rem;" aria-hidden="true"></i>
                                <p>Loading staff...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Modals -->
<?= $this->include('unified/modals/add-staff-modal') ?>
<?= $this->include('unified/modals/view-staff-modal') ?>
<?= $this->include('unified/modals/edit-staff-modal') ?>

<!-- Staff Management Scripts -->
<script src="<?= base_url('assets/js/unified/staff-utils.js') ?>"></script>
<script src="<?= base_url('assets/js/unified/modals/shared/staff-modal-utils.js') ?>"></script>
<script src="<?= base_url('assets/js/unified/modals/add-staff-modal.js') ?>"></script>
<script src="<?= base_url('assets/js/unified/modals/view-staff-modal.js') ?>"></script>
<script src="<?= base_url('assets/js/unified/modals/edit-staff-modal.js') ?>"></script>
<script src="<?= base_url('assets/js/unified/staff-management.js') ?>"></script>
</body>
</html>
