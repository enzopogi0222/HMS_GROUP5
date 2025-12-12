<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= base_url() ?>">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <meta name="csrf-hash" content="<?= csrf_hash() ?>">
    <meta name="user-role" content="<?= esc($userRole ?? 'admin') ?>">
    <title><?= esc($title ?? 'Room Management') ?> - HMS</title>

    <link rel="stylesheet" href="<?= base_url('assets/css/common.css') ?>" />
    <!-- Reuse the same unified styling as patient management for uniformity -->
    <link rel="stylesheet" href="<?= base_url('assets/css/unified/patient-management.css') ?>" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />

    <?php
      // Avoid notices if not passed
      $roomStats = $roomStats ?? [];
    ?>
</head>
<body class="<?= esc($userRole ?? 'admin') ?>">

<?= $this->include('template/header') ?>

<?= $this->include('unified/components/notification', [
    'id' => 'roomsNotification',
    'dismissFn' => 'dismissRoomsNotification()'
]) ?>

<div class="main-container">
    <?= $this->include('unified/components/sidebar') ?>

    <main class="content" role="main">
        <h1 class="page-title">
            <i class="fas fa-hotel"></i>
            <?= esc($title ?? 'Room Management') ?>
        </h1>

        <div class="page-actions">
            <button type="button" class="btn btn-primary" id="addRoomBtn" aria-label="Add New Room"><i class="fas fa-plus" aria-hidden="true"></i> Add New Room</button>
        </div>

        <br />

        <?php if (in_array($userRole ?? '', ['admin', 'doctor'])): ?>
        <!-- Overview cards, same style as other modules -->
        <div class="dashboard-overview" role="region" aria-label="Rooms Overview Cards">
            <div class="overview-card" tabindex="0">
                <div class="card-header-modern">
                    <div class="card-icon-modern blue">
                        <i class="fas fa-hotel"></i>
                    </div>
                    <div class="card-info">
                        <h3 class="card-title-modern">Total Rooms</h3>
                        <p class="card-subtitle">All configured rooms</p>
                    </div>
                </div>
                <div class="card-metrics">
                    <div class="metric">
                        <div class="metric-value blue">
                            <?= $roomStats['total_rooms'] ?? 0 ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overview-card" tabindex="0">
                <div class="card-header-modern">
                    <div class="card-icon-modern purple">
                        <i class="fas fa-bed"></i>
                    </div>
                    <div class="card-info">
                        <h3 class="card-title-modern">Occupancy</h3>
                        <p class="card-subtitle">Occupied vs Available</p>
                    </div>
                </div>
                <div class="card-metrics">
                    <div class="metric">
                        <div class="metric-value blue">
                            <?= $roomStats['occupied_rooms'] ?? 0 ?>
                        </div>
                        <div class="metric-label">Occupied</div>
                    </div>
                    <div class="metric">
                        <div class="metric-value green">
                            <?= $roomStats['available_rooms'] ?? 0 ?>
                        </div>
                        <div class="metric-label">Available</div>
                    </div>
                </div>
            </div>

            <div class="overview-card" tabindex="0">
                <div class="card-header-modern">
                    <div class="card-icon-modern orange">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div class="card-info">
                        <h3 class="card-title-modern">Maintenance</h3>
                        <p class="card-subtitle">Rooms not usable</p>
                    </div>
                </div>
                <div class="card-metrics">
                    <div class="metric">
                        <div class="metric-value orange">
                            <?= $roomStats['maintenance_rooms'] ?? 0 ?>
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
                    <label for="searchRoom" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                        <i class="fas fa-search"></i> Search
                    </label>
                    <input type="text" id="searchRoom" class="form-control" placeholder="Search rooms..." autocomplete="off">
                </div>
                <div class="filter-group" style="margin: 0;">
                    <label for="statusFilterRoom" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                        <i class="fas fa-filter"></i> Status
                    </label>
                    <select id="statusFilterRoom" class="form-control">
                        <option value="">All Status</option>
                        <option value="available">Available</option>
                        <option value="occupied">Occupied</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
                <div class="filter-group" style="margin: 0;">
                    <button type="button" id="clearFiltersRoom" class="btn btn-secondary" style="width: 100%;">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>
        </div>

        <!-- Rooms table (same visual style as patient table) -->
        <div class="patient-table-container">
            <div class="table-header">
                <h3>Rooms</h3>
            </div>
            <div class="table-responsive">
                <table class="table" id="roomsTable" aria-describedby="roomsTableCaption">
                    <thead>
                        <tr>
                            <th scope="col">Room</th>
                            <th scope="col">Type</th>
                            <th scope="col">Department</th>
                            <th scope="col">Capacity</th>
                            <th scope="col">Status</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="roomsTableBody">
                        <!-- Placeholder row while data is loading -->
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem;">
                                <i class="fas fa-spinner fa-spin"
                                   style="font-size: 2rem; color: #ccc; margin-bottom: 1rem;"
                                   aria-hidden="true"></i>
                                <p>Loading rooms...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?= $this->include('unified/modals/add-room-modal', ['roomTypes' => $roomTypes ?? [], 'departments' => $departments ?? []]) ?>
<?= $this->include('unified/modals/view-room-modal') ?>


<script>
window.roomTypeMetadata = <?= json_encode($roomTypeMetadata ?? [], JSON_HEX_TAG) ?>;
</script>

<script src="<?= base_url('assets/js/unified/modals/shared/room-modal-utils.js') ?>"></script>
<script src="<?= base_url('assets/js/unified/modals/add-room-modal.js') ?>"></script>
<script src="<?= base_url('assets/js/unified/modals/view-room-modal.js') ?>"></script>
<script src="<?= base_url('assets/js/unified/room-management.js') ?>"></script>

<?php if (session()->getFlashdata('success') || session()->getFlashdata('error')): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            showRoomsNotification(
                '<?= esc(session()->getFlashdata('success') ?: session()->getFlashdata('error'), 'js') ?>',
                '<?= session()->getFlashdata('success') ? 'success' : 'error' ?>'
            );
        });
    </script>
<?php endif; ?>

</body>
</html>