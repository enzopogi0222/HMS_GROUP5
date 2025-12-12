/**
 * Shared utilities for department modals
 */
class DepartmentModalUtils {
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
            if (e.key === 'Escape' && modal && !modal.hasAttribute('hidden')) {
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

        modal.removeAttribute('hidden');
        modal.style.display = 'flex';
    }

    /**
     * Close modal
     */
    close(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        modal.setAttribute('hidden', 'hidden');
        modal.style.display = 'none';
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
    showNotification(message, type = 'success') {
        if (window.showDepartmentsNotification) {
            window.showDepartmentsNotification(message, type);
        } else {
            alert(message);
        }
    }
}

