<div id="billingAccountModal" class="hms-modal-overlay" hidden>
    <div class="hms-modal" role="dialog" aria-modal="true" aria-labelledby="billingAccountTitle" style="max-width:800px;">
        <div class="hms-modal-header">
            <div class="hms-modal-title" id="billingAccountTitle">Billing Account Details</div>
            <button type="button" class="btn btn-secondary btn-small" data-modal-close="billingAccountModal" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <div class="hms-modal-body">
            <div id="billingAccountHeader" class="billing-account-header" style="margin-bottom:0.75rem;"></div>
            <div id="billingAccountPatientType" class="billing-patient-type-badge" style="margin-bottom:0.75rem;"></div>
            <div id="billingAccountAdmissionInfo" class="billing-admission-info" style="margin-bottom:0.75rem; display:none;"></div>
            <div id="billingAccountInsuranceInfo" class="billing-insurance-info" style="margin-bottom:0.75rem; display:none;"></div>
            <div class="table-responsive">
                <table class="financial-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Line Total</th>
                            <th>Discount %</th>
                            <th>Discount Amount</th>
                            <th>Final Amount</th>
                        </tr>
                    </thead>
                    <tbody id="billingItemsBody">
                        <tr>
                            <td colspan="7" class="loading-row">No items loaded.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="hms-modal-actions">
            <div class="billing-total">
                <div><strong>Gross Total:</strong> <span id="billingAccountGrossTotal">₱0.00</span></div>
                <div><strong>Insurance Discount:</strong> <span id="billingAccountInsuranceDiscountTotal">₱0.00</span></div>
                <div><strong>Net Total:</strong> <span id="billingAccountTotal">₱0.00</span></div>
            </div>
            <button type="button" class="btn btn-secondary" data-modal-close="billingAccountModal">Close</button>
        </div>
    </div>
</div>

