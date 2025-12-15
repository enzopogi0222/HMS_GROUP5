/**
 * Edit Staff Modal Controller
 */

window.EditStaffModal = {
    modal: null,
    form: null,
    departmentsLoadedFor: null,
    initialDepartmentOptions: '',
    
    init() {
        this.modal = document.getElementById('editStaffModal');
        this.form = document.getElementById('editStaffForm');
        
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.handleSubmit(e));
            const designationEl = document.getElementById('e_designation');
            designationEl?.addEventListener('change', () => StaffModalUtils.toggleRoleFields('e_'));

            const dobEl = document.getElementById('e_date_of_birth');
            if (dobEl && !dobEl.__boundDobValidation) {
                dobEl.__boundDobValidation = true;
                dobEl.addEventListener('change', () => {
                    const dobErrors = {};
                    StaffModalUtils.validateDob(this.collectFormData(), dobErrors, 'e_');
                    const dobErrEl = document.getElementById('e_err_date_of_birth');
                    if (dobErrEl) dobErrEl.textContent = dobErrors.date_of_birth || '';
                });
            }

            const contactEl = document.getElementById('e_contact_no');
            const contactErrEl = document.getElementById('e_err_contact_no');
            StaffModalUtils.bindLiveContactNoValidation(contactEl, contactErrEl);
            StaffModalUtils.toggleRoleFields('e_');

            // Department category change handler (scoped to edit form, supports prefixed/unprefixed IDs)
            const deptCategoryEl = this.form.querySelector('#e_department_category, #department_category');
            if (deptCategoryEl) {
                deptCategoryEl.addEventListener('change', () => {
                    // Clear department selection when category changes
                    const deptEl = this.form.querySelector('#e_department, #department');
                    const deptIdEl = this.form.querySelector('#e_department_id, #department_id');
                    if (deptEl) {
                        deptEl.value = '';
                    }
                    if (deptIdEl) {
                        deptIdEl.value = '';
                    }
                    this.loadDepartmentsForCategory(deptCategoryEl.value);
                });
            }

            // Department change handler (scoped to edit form, supports prefixed/unprefixed IDs)
            const deptEl = this.form.querySelector('#e_department, #department');
            if (deptEl) {
                this.initialDepartmentOptions = deptEl.innerHTML || '<option value="">Select department</option>';
                deptEl.addEventListener('change', () => {
                    const deptIdEl = this.form.querySelector('#e_department_id, #department_id');
                    if (deptIdEl) deptIdEl.value = deptEl.selectedOptions[0]?.getAttribute('data-id') || '';

                    const designation = document.getElementById('e_designation')?.value || '';
                    if (designation === 'doctor') {
                        StaffModalUtils.populateDoctorSpecializations('e_', deptEl.value);
                    }
                });
            }
        }
        
        StaffModalUtils.setupModalCloseHandlers(this.modal, () => this.close());
    },
    
    async loadDepartmentsForCategory(category) {
        if (!this.form) return;
        const deptEl = this.form.querySelector('#e_department, #department');
        const deptIdEl = this.form.querySelector('#e_department_id, #department_id');
        if (!deptEl) return;

        let normalized = String(category || '').trim();
        const fallbackOptions = this.initialDepartmentOptions || '<option value="">Select department</option>';

        console.log('[EditStaffModal] loadDepartmentsForCategory called with:', category, 'normalized start:', normalized);

        // Accept label-like values and normalize to API-friendly ones
        const lower = normalized.toLowerCase();
        if (lower === 'medical department' || lower === 'medical') {
            normalized = 'medical';
        } else if (lower === 'non-medical department' || lower === 'non medical department' || lower === 'non_medical' || lower === 'non-medical') {
            normalized = 'non_medical';
        }

        console.log('[EditStaffModal] loadDepartmentsForCategory normalized to:', normalized);
        deptEl.disabled = true;
        deptEl.innerHTML = '<option value="">Loading...</option>';
        if (deptIdEl) deptIdEl.value = '';

        if (!normalized) {
            // When no category is selected, keep the department dropdown disabled,
            // same behavior as in the Add Staff modal.
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
            deptEl.innerHTML = fallbackOptions;
            deptEl.disabled = false;
            StaffUtils.showNotification('Failed to load departments for selected category: ' + (e.message || 'Unknown error'), 'error');
        }
    },
    
    async open(staffId) {
        if (!staffId) {
            StaffUtils.showNotification('Staff ID is required', 'error');
            return;
        }
        
        if (this.modal) {
            this.modal.classList.add('active');
            this.modal.setAttribute('aria-hidden', 'false');
            StaffModalUtils.clearErrors(this.form, 'e_');
            
            try {
                await this.loadStaffDetails(staffId);

                // After staff details are loaded, if category already has a value
                const deptCategoryEl =
                    document.getElementById('e_department_category') ||
                    document.getElementById('department_category');
                console.log('[EditStaffModal] open: deptCategoryEl value after loadStaffDetails =', deptCategoryEl?.value);
                if (deptCategoryEl && deptCategoryEl.value) {
                    await this.loadDepartmentsForCategory(deptCategoryEl.value);
                }
            } catch (error) {
                console.error('Error loading staff details:', error);
                StaffUtils.showNotification('Failed to load staff details', 'error');
                this.close();
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
            StaffModalUtils.clearErrors(this.form, 'e_');
            StaffModalUtils.toggleRoleFields('e_');
            this.departmentsLoadedFor = null;
            
            const deptEl = document.getElementById('e_department');
            const deptIdEl = document.getElementById('e_department_id');
            const deptCategoryEl = document.getElementById('e_department_category');
            
            if (deptEl) {
                deptEl.innerHTML = this.initialDepartmentOptions || '<option value="">Select department</option>';
                deptEl.disabled = true;
            }
            if (deptIdEl) {
                deptIdEl.value = '';
            }
            if (deptCategoryEl) {
                deptCategoryEl.value = '';
            }
        }
    },
    
    async loadStaffDetails(staffId) {
        try {
            const response = await StaffUtils.makeRequest(
                StaffConfig.getUrl(`${StaffConfig.endpoints.staffGet}/${staffId}`)
            );
            
            if (response.status === 'success' && response.data) {
                await this.populateForm(response.data);
            } else {
                throw new Error(response.message || 'Failed to load staff details');
            }
        } catch (error) {
            console.error('Error loading staff details:', error);
            throw error;
        }
    },
    
    async populateForm(staff) {
        const normalizeDepartment = (dept, fallback) => {
            const source = dept ?? fallback;
            if (!source) return '';
            const val = String(source).trim();
            return (val.toUpperCase() === 'N/A') ? '' : val;
        };
        const normalizeCategoryValue = (value) => {
            if (value === undefined || value === null) return '';
            const normalized = String(value).trim().toLowerCase();
            if (!normalized) return '';

            if (normalized.includes('non') && normalized.includes('medical')) {
                return 'non_medical';
            }

            const nonMedicalKeywords = ['administrative', 'support', 'finance', 'billing', 'hr', 'human resources', 'it', 'information technology', 'operations'];
            if (nonMedicalKeywords.some(keyword => normalized.includes(keyword))) {
                return 'non_medical';
            }

            if (normalized.includes('medical')) {
                return 'medical';
            }

            const medicalKeywords = ['clinical', 'diagnostic', 'emergency'];
            if (medicalKeywords.some(keyword => normalized.includes(keyword))) {
                return 'medical';
            }

            return '';
        };

        // Derive a usable role value for the designation select
        let resolvedRole = staff.role || staff.role_slug || staff.designation || '';
        if (resolvedRole) {
            resolvedRole = String(resolvedRole).trim().toLowerCase();
        }

        const fields = {
            'e_staff_id': staff.staff_id || '',
            'e_employee_id': staff.employee_id || '',
            'e_first_name': staff.first_name || '',
            'e_last_name': staff.last_name || '',
            'e_gender': staff.gender || '',
            'e_date_of_birth': staff.date_of_birth || staff.dob || '',
            'e_contact_no': staff.contact_no || staff.phone || '',
            'e_email': staff.email || '',
            'e_department': normalizeDepartment(staff.department, staff.department_name),
            'e_designation': resolvedRole,
            'e_date_joined': staff.date_joined || '',
            'e_status': staff.status || 'active',
            'e_address': staff.address || '',
            'e_doctor_specialization': staff.doctor_specialization || '',
            'e_doctor_license_no': staff.doctor_license_no || '',
            'e_doctor_consultation_fee': staff.doctor_consultation_fee || '',
            'e_nurse_license_no': staff.nurse_license_no || '',
            'e_nurse_specialization': staff.nurse_specialization || '',
            'e_accountant_license_no': staff.accountant_license_no || '',
            'e_laboratorist_license_no': staff.laboratorist_license_no || '',
            'e_laboratorist_specialization': staff.laboratorist_specialization || '',
            'e_lab_room_no': staff.lab_room_no || '',
            'e_pharmacist_license_no': staff.pharmacist_license_no || '',
            'e_pharmacist_specialization': staff.pharmacist_specialization || ''
        };
        
        for (const [fieldId, value] of Object.entries(fields)) {
            const element = document.getElementById(fieldId);
            if (element) {
                element.value = value;
            }
        }

        // Handle department category and department loading
        const deptId = staff.department_id || null;
        const deptName = normalizeDepartment(staff.department, staff.department_name);
        const deptCategoryEl = document.getElementById('e_department_category');
        const deptEl = document.getElementById('e_department');
        const deptIdEl = document.getElementById('e_department_id');

        // Prefer API-provided category fields before falling back to heuristics
        const categorySources = [
            staff.department_category,
            staff.department_category_slug,
            staff.dept_category,
            staff.dept_category_slug,
            staff.department_type,
        ];
        let category = '';
        for (const source of categorySources) {
            category = normalizeCategoryValue(source);
            if (category) break;
        }

        if (!category && deptName) {
            const deptNameLower = deptName.toLowerCase();
            const medicalKeywords = ['emergency', 'cardiology', 'surgery', 'pediatrics', 'radiology', 'laboratory', 'pathology', 'clinical', 'diagnostic'];
            const nonMedicalKeywords = ['administration', 'admin', 'finance', 'billing', 'hr', 'human resources', 'it', 'information technology', 'housekeeping', 'maintenance', 'security', 'inventory'];

            if (medicalKeywords.some(keyword => deptNameLower.includes(keyword))) {
                category = 'medical';
            } else if (nonMedicalKeywords.some(keyword => deptNameLower.includes(keyword))) {
                category = 'non_medical';
            }
        }

        const applyDepartmentSelection = () => {
            if (!deptEl) return false;
            const match = Array.from(deptEl.options).find(
                (opt) =>
                    (deptName && opt.value === deptName) ||
                    (deptId && opt.getAttribute('data-id') === String(deptId))
            );
            if (match) {
                deptEl.value = match.value;
                if (deptIdEl) {
                    deptIdEl.value = match.getAttribute('data-id') || deptId || '';
                }
                return true;
            }
            return false;
        };

        const tryLoadAndSelect = async (candidateCategory) => {
            if (!candidateCategory || !deptCategoryEl) return false;
            await this.loadDepartmentsForCategory(candidateCategory);
            const matched = applyDepartmentSelection();
            if (matched) {
                deptCategoryEl.value = candidateCategory;
            }
            return matched;
        };

        if (deptCategoryEl && category) {
            deptCategoryEl.value = category;
            await this.loadDepartmentsForCategory(category);
            applyDepartmentSelection();
        } else if ((deptId || deptName) && deptCategoryEl) {
            const categoriesToTry = ['medical', 'non_medical'];
            for (const candidate of categoriesToTry) {
                const matched = await tryLoadAndSelect(candidate);
                if (matched) {
                    break;
                }
            }
        } else if (deptIdEl && deptId) {
            // Set department_id even if we couldn't determine category
            deptIdEl.value = deptId;
        }

        StaffModalUtils.toggleRoleFields('e_');
        if (resolvedRole === 'doctor') {
            const selectedDept = document.getElementById('e_department')?.value || '';
            StaffModalUtils.populateDoctorSpecializations('e_', selectedDept, fields.e_doctor_specialization);
        }
    },
    
    async handleSubmit(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('updateStaffBtn');
        const originalText = submitBtn?.innerHTML;
        
        try {
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            }
            
            StaffModalUtils.clearErrors(this.form, 'e_');
            const formData = this.collectFormData();

            const clientErrors = {};
            if (!formData.employee_id || String(formData.employee_id).trim().length < 3) {
                clientErrors.employee_id = 'Employee ID is required (min 3 characters).';
            }
            if (!formData.first_name || String(formData.first_name).trim().length < 2) {
                clientErrors.first_name = 'First name is required (min 2 characters).';
            }
            StaffModalUtils.validateDob(formData, clientErrors, 'e_');
            StaffModalUtils.validateContactNo(formData, clientErrors, 'e_');
            if (!formData.designation) {
                clientErrors.designation = 'Designation is required.';
            }
            StaffModalUtils.validateRoleFields(formData, clientErrors);

            if (Object.keys(clientErrors).length) {
                StaffModalUtils.displayErrors(clientErrors, 'e_');
                StaffUtils.showNotification('Please fix the highlighted errors.', 'warning');
                return;
            }
            
            const response = await StaffUtils.makeRequest(
                StaffConfig.getUrl(StaffConfig.endpoints.staffUpdate),
                {
                    method: 'POST',
                    body: JSON.stringify(formData)
                }
            );
            
            if (response.status === 'success' || response.success === true || response.ok === true) {
                StaffUtils.showNotification('Staff member updated successfully', 'success');
                this.close();
                
                // Refresh staff list
                if (window.staffManager) {
                    window.staffManager.refresh();
                }
            } else {
                if (response.errors) StaffModalUtils.displayErrors(response.errors, 'e_');
                throw new Error(response.message || 'Failed to update staff member');
            }
        } catch (error) {
            console.error('Error updating staff:', error);
            StaffUtils.showNotification('Failed to update staff: ' + error.message, 'error');
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
    
};

// Global functions for backward compatibility
window.openStaffEditModal = (staffId) => window.EditStaffModal?.open(staffId);
window.closeEditStaffModal = () => window.EditStaffModal?.close();
