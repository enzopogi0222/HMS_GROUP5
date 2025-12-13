<div id="addDepartmentModal" class="hms-modal-overlay" hidden>
    <div class="hms-modal" role="dialog" aria-modal="true" aria-labelledby="addDepartmentTitle">
        <div class="hms-modal-header">
            <div class="hms-modal-title" id="addDepartmentTitle"><i class="fas fa-building" style="color:#2563eb"></i> Add Department</div>
            <button type="button" class="btn btn-secondary btn-small" data-modal-close="addDepartmentModal" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
 
        <div class="hms-modal-body">
            <form id="addDepartmentForm" class="patient-form" autocomplete="off">
                <div class="form-section">
                    <div class="section-header">
                        <div>
                            <h4>Department Details</h4>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div>
                            <label class="form-label" for="department_category">Department Category</label>
                            <select id="department_category" name="department_category" class="form-select" required>
                                <option value="">Select type...</option>
                                <option value="medical">Medical Department</option>
                                <option value="non_medical">Non-medical Department</option>
                            </select>
                            <small id="err_department_category" class="form-error"></small>
                        </div>
                        <div class="full">
                            <label class="form-label" for="department_name">Department Name*</label>
                            <input type="text" id="department_name" name="name" class="form-input" required maxlength="150" disabled list="department_name_suggestions">
                            <datalist id="department_name_suggestions"></datalist>
                            <input type="hidden" id="department_type" name="department_type" value="">
                            <small id="err_name" class="form-error"></small>
                            <small id="err_department_type" class="form-error"></small>
                        </div>
                        <div>
                            <label class="form-label" for="department_code">Department Code</label>
                            <input type="text" id="department_code" name="code" class="form-input" maxlength="50" placeholder="Optional">
                        </div>
                        <div>
                            <label class="form-label" for="department_floor">Floor</label>
                            <input type="text" id="department_floor" name="floor" class="form-input" placeholder="e.g., 3F" maxlength="100">
                        </div>
                        <div>
                            <label class="form-label" for="department_head">Head of Department</label>
                            <select id="department_head" name="department_head" class="form-select">
                                <option value="">Select doctor...</option>
                                <?php foreach (($departmentHeads ?? []) as $head): ?>
                                    <option value="<?= esc($head['staff_id']) ?>">
                                        <?= esc($head['full_name']) ?>
                                        <?= isset($head['position']) && $head['position'] ? ' - ' . esc($head['position']) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="department_status">Status</label>
                            <select id="department_status" name="status" class="form-select">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="full">
                            <label class="form-label" for="department_description">Description</label>
                            <textarea id="department_description" name="description" class="form-input" rows="3" placeholder="Department details"></textarea>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="hms-modal-actions">
            <button type="button" class="btn btn-secondary" data-modal-close="addDepartmentModal">Cancel</button>
            <button type="submit" class="btn btn-success" form="addDepartmentForm" id="saveDepartmentBtn"><i class="fas fa-save"></i> Save Department</button>
        </div>
    </div>
</div>
