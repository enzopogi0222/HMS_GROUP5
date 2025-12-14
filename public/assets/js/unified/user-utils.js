/**
 * User Management Utilities
 * Shared utility functions for user management
 */

// Configuration object
window.UserConfig = {
    baseUrl: document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '',
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    userRole: document.querySelector('meta[name="user-role"]')?.getAttribute('content') || 'admin',
    
    endpoints: {
        usersApi: 'users/api',
        userCreate: 'users/create',
        userUpdate: 'users/update',
        userGet: 'users',
        userDelete: 'users/delete',
        userResetPassword: 'users/reset-password',
        userRestore: 'users/restore',
        availableStaff: 'users/available-staff'
    },
    
    getUrl: function(path) {
        return this.baseUrl + path;
    }
};

// Utility functions
window.UserUtils = {
    
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
     * Returns a structured object similar to StaffUtils.makeRequest so callers
     * can inspect status and errors without throwing for non-2xx responses.
     */
    makeRequest: async function(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        if (UserConfig.csrfToken) {
            defaultOptions.headers['X-CSRF-TOKEN'] = UserConfig.csrfToken;
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

        return { ok: response.ok, statusCode: response.status, ...payload };
    },

    /**
     * Show notification
     * Uses the shared usersNotification bar when present, otherwise falls back to a floating toast.
     */
    showNotification: function(message, type = 'info') {
        const container = document.getElementById('usersNotification');
        const iconEl = document.getElementById('usersNotificationIcon');
        const textEl = document.getElementById('usersNotificationText');

        // If the shared notification bar exists on the page, prefer that
        if (container && iconEl && textEl) {
            const isError = type === 'error';
            const isSuccess = type === 'success';

            // Match appointments/staff notification styling (soft success/error)
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
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 10000;
            max-width: 400px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transform: translateX(100%);
            transition: transform 0.3s ease;
        `;

        const colors = {
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#3b82f6'
        };
        notification.style.backgroundColor = colors[type] || colors.info;

        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
                <span>${this.escapeHtml(message)}</span>
                <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: white; margin-left: auto; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);

        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
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
        const roleClass = role ? role.toLowerCase().replace('_', '-') : 'user';
        const roleText = role ? role.charAt(0).toUpperCase() + role.slice(1).replace('_', ' ') : 'User';
        
        return `<span class="role-badge role-${roleClass}">${this.escapeHtml(roleText)}</span>`;
    },

    /**
     * Format date for display
     */
    formatDate: function(dateString) {
        if (!dateString) return 'N/A';
        
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        } catch (e) {
            return 'N/A';
        }
    },

    /**
     * Format datetime for display
     */
    formatDateTime: function(dateString) {
        if (!dateString) return 'N/A';
        
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (e) {
            return 'N/A';
        }
    }
};

// Helper for shared users notification bar
function dismissUserNotification() {
    const container = document.getElementById('usersNotification');
    if (container) {
        container.style.display = 'none';
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { UserConfig, UserUtils };
}
