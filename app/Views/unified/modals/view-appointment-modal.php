<!-- View Appointment Modal -->
<div id="viewAppointmentModal" class="hms-modal-overlay" aria-hidden="true">
    <div class="hms-modal" role="dialog" aria-modal="true" aria-labelledby="viewAppointmentTitle">
        <div class="hms-modal-header">
            <div class="hms-modal-title" id="viewAppointmentTitle"><i class="fas fa-calendar-alt" style="color:#4f46e5"></i> Appointment Details</div>
            <button type="button" class="btn btn-secondary btn-small" id="closeViewAppointmentModal" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <form id="viewAppointmentForm">
            <div class="hms-modal-body">
                <div class="form-grid">
                    <div>
                        <label class="form-label" for="view_appointment_patient">Patient*</label>
                        <select id="view_appointment_patient" class="form-select" disabled>
                            <option value="">Select Patient...</option>
                        </select>
                    </div>
                    <?php if (in_array($userRole, ['admin', 'receptionist'])): ?>
                    <div>
                        <label class="form-label" for="view_appointment_doctor">Doctor*</label>
                        <select id="view_appointment_doctor" class="form-select" disabled>
                            <option value="">Select Doctor...</option>
                        </select>
                        <small class="form-text" style="color:#6b7280">Only doctors available on the selected date will appear in this list.</small>
                    </div>
                    <?php endif; ?>
                    <div>
                        <label class="form-label" for="view_appointment_date">Date*</label>
                        <input type="date" id="view_appointment_date" class="form-input" disabled>
                        <small id="view_appointment_date_help" class="form-text" style="color:#6b7280"></small>
                    </div>
                    <div>
                        <label class="form-label" for="view_appointment_type">Appointment Type*</label>
                        <select id="view_appointment_type" class="form-select" disabled>
                            <option value="">Select Type...</option>
                            <option value="Consultation">Consultation</option>
                            <option value="Follow-up">Follow-up</option>
                            <option value="Check-up">Check-up</option>
                        </select>
                    </div>
                    <div class="full">
                        <label class="form-label" for="view_appointment_notes">Additional Notes</label>
                        <textarea id="view_appointment_notes" class="form-input" rows="2" placeholder="Any additional notes or instructions..." disabled></textarea>
                    </div>
                </div>
            </div>
            <div class="hms-modal-actions">
                <button type="button" class="btn btn-secondary" id="closeViewAppointmentBtn">Close</button>
            </div>
        </form>
    </div>
</div>
