<!-- Edit Appointment Modal -->
<div id="editAppointmentModal" class="hms-modal-overlay" aria-hidden="true">
    <div class="hms-modal" role="dialog" aria-modal="true" aria-labelledby="editAppointmentTitle">
        <div class="hms-modal-header">
            <div class="hms-modal-title" id="editAppointmentTitle"><i class="fas fa-edit" style="color:#4f46e5"></i> Edit Appointment</div>
            <button type="button" class="btn btn-secondary btn-small" id="closeEditAppointmentModal" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <form id="editAppointmentForm">
            <input type="hidden" id="edit_appointment_id" name="appointment_id" value="">
            <div class="hms-modal-body">
                <div class="form-grid"> 
                    <div>
                        <label class="form-label" for="edit_appointment_patient">Patient*</label>
                        <select id="edit_appointment_patient" name="patient_id" class="form-select" required>
                            <option value="">Select Patient...</option>
                        </select>
                        <small id="err_edit_appointment_patient" style="color:#dc2626"></small>
                    </div>
                    <?php if (in_array($userRole, ['admin', 'receptionist'])): ?>
                    <div>
                        <label class="form-label" for="edit_appointment_doctor">Doctor*</label>
                        <select id="edit_appointment_doctor" name="doctor_id" class="form-select" required>
                            <option value="">Select Doctor...</option>
                            <?php if (!empty($doctors_for_modal)): foreach ($doctors_for_modal as $doctor): ?>
                                <option value="<?= esc($doctor['staff_id'] ?? $doctor['id']) ?>">
                                    <?= esc(trim(($doctor['first_name'] ?? '') . ' ' . ($doctor['last_name'] ?? ''))) ?><?= !empty($doctor['specialization']) ? ' - ' . esc($doctor['specialization']) : '' ?>
                                </option>
                            <?php endforeach; else: ?>
                                <option value="">No doctors available</option>
                            <?php endif; ?>
                        </select>
                        <small class="form-text" style="color:#6b7280">Only doctors available on the selected date will appear in this list.</small>
                        <small id="err_edit_appointment_doctor" style="color:#dc2626"></small>
                    </div>
                    <?php endif; ?>
                    <div>
                        <label class="form-label" for="edit_appointment_date">Date*</label>
                        <input type="date" id="edit_appointment_date" name="appointment_date" class="form-input" required min="<?= date('Y-m-d') ?>">
                        <small id="edit_appointment_date_help" class="form-text" style="color:#6b7280"></small>
                        <small id="err_edit_appointment_date" style="color:#dc2626"></small>
                    </div>
                    <div>
                        <label class="form-label" for="edit_appointment_type">Appointment Type*</label>
                        <select id="edit_appointment_type" name="appointment_type" class="form-select" required>
                            <option value="">Select Type...</option>
                            <option value="Consultation">Consultation</option>
                            <option value="Follow-up">Follow-up</option>
                            <option value="Check-up">Check-up</option>
                        </select>
                        <small id="err_edit_appointment_type" style="color:#dc2626"></small>
                    </div>
                    <div class="full">
                        <label class="form-label" for="edit_appointment_notes">Additional Notes</label>
                        <textarea id="edit_appointment_notes" name="notes" class="form-input" rows="2" placeholder="Any additional notes or instructions..."></textarea>
                    </div>
                </div>
            </div>
            <div class="hms-modal-actions">
                <button type="button" class="btn btn-secondary" id="cancelEditAppointmentBtn">Cancel</button>
                <button type="submit" id="saveEditAppointmentBtn" class="btn btn-success"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

