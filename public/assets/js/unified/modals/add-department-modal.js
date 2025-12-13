/**
 * Add Department Modal
 */
(function() {
    const baseUrl = document.querySelector('meta[name="base-url"]')?.content?.replace(/\/+$/, '') || '';
    const utils = new DepartmentModalUtils(baseUrl);
    const modalId = 'addDepartmentModal';
    const formId = 'addDepartmentForm';

    const modal = document.getElementById(modalId);
    const form = document.getElementById(formId);
    const submitBtn = document.getElementById('saveDepartmentBtn');

    const categorySelect = document.getElementById('department_category');
    const nameInput = document.getElementById('department_name');
    const typeInput = document.getElementById('department_type');
    const nameSuggestions = document.getElementById('department_name_suggestions');

    const suggestedNamesByCategory = {
        medical: [
            'Emergency Department',
            'Internal Medicine',
            'Pediatrics',
            'Obstetrics and Gynecology',
            'Surgery',
            'Orthopedics',
            'Cardiology',
            'Radiology',
            'Laboratory',
            'Pharmacy',
        ],
        non_medical: [
            'Hospital Administration',
            'Human Resources',
            'Billing and Finance',
            'Medical Records',
            'Patient Registration',
            'Information Technology',
            'Maintenance',
            'Housekeeping',
            'Security',
            'Inventory / Supply',
        ],
    };

    function normalizeText(value) {
        return String(value || '').trim().toLowerCase();
    }

    function populateNameSuggestions(category) {
        if (!nameSuggestions) return;

        nameSuggestions.innerHTML = '';
        const names = suggestedNamesByCategory[category] || [];
        names.forEach(n => {
            const opt = document.createElement('option');
            opt.value = n;
            nameSuggestions.appendChild(opt);
        });
    }

    function inferDepartmentType(category, name) {
        const cat = String(category || '');
        const n = normalizeText(name);
        if (!cat || !n) return '';

        if (cat === 'medical') {
            if (n.includes('emergency') || n === 'er') return 'Emergency';
            if (
                n.includes('radiology') ||
                n.includes('laboratory') ||
                n.includes('lab') ||
                n.includes('imaging') ||
                n.includes('x-ray') ||
                n.includes('xray')
            ) {
                return 'Diagnostic';
            }
            return 'Clinical';
        }

        // non_medical
        if (
            n.includes('maintenance') ||
            n.includes('housekeeping') ||
            n.includes('security') ||
            n.includes('inventory') ||
            n.includes('supply') ||
            n.includes('information technology') ||
            n === 'it'
        ) {
            return 'Support';
        }
        return 'Administrative';
    }

    function syncInferredType() {
        if (!categorySelect || !nameInput || !typeInput) return;
        typeInput.value = inferDepartmentType(categorySelect.value, nameInput.value);
    }

    function applyCategoryState() {
        if (!categorySelect || !nameInput || !typeInput) return;

        const category = String(categorySelect.value || '');
        const hasCategory = category !== '';

        nameInput.disabled = !hasCategory;
        if (!hasCategory) {
            if (nameSuggestions) nameSuggestions.innerHTML = '';
            nameInput.value = '';
            typeInput.value = '';
            return;
        }

        populateNameSuggestions(category);
        syncInferredType();
    }

    function init() {
        if (!modal || !form) return;

        utils.setupModalCloseHandlers(modalId);
        form.addEventListener('submit', handleSubmit);

        if (categorySelect) {
            categorySelect.addEventListener('change', applyCategoryState);
        }

        if (nameInput) {
            nameInput.addEventListener('input', syncInferredType);
            nameInput.addEventListener('blur', syncInferredType);
        }

        applyCategoryState();
    }

    function open() {
        if (!modal || !form) return;

        form.reset();
        utils.clearErrors('err_');
        applyCategoryState();
        utils.open(modalId);
    }

    function close() {
        utils.close(modalId);
    }

    async function handleSubmit(e) {
        e.preventDefault();
        if (!form) return;

        utils.clearErrors('err_');

        const formData = new FormData(form);
        const payload = Object.fromEntries(formData.entries());

        const errors = {};
        const category = String(payload.department_category || '');
        const name = String(payload.name || '').trim();
        const inferredType = inferDepartmentType(category, name);
        payload.department_type = inferredType;
        if (typeInput) {
            typeInput.value = inferredType;
        }

        if (!category) {
            errors.department_category = 'Please select medical or non-medical department';
        }

        if (!name) {
            errors.name = 'Department name is required';
        }

        if (!inferredType) {
            errors.department_type = 'Department type is required';
        }

        if (Object.keys(errors).length > 0) {
            utils.displayErrors(errors, 'err_');
            return;
        }

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        }

        try {
            const response = await fetch(`${baseUrl}/departments/create`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });

            const result = await response.json().catch(() => ({ status: 'error', message: 'Invalid response' }));

            if (response.ok && result.status === 'success') {
                utils.showNotification('Department created successfully', 'success');
                close();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                const message = result.message || 'Failed to create department';
                if (result.errors) {
                    utils.displayErrors(result.errors, 'err_');
                } else {
                    utils.showNotification(message, 'error');
                }
            }
        } catch (error) {
            console.error('Failed to create department', error);
            utils.showNotification('Server error while creating department', 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Department';
            }
        }
    }

    // Export to global scope
    window.AddDepartmentModal = { init, open, close };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

