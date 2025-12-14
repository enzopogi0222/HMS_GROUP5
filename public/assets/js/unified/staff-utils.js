/**
 * Staff Management Utilities
 * Shared utility functions for staff management
 */

// Configuration object
window.StaffConfig = {
    baseUrl: document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '',
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    userRole: document.querySelector('meta[name="user-role"]')?.getAttribute('content') || 'admin',
    
    endpoints: {
        staffApi: 'staff/api',
        staffCreate: 'staff/create',
        staffUpdate: 'staff/update',
        staffGet: 'staff',
        staffDelete: 'staff/delete'
    },
    
    getUrl: function(path) {
        return this.baseUrl + path;
    }
};

// Utility functions
window.StaffUtils = {
    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml: function(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    /**
     * Format full name
     */
    formatFullName: function(firstName, lastName) {
        const first = (firstName || '').trim();
        const last = (lastName || '').trim();
        return [first, last].filter(Boolean).join(' ') || 'Unknown';
    },

    /**
     * Get initials from name
     */
    getInitials: function(firstName, lastName) {
        const first = (firstName || '').trim();
        const last = (lastName || '').trim();
        return ((first.charAt(0) || '') + (last.charAt(0) || '')).toUpperCase() || '??';
    },

    /**
     * Show loading state
     */
    showLoading: function(container, message = 'Loading...') {
        if (container) {
            container.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align: center; padding: 2rem;">
                        <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #ccc; margin-bottom: 1rem;"></i>
                        <p>${this.escapeHtml(message)}</p>
                    </td>
                </tr>
            `;
        }
    },

    /**
     * Show error state
     */
    showError: function(container, message = 'An error occurred') {
        if (container) {
            container.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align: center; padding: 2rem;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: #ef4444; margin-bottom: 1rem;"></i>
                        <p style="color: #ef4444;">${this.escapeHtml(message)}</p>
                    </td>
                </tr>
            `;
        }
    },

    /**
     * Make HTTP request with proper headers
     */
    makeRequest: async function(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        if (StaffConfig.csrfToken) {
            defaultOptions.headers['X-CSRF-TOKEN'] = StaffConfig.csrfToken;
        }

        const finalOptions = { ...defaultOptions, ...options };
        
        if (finalOptions.headers) {
            finalOptions.headers = { ...defaultOptions.headers, ...finalOptions.headers };
        }

        const response = await fetch(url, finalOptions);

        let payload = null;
        const contentType = response.headers.get('content-type') || '';
        try {
            if (contentType.includes('application/json')) {
                payload = await response.json();
            } else {
                const text = await response.text();
                payload = { status: response.ok ? 'success' : 'error', message: text };
            }
        } catch (e) {
            payload = { status: 'error', message: 'Failed to parse server response' };
        }

        // Always return a structured object so caller can decide
        return { ok: response.ok, statusCode: response.status, ...payload };
    },

    /**
     * Show notification
     * Uses the shared staffNotification bar when present, otherwise falls back to a floating toast.
     */
    showNotification: function(message, type = 'info') {
        const container = document.getElementById('staffNotification');
        const iconEl = document.getElementById('staffNotificationIcon');
        const textEl = document.getElementById('staffNotificationText');

        // If the shared notification bar exists on the page, prefer that
        if (container && iconEl && textEl) {
            const isError = type === 'error';
            const isSuccess = type === 'success';

            // Match the appointments notification styling (soft success/error)
            container.style.border = isError ? '1px solid #fecaca' : '1px solid #bbf7d0';
            container.style.background = isError ? '#fee2e2' : '#ecfdf5';
            container.style.color = isError ? '#991b1b' : '#166534';

            const iconClass = isError
                ? 'fa-exclamation-triangle'
                : (isSuccess ? 'fa-check-circle' : 'fa-info-circle');
            iconEl.className = 'fas ' + iconClass;

            textEl.textContent = this.escapeHtml(message || '');
            container.style.display = 'flex';

            // Auto-hide after a few seconds
            setTimeout(() => {
                if (container.style.display !== 'none') {
                    container.style.display = 'none';
                }
            }, 4000);
            return;
        }

        // Fallback: floating toast notification (for pages that don't include the shared bar)
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        const isError = type === 'error';
        const isSuccess = type === 'success';
        
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            left: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            color: ${isError ? '#991b1b' : '#166534'};
            font-weight: 500;
            z-index: 10050;
            max-width: 400px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.25);
            border: ${isError ? '1px solid #fecaca' : '1px solid #bbf7d0'};
            background: ${isError ? '#fee2e2' : '#ecfdf5'};
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        `;

        const iconClass = isError
            ? 'fa-exclamation-triangle'
            : (isSuccess ? 'fa-check-circle' : 'fa-info-circle');

        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 0.5rem; width: 100%;">
                <i class="fas ${iconClass}" style="flex-shrink: 0;"></i>
                <span style="flex: 1;">${this.escapeHtml(message)}</span>
                <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: inherit; margin-left: auto; cursor: pointer; padding: 0.25rem; flex-shrink: 0;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);

        setTimeout(() => {
            notification.style.transform = 'translateX(-100%)';
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 300);
        }, 5000);
    },

    /**
     * Debounce function
     */
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Get status badge HTML
     */
    getStatusBadge: function(status) {
        const statusClass = status ? status.toLowerCase() : 'active';
        const statusText = status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Active';
        
        return `<span class="status-badge status-${statusClass}">${this.escapeHtml(statusText)}</span>`;
    },

    /**
     * Get role badge HTML
     */
    getRoleBadge: function(role) {
        const roleClass = role ? role.toLowerCase().replace('_', '-') : 'staff';
        const roleText = role ? role.charAt(0).toUpperCase() + role.slice(1).replace('_', ' ') : 'Staff';
        
        return `<span class="role-badge role-${roleClass}">${this.escapeHtml(roleText)}</span>`;
    }
};

// Helper for shared staff notification bar
function dismissStaffNotification() {
    const container = document.getElementById('staffNotification');
    if (container) {
        container.style.display = 'none';
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { StaffConfig, StaffUtils };
}
