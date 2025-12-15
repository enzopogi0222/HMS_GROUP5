<!-- New Appointment Modal -->
<div id="newAppointmentModal" class="hms-modal-overlay" aria-hidden="true">
    <div class="hms-modal" role="dialog" aria-modal="true" aria-labelledby="newAppointmentTitle">
        <div class="hms-modal-header">
            <div class="hms-modal-title" id="newAppointmentTitle"><i class="fas fa-calendar-plus" style="color:#4f46e5"></i> Schedule New Appointment</div>
            <button type="button" class="btn btn-secondary btn-small" id="closeNewAppointmentModal" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <form id="newAppointmentForm">
            <input type="hidden" id="appointment_id" name="appointment_id" value="">
            <div class="hms-modal-body">
                <div class="form-grid"> 
                    <div>
                        <label class="form-label" for="appointment_patient">Patient*</label>
                        <select id="appointment_patient" name="patient_id" class="form-select" required>
                            <option value="">Select Patient...</option>
                        </select>
                        <small id="err_appointment_patient" style="color:#dc2626"></small>
                    </div>
                    <?php if (in_array($userRole, ['admin', 'receptionist'])): ?>
                    <div>
                        <label class="form-label" for="appointment_doctor">Doctor*</label>
                        <select id="appointment_doctor" name="doctor_id" class="form-select" required>
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
                        <small id="err_appointment_doctor" style="color:#dc2626"></small>
                    </div>
                    <?php endif; ?>
                    <div>
                        <label class="form-label" for="appointment_date">Date*</label>
                        <input type="date" id="appointment_date" name="appointment_date" class="form-input" required min="<?= date('Y-m-d') ?>">
                        <small id="appointment_date_help" class="form-text" style="color:#6b7280"></small>
                        <small id="err_appointment_date" style="color:#dc2626"></small>
                    </div>
                    <div>
                        <input type="hidden" id="appointment_time" name="appointment_time">
                        <small id="err_appointment_time" style="color:#dc2626"></small>
                        <div id="appointment_slots" class="form-text" style="color:#6b7280; margin-top:6px;"></div>
                    </div>
                    <div>
                        <label class="form-label" for="appointment_type">Appointment Type*</label>
                        <select id="appointment_type" name="appointment_type" class="form-select" required>
                            <option value="">Select Type...</option>
                            <option value="Consultation">Consultation</option>
                            <option value="Follow-up">Follow-up</option>
                            <option value="Check-up">Check-up</option>
                            <?php if (($userRole ?? '') === 'doctor'): ?>
                            <option value="Emergency">Emergency</option>
                            <?php endif; ?>
                        </select>
                        <small id="err_appointment_type" style="color:#dc2626"></small>
                    </div>
                    <div class="full">
                        <label class="form-label" for="appointment_notes">Additional Notes</label>
                        <textarea id="appointment_notes" name="notes" class="form-input" rows="2" placeholder="Any additional notes or instructions..."></textarea>
                    </div>
                </div>
            </div>
            <div class="hms-modal-actions">
                <button type="button" class="btn btn-secondary" id="cancelNewAppointmentBtn">Cancel</button>
                <button type="submit" id="saveAppointmentBtn" class="btn btn-success"><i class="fas fa-calendar-check"></i> Schedule Appointment</button>
            </div>
        </form>
    </div>
</div>
