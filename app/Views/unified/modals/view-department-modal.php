<!-- View Department Modal -->
<div id="viewDepartmentModal" class="hms-modal-overlay" hidden>
    <div class="hms-modal" role="dialog" aria-modal="true" aria-labelledby="viewDepartmentTitle" style="max-width: 800px;">
        <div class="hms-modal-header">
            <div class="hms-modal-title" id="viewDepartmentTitle">
                <i class="fas fa-building" style="color:#4f46e5"></i>
                Department Details
            </div>
            <button type="button" class="btn btn-secondary btn-small" onclick="closeViewDepartmentModal()" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="hms-modal-body">
            <div id="viewDepartmentContent">
                <div style="text-align: center; padding: 3rem;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #cbd5e0; margin-bottom: 1rem;" aria-hidden="true"></i>
                    <p style="color: #64748b;">Loading department details...</p>
                </div>
            </div>
        </div>
        <div class="hms-modal-actions" style="display: flex; justify-content: flex-end; gap: 0.75rem;">
            <button type="button" class="btn btn-secondary" onclick="closeViewDepartmentModal()">Close</button>
            <button type="button" class="btn btn-warning" id="editFromViewDepartmentBtn" style="display: none;">
                <i class="fas fa-edit"></i> Edit Department
            </button>
        </div>
    </div>
</div>
