<?php
// Shared common form fields component
$prefix = $prefix ?? '';
$required = $required ?? false;
$departments = $departments ?? [];
$defaultDepts = ['Administration','Emergency','Cardiology','Intensive Care Unit','Outpatient','Pharmacy','Laboratory','Radiology','Pediatrics','Surgery'];
?>
<div>
    <label class="form-label" for="<?= $prefix ?>employee_id">Employee ID</label>
    <input type="text" id="<?= $prefix ?>employee_id" name="employee_id" class="form-input" placeholder="e.g., DOC003">
    <small id="<?= $prefix ?>err_employee_id" style="color:#dc2626"></small>
</div>
<div>
    <label class="form-label" for="<?= $prefix ?>first_name">First Name*</label>
    <input type="text" id="<?= $prefix ?>first_name" name="first_name" class="form-input" required>
    <small id="<?= $prefix ?>err_first_name" style="color:#dc2626"></small>
</div>
<div>
    <label class="form-label" for="<?= $prefix ?>last_name">Last Name*</label>
    <input type="text" id="<?= $prefix ?>last_name" name="last_name" class="form-input" required>
    <small id="<?= $prefix ?>err_last_name" style="color:#dc2626"></small>
</div>
<div>
    <label class="form-label" for="<?= $prefix ?>gender">Gender<?= $required ? '*' : '' ?></label>
    <select id="<?= $prefix ?>gender" name="gender" class="form-select" <?= $required ? 'required' : '' ?>>
        <option value="">Select...</option>
        <option value="male">Male</option>
        <option value="female">Female</option>
        <option value="other">Other</option>
    </select>
    <small id="<?= $prefix ?>err_gender" style="color:#dc2626"></small>
</div>
<div>
    <label class="form-label" for="<?= $prefix ?>date_of_birth">Date of Birth</label>
    <input type="date" id="<?= $prefix ?>date_of_birth" name="date_of_birth" class="form-input">
    <small id="<?= $prefix ?>err_date_of_birth" style="color:#dc2626"></small>
</div>
<div>
    <label class="form-label" for="<?= $prefix ?>contact_no">Contact Number</label>
    <input type="text" id="<?= $prefix ?>contact_no" name="contact_no" class="form-input" inputmode="numeric" maxlength="11" pattern="^09\d{9}$">
    <small id="<?= $prefix ?>err_contact_no" style="color:#dc2626"></small>
</div>
<div>
    <label class="form-label" for="<?= $prefix ?>email">Email</label>
    <input type="email" id="<?= $prefix ?>email" name="email" class="form-input">
    <small id="<?= $prefix ?>err_email" style="color:#dc2626"></small>
</div>
<?php if ($prefix === ''): ?>
<div>
    <label class="form-label" for="department_category">Department Category</label>
    <select id="department_category" name="department_category" class="form-select" required>
        <option value="">Select category</option>
        <option value="medical">Medical Department</option>
        <option value="non_medical">Non-medical Department</option>
    </select>
    <small id="err_department_category" style="color:#dc2626"></small>
</div>
<?php else: ?>
<div>
    <label class="form-label" for="<?= $prefix ?>department_category">Department Category</label>
    <select id="<?= $prefix ?>department_category" name="department_category" class="form-select">
        <option value="">Select category</option>
        <option value="medical">Medical Department</option>
        <option value="non_medical">Non-medical Department</option>
    </select>
    <small id="<?= $prefix ?>err_department_category" style="color:#dc2626"></small>
</div>
<?php endif; ?>
<?php
    $deptSelectAttrs = [];
    if ($prefix === '') {
        $deptSelectAttrs[] = 'disabled';
        $deptSelectAttrs[] = 'required';
    }
?>
<div>
    <label class="form-label" for="<?= $prefix ?>department">Department</label>
    <select id="<?= $prefix ?>department" name="department" class="form-select" <?= implode(' ', $deptSelectAttrs) ?>>
        <option value="">Select department</option>
        <?php if (!empty($departments) && is_array($departments)): ?>
            <?php foreach ($departments as $dept): ?>
                <option value="<?= esc($dept['name'] ?? '') ?>" <?= isset($dept['department_id']) ? 'data-id="' . esc($dept['department_id']) . '"' : '' ?>><?= esc($dept['name'] ?? '') ?></option>
            <?php endforeach; ?>
        <?php else: ?>
            <?php foreach ($defaultDepts as $name): ?>
                <option value="<?= esc($name) ?>"><?= esc($name) ?></option>
            <?php endforeach; ?>
        <?php endif; ?>
    </select>
    <?php if ($prefix === ''): ?><input type="hidden" id="department_id" name="department_id" value=""><?php elseif ($prefix === 'e_'): ?><input type="hidden" id="e_department_id" name="department_id" value=""><?php endif; ?>
    <small id="<?= $prefix ?>err_department" style="color:#dc2626"></small>
</div>
<?= $this->include('unified/modals/shared/doctor-fields', ['prefix' => $prefix]) ?>
<div>
    <label class="form-label" for="<?= $prefix ?>date_joined">Date Joined</label>
    <input type="date" id="<?= $prefix ?>date_joined" name="date_joined" class="form-input">
    <small id="<?= $prefix ?>err_date_joined" style="color:#dc2626"></small>
</div>
<div class="full">
    <label class="form-label" for="<?= $prefix ?>address">Address</label>
    <textarea id="<?= $prefix ?>address" name="address" class="form-input" rows="2"></textarea>
    <small id="<?= $prefix ?>err_address" style="color:#dc2626"></small>
</div>

