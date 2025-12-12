/**
 * Shared utilities for shift modals
 */
window.ShiftModalUtils = {
    /**
     * Setup common modal behavior (close on escape, background click)
     */
    setupModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal && !modal.getAttribute('aria-hidden')) {
                this.closeModal(modalId);
            }
        });

        // Close on background click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.closeModal(modalId);
            }
        });
    },

    /**
     * Open modal
     */
    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }
    },

    /**
     * Close modal
     */
    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }
    },

    /**
     * Clear form errors
     */
    clearErrors(form) {
        const errorElements = form?.querySelectorAll('.error, .error-message');
        errorElements?.forEach(el => {
            el.classList.remove('error');
            if (el.classList.contains('error-message')) el.remove();
        });
    },

    /**
     * Display form errors
     */
    displayErrors(errors) {
        for (const [field, message] of Object.entries(errors)) {
            const input = document.querySelector(`[name="${field}"]`);
            if (input) {
                input.classList.add('error');
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.textContent = Array.isArray(message) ? message[0] : message;
                input.parentNode.appendChild(errorDiv);
            }
        }
    },

    /**
     * Collect form data (handles weekdays[] array)
     */
    collectFormData(form) {
        const formData = new FormData(form);
        const data = {};
        formData.forEach((value, key) => {
            if (key === 'weekdays[]') {
                if (!data.weekdays) data.weekdays = [];
                data.weekdays.push(value);
            } else {
                data[key] = value;
            }
        });
        return data;
    },

    /**
     * Normalize status value
     */
    normalizeStatus(status) {
        const raw = (status || 'Scheduled').toString().toLowerCase();
        if (raw === 'active' || raw === 'scheduled') return 'Scheduled';
        if (raw === 'completed' || raw === 'done' || raw === 'finished') return 'Completed';
        if (raw === 'cancelled' || raw === 'canceled' || raw === 'inactive') return 'Cancelled';
        return raw.charAt(0).toUpperCase() + raw.slice(1);
    },

    /**
     * Format weekday number to name
     */
    formatWeekday(weekday) {
        const labels = {1: 'Monday', 2: 'Tuesday', 3: 'Wednesday', 4: 'Thursday', 5: 'Friday', 6: 'Saturday', 7: 'Sunday'};
        return labels[parseInt(weekday, 10)] || 'N/A';
    },
    
    /**
     * Setup modal close handlers
     */
    setupModalCloseHandlers(modal, onClose) {
        if (!modal) return;
        
        const closeButtons = modal.querySelectorAll('[id*="close"], [id*="cancel"]');
        closeButtons.forEach(btn => {
            btn.addEventListener('click', () => onClose());
        });
    }
};

