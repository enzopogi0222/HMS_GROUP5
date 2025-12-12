<!-- Edit Shift Modal -->
<div id="editShiftModal" class="hms-modal-overlay" aria-hidden="true">
    <div class="hms-modal" role="dialog" aria-modal="true" aria-labelledby="editShiftTitle">
        <div class="hms-modal-header">
            <div class="hms-modal-title" id="editShiftTitle"><i class="fas fa-calendar-plus" style="color:#4f46e5"></i> Edit Shift</div>
            <button type="button" class="btn btn-secondary btn-small" id="closeEditShiftModal" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <form id="editShiftForm">
            <?= csrf_field() ?>
            <input type="hidden" id="editShiftId" name="id">
            <div class="hms-modal-body">
                <div class="form-grid">
                    <div>
                        <label class="form-label" for="editDoctorSelect">Doctor *</label>
                        <select id="editDoctorSelect" name="doctor_id" class="form-select" required>
                            <option value="">Select Doctor</option>
                            <?php if (!empty($availableDoctors)): ?>
                                <?php foreach ($availableDoctors as $doctor): ?>
                                    <option value="<?= esc($doctor['staff_id'] ?? $doctor['doctor_id']) ?>">
                                        <?= esc(trim(($doctor['first_name'] ?? '') . ' ' . ($doctor['last_name'] ?? ''))) ?>
                                        <?= !empty($doctor['specialization']) ? ' - ' . esc($doctor['specialization']) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">No doctors available</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div>
                        <label class="form-label" for="editWeekdays-group">Day(s) *</label>
                        <div id="editWeekdays-group" class="weekday-checkbox-group">
                            <?php 
                            $weekdays = ['Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6, 'Sunday' => 7];
                            foreach ($weekdays as $day => $value): ?>
                            <label class="checkbox-inline"><input type="checkbox" name="weekdays[]" value="<?= $value ?>"> <?= esc($day) ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div>
                        <label class="form-label" for="editShiftStart">Start Time *</label>
                        <input type="time" id="editShiftStart" name="start_time" class="form-input" required>
                    </div>

                    <div>
                        <label class="form-label" for="editShiftEnd">End Time *</label>
                        <input type="time" id="editShiftEnd" name="end_time" class="form-input" required>
                    </div>

                    <div>
                        <label class="form-label" for="editShiftStatus">Status</label>
                        <select id="editShiftStatus" name="status" class="form-select">
                            <option value="Scheduled">Scheduled</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>

                </div>
            </div>
            <div class="hms-modal-actions">
                <button type="button" class="btn btn-secondary" id="cancelEditShiftBtn">Cancel</button>
                <button type="submit" class="btn btn-success" id="saveEditShiftBtn">
                    <i class="fas fa-save"></i> Save Shift
                </button>
            </div>
        </form>
    </div>
</div>
