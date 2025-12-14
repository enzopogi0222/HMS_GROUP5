/**
 * Add Resource Modal
 */
(function() {
    const baseUrl = (window.HMS?.baseUrl || '').replace(/\/+$/, '') + '/';
    const utils = new ResourceModalUtils(baseUrl);
    const modalId = 'addResourceModal';
    const formId = 'addResourceForm';

    const modal = document.getElementById(modalId);
    const form = document.getElementById(formId);

    function init() {
        if (!modal || !form) return;

        utils.setupModalCloseHandlers(modalId);
        utils.toggleMedicationFields('res_category', 'medicationFields');

        form.addEventListener('submit', handleSubmit);
        
        // Additional validation for selling price when category is Medications
        const categorySelect = document.getElementById('res_category');
        if (categorySelect) {
            categorySelect.addEventListener('change', () => {
                const isMedication = categorySelect.value === 'Medications';
                const sellingPriceInput = document.getElementById('res_selling_price');
                if (sellingPriceInput) {
                    sellingPriceInput.required = isMedication;
                }
            });
        }
    }

    function open() {
        if (!modal || !form) return;

        form.reset();
        utils.clearErrors('err_res_');
        utils.open(modalId);
    }

    function close() {
        utils.close(modalId);
    }

    async function handleSubmit(e) {
        e.preventDefault();
        if (!form) return;

        utils.clearErrors('err_res_');

        const name = document.getElementById('res_name')?.value?.trim();
        const category = document.getElementById('res_category')?.value;
        const quantity = document.getElementById('res_quantity')?.value;
        const location = document.getElementById('res_location')?.value?.trim();

        let hasErrors = false;
        if (!name) {
            document.getElementById('err_res_name').textContent = 'Resource name is required.';
            hasErrors = true;
        }
        if (!category) {
            document.getElementById('err_res_category').textContent = 'Please select a category.';
            hasErrors = true;
        }
        if (!quantity || quantity < 1) {
            document.getElementById('err_res_quantity').textContent = 'Quantity must be at least 1.';
            hasErrors = true;
        }
        if (!location) {
            document.getElementById('err_res_location').textContent = 'Location is required.';
            hasErrors = true;
        }
        
        // Validate selling price for medications
        if (category === 'Medications') {
            const sellingPrice = document.getElementById('res_selling_price')?.value;
            if (!sellingPrice || parseFloat(sellingPrice) < 0) {
                document.getElementById('err_res_selling_price').textContent = 'Selling price is required and must be 0 or greater for medications.';
                hasErrors = true;
            }
        }

        if (hasErrors) return;

        try {
            const formData = new FormData(form);
            const resourceBaseUrl = window.HMS?.resourceManagementBaseUrl || 'admin/resource-management';
            const res = await fetch(baseUrl + resourceBaseUrl + '/create', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            });

            let data = null;
            try {
                const contentType = res.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    data = await res.json();
                } else {
                    const raw = await res.text();
                    data = raw ? JSON.parse(raw) : null;
                }
            } catch (e) {
                console.error('Error parsing response:', e);
            }

            if (data && data.success === true) {
                utils.showNotification(data.message || 'Resource added successfully!', 'success');
                setTimeout(() => {
                    close();
                    window.location.reload();
                }, 1500);
            } else {
                const errorMsg = data?.message || data?.db_error?.message || 'Failed to save resource';
                if (data?.errors) {
                    utils.displayErrors(data.errors, 'err_res_');
                } else {
                    if (typeof showUniversalNotification === 'function') {
                        showUniversalNotification(errorMsg, 'error');
                    }
                }
            }
        } catch (err) {
            console.error('Error:', err);
            if (typeof showUniversalNotification === 'function') {
                showUniversalNotification('An error occurred while saving the resource.', 'error');
            }
        }
    }

    // Export to global scope
    window.AddResourceModal = { init, open, close };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

