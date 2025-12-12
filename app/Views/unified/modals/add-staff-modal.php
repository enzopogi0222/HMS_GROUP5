<!-- Add Staff Modal -->
<div id="addStaffModal" class="hms-modal-overlay" aria-hidden="true">
    <div class="hms-modal" role="dialog" aria-modal="true" aria-labelledby="addStaffTitle">
        <div class="hms-modal-header">
            <div class="hms-modal-title" id="addStaffTitle"><i class="fas fa-user-plus" style="color:#4f46e5"></i> Add New Staff</div>
            <button type="button" class="btn btn-secondary btn-small" onclick="closeAddStaffModal()" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <form id="addStaffForm">
            <div class="hms-modal-body">
                <div class="form-grid">
                    <div>
                        <label class="form-label" for="designation">Role/Designation*</label>
                        <p class="form-hint">Select a role first to load the correct requirements.</p>
                        <select id="designation" name="designation" class="form-select" required>
                            <option value="">Select role</option>
                            <?php foreach (['admin', 'doctor', 'nurse', 'pharmacist', 'receptionist', 'laboratorist', 'it_staff', 'accountant'] as $role): ?>
                            <option value="<?= $role ?>"><?= ucfirst(str_replace('_', ' ', $role)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small id="err_designation" style="color:#dc2626"></small>
                    </div>
                    <?= $this->include('unified/modals/shared/common-fields', ['prefix' => '', 'required' => true, 'departments' => $departments ?? [], 'excludeDesignation' => true]) ?>
                    <?= $this->include('unified/modals/shared/role-fields', ['prefix' => '']) ?>
                </div>
            </div>
            <div class="hms-modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeAddStaffModal()">Cancel</button>
                <button type="submit" id="saveStaffBtn" class="btn btn-success"><i class="fas fa-save"></i> Save Staff</button>
            </div>
        </form>
    </div>
</div>
