<?php
$prefix = $prefix ?? '';
$specializations = ['Pediatrics', 'Cardiology', 'Internal Medicine', 'General Practice', 'Obstetrics and Gynecology', 'Surgery', 'Orthopedics', 'Neurology', 'Psychiatry', 'Dermatology', 'Ophthalmology', 'Otolaryngology', 'Emergency Medicine', 'Radiology', 'Anesthesiology'];
?>
<div id="<?= $prefix ?>doctorFields" class="full" style="display:none; grid-column: 1 / -1;">
    <div class="form-grid">
        <div>
            <label class="form-label" for="<?= $prefix ?>doctor_specialization">Doctor Specialization*</label>
            <select id="<?= $prefix ?>doctor_specialization" name="doctor_specialization" class="form-select">
                <option value="">Select specialization</option>
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
