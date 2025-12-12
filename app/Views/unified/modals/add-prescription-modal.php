<!-- Add Prescription Modal -->
<div id="addPrescriptionModal" class="hms-modal-overlay" aria-hidden="true">
    <div class="hms-modal" role="dialog" aria-modal="true" aria-labelledby="addPrescriptionTitle">
        <div class="hms-modal-header">
            <div class="hms-modal-title" id="addPrescriptionTitle"><i class="fas fa-prescription-bottle" style="color:#4f46e5"></i> Create Prescription</div>
            <button type="button" class="btn btn-secondary btn-small" id="closeAddPrescriptionModal" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        
        <form id="addPrescriptionForm">
            <input type="hidden" id="edit_prescription_id" name="prescription_id" value="">
            <div class="hms-modal-body">
                <div class="form-grid">
                    <div>
                        <label class="form-label" for="add_patientSelect">Patient*</label>
                        <select id="add_patientSelect" name="patient_id" class="form-select" required>
                            <option value="">Select Patient...</option>
                        </select>
                        <small id="err_add_patientSelect" style="color:#dc2626"></small>
                    </div>
                    <div>
                        <label class="form-label" for="add_prescriptionDate">Date Issued*</label>
                        <input type="date" id="add_prescriptionDate" name="date_issued" class="form-input" required value="<?= date('Y-m-d') ?>">
                        <small id="err_add_prescriptionDate" style="color:#dc2626"></small>
                    </div>
                    <?php if (($userRole ?? '') === 'nurse'): ?>
                    <div>
                        <label class="form-label" for="add_doctorSelect">Assign Doctor*</label>
                        <select id="add_doctorSelect" name="doctor_id" class="form-select" required>
                            <option value="">Select Doctor...</option>
                        </select>
                        <small class="form-text" style="color:#6b7280">Draft prescriptions require doctor approval</small>
                        <small id="err_add_doctorSelect" style="color:#dc2626"></small>
                    </div>
                    <?php elseif (($userRole ?? '') === 'admin'): ?>
                    <div>
                        <label class="form-label" for="add_doctorSelect">Assign Doctor (Optional)</label>
                        <select id="add_doctorSelect" name="doctor_id" class="form-select">
                            <option value="">Select Doctor (Optional)...</option>
                        </select>
                        <small class="form-text" style="color:#6b7280">Leave empty to use your own staff ID, or select a doctor to assign this prescription</small>
                        <small id="err_add_doctorSelect" style="color:#dc2626"></small>
                    </div>
                    <?php endif; ?>
                    
                    <div class="full">
                        <label class="form-label">Medicines*</label>
                        <div class="medicines-table-wrapper">
                            <table class="medicines-table">
                                <thead>
                                    <tr>
                                        <th>Medication</th>
                                        <th>Frequency</th>
                                        <th>Duration</th>
                                        <th>Quantity</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="addMedicinesTableBody">
                                    <tr class="medicine-row">
                                        <td>
                                            <select name="medication_resource_id[]" class="form-select medicine-medication-select">
                                                <option value="">Select medication</option>
                                            </select>
                                            <input type="hidden" name="medication_name[]" class="medicine-name-hidden">
                                        </td>
                                        <td>
                                            <select name="frequency[]" class="form-select">
                                                <option value="">Select Frequency</option>
                                                <?php foreach (['Once daily', 'Twice daily', 'Three times daily', 'Every 4 hours', 'Every 6 hours', 'Every 8 hours', 'As needed'] as $freq): ?>
                                                    <option value="<?= esc($freq) ?>"><?= esc($freq) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="duration[]" class="form-select">
                                                <option value="">Select Duration</option>
                                                <?php foreach (['3 days', '5 days', '7 days', '10 days', '14 days', '30 days'] as $dur): ?>
                                                    <option value="<?= esc($dur) ?>"><?= esc($dur) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td><input type="number" name="quantity[]" class="form-input" min="1" placeholder="e.g., 30"></td>
                                        <td><button type="button" class="btn btn-sm btn-danger remove-medicine-row" title="Remove"><i class="fas fa-trash"></i></button></td>
                                    </tr>
                                </tbody>
                            </table>
                            <button type="button" class="btn btn-secondary" id="addMedicineRowBtn"><i class="fas fa-plus"></i> Add Medicine</button>
                        </div>
                    </div>
                    <?php if (($userRole ?? '') !== 'nurse'): ?>
                    <div>
                        <label class="form-label" for="add_prescriptionStatus">Status</label>
                        <select id="add_prescriptionStatus" name="status" class="form-select">
                            <?php 
                            $defaultStatuses = [['status' => 'active'], ['status' => 'pending'], ['status' => 'ready'], ['status' => 'completed'], ['status' => 'cancelled']];
                            foreach ($statuses ?? $defaultStatuses as $status): 
                            ?>
                                <option value="<?= esc($status['status']) ?>" <?= ($status['status'] === 'active') ? 'selected' : '' ?>><?= esc(ucfirst($status['status'])) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                    <input type="hidden" id="add_prescriptionStatus" name="status" value="draft">
                    <div>
                        <label class="form-label">Status</label>
                        <input type="text" class="form-input" value="Draft (Pending Doctor Approval)" readonly style="background-color: #f3f4f6; color: #6b7280;">
                        <small class="form-text" style="color:#6b7280">Nurse-created prescriptions are automatically set to draft status</small>
                    </div>
                    <?php endif; ?>
                    <div class="full">
                        <label class="form-label" for="add_prescriptionNotes">Notes</label>
                        <textarea id="add_prescriptionNotes" name="notes" class="form-input" rows="3" placeholder="Additional instructions or notes..."></textarea>
                    </div>
                </div>
            </div>
            <div class="hms-modal-actions">
                <button type="button" class="btn btn-secondary" id="cancelAddPrescriptionBtn">Cancel</button>
                <button type="submit" id="saveAddPrescriptionBtn" class="btn btn-success"><i class="fas fa-save"></i> Save Prescription</button>
            </div>
        </form>
    </div>
</div>
