<!-- Add User Modal -->
<div id="addUserModal" class="hms-modal-overlay" aria-hidden="true">
    <div class="hms-modal" role="dialog" aria-modal="true" aria-labelledby="addUserTitle">
        <div class="hms-modal-header">
            <div class="hms-modal-title" id="addUserTitle"><i class="fas fa-user-plus" style="color:#4f46e5"></i> Add New User</div>
            <button type="button" class="btn btn-secondary btn-small" onclick="closeAddUserModal()" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <form id="addUserForm">
            <div class="hms-modal-body">
                <div class="form-grid">
                    <div>
                        <label class="form-label" for="staff_id">Staff Member*</label>
                        <select id="staff_id" name="staff_id" class="form-select" required>
                            <?php if (!empty($availableStaff) && is_array($availableStaff)): ?>
                                <option value="">Select staff member...</option>
                                <?php foreach ($availableStaff as $staff): 
                                    $fullName = trim(($staff['first_name'] ?? '') . ' ' . ($staff['last_name'] ?? '')) ?: 'Unnamed';
                                    $display = sprintf('%s - %s (%s)', $fullName, $staff['department'] ?? 'No Department', $staff['employee_id'] ?? $staff['staff_id'] ?? '');
                                ?>
                                    <option value="<?= esc($staff['staff_id']) ?>" data-email="<?= esc($staff['email'] ?? '') ?>" data-role="<?= esc($staff['role'] ?? $staff['designation'] ?? '') ?>"><?= esc($display) ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">No available staff members</option>
                            <?php endif; ?>
                        </select>
                        <small id="err_staff_id" style="color:#dc2626"></small>
                    </div>
                    <div>
                        <label class="form-label" for="username">Username*</label>
                        <input type="text" id="username" name="username" class="form-input" required>
                        <small id="err_username" style="color:#dc2626"></small>
                    </div>
                    <div>
                        <label class="form-label" for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-input" readonly>
                        <small class="form-help">Email will be populated from selected staff member</small>
                        <small id="err_email" style="color:#dc2626"></small>
                    </div>
                    <div>
                        <label class="form-label" for="password">Password*</label>
                        <input type="password" id="password" name="password" class="form-input" required minlength="6">
                        <small class="form-help">Minimum 6 characters</small>
                        <small id="err_password" style="color:#dc2626"></small>
                    </div>
                    <div>
                        <label class="form-label" for="confirm_password">Confirm Password*</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                        <small id="err_confirm_password" style="color:#dc2626"></small>
                    </div>
                    <div>
                        <label class="form-label" for="status">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <small id="err_status" style="color:#dc2626"></small>
                    </div>
                </div>
            </div>
            <div class="hms-modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeAddUserModal()">Cancel</button>
                <button type="submit" id="saveUserBtn" class="btn btn-success"><i class="fas fa-save"></i> Create User</button>
            </div>
        </form>
    </div>
</div>
