<div id="editLabOrderModal" class="modal" aria-hidden="true" hidden>
    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="editLabOrderModalTitle" style="max-width: 640px; width: 90%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editLabOrderModalTitle"><i class="fas fa-edit"></i> Edit Lab Order</h5>
                <button type="button" class="close" aria-label="Close" data-modal-close="editLabOrderModal"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <form id="editLabOrderForm">
                    <input type="hidden" id="editLabOrderId" name="lab_order_id">
                    <div class="form-group">
                        <label for="editLabPatientSelect">Patient</label>
                        <select class="form-control" id="editLabPatientSelect" name="patient_id" required>
                            <option value="">Loading patients...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editLabTestSelect">Lab Test</label>
                        <select id="editLabTestSelect" name="test_code" class="form-control" required>
                            <option value="">Loading tests...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editLabPriority">Priority</label>
                        <select id="editLabPriority" name="priority" class="form-control">
                            <option value="routine">Routine</option>
                            <option value="urgent">Urgent</option>
                            <option value="stat">STAT</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close="editLabOrderModal">Cancel</button>
                <button type="button" class="btn btn-primary" id="editLabOrderSubmitBtn"><i class="fas fa-check"></i> Update Order</button>
            </div>
        </div>
    </div>
</div>

