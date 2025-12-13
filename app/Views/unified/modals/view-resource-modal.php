<!-- View Resource Modal -->
<div id="viewResourceModal" class="hms-modal-overlay" aria-hidden="true" style="display:none;">
    <div class="hms-modal" role="dialog" aria-modal="true" aria-labelledby="viewResourceTitle">
        <div class="hms-modal-header">
            <div class="hms-modal-title" id="viewResourceTitle">
                <i class="fas fa-eye" style="color:#4f46e5"></i> Resource Details
            </div>
            <button type="button" class="btn btn-secondary btn-small" id="closeViewResourceModal" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="hms-modal-body">
            <div class="resource-details">
                <div class="detail-section">
                    <h4><i class="fas fa-info-circle"></i> Resource Information</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Resource Name:</label>
                            <span id="viewResourceName" class="detail-value">-</span>
                        </div>
                        <div class="detail-item">
                            <label>Category:</label>
                            <span id="viewResourceCategory" class="detail-value">-</span>
                        </div>
                        <div class="detail-item">
                            <label>Quantity:</label>
                            <span id="viewResourceQuantity" class="detail-value">-</span>
                        </div>
                        <div class="detail-item">
                            <label>Status:</label>
                            <span id="viewResourceStatus" class="status-badge">-</span>
                        </div>
                        <div class="detail-item">
                            <label>Location:</label>
                            <span id="viewResourceLocation" class="detail-value">-</span>
                        </div>
                        <div class="detail-item">
                            <label>Serial Number:</label>
                            <span id="viewResourceSerialNumber" class="detail-value">-</span>
                        </div>
                    </div>
                </div>

                <div class="detail-section" id="viewMedicationDetails" style="display: none;">
                    <h4><i class="fas fa-pills"></i> Medication Details</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Batch Number:</label>
                            <span id="viewResourceBatchNumber" class="detail-value">-</span>
                        </div>
                        <div class="detail-item">
                            <label>Expiry Date:</label>
                            <span id="viewResourceExpiryDate" class="detail-value">-</span>
                        </div>
                    </div>
                </div>

                <div class="detail-section" id="viewPricingDetails" style="display: none;">
                    <h4><i class="fas fa-dollar-sign"></i> Pricing Information</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Price (Purchase Cost):</label>
                            <span id="viewResourcePrice" class="detail-value">-</span>
                        </div>
                        <div class="detail-item">
                            <label>Selling Price:</label>
                            <span id="viewResourceSellingPrice" class="detail-value">-</span>
                        </div>
                    </div>
                </div>

                <div class="detail-section">
                    <h4><i class="fas fa-sticky-note"></i> Remarks</h4>
                    <div class="notes-content" id="viewResourceRemarks">
                        No remarks available
                    </div>
                </div>

                <div class="detail-section">
                    <h4><i class="fas fa-clock"></i> Timestamps</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Created At:</label>
                            <span id="viewResourceCreatedAt" class="detail-value">-</span>
                        </div>
                        <div class="detail-item">
                            <label>Last Updated:</label>
                            <span id="viewResourceUpdatedAt" class="detail-value">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="hms-modal-actions">
            <button type="button" class="btn btn-secondary" id="closeViewResourceBtn">Close</button>
            <?php if (in_array('edit', $permissions['resources'] ?? [])): ?>
                <button type="button" class="btn btn-primary" id="editFromViewResourceBtn">
                    <i class="fas fa-edit"></i> Edit Resource
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>
