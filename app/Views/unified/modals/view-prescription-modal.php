<!-- View Prescription Modal -->
<div id="viewPrescriptionModal" class="hms-modal-overlay" aria-hidden="true">
    <div class="hms-modal" role="dialog" aria-modal="true" aria-labelledby="viewPrescriptionTitle">
        <div class="hms-modal-header">
            <div class="hms-modal-title" id="viewPrescriptionTitle"><i class="fas fa-eye" style="color:#4f46e5"></i> Prescription Details</div>
            <button type="button" class="btn btn-secondary btn-small" id="closeViewPrescriptionModal" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <div class="hms-modal-body">
            <div class="prescription-details">
                <div class="detail-section">
                    <h4>Prescription Information</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Prescription ID:</label>
                            <span id="viewPrescriptionId">-</span>
                        </div>
                        <div class="detail-item">
                            <label>Date Issued:</label>
                            <span id="viewPrescriptionDate">-</span>
                        </div>
                        <div class="detail-item">
                            <label>Status:</label>
                            <span id="viewPrescriptionStatus" class="status-badge">-</span>
                        </div>
                        <div class="detail-item">
                            <label>Doctor:</label>
                            <span id="viewDoctorName">-</span>
                        </div>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h4>Patient Information</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Patient Name:</label>
                            <span id="viewPatientName">-</span>
                        </div>
                        <div class="detail-item">
                            <label>Patient ID:</label>
                            <span id="viewPatientId">-</span>
                        </div>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h4>Medication Details</h4>
                    <div class="medicines-view-wrapper">
                        <table class="medicines-view-table">
                            <thead>
                                <tr>
                                    <th>Medication</th>
                                    <th>Frequency</th>
                                    <th>Duration</th>
                                    <th>Quantity</th>
                                </tr>
                            </thead>
                            <tbody id="viewMedicinesBody">
                                <tr>
                                    <td colspan="5">No medicines found</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h4>Notes</h4>
                    <div class="notes-content" id="viewPrescriptionNotes">
                        No notes available
                    </div>
                </div>
            </div>
        </div>
        <div class="hms-modal-actions">
            <button type="button" class="btn btn-secondary" id="closeViewPrescriptionBtn">Close</button>
            <?php if ($permissions['canEdit'] ?? false): ?>
                <button type="button" class="btn btn-primary" id="editFromViewBtn"><i class="fas fa-edit"></i> Edit</button>
            <?php endif; ?>
        </div>
    </div>
</div>
