/**
 * Shared utilities for user modals
 */
window.UserModalUtils = {
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
        }
    },

    /**
     * Clear form errors
     */
    clearErrors(form, prefix = '') {
        const errorElements = form?.querySelectorAll(`[id^="${prefix}err_"]`);
        errorElements?.forEach(el => el.textContent = '');
    },

    /**
     * Display form errors
     */
    displayErrors(errors, prefix = '') {
        for (const [field, message] of Object.entries(errors)) {
            const errorElement = document.getElementById(`${prefix}err_${field}`);
            if (errorElement) {
                errorElement.textContent = Array.isArray(message) ? message[0] : message;
            }
        }
    },

    /**
     * Collect form data
     */
    collectFormData(form) {
        const formData = new FormData(form);
        const data = {};
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        return data;
    },

    /**
     * Validate password match
     */
    validatePasswordMatch(passwordId, confirmId, errorId) {
        const password = document.getElementById(passwordId)?.value;
        const confirmPassword = document.getElementById(confirmId)?.value;
        const errorElement = document.getElementById(errorId);

        if (confirmPassword && password !== confirmPassword) {
            if (errorElement) errorElement.textContent = 'Passwords do not match';
            return false;
        }
        if (errorElement) errorElement.textContent = '';
        return true;
    }
};

