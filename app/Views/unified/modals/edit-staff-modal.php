<!-- Edit Staff Modal -->
<div id="editStaffModal" class="hms-modal-overlay" aria-hidden="true">
    <div class="hms-modal" role="dialog" aria-modal="true" aria-labelledby="editStaffTitle">
        <div class="hms-modal-header">
            <div class="hms-modal-title" id="editStaffTitle"><i class="fas fa-user-edit" style="color:#4f46e5"></i> Edit Staff</div>
            <button type="button" class="btn btn-secondary btn-small" onclick="closeEditStaffModal()" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <form id="editStaffForm">
            <input type="hidden" id="e_staff_id" name="staff_id">
            <div class="hms-modal-body">
                <div class="form-grid">
                    <?= $this->include('unified/modals/shared/common-fields', ['prefix' => 'e_', 'required' => false, 'departments' => $departments ?? []]) ?>
                    <?= $this->include('unified/modals/shared/role-fields', ['prefix' => 'e_']) ?>
                </div>
            </div>
            <div class="hms-modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeEditStaffModal()">Cancel</button>
                <button type="submit" id="updateStaffBtn" class="btn btn-success"><i class="fas fa-save"></i> Update Staff</button>
            </div>
        </form>
    </div>
</div>
S