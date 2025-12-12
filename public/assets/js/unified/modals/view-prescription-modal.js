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
    },
    
    getConfig() {
        const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
        return { baseUrl: baseUrl.replace(/\/$/, ''), endpoints: { getPrescription: `${baseUrl}prescriptions` } };
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

