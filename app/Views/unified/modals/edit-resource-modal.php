<div id="editResourceModal" class="modal" aria-hidden="true">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Resource</h3>
            <button type="button" class="modal-close" data-modal-close="editResourceModal" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>
        <form id="editResourceForm">
            <input type="hidden" id="er_id" name="id">
            <div class="modal-body">
                <div class="form-section">
                    <h4><i class="fas fa-info-circle"></i> Resource Information</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="er_name">Resource Name*</label>
                            <input id="er_name" name="equipment_name" type="text" class="form-control" required autocomplete="off" placeholder="Enter resource name">
                            <small id="err_er_name" style="color:#dc2626"></small>
                        </div>
                        <div class="form-group">
                            <label for="er_category">Category*</label>
                            <select id="er_category" name="category" class="form-control" required>
                                <option value="">Select category</option>
                                <?php foreach ($categories ?? [] as $cat): ?>
                                    <option value="<?= esc($cat) ?>"><?= esc($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small id="err_er_category" style="color:#dc2626"></small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="er_quantity">Quantity*</label>
                            <input id="er_quantity" name="quantity" type="number" class="form-control" min="1" required autocomplete="off" placeholder="Enter quantity">
                            <small id="err_er_quantity" style="color:#dc2626"></small>
                        </div>
                    </div>
                    <div class="form-row" id="editPurchaseCostFields" style="display: none;">
                        <div class="form-group">
                            <label for="er_purchase_cost"><i class="fas fa-shopping-cart"></i> Purchase Cost per Unit (₱) <small style="color: #666;">(Optional - creates expense transaction when adding stock)</small></label>
                            <input id="er_purchase_cost" name="purchase_cost" type="number" step="0.01" min="0" class="form-control" autocomplete="off" placeholder="0.00">
                            <small id="err_er_purchase_cost" style="color:#dc2626"></small>
                            <small style="color: #666; font-size: 0.85em;">Enter the cost per unit for the additional stock. Total expense = unit cost × quantity added. This will create an expense transaction.</small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="er_location">Location*</label>
                            <input id="er_location" name="location" type="text" class="form-control" required autocomplete="off" placeholder="Enter location">
                            <small id="err_er_location" style="color:#dc2626"></small>
                        </div>
                        <div class="form-group">
                            <label for="er_serial_number"><i class="fas fa-hashtag"></i> Serial Number <small style="color: #666;">(Optional)</small></label>
                            <input id="er_serial_number" name="serial_number" type="text" class="form-control" autocomplete="off" placeholder="Enter serial number">
                            <small id="err_er_serial_number" style="color:#dc2626"></small>
                        </div>
                    </div>
                    <div class="form-row" id="editMedicationFields" style="display: none;">
                        <div class="form-group">
                            <label for="er_batch_number"><i class="fas fa-barcode"></i> Batch Number <small style="color: #666;">(Required for medications)</small></label>
                            <input id="er_batch_number" name="batch_number" type="text" class="form-control" autocomplete="off" placeholder="Enter batch/lot number">
                            <small id="err_er_batch_number" style="color:#dc2626"></small>
                        </div>
                        <div class="form-group">
                            <label for="er_expiry_date"><i class="fas fa-calendar-times"></i> Expiry Date <small style="color: #666;">(Required for medications)</small></label>
                            <input id="er_expiry_date" name="expiry_date" type="date" class="form-control" autocomplete="off">
                            <small id="err_er_expiry_date" style="color:#dc2626"></small>
                        </div>
                    </div>
                    <div class="form-row" id="editMedicationPriceFields" style="display: none;">
                        <div class="form-group">
                            <label for="er_price"><i class="fas fa-dollar-sign"></i> Price <small style="color: #666;">(Required for medications - used for billing)</small></label>
                            <input id="er_price" name="price" type="number" step="0.01" min="0" class="form-control" autocomplete="off" placeholder="0.00">
                            <small id="err_er_price" style="color:#dc2626"></small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="er_remarks">Remarks/Notes</label>
                            <textarea id="er_remarks" name="remarks" rows="3" class="form-control" autocomplete="off" placeholder="Additional notes..."></textarea>
                            <small id="err_er_remarks" style="color:#dc2626"></small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close="editResourceModal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Resource</button>
            </div>
        </form>
    </div>
</div>

