<!-- View Room Modal -->
<div id="viewRoomModal" class="hms-modal-overlay" aria-hidden="true">
    <div class="hms-modal" role="dialog" aria-modal="true" aria-labelledby="viewRoomTitle" style="max-width: 800px;">
        <div class="hms-modal-header">
            <div class="hms-modal-title" id="viewRoomTitle">
                <i class="fas fa-hotel" style="color:#0ea5e9"></i>
                Room Details
            </div>
            <button type="button" class="btn btn-secondary btn-small" aria-label="Close" data-modal-close="viewRoomModal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="hms-modal-body">
            <div id="viewRoomContent">
                <div style="text-align: center; padding: 3rem;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #cbd5e0; margin-bottom: 1rem;" aria-hidden="true"></i>
                    <p style="color: #64748b;">Loading room details...</p>
                </div>
            </div>
        </div>
        <div class="hms-modal-actions" style="display: flex; justify-content: flex-end; gap: 0.75rem;">
            <button type="button" class="btn btn-secondary" data-modal-close="viewRoomModal">Close</button>
            <button type="button" class="btn btn-primary" id="editFromViewRoomBtn" style="display: none;">
                <i class="fas fa-edit"></i> Edit Room
            </button>
        </div>
    </div>
</div>

