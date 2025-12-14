/**
 * Edit Resource Modal
 */
(function() {
    const baseUrl = (window.HMS?.baseUrl || '').replace(/\/+$/, '') + '/';
    const utils = new ResourceModalUtils(baseUrl);
    const modalId = 'editResourceModal';
    const formId = 'editResourceForm';

    const modal = document.getElementById(modalId);
    const form = document.getElementById(formId);
    let originalQuantity = 0; // Store original quantity to detect increases

    function init() {
        if (!modal || !form) return;

        utils.setupModalCloseHandlers(modalId);
        utils.toggleMedicationFields('er_category', 'editMedicationFields');

        form.addEventListener('submit', handleSubmit);
        
        // Additional validation for price when category is Medications
        const categorySelect = document.getElementById('er_category');
        if (categorySelect) {
            categorySelect.addEventListener('change', () => {
                const isMedication = categorySelect.value === 'Medications';
                const priceInput = document.getElementById('er_price');
                if (priceInput) {
                    priceInput.required = isMedication;
                }
            });
        }

        // Show/hide purchase cost field when quantity changes
        const quantityInput = document.getElementById('er_quantity');
        if (quantityInput) {
            quantityInput.addEventListener('input', togglePurchaseCostField);
        }
    }

    function open(resourceId) {
        if (!modal || !form) return;

        form.reset();
        utils.clearErrors('err_er_');
        utils.open(modalId);
        loadResource(resourceId);
    }

    function close() {
        utils.close(modalId);
    }

    async function loadResource(resourceId) {
        const resource = (window.resourcesById || {})[resourceId];
        if (!resource) {
            alert('Resource not found');
            close();
            return;
        }

        const set = (id, val) => {
            const el = document.getElementById(id);
            if (el && (el.tagName === 'SELECT' || el.tagName === 'INPUT' || el.tagName === 'TEXTAREA')) {
                el.value = val ?? '';
            }
        };

        set('er_id', resource.id || '');
        set('er_name', resource.equipment_name || '');
        set('er_category', resource.category || '');
        set('er_quantity', resource.quantity || '');
        set('er_location', resource.location || '');
        set('er_serial_number', resource.serial_number || '');
        set('er_batch_number', resource.batch_number || '');
        set('er_expiry_date', resource.expiry_date || '');
        set('er_price', resource.price || '');
        set('er_remarks', resource.remarks || '');
        set('er_purchase_cost', ''); // Reset purchase cost

        // Store original quantity
        originalQuantity = parseInt(resource.quantity || 0);

        // Show/hide medication fields
        const isMedication = resource.category === 'Medications';
        const medFields = document.getElementById('editMedicationFields');
        if (medFields) medFields.style.display = isMedication ? 'flex' : 'none';
        const priceFields = document.getElementById('editMedicationPriceFields');
        if (priceFields) priceFields.style.display = isMedication ? 'flex' : 'none';

        // Check if purchase cost field should be shown
        togglePurchaseCostField();
    }

    function togglePurchaseCostField() {
        const quantityInput = document.getElementById('er_quantity');
        const purchaseCostFields = document.getElementById('editPurchaseCostFields');
        
        if (!quantityInput || !purchaseCostFields) return;

        const newQuantity = parseInt(quantityInput.value || 0);
        const isIncreasing = newQuantity > originalQuantity;
        
        // Show purchase cost field only when quantity is being increased
        purchaseCostFields.style.display = isIncreasing ? 'flex' : 'none';
        
        // Clear purchase cost if quantity is not increasing
        if (!isIncreasing) {
            const purchaseCostInput = document.getElementById('er_purchase_cost');
            if (purchaseCostInput) {
                purchaseCostInput.value = '';
            }
        }
    }

    async function handleSubmit(e) {
        e.preventDefault();
        if (!form) return;

        utils.clearErrors('err_er_');

        const name = document.getElementById('er_name')?.value?.trim();
        const category = document.getElementById('er_category')?.value;
        const quantity = document.getElementById('er_quantity')?.value;
        const location = document.getElementById('er_location')?.value?.trim();

        let hasErrors = false;
        if (!name) {
            document.getElementById('err_er_name').textContent = 'Resource name is required.';
            hasErrors = true;
        }
        if (!category) {
            document.getElementById('err_er_category').textContent = 'Please select a category.';
            hasErrors = true;
        }
        if (!quantity || quantity < 1) {
            document.getElementById('err_er_quantity').textContent = 'Quantity must be at least 1.';
            hasErrors = true;
        }
        if (!location) {
            document.getElementById('err_er_location').textContent = 'Location is required.';
            hasErrors = true;
        }
        
        // Validate price for medications
        if (category === 'Medications') {
            const price = document.getElementById('er_price')?.value;
            if (!price || parseFloat(price) < 0) {
                document.getElementById('err_er_price').textContent = 'Price is required and must be 0 or greater for medications.';
                hasErrors = true;
            }
        }

        if (hasErrors) return;

        try {
            const formData = new FormData(form);
            const resourceBaseUrl = window.HMS?.resourceManagementBaseUrl || 'admin/resource-management';
            const res = await fetch(baseUrl + resourceBaseUrl + '/update', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await res.json().catch(() => ({ success: false, message: 'Failed to update resource' }));

            if (data && data.success === true) {
                utils.showNotification(data.message || 'Resource updated successfully!', 'success');
                setTimeout(() => {
                    close();
                    window.location.reload();
                }, 1500);
            } else {
                const errorMsg = data?.message || 'Failed to update resource';
                if (data?.errors) {
                    utils.displayErrors(data.errors, 'err_er_');
                } else {
                    alert(errorMsg);
                }
            }
        } catch (err) {
            console.error('Error:', err);
            alert('An error occurred while updating the resource.');
        }
    }

    // Export to global scope
    window.EditResourceModal = { init, open, close };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

