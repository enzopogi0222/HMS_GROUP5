/**
 * Shared utilities for appointment modals
 */
window.AppointmentModalUtils = {
    /**
     * Setup common modal behavior (close on escape, background click)
     */
    setupModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal && !modal.getAttribute('aria-hidden')) {
                this.closeModal(modalId);
            }
        });

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
     * Setup modal close handlers
     */
    setupModalCloseHandlers(modal, onClose) {
        if (!modal) return;
        const closeButtons = modal.querySelectorAll('[id*="close"], [id*="cancel"]');
        closeButtons.forEach(btn => btn.addEventListener('click', () => onClose()));
    },

    /**
     * Clear form errors
     */
    clearErrors(form, prefix = '') {
        const errorElements = form?.querySelectorAll('.error, .error-message, [id^="err_' + prefix + '"]');
        errorElements?.forEach(el => {
            el.classList.remove('error');
            if (el.classList.contains('error-message') || el.id.startsWith('err_')) {
                el.textContent = '';
            }
        });
    },

    /**
     * Display form errors
     */
    displayErrors(errors, prefix = '') {
        for (const [field, message] of Object.entries(errors)) {
            const input = document.querySelector(`[name="${field}"]`);
            const errorEl = document.getElementById(`err_${prefix}${field}`);
            if (input) input.classList.add('error');
            if (errorEl) errorEl.textContent = Array.isArray(message) ? message[0] : message;
        }
    },

    /**
     * Collect form data
     */
    collectFormData(form) {
        const formData = new FormData(form);
        return Object.fromEntries(formData.entries());
    },

    /**
     * Normalize status value
     */
    normalizeStatus(status) {
        const raw = (status || 'scheduled').toString().toLowerCase();
        if (raw === 'active' || raw === 'scheduled') return 'scheduled';
        if (raw === 'completed' || raw === 'done' || raw === 'finished') return 'completed';
        if (raw === 'cancelled' || raw === 'canceled') return 'cancelled';
        if (raw === 'no-show' || raw === 'no_show') return 'no-show';
        if (raw === 'in-progress' || raw === 'in_progress') return 'in-progress';
        return raw.charAt(0).toUpperCase() + raw.slice(1);
    }
};

