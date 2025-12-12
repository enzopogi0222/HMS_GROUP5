<!-- View Patient Modal -->
<div id="viewPatientModal" class="hms-modal-overlay" hidden>
    <div class="hms-modal" role="dialog" aria-modal="true" aria-labelledby="viewPatientTitle" style="max-width: 900px;">
        <div class="hms-modal-header">
            <div class="hms-modal-title" id="viewPatientTitle">
                <i class="fas fa-user" style="color:#4f46e5"></i>
                Patient Details
            </div>
            <button type="button" class="btn btn-secondary btn-small" onclick="closeViewPatientModal()" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="hms-modal-body">
            <div id="viewPatientContent">
                <div style="text-align: center; padding: 3rem;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #cbd5e0; margin-bottom: 1rem;" aria-hidden="true"></i>
                    <p style="color: #64748b;">Loading patient details...</p>
                </div>
            </div>
        </div>
        <div class="hms-modal-actions" style="display: flex; justify-content: flex-end; gap: 0.75rem;">
            <button type="button" class="btn btn-secondary" onclick="closeViewPatientModal()">Close</button>
            <?php if (in_array('edit', $permissions ?? [])): ?>
                <button type="button" class="btn btn-warning" id="editFromViewBtn">
                    <i class="fas fa-edit"></i> Edit Patient
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>
