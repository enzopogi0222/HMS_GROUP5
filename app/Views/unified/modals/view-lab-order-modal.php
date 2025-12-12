<div id="viewLabOrderModal" class="hms-modal-overlay" aria-hidden="true" hidden>
    <div class="hms-modal" role="dialog" aria-modal="true" aria-labelledby="viewLabOrderModalTitle" style="max-width: 700px;">
        <div class="hms-modal-header">
            <div class="hms-modal-title" id="viewLabOrderModalTitle">
                <i class="fas fa-vials" style="color:#4f46e5"></i> Lab Order Details
            </div>
            <button type="button" class="btn btn-secondary btn-small" data-modal-close="viewLabOrderModal" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="hms-modal-body">
            <div class="lab-order-details">
                <!-- Order Information Section -->
                <div class="detail-section">
                    <h4><i class="fas fa-info-circle" style="color:#4f46e5; margin-right:0.5rem;"></i>Order Information</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label><i class="fas fa-hashtag"></i> Order ID:</label>
                            <span id="viewLabOrderId" class="detail-value">-</span>
                        </div>
                        <div class="detail-item">
                            <label><i class="fas fa-clock"></i> Ordered At:</label>
                            <span id="viewLabOrderedAt" class="detail-value">-</span>
                        </div>
                        <div class="detail-item">
                            <label><i class="fas fa-flag"></i> Priority:</label>
                            <span id="viewLabPriority" class="detail-value priority-badge">-</span>
                        </div>
                        <div class="detail-item">
                            <label><i class="fas fa-check-circle"></i> Status:</label>
                            <span id="viewLabStatus" class="detail-value status-badge">-</span>
                        </div>
                    </div>
                </div>

                <!-- Patient Information Section -->
                <div class="detail-section">
                    <h4><i class="fas fa-user" style="color:#4f46e5; margin-right:0.5rem;"></i>Patient Information</h4>
                    <div class="detail-grid">
                        <div class="detail-item full">
                            <label><i class="fas fa-user-injured"></i> Patient Name:</label>
                            <span id="viewLabPatient" class="detail-value">-</span>
                        </div>
                    </div>
                </div>

                <!-- Test Information Section -->
                <div class="detail-section">
                    <h4><i class="fas fa-flask" style="color:#4f46e5; margin-right:0.5rem;"></i>Test Information</h4>
                    <div class="detail-grid">
                        <div class="detail-item full">
                            <label><i class="fas fa-vial"></i> Test Name:</label>
                            <span id="viewLabTest" class="detail-value">-</span>
                        </div>
                        <div class="detail-item full" id="viewLabTestCodeContainer" style="display:none;">
                            <label><i class="fas fa-barcode"></i> Test Code:</label>
                            <span id="viewLabTestCode" class="detail-value">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="hms-modal-actions">
            <button type="button" class="btn btn-secondary" data-modal-close="viewLabOrderModal">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
    </div>
</div>

<style>
.lab-order-details {
    padding: 0.5rem 0;
}

.detail-section {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.detail-section:last-child {
    margin-bottom: 0;
}

.detail-section h4 {
    margin: 0 0 1rem 0;
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    display: flex;
    align-items: center;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #e5e7eb;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.detail-item.full {
    grid-column: 1 / -1;
}

.detail-item label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.detail-item label i {
    width: 16px;
    color: #9ca3af;
}

.detail-value {
    font-size: 1rem;
    color: #1f2937;
    font-weight: 500;
    padding: 0.5rem;
    background: #ffffff;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
    min-height: 2.5rem;
    display: flex;
    align-items: center;
}

.priority-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: capitalize;
}

.priority-badge:contains("Urgent"),
.priority-badge:contains("urgent") {
    background: #fee2e2;
    color: #991b1b;
}

.priority-badge:contains("Routine"),
.priority-badge:contains("routine") {
    background: #dbeafe;
    color: #1e40af;
}

.priority-badge:contains("Stat"),
.priority-badge:contains("stat") {
    background: #fef3c7;
    color: #92400e;
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: capitalize;
}

.status-badge:contains("Completed"),
.status-badge:contains("completed") {
    background: #d1fae5;
    color: #065f46;
}

.status-badge:contains("In Progress"),
.status-badge:contains("in_progress"),
.status-badge:contains("in progress") {
    background: #fef3c7;
    color: #92400e;
}

.status-badge:contains("Ordered"),
.status-badge:contains("ordered") {
    background: #dbeafe;
    color: #1e40af;
}

.status-badge:contains("Cancelled"),
.status-badge:contains("cancelled") {
    background: #fee2e2;
    color: #991b1b;
}

@media (max-width: 640px) {
    .detail-grid {
        grid-template-columns: 1fr;
    }
}
</style>

