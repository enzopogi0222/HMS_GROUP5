/**
 * Edit Staff Modal Controller
 */

window.EditStaffModal = {
    modal: null,
    form: null,
    
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
            StaffModalUtils.toggleRoleFields('e_');
        }
        
        StaffModalUtils.setupModalCloseHandlers(this.modal, () => this.close());
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
        }
    },
    
    async loadStaffDetails(staffId) {
        try {
            const response = await StaffUtils.makeRequest(
                StaffConfig.getUrl(`${StaffConfig.endpoints.staffGet}/${staffId}`)
            );
            
            if (response.status === 'success' && response.data) {
                this.populateForm(response.data);
            } else {
                throw new Error(response.message || 'Failed to load staff details');
            }
        } catch (error) {
            console.error('Error loading staff details:', error);
            throw error;
        }
    },
    
    populateForm(staff) {
        const normalizeDepartment = (dept) => {
            if (!dept) return '';
            const val = String(dept).trim();
            return (val.toUpperCase() === 'N/A') ? '' : val;
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
            'e_department': normalizeDepartment(staff.department),
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

        StaffModalUtils.toggleRoleFields('e_');
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
