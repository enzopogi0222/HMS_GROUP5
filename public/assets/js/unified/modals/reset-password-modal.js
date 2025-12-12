/**
 * Reset Password Modal Controller
 */

window.ResetPasswordModal = {
    modal: null,
    form: null,
    
    init() {
        this.modal = document.getElementById('resetPasswordModal');
        this.form = document.getElementById('resetPasswordForm');
        
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        }
        
        UserModalUtils.setupModal('resetPasswordModal');
    },
    
    async open(userId) {
        if (!userId) {
            UserUtils.showNotification('User ID is required', 'error');
            return;
        }
        
        UserModalUtils.openModal('resetPasswordModal');
        UserModalUtils.clearErrors(this.form, 'rp_');
        this.resetForm();
        
        try {
            await this.loadUserDetails(userId);
        } catch (error) {
            console.error('Error loading user details:', error);
            UserUtils.showNotification('Failed to load user details', 'error');
            this.close();
        }
    },
    
    close() {
        UserModalUtils.closeModal('resetPasswordModal');
        this.resetForm();
    },
    
    resetForm() {
        if (this.form) {
            this.form.reset();
            UserModalUtils.clearErrors(this.form, 'rp_');
        }
    },
    
    async loadUserDetails(userId) {
        try {
            const response = await UserUtils.makeRequest(
                UserConfig.getUrl(`${UserConfig.endpoints.userGet}/${userId}`)
            );
            
            if (response.status === 'success' && response.data) {
                this.populateUserInfo(response.data);
            } else {
                throw new Error(response.message || 'Failed to load user details');
            }
        } catch (error) {
            console.error('Error loading user details:', error);
            throw error;
        }
    },
    
    populateUserInfo(user) {
        // Set user ID
        const userIdField = document.getElementById('rp_user_id');
        if (userIdField) {
            userIdField.value = user.user_id || '';
        }
        
        // Populate user information display
        const staffName = UserUtils.formatFullName(user.first_name, user.last_name);
        const email = user.email || 'N/A';
        const username = user.username || 'N/A';
        
        const nameEl = document.getElementById('rp_user_name');
        const emailEl = document.getElementById('rp_user_email');
        const usernameEl = document.getElementById('rp_user_username');
        
        if (nameEl) nameEl.textContent = staffName || 'N/A';
        if (emailEl) emailEl.textContent = email;
        if (usernameEl) usernameEl.textContent = username;
    },
    
    validateForm() {
        const password = document.getElementById('rp_new_password')?.value || '';
        const confirmPassword = document.getElementById('rp_confirm_password')?.value || '';
        
        // Clear previous errors
        UserModalUtils.clearErrors(this.form, 'rp_');
        
        let isValid = true;
        
        // Validate password length
        if (password.length < 6) {
            const errEl = document.getElementById('rp_err_password');
            if (errEl) {
                errEl.textContent = 'Password must be at least 6 characters';
            }
            isValid = false;
        }
        
        // Validate password match
        if (password !== confirmPassword) {
            const errEl = document.getElementById('rp_err_confirm');
            if (errEl) {
                errEl.textContent = 'Passwords do not match';
            }
            isValid = false;
        }
        
        return isValid;
    },
    
    async handleSubmit(e) {
        e.preventDefault();
        
        if (!this.validateForm()) {
            UserUtils.showNotification('Please fix the form errors', 'error');
            return;
        }
        
        const submitBtn = document.getElementById('resetPasswordBtn');
        const originalText = submitBtn?.innerHTML;
        
        try {
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Resetting...';
            }
            
            const formData = UserModalUtils.collectFormData(this.form);
            const userId = formData.user_id;
            const newPassword = formData.new_password;
            
            const response = await UserUtils.makeRequest(
                UserConfig.getUrl(`${UserConfig.endpoints.userResetPassword}/${userId}`),
                {
                    method: 'POST',
                    body: JSON.stringify({ new_password: newPassword })
                }
            );
            
            if (response.status === 'success') {
                UserUtils.showNotification('Password reset successfully', 'success');
                this.close();
                
                // Refresh user list
                if (window.userManager) {
                    window.userManager.refresh();
                }
            } else {
                if (response.errors) {
                    UserModalUtils.displayErrors(response.errors, 'rp_');
                }
                throw new Error(response.message || 'Failed to reset password');
            }
        } catch (error) {
            console.error('Error resetting password:', error);
            UserUtils.showNotification('Failed to reset password: ' + error.message, 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }
    },
    
};

// Global functions for backward compatibility
window.openResetPasswordModal = function(userId) {
    if (window.ResetPasswordModal) {
        window.ResetPasswordModal.open(userId);
    }
};

window.closeResetPasswordModal = function() {
    if (window.ResetPasswordModal) {
        window.ResetPasswordModal.close();
    }
};

