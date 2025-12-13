/**
 * View Prescription Modal Controller
 */

window.ViewPrescriptionModal = {
    modal: null,
    config: null,
    currentPrescription: null,
    
    init() {
        this.modal = document.getElementById('viewPrescriptionModal');
        this.config = this.getConfig();
        
        PrescriptionModalUtils.setupModalCloseHandlers(this.modal, () => this.close());
        
        const editBtn = document.getElementById('editFromViewBtn');
        if (editBtn) {
            editBtn.addEventListener('click', () => this.editFromView());
        }
        
        const completeBtn = document.getElementById('completeFromViewBtn');
        if (completeBtn) {
            completeBtn.addEventListener('click', () => this.completeFromView());
        }
    },
    
    getConfig() {
        const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
        const userRole = document.querySelector('meta[name="user-role"]')?.content || '';
        return { 
            baseUrl: baseUrl.replace(/\/$/, ''), 
            userRole: userRole,
            endpoints: { getPrescription: `${baseUrl}prescriptions` } 
        };
    },
    
    async open(prescriptionId) {
        if (!prescriptionId) {
            this.showNotification('Prescription ID is required', 'error');
            return;
        }
        
        if (this.modal) {
            PrescriptionModalUtils.openModal('viewPrescriptionModal');
            
            try {
                await this.loadPrescriptionDetails(prescriptionId);
            } catch (error) {
                console.error('Error loading prescription details:', error);
                this.showNotification('Failed to load prescription details', 'error');
                this.close();
            }
        }
    },
    
    close() {
        if (this.modal) {
            PrescriptionModalUtils.closeModal('viewPrescriptionModal');
            this.currentPrescription = null;
            this.clearForm();
        }
    },
    
    clearForm() {
        const elements = ['viewPrescriptionId', 'viewPrescriptionDate', 'viewPrescriptionStatus', 'viewDoctorName', 'viewPatientName', 'viewPatientId', 'viewPrescriptionNotes'];
        elements.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = '-';
        });
        
        const body = document.getElementById('viewMedicinesBody');
        if (body) {
            body.innerHTML = '<tr><td colspan="5">No medicines found</td></tr>';
        }
    },
    
    async loadPrescriptionDetails(prescriptionId) {
        const response = await fetch(`${this.config.endpoints.getPrescription}/${prescriptionId}`);
        const result = await response.json();
        
        let prescription = result;
        if (result && typeof result === 'object' && 'status' in result) {
            if (result.status !== 'success') {
                throw new Error(result.message || 'Failed to load prescription details');
            }
            prescription = result.data || result.prescription || {};
        }
        
        if (!prescription) {
            throw new Error('Empty prescription response');
        }
        
        this.currentPrescription = prescription;
        this.populateForm(prescription);
    },
    
    populateForm(prescription) {
        const setElementText = (id, value) => {
            const element = document.getElementById(id);
            if (element) element.textContent = value || 'N/A';
        };
        
        setElementText('viewPrescriptionId', prescription.prescription_id || prescription.rx_number);
        setElementText('viewPrescriptionDate', this.formatDate(prescription.created_at));
        setElementText('viewDoctorName', prescription.prescriber || 'Unknown');
        setElementText('viewPatientName', prescription.patient_name || 'Unknown');
        setElementText('viewPatientId', prescription.pat_id || prescription.patient_id);
        setElementText('viewPrescriptionNotes', prescription.notes || 'No notes available');
        
        const statusElement = document.getElementById('viewPrescriptionStatus');
        if (statusElement) {
            statusElement.textContent = prescription.status || 'Queued';
            statusElement.className = `status-badge ${(prescription.status || 'queued').toLowerCase()}`;
        }
        
        // Show/hide Complete button for pharmacists based on prescription status
        this.updateCompleteButtonVisibility(prescription);
        
        const body = document.getElementById('viewMedicinesBody');
        if (body) {
            const items = Array.isArray(prescription.items) && prescription.items.length
                ? prescription.items
                : [{
                    medication_name: prescription.medication,
                    dosage: prescription.dosage,
                    frequency: prescription.frequency,
                    duration: prescription.duration,
                    quantity: prescription.quantity
                }];
            
            body.innerHTML = items.map(item => `
                <tr>
                    <td>${this.escapeHtml(item.medication_name || prescription.medication || 'N/A')}</td>
                    <td>${this.escapeHtml(item.frequency || '')}</td>
                    <td>${this.escapeHtml(item.duration || '')}</td>
                    <td>${this.escapeHtml(String(item.quantity || ''))}</td>
                </tr>
            `).join('');
        }
    },
    
    editFromView() {
        if (this.currentPrescription && window.AddPrescriptionModal) {
            this.close();
            // Open edit modal with prescription data
            window.AddPrescriptionModal.openForEdit(this.currentPrescription);
        } else {
            this.showNotification('Failed to load prescription for editing', 'error');
        }
    },
    
    updateCompleteButtonVisibility(prescription) {
        const completeBtn = document.getElementById('completeFromViewBtn');
        if (!completeBtn) return;
        
        // Get user role from config or meta tag
        const userRole = this.config.userRole || document.querySelector('meta[name="user-role"]')?.content || '';
        const status = (prescription.status || '').toLowerCase().trim();
        
        // Never show Complete button if prescription is already completed or dispensed
        if (['completed', 'dispensed'].includes(status)) {
            completeBtn.style.display = 'none';
            return;
        }
        
        // Show button for pharmacists when prescription can be completed
        if (userRole === 'pharmacist') {
            const canComplete = ['active', 'ready', 'queued', 'in_progress', 'verifying'].includes(status) && 
                               !['cancelled'].includes(status);
            completeBtn.style.display = canComplete ? 'inline-block' : 'none';
        } else {
            completeBtn.style.display = 'none';
        }
    },
    
    async completeFromView() {
        if (!this.currentPrescription || !this.currentPrescription.id) {
            this.showNotification('Prescription ID is required', 'error');
            return;
        }
        
        if (!confirm('Are you sure you want to mark this prescription as completed?')) {
            return;
        }
        
        try {
            const baseUrl = this.config.baseUrl || '';
            const csrfTokenName = document.querySelector('meta[name="csrf-token-name"]')?.content || 'csrf_token';
            const csrfHash = document.querySelector('meta[name="csrf-token"]')?.content || '';
            
            const response = await fetch(`${baseUrl}/prescriptions/${this.currentPrescription.id}/status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    status: 'completed',
                    [csrfTokenName]: csrfHash
                })
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                const message = data.message || 'Prescription marked as completed';
                this.showNotification(message, 'success');
                this.close();
                
                // Refresh prescriptions list if available
                if (window.prescriptionManager && typeof window.prescriptionManager.loadPrescriptions === 'function') {
                    window.prescriptionManager.loadPrescriptions();
                } else {
                    // Fallback: reload page after a short delay
                    setTimeout(() => location.reload(), 1000);
                }
            } else {
                this.showNotification(data.message || 'Failed to complete prescription', 'error');
            }
            
            // Update CSRF hash if provided
            if (data.csrf && data.csrf.value) {
                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                if (csrfMeta) {
                    csrfMeta.setAttribute('content', data.csrf.value);
                }
            }
        } catch (error) {
            console.error('Complete prescription error:', error);
            this.showNotification('Failed to complete prescription', 'error');
        }
    },
    
    formatDate(dateStr) {
        if (!dateStr) return 'N/A';
        const date = new Date(dateStr);
        return isNaN(date.getTime()) ? dateStr : date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    },
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },
    
    showNotification(message, type) {
        if (window.prescriptionManager) {
            window.prescriptionManager.showNotification(message, type);
        } else if (typeof showPrescriptionsNotification === 'function') {
            showPrescriptionsNotification(message, type);
        } else {
            alert(message);
        }
    }
};

// Global functions for backward compatibility
window.openViewPrescriptionModal = (id) => window.ViewPrescriptionModal?.open(id);
window.closeViewPrescriptionModal = () => window.ViewPrescriptionModal?.close();
window.viewPrescription = (id) => window.ViewPrescriptionModal?.open(id);

