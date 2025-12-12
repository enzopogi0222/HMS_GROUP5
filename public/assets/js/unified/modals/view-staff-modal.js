/**
 * View Staff Modal Controller
 */

window.ViewStaffModal = {
    modal: null,
    
    init() {
        this.modal = document.getElementById('viewStaffModal');
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
            this.clearForm();
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
        const formatDate = (d) => d ? new Date(d).toLocaleDateString() : '';
        const formatText = (t) => t ? t.charAt(0).toUpperCase() + t.slice(1).replace('_', ' ') : '';
        
        const fields = {
            'v_employee_id': staff.employee_id || staff.staff_id || '',
            'v_first_name': staff.first_name || '',
            'v_last_name': staff.last_name || '',
            'v_gender': formatText(staff.gender),
            'v_date_of_birth': formatDate(staff.date_of_birth),
            'v_contact_no': staff.contact_no || staff.phone || '',
            'v_email': staff.email || '',
            'v_role': formatText(staff.role),
            'v_department': staff.department || '',
            'v_date_joined': formatDate(staff.date_joined),
            'v_status': formatText(staff.status),
            'v_address': staff.address || ''
        };
        
        Object.entries(fields).forEach(([id, val]) => {
            const el = document.getElementById(id);
            if (el) el.value = val;
        });
    },
    
    clearForm() {
        ['v_employee_id', 'v_first_name', 'v_last_name', 'v_gender', 'v_date_of_birth', 'v_contact_no', 'v_email', 'v_role', 'v_department', 'v_date_joined', 'v_status', 'v_address'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
    }
};

// Global functions for backward compatibility
window.openStaffViewModal = (staffId) => window.ViewStaffModal?.open(staffId);
window.closeViewStaffModal = () => window.ViewStaffModal?.close();
