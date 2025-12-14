/**
 * Unified Prescription Management JavaScript
 * Handles all prescription management functionality across different user roles
 */

class PrescriptionManager {
    constructor() {
        this.config = this.getConfig();
        this.filters = {};
        this.prescriptions = [];
        this.medicationOptionsCache = null; // cache medications for all rows
        
        this.init();
    }

    getConfig() {
        const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const csrfHash = document.querySelector('meta[name="csrf-hash"]')?.content || '';
        const userRole = document.querySelector('meta[name="user-role"]')?.content || '';

        return {
            baseUrl: baseUrl.replace(/\/$/, ''),
            csrfToken,
            csrfHash,
            userRole,
            endpoints: {
                prescriptions: `${baseUrl}prescriptions/api`,
                create: `${baseUrl}prescriptions/create`,
                update: `${baseUrl}prescriptions/update`,
                delete: `${baseUrl}prescriptions/delete`,
                getPrescription: `${baseUrl}prescriptions`,
                updateStatus: `${baseUrl}prescriptions`,
                availablePatients: `${baseUrl}prescriptions/available-patients`,
                availableDoctors: `${baseUrl}prescriptions/available-doctors`,
                availableMedications: `${baseUrl}prescriptions/available-medications`,
            }
        };
    }

    init() {
        this.bindEvents();
        this.loadPrescriptions();
        this.setupAutoRefresh();
    }

    bindEvents() {
        this.bindFilterEvents();
        this.bindFormEvents();
        this.bindActionEvents();
        this.initializeModals();
    }
    
    initializeModals() {
        if (window.AddPrescriptionModal) window.AddPrescriptionModal.init();
        if (window.ViewPrescriptionModal) window.ViewPrescriptionModal.init();
    }

    bindFilterEvents() {
        const dateFilter = document.getElementById('dateFilter');
        const statusFilter = document.getElementById('statusFilter');
        const searchFilter = document.getElementById('searchFilter');
        const clearFilters = document.getElementById('clearFilters');

        if (dateFilter) {
            dateFilter.addEventListener('change', () => this.applyFilters());
        }

        if (statusFilter) {
            statusFilter.addEventListener('change', () => this.applyFilters());
        }

        if (searchFilter) {
            let searchTimeout;
            searchFilter.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => this.applyFilters(), 300);
            });
        }

        if (clearFilters) {
            clearFilters.addEventListener('click', () => this.clearFilters());
        }
    }

    bindFormEvents() {
        // Form events are handled elsewhere
    }

    showNotification(message, type) {
        if (type === 'success') {
            this.showSuccess(message);
        } else {
            this.showError(message);
        }
    }

    bindActionEvents() {
        // Delegate event handling for dynamically created buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('.btn-view') || e.target.closest('.btn-view')) {
                const btn = e.target.matches('.btn-view') ? e.target : e.target.closest('.btn-view');
                const prescriptionId = btn.dataset.prescriptionId;
                if (prescriptionId && window.ViewPrescriptionModal) {
                    window.ViewPrescriptionModal.open(prescriptionId);
                }
            }

            if (e.target.matches('.btn-edit') || e.target.closest('.btn-edit')) {
                const btn = e.target.matches('.btn-edit') ? e.target : e.target.closest('.btn-edit');
                const prescriptionId = btn.dataset.prescriptionId;
                if (prescriptionId && window.AddPrescriptionModal) {
                    this.editPrescription(prescriptionId);
                }
            }

            if (e.target.matches('.btn-delete') || e.target.closest('.btn-delete')) {
                const btn = e.target.matches('.btn-delete') ? e.target : e.target.closest('.btn-delete');
                const prescriptionId = btn.dataset.prescriptionId;
                if (prescriptionId) {
                    this.deletePrescription(prescriptionId);
                }
            }

            if (e.target.matches('.btn-status') || e.target.closest('.btn-status')) {
                const btn = e.target.matches('.btn-status') ? e.target : e.target.closest('.btn-status');
                const prescriptionId = btn.dataset.prescriptionId;
                const status = btn.dataset.status;
                const action = btn.dataset.action;
                if (prescriptionId && status) {
                    // Use completePrescription for complete action (includes confirmation)
                    if (action === 'complete' && status === 'completed') {
                        this.completePrescription(prescriptionId);
                    } else {
                        this.updatePrescriptionStatus(prescriptionId, status);
                    }
                }
            }

            if (e.target.matches('.btn-dispense') || e.target.closest('.btn-dispense')) {
                const btn = e.target.matches('.btn-dispense') ? e.target : e.target.closest('.btn-dispense');
                const prescriptionId = btn.dataset.prescriptionId;
                if (prescriptionId) {
                    this.dispensePrescription(prescriptionId);
                }
            }

        });
    }

    async loadPrescriptions() {
        try {
            this.showLoading(true);
            
            const url = new URL(this.config.endpoints.prescriptions, window.location.origin);
            
            // Add filters to URL
            Object.keys(this.filters).forEach(key => {
                if (this.filters[key]) {
                    url.searchParams.append(key, this.filters[key]);
                }
            });

            const response = await fetch(url);
            const data = await response.json();

            if (data.status === 'success') {
                this.prescriptions = data.data || [];
                this.renderPrescriptions();
            } else {
                this.showError('Failed to load prescriptions: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error loading prescriptions:', error);
            this.showError('Failed to load prescriptions');
        } finally {
            this.showLoading(false);
        }
    }

    renderPrescriptions() {
        const tbody = document.getElementById('prescriptionsTableBody');
        
        if (!tbody) {
            return;
        }

        if (this.prescriptions.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="empty-state">
                        <i class="fas fa-prescription-bottle"></i>
                        <h3>No prescriptions found</h3>
                        <p>No prescriptions match your current filters.</p>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.prescriptions.map(prescription => this.renderPrescriptionRow(prescription)).join('');
    }

    renderPrescriptionRow(prescription) {
        // Normalize status for display - map 'dispensed' to 'Completed' for user-friendly display
        let displayStatus = prescription.status || 'queued';
        if (displayStatus.toLowerCase() === 'dispensed') {
            displayStatus = 'Completed';
        }
        const statusClass = prescription.status ? prescription.status.toLowerCase() : 'queued';
        const canEdit = this.canEditPrescription(prescription);
        const canDelete = this.canDeletePrescription(prescription);
        const canDispense = this.canDispensePrescription(prescription);
        
        // For pharmacists: always show Complete button for queued/active/ready prescriptions
        const isPharmacist = this.config.userRole === 'pharmacist';
        // Handle empty/null status - default to 'queued' if status is missing
        const rawStatus = prescription.status || '';
        const prescriptionStatus = rawStatus ? rawStatus.toLowerCase().trim() : 'queued';
        
        // Check if prescription is already completed or dispensed - don't show Complete button
        const isCompleted = ['completed', 'dispensed'].includes(prescriptionStatus);
        
        const showCompleteForPharmacist = isPharmacist && 
                                         ['queued', 'active', 'ready', 'in_progress', 'verifying'].includes(prescriptionStatus) &&
                                         !isCompleted &&
                                         !['cancelled'].includes(prescriptionStatus);
        
        // For non-pharmacists, check permissions AND ensure status is not already completed
        const canComplete = !isCompleted && this.canUpdateStatus(prescription);
        
        return `
            <tr class="fade-in">
                <td>
                    <div class="prescription-id">
                        <strong>${this.escapeHtml(prescription.prescription_id || 'N/A')}</strong>
                    </div>
                </td>
                <td>
                    <div class="patient-info">
                        <div class="patient-name">${this.escapeHtml(prescription.patient_name || 'Unknown')}</div>
                        <div class="patient-id">ID: ${this.escapeHtml(prescription.pat_id || 'N/A')}</div>
                    </div>
                </td>
                <td>
                    <div class="medication-info">
                        <div class="medication-name">${this.escapeHtml(prescription.medication || 'N/A')}</div>
                    </div>
                </td>
                <td>${this.escapeHtml(prescription.frequency || '-')}</td>
                <td>${this.formatDate(prescription.created_at)}</td>
                <td>
                    <span class="status-badge ${statusClass}">
                        ${this.escapeHtml(displayStatus)}
                    </span>
                </td>
                <td>
                    <div class="action-buttons">
                        <button type="button" class="btn btn-sm btn-primary btn-view" data-prescription-id="${prescription.id}" data-action="view" title="View Details">
                            <i class="fas fa-eye"></i> View
                        </button>
                        ${isPharmacist ? `
                            ${showCompleteForPharmacist ? `
                                <button type="button" class="btn btn-sm btn-success btn-status" data-prescription-id="${prescription.id}" data-action="complete" data-status="completed" title="Mark Completed">
                                    <i class="fas fa-check"></i> Complete
                                </button>
                            ` : ''}
                        ` : `
                            ${canEdit ? `
                                <button type="button" class="btn btn-sm btn-warning btn-edit" data-prescription-id="${prescription.id}" data-action="edit" title="Edit Prescription">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            ` : ''}
                            ${canDispense ? `
                                <button type="button" class="btn btn-sm btn-success btn-dispense" data-prescription-id="${prescription.id}" data-action="dispense" title="Dispense">
                                    <i class="fas fa-pills"></i> Dispense
                                </button>
                            ` : ''}
                            ${canComplete ? `
                                <button type="button" class="btn btn-sm btn-success btn-status" data-prescription-id="${prescription.id}" data-action="complete" data-status="completed" title="Mark Completed">
                                    <i class="fas fa-check"></i> Complete
                                </button>
                            ` : ''}
                            ${canDelete ? `
                                <button type="button" class="btn btn-sm btn-danger btn-delete" data-prescription-id="${prescription.id}" data-action="delete" title="Delete Prescription">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            ` : ''}
                        `}
                    </div>
                </td>
            </tr>
        `;
    }

    applyFilters() {
        this.filters = {
            date: document.getElementById('dateFilter')?.value || '',
            status: document.getElementById('statusFilter')?.value || '',
            search: document.getElementById('searchFilter')?.value || ''
        };

        this.loadPrescriptions();
    }

    clearFilters() {
        const dateFilter = document.getElementById('dateFilter');
        const statusFilter = document.getElementById('statusFilter');
        const searchFilter = document.getElementById('searchFilter');

        if (dateFilter) dateFilter.value = '';
        if (statusFilter) statusFilter.value = '';
        if (searchFilter) searchFilter.value = '';

        this.filters = {};
        this.loadPrescriptions();
    }

    async editPrescription(prescriptionId) {
        try {
            const response = await fetch(`${this.config.endpoints.getPrescription}/${prescriptionId}`);
            const data = await response.json();

            if (data.status === 'success' && data.data && window.AddPrescriptionModal) {
                await window.AddPrescriptionModal.loadAvailablePatients();
                await window.AddPrescriptionModal.loadAvailableMedications();
                window.AddPrescriptionModal.openForEdit(data.data);
            } else {
                this.showError('Failed to load prescription details');
            }
        } catch (error) {
            this.showError('Failed to load prescription details');
        }
    }
    
    refreshPrescriptions() {
        this.loadPrescriptions();
    }

    async deletePrescription(prescriptionId) {
        if (!confirm('Are you sure you want to delete this prescription? This action cannot be undone.')) {
            return;
        }

        try {
            const response = await fetch(this.config.endpoints.delete, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    id: prescriptionId,
                    [this.config.csrfToken]: this.config.csrfHash
                })
            });

            const data = await response.json();

            if (data.status === 'success') {
                this.showSuccess('Prescription deleted successfully');
                this.loadPrescriptions();
            } else {
                this.showError(data.message || 'Failed to delete prescription');
            }

            // Update CSRF hash
            if (data.csrf) {
                this.config.csrfHash = data.csrf.value;
            }
        } catch (error) {
            this.showError('Failed to delete prescription');
        }
    }

    async updatePrescriptionStatus(prescriptionId, status) {
        try {
            const response = await fetch(`${this.config.endpoints.updateStatus}/${prescriptionId}/status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    status: status,
                    [this.config.csrfToken]: this.config.csrfHash
                })
            });

            let data;
            try {
                data = await response.json();
            } catch (parseError) {
                // If response is not JSON, try to get text
                const text = await response.text();
                console.error('Failed to parse response as JSON:', text);
                this.showError(`Failed to update prescription status: ${response.status} ${response.statusText}`);
                return;
            }

            if (data.status === 'success') {
                this.showSuccess(`Prescription marked as ${status.toLowerCase()}`);
                this.loadPrescriptions();
            } else {
                // Show detailed error message including validation errors
                let errorMessage = data.message || 'Failed to update prescription status';
                if (data.data && data.data.errors && Array.isArray(data.data.errors) && data.data.errors.length > 0) {
                    // Join errors with HTML line breaks for better display
                    errorMessage += '<br>' + data.data.errors.join('<br>');
                }
                console.error('Prescription status update error:', data);
                this.showError(errorMessage);
            }

            // Update CSRF hash
            if (data.csrf) {
                this.config.csrfHash = data.csrf.value;
            }
        } catch (error) {
            console.error('Update prescription status error:', error);
            this.showError('Failed to update prescription status: ' + (error.message || 'Unknown error'));
        }
    }

    async dispensePrescription(prescriptionId) {
        // For pharmacists - mark as dispensed
        await this.updatePrescriptionStatus(prescriptionId, 'completed');
    }

    async completePrescription(prescriptionId) {
        if (!confirm('Are you sure you want to mark this prescription as completed?')) {
            return;
        }

        try {
            const response = await fetch(`${this.config.endpoints.updateStatus}/${prescriptionId}/status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    status: 'completed',
                    [this.config.csrfToken]: this.config.csrfHash
                })
            });

            let data;
            try {
                data = await response.json();
            } catch (parseError) {
                // If response is not JSON, try to get text
                const text = await response.text();
                console.error('Failed to parse response as JSON:', text);
                this.showError(`Failed to complete prescription: ${response.status} ${response.statusText}`);
                return;
            }

            if (data.status === 'success') {
                // Show success message with billing info if available
                const message = data.message || 'Prescription marked as completed';
                this.showSuccess(message);
                if (window.ViewPrescriptionModal) {
                    window.ViewPrescriptionModal.close();
                } else if (window.closeViewPrescriptionModal) {
                    window.closeViewPrescriptionModal();
                }
                this.loadPrescriptions();
            } else {
                // Show detailed error message including validation errors
                let errorMessage = data.message || 'Failed to complete prescription';
                if (data.data && data.data.errors && Array.isArray(data.data.errors) && data.data.errors.length > 0) {
                    // Join errors with HTML line breaks for better display
                    errorMessage += '<br>' + data.data.errors.join('<br>');
                }
                console.error('Prescription completion error:', data);
                this.showError(errorMessage);
            }

            // Update CSRF hash
            if (data.csrf) {
                this.config.csrfHash = data.csrf.value;
            }
        } catch (error) {
            console.error('Complete prescription error:', error);
            this.showError('Failed to complete prescription: ' + (error.message || 'Unknown error'));
        }
    }



    setupAutoRefresh() {
        // Refresh prescriptions every 5 minutes
        setInterval(() => {
            this.loadPrescriptions();
        }, 5 * 60 * 1000);
    }

    // Permission methods
    canEditPrescription(prescription) {
        if (this.config.userRole === 'admin' || this.config.userRole === 'it_staff') {
            return true;
        }
        
        if (this.config.userRole === 'doctor') {
            // Doctors can edit their own prescriptions
            return prescription.doctor_id === this.getCurrentStaffId();
        }
        
        // Pharmacists cannot edit prescriptions, only view and complete
        if (this.config.userRole === 'pharmacist') {
            return false;
        }
        
        return false;
    }

    canDeletePrescription(prescription) {
        if (this.config.userRole === 'admin') {
            return true;
        }
        
        if (this.config.userRole === 'doctor') {
            // Doctors can delete their own prescriptions if not yet dispensed
            return prescription.doctor_id === this.getCurrentStaffId() && 
                   ['active', 'pending'].includes(prescription.status);
        }
        
        return false;
    }

    canDispensePrescription(prescription) {
        return this.config.userRole === 'pharmacist' && 
               ['active', 'ready', 'queued', 'in_progress'].includes(prescription.status?.toLowerCase());
    }

    canUpdateStatus(prescription) {
        // First check if prescription is already completed or dispensed - cannot update status
        const rawStatus = prescription.status || '';
        const status = rawStatus ? rawStatus.toLowerCase().trim() : 'queued';
        if (['completed', 'dispensed'].includes(status)) {
            return false;
        }
        
        if (this.config.userRole === 'admin' || this.config.userRole === 'it_staff') {
            return true;
        }
        
        if (this.config.userRole === 'doctor') {
            return prescription.doctor_id === this.getCurrentStaffId() && 
                   prescription.status === 'active';
        }
        
        if (this.config.userRole === 'pharmacist') {
            // Pharmacists can update status for queued, active, ready, and in_progress prescriptions
            // Handle empty/null status - default to 'queued' if status is missing
            const allowedStatuses = ['active', 'ready', 'queued', 'in_progress', 'verifying'];
            const blockedStatuses = ['cancelled'];
            
            // Always allow completion for queued status (most common case) or empty status (defaults to queued)
            if (status === 'queued' || !rawStatus) {
                return true;
            }
            
            const canComplete = allowedStatuses.includes(status) && !blockedStatuses.includes(status);
            
            return canComplete;
        }
        
        return false;
    }

    getCurrentStaffId() {
        // This would typically come from session data or user context
        return window.currentStaffId || null;
    }


    // Utility methods
    showLoading(show, element = null) {
        if (element) {
            element.classList.toggle('loading', show);
        } else {
            const tbody = document.getElementById('prescriptionsTableBody');
            if (tbody && show) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="loading-row">
                            <i class="fas fa-spinner fa-spin"></i> Loading prescriptions...
                        </td>
                    </tr>
                `;
            }
        }
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    showNotification(message, type = 'info') {
        const container = document.getElementById('prescriptionsNotification');
        const iconEl = document.getElementById('prescriptionsNotificationIcon');
        const textEl = document.getElementById('prescriptionsNotificationText');

        // Prefer shared top bar when available
        if (container && iconEl && textEl) {
            const isError = type === 'error';
            const isSuccess = type === 'success';

            container.style.border = isError ? '1px solid #fecaca' : '1px solid #bbf7d0';
            container.style.background = isError ? '#fee2e2' : '#ecfdf5';
            container.style.color = isError ? '#991b1b' : '#166534';

            const iconClass = isError
                ? 'fa-exclamation-triangle'
                : (isSuccess ? 'fa-check-circle' : 'fa-info-circle');
            iconEl.className = 'fas ' + iconClass;

            // Check if message contains HTML (like <br> tags)
            if (message && message.includes('<br>')) {
                // Escape HTML but preserve <br> tags for line breaks
                const escaped = this.escapeHtml(message || '');
                textEl.innerHTML = escaped.replace(/&lt;br&gt;/gi, '<br>');
            } else {
                textEl.textContent = this.escapeHtml(message || '');
            }
            container.style.display = 'flex';

            setTimeout(() => {
                if (container.style.display !== 'none') {
                    container.style.display = 'none';
                }
            }, 4000);
            return;
        }

        // Fallback: inline alert at top of content
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} fade-in`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            ${this.escapeHtml(message)}
        `;

        const content = document.querySelector('.content');
        if (content) {
            content.insertBefore(notification, content.firstChild);
        }

        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    showValidationErrors(errors) {
        Object.keys(errors).forEach(field => {
            const input = document.querySelector(`[name="${field}"]`);
            if (input) {
                input.classList.add('error');
                
                // Add error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.textContent = errors[field];
                input.parentNode.appendChild(errorDiv);
            }
        });
    }

    clearValidationErrors() {
        document.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
        document.querySelectorAll('.error-message').forEach(el => el.remove());
    }

    formatDate(dateString) {
        if (!dateString) return '-';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        } catch (e) {
            return dateString;
        }
    }

    escapeHtml(text) {
        if (typeof text !== 'string') return text;
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Helper for shared prescriptions notification bar
function dismissPrescriptionsNotification() {
    const container = document.getElementById('prescriptionsNotification');
    if (container) {
        container.style.display = 'none';
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.prescriptionManager = new PrescriptionManager();
});

// Global functions for backward compatibility
window.viewPrescription = (id) => window.ViewPrescriptionModal?.open(id);
window.editPrescription = (id) => window.prescriptionManager?.editPrescription(id);
window.deletePrescription = (id) => window.prescriptionManager?.deletePrescription(id);
window.updatePrescriptionStatus = (id, status) => window.prescriptionManager?.updatePrescriptionStatus(id, status);
