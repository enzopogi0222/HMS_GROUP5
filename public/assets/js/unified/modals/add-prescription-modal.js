/**
 * Add Prescription Modal Controller
 */

window.AddPrescriptionModal = {
    modal: null,
    form: null,
    config: null,
    medicationOptionsCache: null,
    
    init() {
        this.modal = document.getElementById('addPrescriptionModal');
        this.form = document.getElementById('addPrescriptionForm');
        this.config = this.getConfig();
        
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.handleSubmit(e));
            
            const addBtn = document.getElementById('addMedicineRowBtn');
            if (addBtn) {
                addBtn.addEventListener('click', () => this.addMedicineRow());
            }
            
            const medicinesBody = document.getElementById('addMedicinesTableBody');
            if (medicinesBody) {
                medicinesBody.addEventListener('click', (e) => {
                    const removeBtn = e.target.closest('.remove-medicine-row');
                    if (removeBtn) {
                        const row = removeBtn.closest('.medicine-row');
                        if (row && medicinesBody.children.length > 1) {
                            row.remove();
                        }
                    }
                });
            }
        }
        
        PrescriptionModalUtils.setupModalCloseHandlers(this.modal, () => this.close());
        
        const createBtn = document.getElementById('createPrescriptionBtn');
        if (createBtn) {
            createBtn.addEventListener('click', () => this.open());
        }
    },
    
    getConfig() {
        const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const userRole = document.querySelector('meta[name="user-role"]')?.content || '';
        return { 
            baseUrl: baseUrl.replace(/\/$/, ''), 
            csrfToken, 
            userRole,
            endpoints: { 
                create: `${baseUrl}prescriptions/create`,
                availableDoctors: `${baseUrl}prescriptions/available-doctors`
            } 
        };
    },
    
    async open() {
        if (this.modal) {
            this.resetForm();
            PrescriptionModalUtils.openModal('addPrescriptionModal');
            await this.loadAvailablePatients();
            await this.loadAvailableMedications();
            // Load doctors for admin and nurses
            if (this.config.userRole === 'admin' || this.config.userRole === 'nurse') {
                await this.loadAvailableDoctors();
            }
        }
    },
    
    async openForEdit(prescription) {
        if (this.modal && prescription) {
            this.resetForm();
            PrescriptionModalUtils.openModal('addPrescriptionModal');
            await this.loadAvailablePatients();
            await this.loadAvailableMedications();
            // Load doctors for admin and nurses
            if (this.config.userRole === 'admin' || this.config.userRole === 'nurse') {
                await this.loadAvailableDoctors();
            }
            this.populateForm(prescription);
        }
    },
    
    populateForm(prescription) {
        if (!prescription) return;
        
        const patientSelect = document.getElementById('add_patientSelect');
        const dateInput = document.getElementById('add_prescriptionDate');
        const statusSelect = document.getElementById('add_prescriptionStatus');
        const notesTextarea = document.getElementById('add_prescriptionNotes');
        const doctorSelect = document.getElementById('add_doctorSelect');
        
        if (patientSelect) patientSelect.value = prescription.patient_id || prescription.pat_id || '';
        if (dateInput) dateInput.value = prescription.created_at ? prescription.created_at.split('T')[0] : '';
        if (statusSelect) statusSelect.value = prescription.status || 'active';
        if (notesTextarea) notesTextarea.value = prescription.notes || '';
        if (doctorSelect) doctorSelect.value = prescription.doctor_id || prescription.prescriber_id || '';
        
        const medicinesBody = document.getElementById('addMedicinesTableBody');
        if (medicinesBody) {
            const rows = Array.from(medicinesBody.querySelectorAll('.medicine-row'));
            rows.slice(1).forEach(r => r.remove());
            
            const items = Array.isArray(prescription.items) && prescription.items.length
                ? prescription.items
                : [{
                    medication_name: prescription.medication,
                    frequency: prescription.frequency,
                    duration: prescription.duration,
                    quantity: prescription.quantity
                }];
            
            const firstRow = medicinesBody.querySelector('.medicine-row');
            if (firstRow && items[0]) {
                this.fillMedicineRow(firstRow, items[0]);
            }
            
            for (let i = 1; i < items.length; i++) {
                this.addMedicineRow(items[i]);
            }
        }
    },
    
    fillMedicineRow(row, item) {
        if (!row || !item) return;
        
        const nameInput = row.querySelector('.medicine-name-hidden');
        const freq = row.querySelector('select[name="frequency[]"]');
        const duration = row.querySelector('select[name="duration[]"]');
        const quantity = row.querySelector('input[name="quantity[]"]');
        const select = row.querySelector('.medicine-medication-select');
        
        if (nameInput) nameInput.value = item.medication_name || '';
        if (freq) freq.value = item.frequency || '';
        if (duration) duration.value = item.duration || '';
        if (quantity) quantity.value = item.quantity || '';
        
        if (select && Array.isArray(this.medicationOptionsCache)) {
            if (item.medication_resource_id) {
                select.value = item.medication_resource_id;
            } else if (item.medication_name) {
                const target = item.medication_name.toLowerCase();
                for (let i = 0; i < select.options.length; i++) {
                    const opt = select.options[i];
                    const name = (opt.dataset?.name || opt.textContent || '').toLowerCase();
                    if (name.startsWith(target)) {
                        select.selectedIndex = i;
                        break;
                    }
                }
            }
        }
    },
    
    close() {
        if (this.modal) {
            PrescriptionModalUtils.closeModal('addPrescriptionModal');
            this.resetForm();
        }
    },
    
    resetForm() {
        if (this.form) {
            this.form.reset();
            const dateInput = document.getElementById('add_prescriptionDate');
            if (dateInput) dateInput.value = new Date().toISOString().split('T')[0];
            
            const medicinesBody = document.getElementById('addMedicinesTableBody');
            if (medicinesBody) {
                const rows = Array.from(medicinesBody.querySelectorAll('.medicine-row'));
                rows.slice(1).forEach(r => r.remove());
                const firstRow = medicinesBody.querySelector('.medicine-row');
                if (firstRow) {
                    firstRow.querySelectorAll('input, select').forEach(input => input.value = '');
                }
            }
            
            PrescriptionModalUtils.clearErrors(this.form, 'add_');
        }
    },
    
    async loadAvailablePatients() {
        const patientSelect = document.getElementById('add_patientSelect');
        if (!patientSelect) return;
        
        try {
            patientSelect.innerHTML = '<option value="">Loading patients...</option>';
            patientSelect.disabled = true;
            
            const response = await fetch(`${this.config.baseUrl}/prescriptions/available-patients`);
            const data = await response.json();
            
            if (data.status === 'success' && Array.isArray(data.data)) {
                patientSelect.innerHTML = '<option value="">Select Patient...</option>';
                data.data.forEach(patient => {
                    const option = document.createElement('option');
                    option.value = patient.patient_id;
                    const fullName = `${patient.first_name} ${patient.last_name} (ID: ${patient.patient_id})`;
                    // Display patient type if available (handle both null/undefined and empty string)
                    let patientType = '';
                    if (patient.patient_type && patient.patient_type.trim() !== '') {
                        const type = patient.patient_type.trim();
                        patientType = ` - ${type.charAt(0).toUpperCase() + type.slice(1)}`;
                    }
                    option.textContent = `${fullName}${patientType}`;
                    patientSelect.appendChild(option);
                });
            } else {
                patientSelect.innerHTML = '<option value="">No patients available</option>';
            }
        } catch (error) {
            console.error('Error loading patients:', error);
            patientSelect.innerHTML = '<option value="">Error loading patients</option>';
        } finally {
            patientSelect.disabled = false;
        }
    },
    
    async loadAvailableMedications() {
        if (this.medicationOptionsCache !== null) {
            this.populateAllMedicationSelects();
            return;
        }
        
        try {
            const response = await fetch(`${this.config.baseUrl}/prescriptions/available-medications`);
            const data = await response.json();
            
            if (data.status === 'success' && Array.isArray(data.data)) {
                this.medicationOptionsCache = data.data;
            } else {
                this.medicationOptionsCache = [];
            }
        } catch (error) {
            console.error('Error loading medications:', error);
            this.medicationOptionsCache = [];
        }
        
        this.populateAllMedicationSelects();
    },
    
    async loadAvailableDoctors() {
        const doctorSelect = document.getElementById('add_doctorSelect');
        if (!doctorSelect) return;
        
        try {
            doctorSelect.innerHTML = '<option value="">Loading doctors...</option>';
            doctorSelect.disabled = true;
            
            const response = await fetch(`${this.config.baseUrl}/prescriptions/available-doctors`);
            const data = await response.json();
            
            if (data.status === 'success' && Array.isArray(data.data)) {
                const defaultOption = this.config.userRole === 'nurse' 
                    ? '<option value="">Select Doctor...</option>'
                    : '<option value="">Select Doctor (Optional)...</option>';
                doctorSelect.innerHTML = defaultOption;
                
                data.data.forEach(doctor => {
                    const option = document.createElement('option');
                    option.value = doctor.staff_id;
                    const name = `Dr. ${doctor.first_name} ${doctor.last_name}`;
                    const specialization = doctor.specialization ? ` - ${doctor.specialization}` : '';
                    option.textContent = `${name}${specialization}`;
                    doctorSelect.appendChild(option);
                });
            } else {
                doctorSelect.innerHTML = '<option value="">No doctors available</option>';
            }
        } catch (error) {
            console.error('Error loading doctors:', error);
            doctorSelect.innerHTML = '<option value="">Error loading doctors</option>';
        } finally {
            doctorSelect.disabled = false;
        }
    },
    
    populateAllMedicationSelects() {
        const selects = document.querySelectorAll('#addMedicinesTableBody .medicine-medication-select');
        selects.forEach(select => this.populateMedicationSelect(select));
    },
    
    populateMedicationSelect(select) {
        if (!select) return;
        select.innerHTML = '<option value="">Select medication</option>';
        
        if (!Array.isArray(this.medicationOptionsCache) || !this.medicationOptionsCache.length) {
            select.innerHTML = '<option value="">No medications available</option>';
            return;
        }
        
        this.medicationOptionsCache.forEach(med => {
            const opt = document.createElement('option');
            opt.value = med.id;
            opt.textContent = `${med.equipment_name} (Stock: ${med.quantity})`;
            opt.dataset.name = med.equipment_name;
            select.appendChild(opt);
        });
        
        select.addEventListener('change', () => {
            const nameInput = select.closest('.medicine-row').querySelector('.medicine-name-hidden');
            if (nameInput && select.selectedIndex > 0) {
                nameInput.value = select.options[select.selectedIndex].dataset.name || '';
            }
        });
    },
    
    addMedicineRow(item = null) {
        const body = document.getElementById('addMedicinesTableBody');
        if (!body) return;
        
        const firstRow = body.querySelector('.medicine-row');
        if (!firstRow) return;
        
        const row = firstRow.cloneNode(true);
        row.querySelectorAll('input, select').forEach(input => {
            input.value = item ? (input.name.includes('medication') ? '' : (item[input.name.replace('[]', '')] || '')) : '';
        });
        
        body.appendChild(row);
        const select = row.querySelector('.medicine-medication-select');
        this.populateMedicationSelect(select);
        
        if (item) {
            const nameInput = row.querySelector('.medicine-name-hidden');
            if (nameInput) nameInput.value = item.medication_name || '';
        }
    },
    
    async handleSubmit(e) {
        e.preventDefault();
        const data = PrescriptionModalUtils.collectFormData(this.form);
        
        if (!data.items || data.items.length === 0) {
            this.showNotification('Please add at least one medicine with a name and quantity.', 'error');
            return;
        }
        
        try {
            this.showLoading(true);
            const response = await fetch(this.config.endpoints.create, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-Token': this.config.csrfToken},
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            const success = result.status === 'success';
            
            if (success) {
                this.showNotification(result.message || 'Prescription created successfully', 'success');
                this.close();
                if (window.prescriptionManager) {
                    window.prescriptionManager.refreshPrescriptions();
                } else {
                    setTimeout(() => location.reload(), 800);
                }
            } else {
                this.showNotification(result.message || 'Failed to save prescription', 'error');
                if (result.errors) {
                    PrescriptionModalUtils.displayErrors(result.errors, 'add_');
                }
            }
        } catch (error) {
            console.error('Error saving prescription:', error);
            this.showNotification('Failed to save prescription. Please try again.', 'error');
        } finally {
            this.showLoading(false);
        }
    },
    
    showLoading(show) {
        const submitBtn = document.getElementById('saveAddPrescriptionBtn');
        if (submitBtn) {
            submitBtn.disabled = show;
            submitBtn.innerHTML = show 
                ? '<i class="fas fa-spinner fa-spin"></i> Saving...' 
                : '<i class="fas fa-save"></i> Save Prescription';
        }
    },
    
    showNotification(message, type) {
        if (window.prescriptionManager) {
            window.prescriptionManager.showNotification(message, type);
        } else if (typeof showPrescriptionsNotification === 'function') {
            showPrescriptionsNotification(message, type);
        } else if (typeof showUniversalNotification === 'function') {
            showUniversalNotification(message, type);
        } else {
            // Last resort fallback
            const notif = document.createElement('div');
            notif.style.cssText = 'position:fixed;top:20px;right:20px;padding:1rem;background:#fee2e2;color:#991b1b;border-radius:8px;z-index:10050;max-width:400px;';
            notif.textContent = message;
            document.body.appendChild(notif);
            setTimeout(() => notif.remove(), 4000);
        }
    }
};

// Global functions for backward compatibility
window.openAddPrescriptionModal = () => window.AddPrescriptionModal?.open();
window.closeAddPrescriptionModal = () => window.AddPrescriptionModal?.close();

