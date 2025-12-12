<!-- Edit User Modal -->
<div id="editUserModal" class="hms-modal-overlay" aria-hidden="true">
    <div class="hms-modal" role="dialog" aria-modal="true" aria-labelledby="editUserTitle">
        <div class="hms-modal-header">
            <div class="hms-modal-title" id="editUserTitle"><i class="fas fa-user-edit" style="color:#4f46e5"></i> Edit User</div>
            <button type="button" class="btn btn-secondary btn-small" onclick="closeEditUserModal()" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <form id="editUserForm">
            <input type="hidden" id="e_user_id" name="user_id">
            <div class="hms-modal-body">
                <div class="form-grid">
                    <div>
                        <label class="form-label" for="e_username">Username*</label>
                        <input type="text" id="e_username" name="username" class="form-input" required>
                        <small id="e_err_username" style="color:#dc2626"></small>
                    </div>
                    <div>
                        <label class="form-label" for="e_email">Email</label>
                        <input type="email" id="e_email" name="email" class="form-input">
                        <small id="e_err_email" style="color:#dc2626"></small>
                    </div>
                    <div>
                        <label class="form-label" for="e_status">Status</label>
                        <select id="e_status" name="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <small id="e_err_status" style="color:#dc2626"></small>
                    </div>
                    <div class="full">
                        <label class="form-label">Staff Information</label>
                        <div style="padding: 1rem; background: #f8fafc; border-radius: 4px; border: 1px solid #e5e7eb;">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                                <?php foreach (['Name' => 'e_staff_name', 'Employee ID' => 'e_employee_id', 'Department' => 'e_department'] as $label => $id): ?>
                                <div><strong><?= esc($label) ?>:</strong> <span id="<?= esc($id) ?>">-</span></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="hms-modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeEditUserModal()">Cancel</button>
                <button type="submit" id="updateUserBtn" class="btn btn-success"><i class="fas fa-save"></i> Update User</button>
            </div>
        </form>
    </div>
</div>
