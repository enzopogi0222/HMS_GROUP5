/**
 * Edit User Modal Controller
 */

window.EditUserModal = {
    modal: null,
    form: null,
    
    init() {
        this.modal = document.getElementById('editUserModal');
        this.form = document.getElementById('editUserForm');
        
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        }
        
        UserModalUtils.setupModal('editUserModal');
    },
    
    async open(userId) {
        if (!userId) {
            UserUtils.showNotification('User ID is required', 'error');
            return;
        }
        
        UserModalUtils.openModal('editUserModal');
        UserModalUtils.clearErrors(this.form, 'e_');
        try {
            await this.loadUserDetails(userId);
        } catch (error) {
            console.error('Error loading user details:', error);
            UserUtils.showNotification('Failed to load user details', 'error');
            this.close();
        }
    },
    
    close() {
        UserModalUtils.closeModal('editUserModal');
        this.resetForm();
    },
    
    resetForm() {
        if (this.form) {
            this.form.reset();
            UserModalUtils.clearErrors(this.form, 'e_');
        }
    },
    
    async loadUserDetails(userId) {
        try {
            const response = await UserUtils.makeRequest(
                UserConfig.getUrl(`${UserConfig.endpoints.userGet}/${userId}`)
            );
            
            if (response.status === 'success' && response.data) {
                this.populateForm(response.data);
            } else {
                throw new Error(response.message || 'Failed to load user details');
            }
        } catch (error) {
            console.error('Error loading user details:', error);
            throw error;
        }
    },
    
    populateForm(user) {
        const fields = {
            'e_user_id': user.user_id || '',
            'e_username': user.username || '',
            'e_email': user.email || '',
            'e_status': user.status || 'active'
        };
        
        for (const [fieldId, value] of Object.entries(fields)) {
            const element = document.getElementById(fieldId);
            if (element) {
                element.value = value;
            }
        }
        
        // Populate staff information display
        const staffName = UserUtils.formatFullName(user.first_name, user.last_name);
        const employeeId = user.employee_id || 'N/A';
        const department = user.department || 'N/A';
        
        document.getElementById('e_staff_name').textContent = staffName;
        document.getElementById('e_employee_id').textContent = employeeId;
        document.getElementById('e_department').textContent = department;
    },
    
    async handleSubmit(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('updateUserBtn');
        const originalText = submitBtn?.innerHTML;
        
        try {
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            }
            
            UserModalUtils.clearErrors(this.form, 'e_');
            
            const formData = UserModalUtils.collectFormData(this.form);
            const userId = formData.user_id;
            
            const response = await UserUtils.makeRequest(
                UserConfig.getUrl(`${UserConfig.endpoints.userUpdate}/${userId}`),
                {
                    method: 'POST',
                    body: JSON.stringify(formData)
                }
            );
            
            if (response.status === 'success') {
                UserUtils.showNotification('User updated successfully', 'success');
                this.close();
                
                // Refresh user list
                if (window.userManager) {
                    window.userManager.refresh();
                }
            } else {
                if (response.errors) {
                    UserModalUtils.displayErrors(response.errors, 'e_');
                }
                throw new Error(response.message || 'Failed to update user');
            }
        } catch (error) {
            console.error('Error updating user:', error);
            UserUtils.showNotification('Failed to update user: ' + error.message, 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }
    },
    
};

// Global functions for backward compatibility
window.openUserEditModal = function(userId) {
    if (window.EditUserModal) {
        window.EditUserModal.open(userId);
    }
};

window.closeEditUserModal = function() {
    if (window.EditUserModal) {
        window.EditUserModal.close();
    }
};
