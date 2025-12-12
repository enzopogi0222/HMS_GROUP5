/**
 * Shared utilities for room modals
 */
class RoomModalUtils {
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
        if (window.showRoomsNotification) {
            window.showRoomsNotification(message, type);
        } else {
            alert(message);
        }
    }

    /**
     * Refresh CSRF hash
     */
    refreshCsrfHash(newHash) {
        if (!newHash) return;

        const csrfHashMeta = document.querySelector('meta[name="csrf-hash"]');
        if (csrfHashMeta) {
            csrfHashMeta.setAttribute('content', newHash);
        }

        const csrfTokenName = document.querySelector('meta[name="csrf-token"]')?.content || 'csrf_token';
        const csrfField = document.querySelector(`input[name="${csrfTokenName}"]`);
        if (csrfField) {
            csrfField.value = newHash;
        }
    }

    /**
     * Get CSRF token
     */
    getCsrfToken() {
        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfHashMeta = document.querySelector('meta[name="csrf-hash"]');
        return {
            name: csrfTokenMeta?.content || 'csrf_token',
            hash: csrfHashMeta?.content || ''
        };
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

