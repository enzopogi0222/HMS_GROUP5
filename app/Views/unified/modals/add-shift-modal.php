<!-- Add / Edit Shift Modal -->
<div id="shiftModal" class="hms-modal-overlay" aria-hidden="true">
    <div class="hms-modal" role="dialog" aria-modal="true" aria-labelledby="shiftModalTitle">
        <div class="hms-modal-header">
            <div class="hms-modal-title" id="shiftModalTitle"><i class="fas fa-calendar-plus" style="color:#4f46e5"></i> <span id="modalTitle">Create Shift</span></div>
            <button type="button" class="btn btn-secondary btn-small" id="closeShiftModal" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>

        <form id="shiftForm">
            <?= csrf_field() ?>
            <input type="hidden" id="shiftId" name="id">

            <div class="hms-modal-body">
                <div class="form-grid">
                    <div>
                        <label class="form-label" for="doctorSelect">Doctor *</label>
                        <select id="doctorSelect" name="doctor_id" class="form-select" required>
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
                        <label class="form-label" for="weekdays-group">Day(s) *</label>
                        <div id="weekdays-group" class="weekday-checkbox-group">
                            <?php 
                            $weekdays = ['Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6, 'Sunday' => 7];
                            foreach ($weekdays as $day => $value): ?>
                            <label class="checkbox-inline"><input type="checkbox" name="weekdays[]" value="<?= $value ?>"> <?= esc($day) ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div>
                        <label class="form-label" for="startTime">Start Time *</label>
                        <input type="time" id="startTime" name="start_time" class="form-input" required>
                    </div>

                    <div>
                        <label class="form-label" for="endTime">End Time *</label>
                        <input type="time" id="endTime" name="end_time" class="form-input" required>
                    </div>

                    <div>
                        <label class="form-label" for="shiftStatus">Status</label>
                        <select id="shiftStatus" name="status" class="form-select">
                            <option value="Scheduled">Scheduled</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>

                </div>
            </div>

            <div class="hms-modal-actions">
                <button type="button" class="btn btn-secondary" id="cancelShiftBtn">Cancel</button>
                <button type="submit" class="btn btn-success" id="saveShiftBtn">
                    <i class="fas fa-save"></i> Save Shift
                </button>
            </div>
        </form>
    </div>
</div>
