<!-- Assign Room Modal -->
<div id="assignRoomModal" class="hms-modal-overlay" aria-hidden="true">
    <div class="hms-modal" role="dialog" aria-modal="true" aria-labelledby="assignRoomTitle" style="max-width: 800px;">
        <div class="hms-modal-header">
            <div class="hms-modal-title" id="assignRoomTitle">
                <i class="fas fa-bed" style="color:#0ea5e9"></i> Assign Room to Patient
            </div>
            <button type="button" class="btn btn-secondary btn-small" aria-label="Close" data-modal-close="assignRoomModal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="assignRoomForm">
            <?= csrf_field() ?>
            <input type="hidden" id="assign_admission_id" name="admission_id" />
            <div class="hms-modal-body">
                <!-- Patient Selection -->
                <div class="form-section" style="margin-bottom: 1.5rem;">
                    <h4 style="margin-bottom: 1rem; color: #1e293b; font-size: 1.1rem;">Patient Information</h4>
                    <div class="form-grid">
                        <div class="full">
                            <label class="form-label" for="assign_patient_id">Patient*</label>
                            <select id="assign_patient_id" name="patient_id" class="form-select" required>
                                <option value="">Select patient...</option>
                            </select>
                            <small class="form-hint">Search and select a patient to assign to a room.</small>
                            <small id="err_assign_patient_id" class="form-error"></small>
                        </div>
                    </div>
                </div>

                <!-- Room Assignment -->
                <div class="form-section" style="margin-bottom: 1.5rem;">
                    <h4 style="margin-bottom: 1rem; color: #1e293b; font-size: 1.1rem;">Room Assignment</h4>
                    <div class="form-grid">
                        <div>
                            <label class="form-label" for="assign_department_id">Department*</label>
                            <select id="assign_department_id" name="department_id" class="form-select" required>
                                <option value="">Select department...</option>
                                <?php if (!empty($departments)): ?>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= esc($dept['department_id']) ?>">
                                            <?= esc($dept['name'] ?? '') ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <small id="err_assign_department_id" class="form-error"></small>
                        </div>
                        <div>
                            <label class="form-label" for="assign_room_type">Room Type*</label>
                            <select id="assign_room_type" name="room_type" class="form-select" required>
                                <option value="">Select room type...</option>
                                <?php if (!empty($roomTypes)): ?>
                                    <?php foreach ($roomTypes as $type): ?>
                                        <option value="<?= esc($type['room_type_id']) ?>" 
                                                data-rate="<?= esc($type['base_daily_rate'] ?? '') ?>">
                                            <?= esc($type['type_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <small id="err_assign_room_type" class="form-error"></small>
                        </div>
                        <div>
                            <label class="form-label" for="assign_floor_number">Floor Number*</label>
                            <select id="assign_floor_number" name="floor_number" class="form-select" required disabled>
                                <option value="">Select a floor...</option>
                            </select>
                            <small id="err_assign_floor_number" class="form-error"></small>
                        </div>
                        <div>
                            <label class="form-label" for="assign_room_number">Room Number*</label>
                            <select id="assign_room_number" name="room_number" class="form-select" required disabled>
                                <option value="">Select a room...</option>
                            </select>
                            <small id="err_assign_room_number" class="form-error"></small>
                        </div>
                        <div>
                            <label class="form-label" for="assign_bed_number">Bed Number*</label>
                            <select id="assign_bed_number" name="bed_number" class="form-select" required disabled>
                                <option value="">Select a bed...</option>
                            </select>
                            <small id="err_assign_bed_number" class="form-error"></small>
                        </div>
                        <div>
                            <label class="form-label" for="assign_daily_rate">Daily Room Rate</label>
                            <input type="text" id="assign_daily_rate" name="daily_rate" class="form-input" readonly value="Auto-calculated">
                        </div>
                        <div>
                            <label class="form-label" for="assign_assigned_at">Assignment Date & Time*</label>
                            <input type="datetime-local" id="assign_assigned_at" name="assigned_at" class="form-input" required>
                            <small id="err_assign_assigned_at" class="form-error"></small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="hms-modal-actions" style="display: flex; justify-content: flex-end; gap: 0.75rem; padding: 1rem 1.25rem;">
                <button type="button" class="btn btn-secondary" data-modal-close="assignRoomModal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveAssignRoomBtn">
                    <i class="fas fa-save"></i> Assign Room
                </button>
            </div>
        </form>
    </div>
</div>
