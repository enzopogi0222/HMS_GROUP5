/**
 * Shared utilities for billing modals
 */
class BillingModalUtils {
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
            if (e.key === 'Escape' && modal && !modal.hasAttribute('hidden') && modal.style.display !== 'none') {
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

        modal.style.display = 'flex';
        modal.removeAttribute('hidden');
        modal.setAttribute('aria-hidden', 'false');
    }

    /**
     * Close modal
     */
    close(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        modal.style.display = 'none';
        modal.setAttribute('hidden', 'hidden');
        modal.setAttribute('aria-hidden', 'true');
    }

    /**
     * Show notification
     */
    showNotification(message, type = 'success') {
        if (window.showFinancialNotification) {
            window.showFinancialNotification(message, type);
        } else {
            alert(message);
        }
    }

    /**
     * Escape HTML
     */
    escapeHtml(value) {
        if (!value) return '';
        return value.toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
}

