/**
 * View User Modal Controller
 */

window.ViewUserModal = {
    modal: null,
    
    init() {
        this.modal = document.getElementById('viewUserModal');
        UserModalUtils.setupModal('viewUserModal');
    },
    
    async open(userId) {
        if (!userId) {
            UserUtils.showNotification('User ID is required', 'error');
            return;
        }
        
        UserModalUtils.openModal('viewUserModal');
        try {
            await this.loadUserDetails(userId);
        } catch (error) {
            console.error('Error loading user details:', error);
            UserUtils.showNotification('Failed to load user details', 'error');
            this.close();
        }
    },
    
    close() {
        UserModalUtils.closeModal('viewUserModal');
        this.clearForm();
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
            'v_username': user.username || '',
            'v_full_name': UserUtils.formatFullName(user.first_name, user.last_name),
            'v_email': user.email || '',
            'v_role': user.role ? user.role.charAt(0).toUpperCase() + user.role.slice(1).replace('_', ' ') : '',
            'v_department': user.department || 'N/A',
            'v_status': user.status ? user.status.charAt(0).toUpperCase() + user.status.slice(1) : '',
            'v_employee_id': user.employee_id || 'N/A',
            'v_created_at': user.created_at ? UserUtils.formatDateTime(user.created_at) : 'N/A',
            'v_last_login': user.last_login ? UserUtils.formatDateTime(user.last_login) : 'Never'
        };
        
        for (const [fieldId, value] of Object.entries(fields)) {
            const element = document.getElementById(fieldId);
            if (element) {
                element.value = value;
            }
        }
    },
    
    clearForm() {
        const fields = [
            'v_username', 'v_full_name', 'v_email', 'v_role',
            'v_department', 'v_status', 'v_employee_id', 'v_created_at', 'v_last_login'
        ];
        
        fields.forEach(fieldId => {
            const element = document.getElementById(fieldId);
            if (element) {
                element.value = '';
            }
        });
    }
};

// Global functions for backward compatibility
window.openUserViewModal = function(userId) {
    if (window.ViewUserModal) {
        window.ViewUserModal.open(userId);
    }
};

window.closeViewUserModal = function() {
    if (window.ViewUserModal) {
        window.ViewUserModal.close();
    }
};
