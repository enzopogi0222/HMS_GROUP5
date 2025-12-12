<!-- View User Modal -->
<div id="viewUserModal" class="hms-modal-overlay" aria-hidden="true">
    <div class="hms-modal" role="dialog" aria-modal="true" aria-labelledby="viewUserTitle">
        <div class="hms-modal-header">
            <div class="hms-modal-title" id="viewUserTitle">
                <i class="fas fa-id-badge" style="color:#4f46e5"></i>
                User Details
            </div>
            <button type="button" class="btn btn-secondary btn-small" onclick="closeViewUserModal()" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="hms-modal-body">
            <div class="form-grid">
                <?php
                $fields = [
                    'Username' => 'v_username',
                    'Full Name' => 'v_full_name',
                    'Email' => 'v_email',
                    'Role' => 'v_role',
                    'Department' => 'v_department',
                    'Status' => 'v_status',
                    'Employee ID' => 'v_employee_id',
                    'Created At' => 'v_created_at',
                    'Last Login' => 'v_last_login'
                ];
                foreach ($fields as $label => $id):
                ?>
                <div>
                    <label class="form-label"><?= esc($label) ?></label>
                    <input type="text" id="<?= esc($id) ?>" class="form-input" readonly disabled>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="hms-modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeViewUserModal()">Close</button>
        </div>
    </div>
</div>
