<div id="addResourceModal" class="modal" aria-hidden="true">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Add New Resource</h3>
            <button type="button" class="modal-close" data-modal-close="addResourceModal" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <form id="addResourceForm">
            <div class="modal-body">
                <div class="form-section">
                    <h4><i class="fas fa-info-circle"></i> Resource Information</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="res_category">Category*</label>
                            <select id="res_category" name="category" class="form-control" required onchange="toggleResourceName()">
                                <option value="">Select category</option>
                                <?php foreach ($categories ?? [] as $cat): ?>
                                    <option value="<?= esc($cat) ?>"><?= esc($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small id="err_res_category" style="color:#dc2626"></small>
                        </div>
                        <div class="form-group">
                            <label for="res_name">Name*</label>
                            <input id="res_name" name="equipment_name" type="text" class="form-control" required autocomplete="off" placeholder="Enter resource name" disabled>
                            <small id="err_res_name" style="color:#dc2626"></small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="res_quantity">Quantity*</label>
                            <input id="res_quantity" name="quantity" type="number" class="form-control" min="1" required autocomplete="off" placeholder="Enter quantity">
                            <small id="err_res_quantity" style="color:#dc2626"></small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="res_location">Location*</label>
                            <input id="res_location" name="location" type="text" class="form-control" required autocomplete="off" placeholder="Enter location">
                            <small id="err_res_location" style="color:#dc2626"></small>
                        </div>
                        <div class="form-group">
                            <label for="res_serial_number"><i class="fas fa-hashtag"></i> Serial Number <small style="color: #666;">(Optional)</small></label>
                            <input id="res_serial_number" name="serial_number" type="text" class="form-control" autocomplete="off" placeholder="Enter serial number">
                            <small id="err_res_serial_number" style="color:#dc2626"></small>
                        </div>
                    </div>
                    <div class="form-row" id="medicationFields" style="display: none;">
                        <div class="form-group">
                            <label for="res_batch_number"><i class="fas fa-barcode"></i> Batch Number <small style="color: #666;">(Required for medications)</small></label>
                            <input id="res_batch_number" name="batch_number" type="text" class="form-control" autocomplete="off" placeholder="Enter batch/lot number">
                            <small id="err_res_batch_number" style="color:#dc2626"></small>
                        </div>
                        <div class="form-group">
                            <label for="res_expiry_date"><i class="fas fa-calendar-times"></i> Expiry Date <small style="color: #666;">(Required for medications)</small></label>
                            <input id="res_expiry_date" name="expiry_date" type="date" class="form-control" autocomplete="off">
                            <small id="err_res_expiry_date" style="color:#dc2626"></small>
                        </div>
                    </div>
                    <div class="form-row" id="medicationPriceFields" style="display: none;">
                        <div class="form-group">
                            <label for="res_selling_price"><i class="fas fa-tag"></i> Selling Price (₱) <small style="color: #666;">(Required for medications - used for prescriptions/billing)</small></label>
                            <input id="res_selling_price" name="selling_price" type="number" step="0.01" min="0" class="form-control" autocomplete="off" placeholder="0.00" required>
                            <small id="err_res_selling_price" style="color:#dc2626"></small>
                            <small style="color: #666; font-size: 0.85em;">Price charged to patients for this medication.</small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="res_purchase_cost"><i class="fas fa-shopping-cart"></i> Purchase Cost (₱) <small style="color: #666;">(Optional - creates expense transaction)</small></label>
                            <input id="res_purchase_cost" name="purchase_cost" type="number" step="0.01" min="0" class="form-control" autocomplete="off" placeholder="0.00">
                            <small id="err_res_purchase_cost" style="color:#dc2626"></small>
                            <small style="color: #666; font-size: 0.85em;">Enter the total cost paid to purchase this stock. The system will compute the per-unit purchase price and create an expense transaction.</small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="res_remarks">Remarks/Notes</label>
                            <textarea id="res_remarks" name="remarks" rows="3" class="form-control" autocomplete="off" placeholder="Additional notes..."></textarea>
                            <small id="err_res_remarks" style="color:#dc2626"></small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close="addResourceModal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Resource</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleResourceName() {
    const categorySelect = document.getElementById('res_category');
    const resourceNameInput = document.getElementById('res_name');
    
    if (categorySelect && resourceNameInput) {
        const hasCategory = categorySelect.value !== '';
        resourceNameInput.disabled = !hasCategory;
        
        // Clear resource name if category is cleared
        if (!hasCategory) {
            resourceNameInput.value = '';
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleResourceName();
});
</script>
