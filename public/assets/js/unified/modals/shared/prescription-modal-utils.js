/**
 * Shared Prescription Modal Utilities
 */

window.PrescriptionModalUtils = {
    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }
    },
    
    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }
    },
    
    setupModalCloseHandlers(modal, onClose) {
        if (!modal) return;
        
        const closeBtn = modal.querySelector('[id*="close"]');
        const cancelBtn = modal.querySelector('[id*="cancel"]');
        
        if (closeBtn) closeBtn.addEventListener('click', onClose);
        if (cancelBtn) cancelBtn.addEventListener('click', onClose);
        
        modal.addEventListener('click', (e) => {
            if (e.target === modal) onClose();
        });
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('active')) {
                onClose();
            }
        });
    },
    
    collectFormData(form) {
        const formData = new FormData(form);
        const data = {};
        
        // Handle regular fields
        for (const [key, value] of formData.entries()) {
            if (key.includes('[]')) {
                const baseKey = key.replace('[]', '');
                if (!data[baseKey]) data[baseKey] = [];
                data[baseKey].push(value);
            } else {
                data[key] = value;
            }
        }
        
        // Handle medicine items array
        const items = [];
        const medicationIds = formData.getAll('medication_resource_id[]');
        const medicationNames = formData.getAll('medication_name[]');
        const frequencies = formData.getAll('frequency[]');
        const durations = formData.getAll('duration[]');
        const quantities = formData.getAll('quantity[]');
        
        for (let i = 0; i < medicationIds.length; i++) {
            const medName = medicationNames[i] || '';
            const qty = parseInt(quantities[i] || 0);
            
            if (medName && qty > 0) {
                items.push({
                    medication_resource_id: medicationIds[i] || null,
                    medication_name: medName,
                    frequency: frequencies[i] || null,
                    duration: durations[i] || null,
                    quantity: qty
                });
            }
        }
        
        if (items.length > 0) {
            data.items = items;
        }
        
        return data;
    },
    
    displayErrors(errors, prefix = '') {
        Object.keys(errors).forEach(key => {
            const errorEl = document.getElementById(`err_${prefix}${key}`);
            if (errorEl) {
                errorEl.textContent = errors[key];
            }
        });
    },
    
    clearErrors(form, prefix = '') {
        const errorElements = form.querySelectorAll(`[id^="err_${prefix}"]`);
        errorElements.forEach(el => el.textContent = '');
    },
    
    populateForm(prescription, form) {
        if (!prescription || !form) return;
        
        const patientSelect = form.querySelector('[name="patient_id"]');
        const dateInput = form.querySelector('[name="date_issued"]');
        const statusSelect = form.querySelector('[name="status"]');
        const notesTextarea = form.querySelector('[name="notes"]');
        
        if (patientSelect) patientSelect.value = prescription.patient_id || '';
        if (dateInput) dateInput.value = prescription.created_at ? prescription.created_at.split('T')[0] : '';
        if (statusSelect) statusSelect.value = prescription.status || 'active';
        if (notesTextarea) notesTextarea.value = prescription.notes || '';
        
        // Populate medicine items if available
        if (prescription.items && Array.isArray(prescription.items) && prescription.items.length > 0) {
            // This will be handled by the modal-specific code
        }
    }
};

