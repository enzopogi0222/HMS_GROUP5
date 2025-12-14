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
    const modalTitle = document.getElementById('addDepartmentTitle');

    const categorySelect = document.getElementById('department_category');
    const nameInput = document.getElementById('department_name');
    const typeInput = document.getElementById('department_type');
    const nameSuggestions = document.getElementById('department_name_suggestions');
    const headSelect = document.getElementById('department_head');

    let isEditMode = false;
    let currentDepartmentId = null;

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

    function updateHeadPlaceholder() {
        if (!headSelect) return;
        
        const category = String(categorySelect?.value || '');
        const firstOption = headSelect.querySelector('option[value=""]');
        
        if (firstOption) {
            if (category === 'medical') {
                firstOption.textContent = 'Select doctor...';
            } else if (category === 'non_medical') {
                firstOption.textContent = 'Select admin...';
            } else {
                firstOption.textContent = 'Select doctor...';
            }
        }
    }

    async function loadDepartmentHeads(category) {
        if (!headSelect) return;
        
        const normalizedCategory = String(category || '').trim();
        const currentValue = headSelect.value;
        
        // Update placeholder first
        updateHeadPlaceholder();
        
        if (!normalizedCategory || !['medical', 'non_medical'].includes(normalizedCategory)) {
            headSelect.disabled = true;
            return;
        }
        
        headSelect.disabled = true;
        
        // Clear and show loading
        headSelect.innerHTML = '';
        const placeholderText = normalizedCategory === 'medical' ? 'Select doctor...' : 'Select admin...';
        const placeholderOption = document.createElement('option');
        placeholderOption.value = '';
        placeholderOption.textContent = placeholderText;
        headSelect.appendChild(placeholderOption);
        
        const loadingOption = document.createElement('option');
        loadingOption.value = '';
        loadingOption.textContent = 'Loading...';
        loadingOption.disabled = true;
        headSelect.appendChild(loadingOption);
        
        try {
            const url = `${baseUrl}/departments/heads-by-category?category=${encodeURIComponent(normalizedCategory)}`;
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json'
                }
            });
            
            const result = await response.json();
            
            // Clear and rebuild options
            headSelect.innerHTML = '';
            const newPlaceholderOption = document.createElement('option');
            newPlaceholderOption.value = '';
            newPlaceholderOption.textContent = placeholderText;
            headSelect.appendChild(newPlaceholderOption);
            
            if (response.ok && result.status === 'success' && Array.isArray(result.data)) {
                const heads = result.data;
                
                if (heads.length === 0) {
                    const noOption = document.createElement('option');
                    noOption.value = '';
                    noOption.textContent = `No ${normalizedCategory === 'medical' ? 'doctors' : 'admins'} available`;
                    noOption.disabled = true;
                    headSelect.appendChild(noOption);
                } else {
                    heads.forEach(head => {
                        const option = document.createElement('option');
                        option.value = head.staff_id || '';
                        let text = head.full_name || `Staff #${head.staff_id}`;
                        if (head.position) {
                            text += ` - ${head.position}`;
                        }
                        option.textContent = text;
                        headSelect.appendChild(option);
                    });
                    
                    // Restore previous selection if it still exists
                    if (currentValue && headSelect.querySelector(`option[value="${currentValue}"]`)) {
                        headSelect.value = currentValue;
                    }
                }
            } else {
                throw new Error(result.message || 'Failed to load department heads');
            }
        } catch (error) {
            console.error('Failed to load department heads:', error);
            headSelect.innerHTML = '';
            const errorPlaceholderOption = document.createElement('option');
            errorPlaceholderOption.value = '';
            errorPlaceholderOption.textContent = placeholderText;
            headSelect.appendChild(errorPlaceholderOption);
            
            const errorOption = document.createElement('option');
            errorOption.value = '';
            errorOption.textContent = 'Failed to load';
            errorOption.disabled = true;
            headSelect.appendChild(errorOption);
        } finally {
            headSelect.disabled = false;
        }
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
            updateHeadPlaceholder();
            if (headSelect) {
                headSelect.innerHTML = '<option value="">Select doctor...</option>';
                headSelect.disabled = true;
            }
            return;
        }

        populateNameSuggestions(category);
        syncInferredType();
        updateHeadPlaceholder();
        loadDepartmentHeads(category);
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

        isEditMode = false;
        currentDepartmentId = null;
        form.reset();
        utils.clearErrors('err_');
        
        // Reset head select to default state
        if (headSelect) {
            headSelect.innerHTML = '<option value="">Select doctor...</option>';
            headSelect.disabled = true;
        }
        
        applyCategoryState();
        
        if (modalTitle) {
            modalTitle.innerHTML = '<i class="fas fa-building" style="color:#2563eb"></i> Add Department';
        }
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Department';
        }
        
        utils.open(modalId);
    }

    async function openForEdit(departmentId) {
        if (!modal || !form || !departmentId) return;

        isEditMode = true;
        currentDepartmentId = departmentId;
        form.reset();
        utils.clearErrors('err_');

        if (modalTitle) {
            modalTitle.innerHTML = '<i class="fas fa-building" style="color:#2563eb"></i> Edit Department';
        }
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Update Department';
        }

        utils.open(modalId);

        // Show loading state
        if (nameInput) nameInput.disabled = true;
        if (categorySelect) categorySelect.disabled = true;
        if (headSelect) {
            headSelect.innerHTML = '<option value="">Loading...</option>';
            headSelect.disabled = true;
        }

        try {
            const response = await fetch(`${baseUrl}/departments/${departmentId}`, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (!response.ok || result.status !== 'success') {
                throw new Error(result.message || 'Failed to load department');
            }

            const department = result.data || {};
            await populateFormForEdit(department);
        } catch (error) {
            console.error('Error loading department:', error);
            utils.showNotification(error.message || 'Failed to load department', 'error');
            close();
        } finally {
            if (nameInput) nameInput.disabled = false;
            if (categorySelect) categorySelect.disabled = false;
            if (headSelect) headSelect.disabled = false;
        }
    }

    async function populateFormForEdit(dept) {
        // Determine category from type
        const type = dept.type || '';
        let category = '';
        if (['Clinical', 'Emergency', 'Diagnostic'].includes(type)) {
            category = 'medical';
        } else if (['Administrative', 'Support'].includes(type)) {
            category = 'non_medical';
        }

        if (categorySelect) {
            categorySelect.value = category;
            await applyCategoryStateAsync();
        }

        if (nameInput) {
            nameInput.value = dept.name || '';
            syncInferredType();
        }

        const codeInput = document.getElementById('department_code');
        if (codeInput) codeInput.value = dept.code || '';

        const floorInput = document.getElementById('department_floor');
        if (floorInput) floorInput.value = dept.floor || '';

        // Wait for department heads to load before setting the value
        if (headSelect && category) {
            await loadDepartmentHeads(category);
            if (dept.department_head_id) {
                headSelect.value = dept.department_head_id || '';
            }
        }

        const statusSelect = document.getElementById('department_status');
        if (statusSelect) statusSelect.value = dept.status || 'Active';

        const descTextarea = document.getElementById('department_description');
        if (descTextarea) descTextarea.value = dept.description || '';
    }

    async function applyCategoryStateAsync() {
        if (!categorySelect || !nameInput || !typeInput) return;

        const category = String(categorySelect.value || '');
        const hasCategory = category !== '';

        nameInput.disabled = !hasCategory;
        if (!hasCategory) {
            if (nameSuggestions) nameSuggestions.innerHTML = '';
            nameInput.value = '';
            typeInput.value = '';
            updateHeadPlaceholder();
            if (headSelect) {
                headSelect.innerHTML = '<option value="">Select doctor...</option>';
                headSelect.disabled = true;
            }
            return;
        }

        populateNameSuggestions(category);
        syncInferredType();
        updateHeadPlaceholder();
        await loadDepartmentHeads(category);
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
            const url = isEditMode ? `${baseUrl}/departments/update` : `${baseUrl}/departments/create`;
            const method = 'POST';

            if (isEditMode && currentDepartmentId) {
                payload.id = currentDepartmentId;
            }

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });

            const result = await response.json().catch(() => ({ status: 'error', message: 'Invalid response' }));

            if (response.ok && result.status === 'success') {
                const message = isEditMode ? 'Department updated successfully' : 'Department created successfully';
                utils.showNotification(message, 'success');
                close();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                const message = result.message || (isEditMode ? 'Failed to update department' : 'Failed to create department');
                if (result.errors) {
                    utils.displayErrors(result.errors, 'err_');
                } else {
                    utils.showNotification(message, 'error');
                }
            }
        } catch (error) {
            console.error(`Failed to ${isEditMode ? 'update' : 'create'} department`, error);
            utils.showNotification(`Server error while ${isEditMode ? 'updating' : 'creating'} department`, 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = isEditMode 
                    ? '<i class="fas fa-save"></i> Update Department'
                    : '<i class="fas fa-save"></i> Save Department';
            }
        }
    }

    // Export to global scope
    window.AddDepartmentModal = { init, open, openForEdit, close };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

