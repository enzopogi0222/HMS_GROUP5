<div id="assignRoomModal" class="hms-modal-overlay" aria-hidden="true">
    <div class="hms-modal" role="dialog" aria-modal="true" aria-labelledby="assignRoomTitle" style="max-width: 720px;">
        <div class="hms-modal-header">
            <div class="hms-modal-title" id="assignRoomTitle"><i class="fas fa-user-plus" style="color:#0ea5e9"></i> Assign Room to Patient</div>
            <button type="button" class="btn btn-secondary btn-small" aria-label="Close" data-modal-close="assignRoomModal"><i class="fas fa-times"></i></button>
        </div>
        <form id="assignRoomForm">
            <?= csrf_field() ?>
            <input type="hidden" id="assign_room_id" name="room_id" />
            <div class="hms-modal-body">
                <div class="form-grid">
                    <div>
                        <label class="form-label" for="assign_patient_id">Patient</label>
                        <select id="assign_patient_id" name="patient_id" class="form-input" required>
                            <option value="">Select patient</option>
                        </select>
                        <small class="form-hint">Choose a patient to occupy this room.</small>
                    </div>
                </div>
            </div>
            <div class="hms-modal-actions">
                <button type="button" class="btn btn-secondary" data-modal-close="assignRoomModal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveAssignRoomBtn"><i class="fas fa-save"></i> Assign Room</button>
            </div>
        </form>
    </div>
</div>
