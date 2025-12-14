/**
 * Add Staff Modal Controller
 */

window.AddStaffModal = {
    modal: null,
    form: null,
    departmentsLoadedFor: null,
    
    init() {
        this.modal = document.getElementById('addStaffModal');
        this.form = document.getElementById('addStaffForm');
        
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.handleSubmit(e));
            const employeeIdInput = document.getElementById('employee_id');
            if (employeeIdInput) employeeIdInput.readOnly = true;
            
            const designationEl = document.getElementById('designation');
            designationEl?.addEventListener('change', () => {
                StaffModalUtils.toggleRoleFields('');
                this.updateEmployeeIdForRole();
            });
            StaffModalUtils.toggleRoleFields('');

            const deptEl = document.getElementById('department');
            if (deptEl) {
                deptEl.addEventListener('change', () => {
                    const deptIdEl = document.getElementById('department_id');
                    if (deptIdEl) deptIdEl.value = deptEl.selectedOptions[0]?.getAttribute('data-id') || '';
                });
            }

            const deptCategoryEl = document.getElementById('department_category');
            if (deptCategoryEl) {
                deptCategoryEl.addEventListener('change', () => {
                    // Clear department selection when category changes
                    const deptEl = document.getElementById('department');
                    const deptIdEl = document.getElementById('department_id');
                    if (deptEl) {
                        deptEl.value = '';
                    }
                    if (deptIdEl) {
                        deptIdEl.value = '';
                    }
                    this.loadDepartmentsForCategory(deptCategoryEl.value);
                });
            }

            const dobEl = document.getElementById('date_of_birth');
            if (dobEl && !dobEl.__boundDobValidation) {
                dobEl.__boundDobValidation = true;
                StaffModalUtils.applyDobAgeLimit(dobEl);
                dobEl.addEventListener('change', () => {
                    const dobErrors = {};
                    StaffModalUtils.validateDob(this.collectFormData(), dobErrors);
                    const dobErrEl = document.getElementById('err_date_of_birth');
                    if (dobErrEl) dobErrEl.textContent = dobErrors.date_of_birth || '';
                });
            }

            const contactEl = document.getElementById('contact_no');
            const contactErrEl = document.getElementById('err_contact_no');
            StaffModalUtils.bindLiveContactNoValidation(contactEl, contactErrEl);
        }
        
        StaffModalUtils.setupModalCloseHandlers(this.modal, () => this.close());
    },

    async loadDepartmentsForCategory(category) {
        const deptEl = document.getElementById('department');
        const deptIdEl = document.getElementById('department_id');
        if (!deptEl) return;

        const normalized = String(category || '').trim();
        deptEl.disabled = true;
        deptEl.innerHTML = '<option value="">Loading...</option>';
        if (deptIdEl) deptIdEl.value = '';

        if (!normalized) {
            deptEl.innerHTML = '<option value="">Select department</option>';
            deptEl.disabled = true;
            return;
        }

        try {
            const url = StaffConfig.getUrl('staff/departments-by-category') + '?category=' + encodeURIComponent(normalized);
            const response = await StaffUtils.makeRequest(url);

            if (!response.ok || response.status !== 'success') {
                throw new Error(response.message || 'Failed to load departments');
            }

            const rows = Array.isArray(response.data) ? response.data : [];
            deptEl.innerHTML = '<option value="">Select department</option>';
            
            if (rows.length === 0) {
                // No departments found for this category
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = 'No departments available for this category';
                opt.disabled = true;
                deptEl.appendChild(opt);
                StaffUtils.showNotification('No departments found for the selected category. Please add departments first.', 'warning');
            } else {
                rows.forEach((d) => {
                    const opt = document.createElement('option');
                    opt.value = d.name || '';
                    if (d.department_id !== undefined && d.department_id !== null) {
                        opt.setAttribute('data-id', String(d.department_id));
                    }
                    opt.textContent = d.name || '';
                    deptEl.appendChild(opt);
                });
            }

            deptEl.disabled = false;
            this.departmentsLoadedFor = normalized;
        } catch (e) {
            console.error('Failed to load departments:', e);
            deptEl.innerHTML = '<option value="">Select department</option>';
            deptEl.disabled = false;
            StaffUtils.showNotification('Failed to load departments for selected category: ' + (e.message || 'Unknown error'), 'error');
        }
    },
    
    async open() {
        if (this.modal) {
            this.modal.classList.add('active');
            this.modal.setAttribute('aria-hidden', 'false');
            this.resetForm();
            
            // Set default date joined to today
            const dateJoinedField = document.getElementById('date_joined');
            if (dateJoinedField && !dateJoinedField.value) {
                dateJoinedField.value = new Date().toISOString().split('T')[0];
            }

            StaffModalUtils.applyDobAgeLimit(document.getElementById('date_of_birth'));
            
            // Restore draft if available (this will also load departments if category is set)
            await this.restoreDraft();
            
            // If no draft was restored but category is selected, load departments
            const deptCategoryEl = document.getElementById('department_category');
            if (deptCategoryEl && deptCategoryEl.value && !this.departmentsLoadedFor) {
                await this.loadDepartmentsForCategory(deptCategoryEl.value);
            }
        }
    },
    
    close() {
        if (this.modal) {
            this.modal.classList.remove('active');
            this.modal.setAttribute('aria-hidden', 'true');
            this.resetForm();
        }
    },
    
    resetForm() {
        if (this.form) {
            this.form.reset();
            StaffModalUtils.clearErrors(this.form);
            StaffModalUtils.toggleRoleFields('');
            const deptIdEl = document.getElementById('department_id');
            if (deptIdEl) deptIdEl.value = '';

            const deptCategoryEl = document.getElementById('department_category');
            const deptEl = document.getElementById('department');
            if (deptCategoryEl) deptCategoryEl.value = '';
            if (deptEl) {
                deptEl.disabled = true;
                deptEl.value = '';
            }
            this.departmentsLoadedFor = null;
        }
    },

    async updateEmployeeIdForRole() {
        const designation = document.getElementById('designation')?.value || '';
        const employeeIdInput = document.getElementById('employee_id');
        if (!employeeIdInput) return;

        if (!designation) {
            employeeIdInput.value = '';
            return;
        }

        const originalPlaceholder = employeeIdInput.placeholder || '';
        employeeIdInput.placeholder = 'Generating...';

        try {
            const url = StaffConfig.getUrl('staff/next-employee-id') + '?role=' + encodeURIComponent(designation);
            const response = await StaffUtils.makeRequest(url);

            if (response.status === 'success' && response.employee_id) {
                employeeIdInput.value = response.employee_id;
            } else {
                employeeIdInput.value = '';
                employeeIdInput.placeholder = 'Unable to generate ID';
            }
        } catch (error) {
            console.error('Failed to generate employee ID:', error);
            employeeIdInput.value = '';
            employeeIdInput.placeholder = 'Unable to generate ID';
        } finally {
            if (!employeeIdInput.value) {
                employeeIdInput.placeholder = originalPlaceholder || 'e.g., DOC-0001';
            }
        }
    },
    
    
    async handleSubmit(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('saveStaffBtn');
        const originalText = submitBtn?.innerHTML;
        
        try {
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            }
            
            StaffModalUtils.clearErrors(this.form);
            
            // Ensure department_id is set from selected option before collecting form data
            const deptEl = document.getElementById('department');
            const deptIdEl = document.getElementById('department_id');
            if (deptEl && deptIdEl && deptEl.selectedIndex > 0) {
                const selectedOption = deptEl.options[deptEl.selectedIndex];
                const deptId = selectedOption.getAttribute('data-id');
                if (deptId) {
                    deptIdEl.value = deptId;
                }
            }
            
            const formData = this.collectFormData();

            const clientErrors = {};
            if (!formData.employee_id || String(formData.employee_id).trim().length < 3) {
                clientErrors.employee_id = 'Employee ID is required (min 3 characters).';
            }
            if (!formData.first_name || String(formData.first_name).trim().length < 2) {
                clientErrors.first_name = 'First name is required (min 2 characters).';
            }
            StaffModalUtils.validateDob(formData, clientErrors);
            StaffModalUtils.validateContactNo(formData, clientErrors);
            if (!formData.designation) {
                clientErrors.designation = 'Designation is required.';
            }

            const deptCategory = (formData.department_category || '').trim();
            const department = (formData.department || '').trim();
            const departmentId = (formData.department_id || '').trim();
            if (!deptCategory) {
                clientErrors.department_category = 'Department category is required.';
            }
            if (!department) {
                clientErrors.department = 'Department is required.';
            }
            if (!departmentId) {
                clientErrors.department = 'Department ID is missing. Please select a valid department.';
            }

            StaffModalUtils.validateRoleFields(formData, clientErrors);
            
            if (Object.keys(clientErrors).length) {
                StaffModalUtils.displayErrors(clientErrors);
                StaffUtils.showNotification('Please fix the highlighted errors.', 'warning');
                return;
            }
            
            const response = await StaffUtils.makeRequest(
                StaffConfig.getUrl(StaffConfig.endpoints.staffCreate),
                {
                    method: 'POST',
                    body: JSON.stringify(formData)
                }
            );
            
            if (response.status === 'success') {
                StaffUtils.showNotification('Staff member added successfully', 'success');
                // Clear saved draft now that data is successfully saved
                this.clearDraft();
                this.close();
                
                // Refresh staff list
                if (window.staffManager) {
                    window.staffManager.refresh();
                }
            } else {
                if (response.errors) {
                    StaffModalUtils.displayErrors(response.errors);
                    StaffUtils.showNotification(response.message || 'Please fix the highlighted errors.', 'warning');
                    return;
                }
                throw new Error(response.message || `Request failed (status ${response.statusCode || 'unknown'})`);
            }
        } catch (error) {
            console.error('Error adding staff:', error);
            StaffUtils.showNotification('Failed to add staff: ' + error.message, 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }
    },


    collectFormData() {
        const formData = new FormData(this.form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        return data;
    },

    // Save current form values to localStorage so they survive navigation/back
    saveDraft() {
        if (!this.form || !window.localStorage) return;
        try {
            const data = this.collectFormData();
            window.localStorage.setItem('hms_staff_add_draft', JSON.stringify(data));
        } catch (e) {
            // Fail silently if storage is not available
            console.warn('Unable to save staff add draft:', e);
        }
    },

    // Restore draft values from localStorage
    async restoreDraft() {
        if (!this.form || !window.localStorage) return;
        try {
            const raw = window.localStorage.getItem('hms_staff_add_draft');
            if (!raw) return;
            const data = JSON.parse(raw);
            if (!data || typeof data !== 'object') return;

            Object.keys(data).forEach((key) => {
                const field = this.form.querySelector(`[name="${key}"]`);
                if (!field) return;
                if (field.type === 'checkbox' || field.type === 'radio') {
                    field.checked = !!data[key];
                } else {
                    field.value = data[key];
                }
            });

            // If department_category was restored, load departments for it
            const deptCategoryEl = document.getElementById('department_category');
            if (deptCategoryEl && deptCategoryEl.value) {
                await this.loadDepartmentsForCategory(deptCategoryEl.value);
                
                // After loading, try to restore the selected department
                const deptEl = document.getElementById('department');
                const deptIdEl = document.getElementById('department_id');
                if (deptEl && data.department) {
                    // Wait a bit for options to be populated
                    setTimeout(() => {
                        const matchingOption = Array.from(deptEl.options).find(
                            opt => opt.value === data.department
                        );
                        if (matchingOption) {
                            deptEl.value = data.department;
                            if (deptIdEl) {
                                deptIdEl.value = matchingOption.getAttribute('data-id') || '';
                            }
                        }
                    }, 100);
                }
            } else {
                // Re-apply department_id based on selected department option
                const deptEl = document.getElementById('department');
                const deptIdEl = document.getElementById('department_id');
                if (deptEl && deptIdEl && deptEl.selectedOptions.length) {
                    const opt = deptEl.selectedOptions[0];
                    deptIdEl.value = opt.getAttribute('data-id') || '';
                }
            }

            StaffModalUtils.toggleRoleFields('');
        } catch (e) {
            console.warn('Unable to restore staff add draft:', e);
        }
    },

    // Clear any stored draft (used after successful save)
    clearDraft() {
        if (!window.localStorage) return;
        try {
            window.localStorage.removeItem('hms_staff_add_draft');
        } catch (e) {
            console.warn('Unable to clear staff add draft:', e);
        }
    },
    
};

// Global functions for backward compatibility
window.openAddStaffModal = () => window.AddStaffModal?.open();
window.closeAddStaffModal = () => window.AddStaffModal?.close();
