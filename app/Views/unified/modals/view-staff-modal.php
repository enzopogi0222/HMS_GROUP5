<!-- View Staff Modal -->
<div id="viewStaffModal" class="hms-modal-overlay" aria-hidden="true">
    <div class="hms-modal" role="dialog" aria-modal="true" aria-labelledby="viewStaffTitle">
        <div class="hms-modal-header">
            <div class="hms-modal-title" id="viewStaffTitle"><i class="fas fa-id-badge" style="color:#4f46e5"></i> Staff Details</div>
            <button type="button" class="btn btn-secondary btn-small" onclick="closeViewStaffModal()" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <div class="hms-modal-body">
            <div class="form-grid">
                <?php foreach (['employee_id', 'first_name', 'last_name', 'gender', 'date_of_birth', 'contact_no', 'email', 'role', 'department', 'date_joined', 'status'] as $field): ?>
                <div>
                    <label class="form-label"><?= ucwords(str_replace('_', ' ', $field)) ?></label>
                    <input type="text" id="v_<?= $field ?>" class="form-input" readonly disabled>
                </div>
                <?php endforeach; ?>
                <div class="full">
                    <label class="form-label">Address</label>
                    <textarea id="v_address" class="form-input" rows="2" readonly disabled></textarea>
                </div>
            </div>
        </div>
        <div class="hms-modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeViewStaffModal()">Close</button>
        </div>
    </div>
</div>
