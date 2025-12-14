/**
 * Shared utilities for resource modals
 */
class ResourceModalUtils {
    constructor(baseUrl) {
        this.baseUrl = baseUrl;
    }

    /**
     * Setup modal close handlers
     */
    setupModalCloseHandlers(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        // Close button handlers
        modal.querySelectorAll(`[data-modal-close="${modalId}"]`).forEach(btn => {
            btn.addEventListener('click', () => this.close(modalId));
        });

        // Click outside to close
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.close(modalId);
            }
        });

        // Escape key to close
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal && !modal.getAttribute('aria-hidden')) {
                this.close(modalId);
            }
        });
    }

    /**
     * Open modal
     */
    open(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        modal.setAttribute('aria-hidden', 'false');
        modal.style.display = 'block';
    }

    /**
     * Close modal
     */
    close(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        modal.setAttribute('aria-hidden', 'true');
        modal.style.display = 'none';
    }

    /**
     * Toggle medication fields based on category
     */
    toggleMedicationFields(categorySelectId, fieldsContainerId) {
        const categorySelect = document.getElementById(categorySelectId);
        const fieldsContainer = document.getElementById(fieldsContainerId);
        
        if (!categorySelect || !fieldsContainer) return;
        
        const toggleFields = () => {
            const isMedication = categorySelect.value === 'Medications';
            fieldsContainer.style.display = isMedication ? 'flex' : 'none';
            
            const batchNumber = fieldsContainer.querySelector('[name="batch_number"]');
            const expiryDate = fieldsContainer.querySelector('[name="expiry_date"]');
            
            if (batchNumber) {
                batchNumber.required = isMedication;
                if (!isMedication) batchNumber.value = '';
            }
            if (expiryDate) {
                expiryDate.required = isMedication;
                if (!isMedication) expiryDate.value = '';
            }
            
            // Toggle price fields
            const priceFieldsId = fieldsContainerId === 'medicationFields' ? 'medicationPriceFields' : 'editMedicationPriceFields';
            const priceFields = document.getElementById(priceFieldsId);
            if (priceFields) {
                priceFields.style.display = isMedication ? 'flex' : 'none';
                const priceInput = priceFields.querySelector('[name="price"]');
                if (priceInput) {
                    priceInput.required = isMedication;
                    if (!isMedication) priceInput.value = '';
                }
            }
        };
        
        categorySelect.addEventListener('change', toggleFields);
        toggleFields(); // Initial check
    }

    /**
     * Display errors
     */
    displayErrors(errors, prefix = 'err_') {
        if (!errors || Object.keys(errors).length === 0) return;

        Object.keys(errors).forEach(field => {
            const errorEl = document.getElementById(prefix + field);
            if (errorEl) {
                errorEl.textContent = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
            }
        });
    }

    /**
     * Clear errors
     */
    clearErrors(prefix = 'err_') {
        document.querySelectorAll(`[id^="${prefix}"]`).forEach(el => {
            el.textContent = '';
        });
    }

    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        const container = document.getElementById('resourcesNotification');
        const iconEl = document.getElementById('resourcesNotificationIcon');
        const textEl = document.getElementById('resourcesNotificationText');

        if (!container || !iconEl || !textEl) {
            if (typeof showUniversalNotification === 'function') {
                showUniversalNotification(message, 'error');
            }
            return;
        }

        const isError = type === 'error';
        const isSuccess = type === 'success';

        container.style.border = isError ? '1px solid #fecaca' : '1px solid #bbf7d0';
        container.style.background = isError ? '#fee2e2' : '#ecfdf5';
        container.style.color = isError ? '#991b1b' : '#166534';

        const iconClass = isError ? 'fa-exclamation-triangle' : (isSuccess ? 'fa-check-circle' : 'fa-info-circle');
        iconEl.className = 'fas ' + iconClass;

        textEl.textContent = message || '';
        container.style.display = 'flex';

        setTimeout(() => {
            if (container && container.style.display !== 'none') {
                container.style.display = 'none';
            }
        }, 4000);
    }
}

