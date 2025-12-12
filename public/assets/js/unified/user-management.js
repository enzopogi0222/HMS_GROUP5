/**
 * Unified User Management Controller
 * Main controller for user management functionality across all roles
 */

class UserManager {
    constructor() {
        this.users = [];
        this.filteredUsers = [];
        this.currentFilters = {
            status: '',
            role: '',
            search: ''
        };
        
        this.init();
    }

    /**
     * Initialize the user manager
     */
    init() {
        this.bindEvents();
        
        // Load initial data from server if available
        this.loadInitialData();
        
        // Initialize modals if they exist
        if (window.AddUserModal) {
            window.AddUserModal.init();
        }
        if (window.ViewUserModal) {
            window.ViewUserModal.init();
        }
        if (window.EditUserModal) {
            window.EditUserModal.init();
        }
        if (window.ResetPasswordModal) {
            window.ResetPasswordModal.init();
        }
    }

    async restoreUser(userId) {
        if (!this.canRestore()) {
            UserUtils.showNotification('You do not have permission to restore users', 'error');
            return;
        }

        const user = this.users.find(u => u.user_id == userId);
        if (!user) {
            UserUtils.showNotification('User not found', 'error');
            return;
        }

        const fullName = UserUtils.formatFullName(user.first_name, user.last_name);

        if (!confirm(`Are you sure you want to restore user "${fullName}"?`)) {
            return;
        }

        try {
            const response = await UserUtils.makeRequest(
                UserConfig.getUrl(`${UserConfig.endpoints.userRestore}/${userId}`),
                { method: 'POST' }
            );

            if (response.status === 'success') {
                UserUtils.showNotification(response.message || 'User restored successfully', 'success');
                this.loadUsers(); // IT staff list will update (user disappears because now active)
            } else {
                throw new Error(response.message || 'Failed to restore user');
            }
        } catch (error) {
            console.error('Error restoring user:', error);
            UserUtils.showNotification('Failed to restore user: ' + error.message, 'error');
        }
    }

    /**
     * Bind event listeners
     */
    bindEvents() {
        // Add user button
        const addUserBtn = document.getElementById('addUserBtn');
        if (addUserBtn) {
            addUserBtn.addEventListener('click', () => this.openAddUserModal());
        }

        // Export button
        const exportBtn = document.getElementById('exportBtn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportUsers());
        }

        // Filter controls
        const statusFilter = document.getElementById('statusFilter');
        const roleFilter = document.getElementById('roleFilter');
        const searchFilter = document.getElementById('searchFilter');

        if (statusFilter) {
            statusFilter.addEventListener('change', (e) => {
                this.currentFilters.status = e.target.value;
                this.applyFilters();
            });
        }

        if (roleFilter) {
            roleFilter.addEventListener('change', (e) => {
                this.currentFilters.role = e.target.value;
                this.applyFilters();
            });
        }

        if (searchFilter) {
            const debouncedSearch = UserUtils.debounce((e) => {
                this.currentFilters.search = e.target.value;
                this.applyFilters();
            }, 300);
            
            searchFilter.addEventListener('input', debouncedSearch);
        }

        // Table row clicks for actions
        document.addEventListener('click', (e) => {
            if (e.target.closest('.action-btn')) {
                const btn = e.target.closest('.action-btn');
                const action = btn.dataset.action;
                const userId = btn.dataset.userId;
                
                this.handleAction(action, userId);
            }
        });
    }

    /**
     * Load initial data from server-rendered HTML
     */
    loadInitialData() {
        // Extract user data from the existing table
        const tableBody = document.getElementById('usersTableBody');
        const rows = tableBody.querySelectorAll('tr.user-row');
        
        if (rows.length > 0) {
            this.users = Array.from(rows).map(row => {
                const cells = row.querySelectorAll('td');
                const userId = row.querySelector('[data-user-id]')?.getAttribute('data-user-id');
                
                // Extract data from the HTML
                const nameCell = cells[0].querySelector('div > div:first-child');
                const emailCell = cells[0].querySelector('div > div:nth-child(2)');
                const idCell = cells[0].querySelector('div > div:nth-child(3)');
                const roleCell = cells[1].querySelector('.role-badge');
                const deptCell = cells[2];
                const statusCell = cells[3];
                
                return {
                    user_id: parseInt(userId),
                    first_name: nameCell?.textContent.trim().split(' ')[0] || '',
                    last_name: nameCell?.textContent.trim().split(' ').slice(1).join(' ') || '',
                    email: emailCell?.textContent.replace('Email: ', '').trim() || '',
                    username: idCell?.textContent.replace('ID: ', '').trim() || '',
                    role: roleCell?.textContent.trim() || '',
                    department: deptCell?.textContent.trim() || '',
                    status: statusCell?.textContent.trim().toLowerCase() || 'active'
                };
            });
            
            this.filteredUsers = [...this.users];
        } else {
            // No users in table, try loading via API
            this.loadUsers();
        }
    }

    /**
     * Load users from API
     */
    async loadUsers() {
        const tableBody = document.getElementById('usersTableBody');
        
        try {
            UserUtils.showLoading(tableBody, 'Loading users...');
            
            const response = await UserUtils.makeRequest(
                UserConfig.getUrl(UserConfig.endpoints.usersApi)
            );
            
            if (response.status === 'success') {
                this.users = response.data || [];
                this.filteredUsers = [...this.users];
                this.renderUsersTable();
            } else {
                throw new Error(response.message || 'Failed to load users');
            }
        } catch (error) {
            console.error('Error loading users:', error);
            UserUtils.showError(tableBody, 'Failed to load users. Please try again.');
            UserUtils.showNotification('Failed to load users: ' + error.message, 'error');
        }
    }

    /**
     * Apply filters to user list
     */
    applyFilters() {
        this.filteredUsers = this.users.filter(user => {
            // Status filter
            if (this.currentFilters.status && user.status !== this.currentFilters.status) {
                return false;
            }

            // Role filter
            if (this.currentFilters.role && user.role !== this.currentFilters.role) {
                return false;
            }

            // Search filter
            if (this.currentFilters.search) {
                const searchTerm = this.currentFilters.search.toLowerCase();
                const searchableText = [
                    user.username,
                    user.first_name,
                    user.last_name,
                    user.email,
                    user.role,
                    user.department
                ].join(' ').toLowerCase();

                if (!searchableText.includes(searchTerm)) {
                    return false;
                }
            }

            return true;
        });

        this.renderUsersTable();
    }

    /**
     * Render users table
     */
    renderUsersTable() {
        const tableBody = document.getElementById('usersTableBody');
        
        if (this.filteredUsers.length === 0) {
            const emptyMessage = (UserConfig.userRole === 'it_staff') ? 'No inactive users found.' : 'No users found.';
            tableBody.innerHTML = `
                <tr>
                    <td colspan="5" style="text-align: center; padding: 2rem;">
                        <i class="fas fa-users" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;" aria-hidden="true"></i>
                        <p>${emptyMessage}</p>
                        ${this.hasActiveFilters() ? `
                            <button onclick="clearFilters()" class="btn btn-secondary" aria-label="Clear Filters">
                                <i class="fas fa-times" aria-hidden="true"></i> Clear Filters
                            </button>
                        ` : ''}
                    </td>
                </tr>
            `;
            return;
        }

        const rows = this.filteredUsers.map(user => this.createUserRow(user));
        tableBody.innerHTML = rows.join('');
    }

    /**
     * Create HTML for user table row
     */
    createUserRow(user) {
        const fullName = UserUtils.formatFullName(user.first_name, user.last_name);
        
        // Normalize status value
        const statusValue = (user.status || 'active').toLowerCase();

        // Determine role class (slug) and display label (name)
        const roleSlug = user.role || user.role_slug || '';
        const roleClass = roleSlug ? roleSlug.toLowerCase().replace('_', '-') : 'user';
        const roleLabel = user.role_name
            ? user.role_name
            : (roleSlug
                ? roleSlug.charAt(0).toUpperCase() + roleSlug.slice(1).replace('_', ' ')
                : 'User');

        return `
            <tr class="user-row">
                <td>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <div>
                            <div style="font-weight: 600;">
                                ${UserUtils.escapeHtml(fullName)}
                            </div>
                            <div style="font-size: 0.8rem; color: #6b7280;">
                                ${UserUtils.escapeHtml(user.email || 'No email')}
                            </div>
                            <div style="font-size: 0.8rem; color: #6b7280;">
                                ID: ${UserUtils.escapeHtml(user.username || user.user_id || 'N/A')}
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="role-badge role-${roleClass}">
                        ${UserUtils.escapeHtml(roleLabel)}
                    </span>
                </td>
                <td>${UserUtils.escapeHtml(user.department || 'N/A')}</td>
                <td>
                    <span class="status-badge ${statusValue}">
                        ${UserUtils.escapeHtml(statusValue.charAt(0).toUpperCase() + statusValue.slice(1))}
                    </span>
                </td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-warning btn-small action-btn" 
                                data-action="edit" 
                                data-user-id="${user.user_id}"
                                aria-label="Edit User ${UserUtils.escapeHtml(fullName)}">
                            <i class="fas fa-edit" aria-hidden="true"></i> Edit
                        </button>
                        <button class="btn btn-primary btn-small action-btn" 
                                data-action="reset" 
                                data-user-id="${user.user_id}"
                                aria-label="Reset Password for ${UserUtils.escapeHtml(fullName)}">
                            <i class="fas fa-key" aria-hidden="true"></i> Reset
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }

    /**
     * Handle action button clicks
     */
    handleAction(action, userId) {
        const actions = {
            'view': () => this.viewUser(userId),
            'edit': () => this.editUser(userId),
            'reset': () => this.resetPassword(userId),
            'restore': () => this.restoreUser(userId)
        };
        actions[action]?.();
    }

    openAddUserModal() { window.AddUserModal?.open(); }
    viewUser(userId) { window.ViewUserModal?.open(userId); }
    editUser(userId) { window.EditUserModal?.open(userId); }

    /**
     * Reset user password
     */
    async resetPassword(userId) {
        if (!this.canResetPassword()) {
            UserUtils.showNotification('You do not have permission to reset passwords', 'error');
            return;
        }

        const user = this.users.find(u => u.user_id == userId);
        if (!user) {
            UserUtils.showNotification('User not found', 'error');
            return;
        }

        // Open the reset password modal
        if (window.ResetPasswordModal) {
            window.ResetPasswordModal.open(userId);
        } else {
            UserUtils.showNotification('Reset password modal not available', 'error');
        }
    }

    /**
     * Export users data
     */
    exportUsers() {
        try {
            const csvData = this.generateCSV();
            const blob = new Blob([csvData], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            
            const a = document.createElement('a');
            a.href = url;
            a.download = `users_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            UserUtils.showNotification('Users data exported successfully', 'success');
        } catch (error) {
            console.error('Export error:', error);
            UserUtils.showNotification('Failed to export data', 'error');
        }
    }

    /**
     * Generate CSV data for export
     */
    generateCSV() {
        const headers = [
            'User ID', 'Username', 'First Name', 'Last Name', 'Email',
            'Role', 'Department', 'Status', 'Created At'
        ];

        const rows = this.filteredUsers.map(user => [
            user.user_id,
            user.username || '',
            user.first_name || '',
            user.last_name || '',
            user.email || '',
            user.role || '',
            user.department || '',
            user.status || '',
            user.created_at || ''
        ]);

        const csvContent = [headers, ...rows]
            .map(row => row.map(field => `"${String(field).replace(/"/g, '""')}"`).join(','))
            .join('\n');

        return csvContent;
    }

    /**
     * Check if there are active filters
     */
    hasActiveFilters() {
        return this.currentFilters.status || this.currentFilters.role || this.currentFilters.search;
    }

    /**
     * Refresh users list
     */
    refresh() {
        this.loadUsers();
    }

    /**
     * Permission checks
     */
    canEdit() { return ['admin', 'it_staff'].includes(UserConfig.userRole); }
    canDelete() { return ['admin', 'it_staff'].includes(UserConfig.userRole); }
    canResetPassword() { return ['admin', 'it_staff'].includes(UserConfig.userRole); }
    canRestore() { return ['admin', 'it_staff'].includes(UserConfig.userRole); }
}

// Global functions for backward compatibility
window.clearFilters = function() {
    if (window.userManager) {
        window.userManager.currentFilters = { status: '', role: '', search: '' };
        
        // Reset filter controls
        const statusFilter = document.getElementById('statusFilter');
        const roleFilter = document.getElementById('roleFilter');
        const searchFilter = document.getElementById('searchFilter');
        
        if (statusFilter) statusFilter.value = '';
        if (roleFilter) roleFilter.value = '';
        if (searchFilter) searchFilter.value = '';
        
        window.userManager.applyFilters();
    }
};

window.viewUser = function(userId) {
    if (window.userManager) {
        window.userManager.viewUser(userId);
    }
};

window.editUser = function(userId) {
    if (window.userManager) {
        window.userManager.editUser(userId);
    }
};

window.resetUserPassword = function(userId) {
    if (window.userManager) {
        window.userManager.resetPassword(userId);
    }
};

window.deleteUser = function(userId) {
    if (window.userManager) {
        window.userManager.deleteUser(userId);
    }
};

window.refreshUsers = function() {
    if (window.userManager) {
        window.userManager.refresh();
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.userManager = new UserManager();
});
