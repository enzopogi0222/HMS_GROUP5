<?php
// Shared role-specific fields component
$prefix = $prefix ?? '';
$specializations = ['Pediatrics', 'Cardiology', 'Internal Medicine', 'General Practice', 'Obstetrics and Gynecology', 'Surgery', 'Orthopedics', 'Neurology', 'Psychiatry', 'Dermatology', 'Ophthalmology', 'Otolaryngology', 'Emergency Medicine', 'Radiology', 'Anesthesiology'];
?>
<!-- Doctor-specific fields -->
<div id="<?= $prefix ?>doctorFields" class="full" style="display:none; grid-column: 1 / -1;">
    <div class="form-grid">
        <div>
            <label class="form-label" for="<?= $prefix ?>doctor_specialization">Doctor Specialization*</label>
            <select id="<?= $prefix ?>doctor_specialization" name="doctor_specialization" class="form-select">
                <option value="">Select specialization</option>
                <?php foreach ($specializations as $spec): ?><option value="<?= esc($spec) ?>"><?= esc($spec) ?></option><?php endforeach; ?>
            </select>
            <small id="<?= $prefix ?>err_doctor_specialization" style="color:#dc2626"></small>
        </div>
        <div>
            <label class="form-label" for="<?= $prefix ?>doctor_license_no">License No.</label>
            <input type="text" id="<?= $prefix ?>doctor_license_no" name="doctor_license_no" class="form-input" placeholder="e.g., PRC-1234567">
            <small id="<?= $prefix ?>err_doctor_license_no" style="color:#dc2626"></small>
        </div>
        <?php if ($prefix === 'e_'): ?>
        <div>
            <label class="form-label" for="<?= $prefix ?>doctor_consultation_fee">Consultation Fee</label>
            <input type="number" step="0.01" id="<?= $prefix ?>doctor_consultation_fee" name="doctor_consultation_fee" class="form-input" placeholder="e.g., 500.00">
            <small id="<?= $prefix ?>err_doctor_consultation_fee" style="color:#dc2626"></small>
        </div>
        <?php endif; ?>
    </div>
</div>
<!-- Accountant-specific fields -->
<div id="<?= $prefix ?>accountantFields" class="full" style="display:none; grid-column: 1 / -1;">
    <div class="form-grid">
        <div>
            <label class="form-label" for="<?= $prefix ?>accountant_license_no">Accountant License No.*</label>
            <input type="text" id="<?= $prefix ?>accountant_license_no" name="accountant_license_no" class="form-input" placeholder="e.g., ACC-1234567">
            <small id="<?= $prefix ?>err_accountant_license_no" style="color:#dc2626"></small>
        </div>
    </div>
</div>
<!-- Pharmacist-specific fields -->
<div id="<?= $prefix ?>pharmacistFields" class="full" style="display:none; grid-column: 1 / -1;">
    <div class="form-grid">
        <div>
            <label class="form-label" for="<?= $prefix ?>pharmacist_license_no">Pharmacist License No.*</label>
            <input type="text" id="<?= $prefix ?>pharmacist_license_no" name="pharmacist_license_no" class="form-input" placeholder="e.g., PHA-1357924">
            <small id="<?= $prefix ?>err_pharmacist_license_no" style="color:#dc2626"></small>
        </div>
        <div>
            <label class="form-label" for="<?= $prefix ?>pharmacist_specialization">Pharmacist Specialization</label>
            <input type="text" id="<?= $prefix ?>pharmacist_specialization" name="pharmacist_specialization" class="form-input" placeholder="e.g., Clinical Pharmacy">
            <small id="<?= $prefix ?>err_pharmacist_specialization" style="color:#dc2626"></small>
        </div>
    </div>
</div>
<!-- Laboratorist-specific fields -->
<div id="<?= $prefix ?>laboratoristFields" class="full" style="display:none; grid-column: 1 / -1;">
    <div class="form-grid">
        <div>
            <label class="form-label" for="<?= $prefix ?>laboratorist_license_no">Laboratorist License No.*</label>
            <input type="text" id="<?= $prefix ?>laboratorist_license_no" name="laboratorist_license_no" class="form-input" placeholder="e.g., LAB-9876543">
            <small id="<?= $prefix ?>err_laboratorist_license_no" style="color:#dc2626"></small>
        </div>
        <div>
            <label class="form-label" for="<?= $prefix ?>laboratorist_specialization">Laboratorist Specialization</label>
            <input type="text" id="<?= $prefix ?>laboratorist_specialization" name="laboratorist_specialization" class="form-input" placeholder="e.g., Hematology">
            <small id="<?= $prefix ?>err_laboratorist_specialization" style="color:#dc2626"></small>
        </div>
        <div>
            <label class="form-label" for="<?= $prefix ?>lab_room_no">Lab Room No.</label>
            <input type="text" id="<?= $prefix ?>lab_room_no" name="lab_room_no" class="form-input" placeholder="e.g., R-201">
            <small id="<?= $prefix ?>err_lab_room_no" style="color:#dc2626"></small>
        </div>
    </div>
</div>
<!-- Nurse-specific fields -->
<div id="<?= $prefix ?>nurseFields" class="full" style="display:none; grid-column: 1 / -1;">
    <div class="form-grid">
        <div>
            <label class="form-label" for="<?= $prefix ?>nurse_license_no">Nurse License No.*</label>
            <input type="text" id="<?= $prefix ?>nurse_license_no" name="nurse_license_no" class="form-input" placeholder="e.g., PRC-7654321">
            <small id="<?= $prefix ?>err_nurse_license_no" style="color:#dc2626"></small>
        </div>
        <div>
            <label class="form-label" for="<?= $prefix ?>nurse_specialization">Nurse Specialization</label>
            <input type="text" id="<?= $prefix ?>nurse_specialization" name="nurse_specialization" class="form-input" placeholder="e.g., ICU">
            <small id="<?= $prefix ?>err_nurse_specialization" style="color:#dc2626"></small>
        </div>
    </div>
</div>

