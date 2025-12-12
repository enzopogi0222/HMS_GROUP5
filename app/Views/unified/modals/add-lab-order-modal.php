<div id="addLabOrderModal" class="modal" aria-hidden="true" hidden>
    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="addLabOrderModalTitle" style="max-width: 640px; width: 90%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addLabOrderModalTitle"><i class="fas fa-flask"></i> New Lab Order</h5>
                <button type="button" class="close" aria-label="Close" data-modal-close="addLabOrderModal"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <form id="addLabOrderForm">
                    <div class="form-group">
                        <label for="addLabPatientSelect">Patient</label>
                        <select class="form-control" id="addLabPatientSelect" name="patient_id" required>
                            <option value="">Loading patients...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="addLabTestSelect">Lab Test</label>
                        <select id="addLabTestSelect" name="test_code" class="form-control" required>
                            <option value="">Loading tests...</option>
                        </select>
                        <small class="form-text text-muted">Tests are loaded from the lab tests master list.</small>
                    </div>
                    <div class="form-group">
                        <label for="addLabPriority">Priority</label>
                        <select id="addLabPriority" name="priority" class="form-control">
                            <option value="routine">Routine</option>
                            <option value="urgent">Urgent</option>
                            <option value="stat">STAT</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close="addLabOrderModal">Cancel</button>
                <button type="button" class="btn btn-primary" id="addLabOrderSubmitBtn"><i class="fas fa-check"></i> Create Order</button>
            </div>
        </div>
    </div>
</div>

