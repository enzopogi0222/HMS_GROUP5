<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= base_url() ?>">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <meta name="user-role" content="<?= esc($userRole ?? 'admin') ?>">
    <title><?= esc($title ?? 'User Management') ?> - HMS</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/common.css') ?>" />
    <link rel="stylesheet" href="<?= base_url('assets/css/unified/user-management.css') ?>" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
</head>
<?= $this->include('template/header') ?>

<?= $this->include('unified/components/notification', ['id' => 'usersNotification', 'dismissFn' => 'dismissUserNotification()']) ?>

<div class="main-container">
    <?= $this->include('unified/components/sidebar') ?>

    <main class="content" role="main">
        <h1 class="page-title">
            <i class="fas fa-user-shield"></i>
            <?= esc($title ?? 'User Management') ?>
        </h1>
        <div class="page-actions">
            <?php if (($permissions['canCreate'] ?? false)): ?>
                <button type="button" class="btn btn-primary" id="addUserBtn" aria-label="Add New User"><i class="fas fa-plus"></i> Add New User</button>
            <?php endif; ?>
            <?php if (in_array($userRole ?? '', ['admin', 'it_staff'])): ?>
                <button type="button" class="btn btn-secondary" id="exportBtn" aria-label="Export Data"><i class="fas fa-download"></i> Export</button>
            <?php endif; ?>
        </div>
        <?php if (in_array($userRole ?? '', ['admin', 'doctor'])): ?>
        <br />

        <div class="dashboard-overview" role="region" aria-label="Dashboard Overview Cards">
            <?php if (in_array($userRole ?? '', ['admin', 'it_staff'])): ?>
                <!-- Total Users Card -->
                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern blue" aria-hidden="true">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Total Users</h3>
                            <p class="card-subtitle">All Registered Users</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value blue"><?= esc($userStats['total_users'] ?? 0) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Admin Users Card -->
                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern purple" aria-hidden="true">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Admin Users</h3>
                            <p class="card-subtitle">System Administrators</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value purple"><?= esc($userStats['admin_users'] ?? 0) ?></div>
                        </div>
                    </div>
                </div>
            <?php elseif ($userRole === 'doctor'): ?>
                <!-- Department Users Card -->
                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern blue" aria-hidden="true">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Department Users</h3>
                            <p class="card-subtitle">My Department</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value blue"><?= esc($userStats['department_users'] ?? 0) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Active Users Card -->
                <div class="overview-card" tabindex="0">
                    <div class="card-header-modern">
                        <div class="card-icon-modern purple" aria-hidden="true">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="card-info">
                            <h3 class="card-title-modern">Active Users</h3>
                            <p class="card-subtitle">Currently Active</p>
                        </div>
                    </div>
                    <div class="card-metrics">
                        <div class="metric">
                            <div class="metric-value purple"><?= esc($userStats['department_active'] ?? 0) ?></div>
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
                    <input type="text" id="searchFilter" class="form-control" placeholder="Search users..." autocomplete="off">
                </div>
                <div class="filter-group" style="margin: 0;">
                    <label for="roleFilter" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                        <i class="fas fa-filter"></i> Role
                    </label>
                    <select id="roleFilter" class="form-control">
                        <option value="">All Roles</option>
                        <option value="admin">Admin</option>
                        <option value="doctor">Doctor</option>
                        <option value="nurse">Nurse</option>
                        <option value="receptionist">Receptionist</option>
                        <option value="accountant">Accountant</option>
                        <option value="laboratorist">Laboratorist</option>
                        <option value="it_staff">IT Staff</option>
                    </select>
                </div>
                <div class="filter-group" style="margin: 0;">
                    <label for="statusFilter" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                        <i class="fas fa-filter"></i> Status
                    </label>
                    <select id="statusFilter" class="form-control">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="filter-group" style="margin: 0;">
                    <button type="button" onclick="clearFilters()" class="btn btn-secondary" style="width: 100%;">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>
        </div>

        <div class="user-table">
            <div class="table-header">
                <h3>Users</h3>
            </div>
            <table class="table" id="usersTable" aria-describedby="usersTableCaption">
                <thead>
                    <tr>
                        <th scope="col">User</th>
                        <th scope="col">Role</th>
                        <th scope="col">Department</th>
                        <th scope="col">Status</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <tr class="user-row">
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <div>
                                            <div style="font-weight: 600;">
                                                <?= esc(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?>
                                            </div>
                                            <div style="font-size: 0.8rem; color: #6b7280;">
                                                <?= esc($user['email'] ?? 'No email') ?>
                                            </div>
                                            <div style="font-size: 0.8rem; color: #6b7280;">
                                             <?= esc($user['username'] ?? $user['user_id'] ?? 'N/A') ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                        $roleSlug = $user['role_slug'] ?? null;
                                        $roleName = $user['role_name'] ?? null;
                                        $roleClass = $roleSlug ? strtolower(str_replace('_', '-', $roleSlug)) : 'user';
                                        $roleLabel = $roleName ?: ($roleSlug ? ucfirst(str_replace('_', ' ', $roleSlug)) : 'User');
                                    ?>
                                    <span class="role-badge role-<?= esc($roleClass) ?>">
                                        <?= esc($roleLabel) ?>
                                    </span>
                                </td>
                                <td><?= esc($user['department'] ?? 'N/A') ?></td>
                                <td>
                                    <?php $status = strtolower($user['status'] ?? 'active'); ?>
                                    <span class="status-badge <?= esc($status) ?>">
                                        <?= esc(ucfirst($status)) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($permissions['canEdit'] ?? false): ?>
                                            <button class="btn btn-warning btn-small action-btn" data-action="edit" data-user-id="<?= esc($user['user_id']) ?>" aria-label="Edit User"><i class="fas fa-edit"></i> Edit</button>
                                        <?php endif; ?>
                                        <?php if ($permissions['canResetPassword'] ?? false): ?>
                                            <button class="btn btn-primary btn-small action-btn" data-action="reset" data-user-id="<?= esc($user['user_id']) ?>" aria-label="Reset Password"><i class="fas fa-key"></i> Reset</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 2rem;">
                                <i class="fas fa-users" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;" aria-hidden="true"></i>
                                <p><?= ($userRole ?? '') === 'it_staff' ? 'No inactive users found.' : 'No users found.' ?></p>
                                <?php if (($permissions['canCreate'] ?? false)): ?>
                                    <button type="button" class="btn btn-primary" onclick="document.getElementById('addUserBtn').click()" aria-label="Add First User">
                                        <i class="fas fa-plus" aria-hidden="true"></i> Add Your First User
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

        <!-- Modals -->
        <?= $this->include('unified/modals/add-user-modal') ?>
        <?= $this->include('unified/modals/view-user-modal') ?>
        <?= $this->include('unified/modals/edit-user-modal') ?>
        <?= $this->include('unified/modals/reset-password-modal') ?>

        <!-- Scripts -->
        <script src="<?= base_url('assets/js/unified/user-utils.js') ?>"></script>
        <script src="<?= base_url('assets/js/unified/modals/shared/user-modal-utils.js') ?>"></script>
        <script src="<?= base_url('assets/js/unified/modals/add-user-modal.js') ?>"></script>
        <script src="<?= base_url('assets/js/unified/modals/view-user-modal.js') ?>"></script>
        <script src="<?= base_url('assets/js/unified/modals/edit-user-modal.js') ?>"></script>
        <script src="<?= base_url('assets/js/unified/modals/reset-password-modal.js') ?>"></script>
        <script src="<?= base_url('assets/js/unified/user-management.js') ?>"></script>
