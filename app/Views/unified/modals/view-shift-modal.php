<!-- View Shift Modal -->
<div id="viewShiftModal" class="hms-modal-overlay" aria-hidden="true">
    <div class="hms-modal" role="dialog" aria-modal="true" aria-labelledby="viewShiftTitle">
        <div class="hms-modal-header">
            <div class="hms-modal-title" id="viewShiftTitle"><i class="fas fa-calendar-plus" style="color:#4f46e5"></i> View Shift</div>
            <button type="button" class="btn btn-secondary btn-small" id="closeViewShiftModal" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <div class="hms-modal-body">
            <div class="form-grid">
                <div>
                    <label class="form-label">Doctor *</label>
                    <input type="text" id="viewDoctorName" class="form-input" readonly disabled>
                </div>

                <div>
                    <label class="form-label">Day(s) *</label>
                    <div id="viewWeekdays-group" class="weekday-checkbox-group">
                        <?php 
                        $weekdays = ['Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6, 'Sunday' => 7];
                        foreach ($weekdays as $day => $value): ?>
                        <label class="checkbox-inline"><input type="checkbox" name="view_weekdays[]" value="<?= $value ?>" disabled> <?= esc($day) ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div>
                    <label class="form-label">Start Time *</label>
                    <input type="text" id="viewShiftStart" class="form-input" readonly disabled>
                </div>

                <div>
                    <label class="form-label">End Time *</label>
                    <input type="text" id="viewShiftEnd" class="form-input" readonly disabled>
                </div>

                <div>
                    <label class="form-label">Status</label>
                    <input type="text" id="viewShiftStatus" class="form-input" readonly disabled>
                </div>

            </div>
        </div>
        <div class="hms-modal-actions">
            <button type="button" class="btn btn-secondary" id="closeViewShiftBtn">Cancel</button>
        </div>
    </div>
</div>
