/**
 * Add User Modal Controller
 */

window.AddUserModal = {
    modal: null,
    form: null,
    staffCache: [],
    
    init() {
        this.modal = document.getElementById('addUserModal');
        this.form = document.getElementById('addUserForm');
        
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        }
        
        const staffSelect = document.getElementById('staff_id');
        if (staffSelect) {
            staffSelect.addEventListener('change', (e) => this.handleStaffSelection(e));
        }
        
        const confirmPassword = document.getElementById('confirm_password');
        if (confirmPassword) {
            confirmPassword.addEventListener('input', () => this.validatePasswordMatch());
        }
        
        UserModalUtils.setupModal('addUserModal');
    },
    
    async open() {
        UserModalUtils.openModal('addUserModal');
        this.resetForm();
        try {
            await this.loadAvailableStaff();
        } catch (error) {
            console.error('Error loading staff:', error);
            UserUtils.showNotification('Failed to load available staff', 'error');
        }
    },
    
    close() {
        UserModalUtils.closeModal('addUserModal');
        this.resetForm();
    },
    
    resetForm() {
        if (this.form) {
            this.form.reset();
            UserModalUtils.clearErrors(this.form);
            const staffSelect = document.getElementById('staff_id');
            if (staffSelect) staffSelect.value = '';
        }
    },
    
    async loadAvailableStaff() {
        const staffSelect = document.getElementById('staff_id');
        if (!staffSelect) return;
        
        try {
            // Only show loading placeholder if there are no existing options
            if (staffSelect.options.length <= 1) {
                staffSelect.innerHTML = '<option value="">Loading staff...</option>';
            }
            
            const response = await UserUtils.makeRequest(
                UserConfig.getUrl(UserConfig.endpoints.availableStaff)
            );
            
            if (response.status === 'success') {
                this.staffCache = response.data || [];
                this.populateStaffSelect();
            } else {
                throw new Error(response.message || 'Failed to load staff');
            }
        } catch (error) {
            console.error('Error loading staff:', error);
            // Preserve existing options if present; only show error if empty
            if (staffSelect.options.length <= 1) {
                staffSelect.innerHTML = '<option value="">Error loading staff</option>';
            }
            throw error;
        }
    },
    
    populateStaffSelect() {
        const staffSelect = document.getElementById('staff_id');
        if (!staffSelect) return;
        
        if (this.staffCache.length === 0) {
            staffSelect.innerHTML = '<option value="">No available staff members</option>';
            return;
        }
        
        const options = ['<option value="">Select staff member...</option>'];
        
        this.staffCache.forEach(staff => {
            const displayName = `${staff.full_name} - ${staff.department || 'No Department'} (${staff.employee_id || staff.staff_id})`;
            options.push(`<option value="${staff.staff_id}" data-email="${staff.email || ''}" data-role="${staff.role || staff.designation || ''}">${UserUtils.escapeHtml(displayName)}</option>`);
        });
        
        staffSelect.innerHTML = options.join('');
    },
    
    handleStaffSelection(e) {
        const selectedOption = e.target.selectedOptions[0];
        if (!selectedOption || !selectedOption.value) {
            // Clear dependent fields
            document.getElementById('email').value = '';
            return;
        }
        
        // Populate email from selected staff
        const email = selectedOption.dataset.email || '';
        
        document.getElementById('email').value = email;
        
        // Generate username suggestion
        const staff = this.staffCache.find(s => s.staff_id == selectedOption.value);
        if (staff) {
            const usernameSuggestion = this.generateUsername(staff);
            document.getElementById('username').value = usernameSuggestion;
        }
    },
    
    generateUsername(staff) {
        // Generate username from first name + last name initial
        const firstName = (staff.first_name || '').toLowerCase().replace(/[^a-z]/g, '');
        const lastInitial = (staff.last_name || '').charAt(0).toLowerCase();
        return firstName + lastInitial;
    },
    
    validatePasswordMatch() {
        return UserModalUtils.validatePasswordMatch('password', 'confirm_password', 'err_confirm_password');
    },
    
    async handleSubmit(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('saveUserBtn');
        const originalText = submitBtn?.innerHTML;
        
        try {
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
            }
            
            UserModalUtils.clearErrors(this.form);
            
            if (!this.validatePasswordMatch()) {
                return;
            }
            
            const formData = UserModalUtils.collectFormData(this.form);
            
            const response = await UserUtils.makeRequest(
                UserConfig.getUrl(UserConfig.endpoints.userCreate),
                {
                    method: 'POST',
                    body: JSON.stringify(formData)
                }
            );

            if (response.status === 'success' || response.success === true || response.ok === true) {
                UserUtils.showNotification('User created successfully', 'success');
                this.close();
                
                // Refresh user list
                if (window.userManager) {
                    window.userManager.refresh();
                }
            } else {
                if (response.errors) {
                    UserModalUtils.displayErrors(response.errors);
                    UserUtils.showNotification(response.message || 'Please fix the highlighted errors.', 'warning');
                    return;
                }
                throw new Error(response.message || `Failed to create user (status ${response.statusCode || 'unknown'})`);
            }
        } catch (error) {
            console.error('Error creating user:', error);
            UserUtils.showNotification('Failed to create user: ' + error.message, 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }
    },
    
};

// Global functions for backward compatibility
window.openAddUserModal = function() {
    if (window.AddUserModal) {
        window.AddUserModal.open();
    }
};

window.closeAddUserModal = function() {
    if (window.AddUserModal) {
        window.AddUserModal.close();
    }
};
