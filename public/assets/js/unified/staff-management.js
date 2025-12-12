/**
 * Unified Staff Management Controller
 * Main controller for staff management functionality across all roles
 */

class StaffManager {
    constructor() {
        this.staff = [];
        this.filteredStaff = [];
        this.currentFilters = {
            role: '',
            search: ''
        };
        
        this.init();
    }

    /**
     * Initialize the staff manager
     */
    init() {
        this.bindEvents();
        this.loadStaff();
        
        // Initialize modals if they exist
        if (window.AddStaffModal) {
            window.AddStaffModal.init();
        }
        if (window.ViewStaffModal) {
            window.ViewStaffModal.init();
        }
        if (window.EditStaffModal) {
            window.EditStaffModal.init();
        }
    }

    /**
     * Bind event listeners
     */
    bindEvents() {
        // Add staff button
        const addStaffBtn = document.getElementById('addStaffBtn');
        if (addStaffBtn) {
            addStaffBtn.addEventListener('click', () => this.openAddStaffModal());
        }

        // Export button
        const exportBtn = document.getElementById('exportBtn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportStaff());
        }

        // Filter controls
        const roleFilter = document.getElementById('roleFilter');
        const searchFilter = document.getElementById('searchFilter');

        if (roleFilter) {
            roleFilter.addEventListener('change', (e) => {
                this.currentFilters.role = e.target.value;
                this.applyFilters();
            });
        }

        if (searchFilter) {
            const debouncedSearch = StaffUtils.debounce((e) => {
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
                const staffId = btn.dataset.staffId;
                
                this.handleAction(action, staffId);
            }
        });
    }

    /**
     * Load staff from API
     */
    async loadStaff() {
        const tableBody = document.getElementById('staffTableBody');
        
        try {
            StaffUtils.showLoading(tableBody, 'Loading staff...');
            
            const response = await StaffUtils.makeRequest(
                StaffConfig.getUrl(StaffConfig.endpoints.staffApi)
            );
            
            if (response.status === 'success') {
                this.staff = response.data || [];
                this.filteredStaff = [...this.staff];
                this.renderStaffTable();
            } else {
                throw new Error(response.message || 'Failed to load staff');
            }
        } catch (error) {
            console.error('Error loading staff:', error);
            StaffUtils.showError(tableBody, 'Failed to load staff. Please try again.');
            StaffUtils.showNotification('Failed to load staff: ' + error.message, 'error');
        }
    }

    /**
     * Apply filters to staff list
     */
    applyFilters() {
        this.filteredStaff = this.staff.filter(staff => {
            // Role filter
            if (this.currentFilters.role && staff.role !== this.currentFilters.role) {
                return false;
            }

            // Search filter
            if (this.currentFilters.search) {
                const searchTerm = this.currentFilters.search.toLowerCase();
                const searchableText = [
                    staff.first_name,
                    staff.last_name,
                    staff.employee_id,
                    staff.email,
                    staff.department,
                    staff.role
                ].join(' ').toLowerCase();

                if (!searchableText.includes(searchTerm)) {
                    return false;
                }
            }

            return true;
        });

        this.renderStaffTable();
    }

    /**
     * Render staff table
     */
    renderStaffTable() {
        const tableBody = document.getElementById('staffTableBody');
        
        if (this.filteredStaff.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="5" style="text-align: center; padding: 2rem;">
                        <i class="fas fa-users" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;" aria-hidden="true"></i>
                        <p>No staff found.</p>
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

        const rows = this.filteredStaff.map(staff => this.createStaffRow(staff));
        tableBody.innerHTML = rows.join('');
    }

    /**
     * Create HTML for staff table row
     */
    createStaffRow(staff) {
        const fullName = StaffUtils.formatFullName(staff.first_name, staff.last_name);
        const dateJoined = staff.date_joined ? new Date(staff.date_joined).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : 'N/A';
        const roleSlug = staff.role || staff.role_slug || '';
        const roleClass = roleSlug ? roleSlug.toLowerCase().replace('_', '-') : 'staff';
        const roleLabel = staff.role_name || (roleSlug ? roleSlug.charAt(0).toUpperCase() + roleSlug.slice(1).replace('_', ' ') : 'Staff');
        const esc = StaffUtils.escapeHtml;

        return `<tr class="staff-row">
            <td><div style="display: flex; align-items: center; gap: 0.5rem;"><div>
                <div style="font-weight: 600;">${esc(fullName)}</div>
                <div style="font-size: 0.8rem; color: #6b7280;">${esc(staff.email || 'No email')}</div>
                <div style="font-size: 0.8rem; color: #6b7280;">ID: ${esc(staff.employee_id || staff.staff_id || 'N/A')}</div>
            </div></div></td>
            <td><span class="role-badge role-${roleClass}">${esc(roleLabel)}</span></td>
            <td>${esc(staff.department || 'N/A')}</td>
            <td>${dateJoined}</td>
            <td><div class="action-buttons">
                <button class="btn btn-warning btn-small action-btn" data-action="edit" data-staff-id="${staff.staff_id}" aria-label="Edit Staff ${esc(fullName)}">
                    <i class="fas fa-edit" aria-hidden="true"></i> Edit
                </button>
                <button class="btn btn-primary btn-small action-btn" data-action="view" data-staff-id="${staff.staff_id}" aria-label="View Staff ${esc(fullName)}">
                    <i class="fas fa-eye" aria-hidden="true"></i> View
                </button>
                ${this.canDelete() ? `<button class="btn btn-danger btn-small action-btn" data-action="delete" data-staff-id="${staff.staff_id}" aria-label="Delete Staff ${esc(fullName)}">
                    <i class="fas fa-trash" aria-hidden="true"></i> Delete
                </button>` : ''}
            </div></td>
        </tr>`;
    }

    /**
     * Handle action button clicks
     */
    handleAction(action, staffId) {
        const actions = { view: () => this.viewStaff(staffId), edit: () => this.editStaff(staffId), delete: () => this.deleteStaff(staffId) };
        if (actions[action]) actions[action]();
    }

    openAddStaffModal() {
        window.AddStaffModal?.open();
    }

    viewStaff(staffId) {
        window.ViewStaffModal?.open(staffId);
    }

    editStaff(staffId) {
        window.EditStaffModal?.open(staffId);
    }

    /**
     * Delete staff
     */
    async deleteStaff(staffId) {
        if (!this.canDelete()) {
            StaffUtils.showNotification('You do not have permission to delete staff', 'error');
            return;
        }

        const staff = this.staff.find(s => s.staff_id == staffId);
        if (!staff) {
            StaffUtils.showNotification('Staff not found', 'error');
            return;
        }

        const fullName = StaffUtils.formatFullName(staff.first_name, staff.last_name);
        
        if (!confirm(`Are you sure you want to delete staff member "${fullName}"? This action cannot be undone.`)) {
            return;
        }

        try {
            const response = await StaffUtils.makeRequest(
                StaffConfig.getUrl(`${StaffConfig.endpoints.staffDelete}/${staffId}`),
                { method: 'DELETE' }
            );

            if (response.status === 'success') {
                StaffUtils.showNotification('Staff deleted successfully', 'success');
                this.loadStaff(); // Reload the list
            } else {
                throw new Error(response.message || 'Failed to delete staff');
            }
        } catch (error) {
            console.error('Error deleting staff:', error);
            StaffUtils.showNotification('Failed to delete staff: ' + error.message, 'error');
        }
    }

    /**
     * Export staff data
     */
    exportStaff() {
        try {
            const csvData = this.generateCSV();
            const blob = new Blob([csvData], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            
            const a = document.createElement('a');
            a.href = url;
            a.download = `staff_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            StaffUtils.showNotification('Staff data exported successfully', 'success');
        } catch (error) {
            console.error('Export error:', error);
            StaffUtils.showNotification('Failed to export data', 'error');
        }
    }

    /**
     * Generate CSV data for export
     */
    generateCSV() {
        const headers = [
            'Staff ID', 'Employee ID', 'First Name', 'Last Name', 'Email',
            'Role', 'Department', 'Date Joined', 'Phone', 'Address'
        ];

        const rows = this.filteredStaff.map(staff => [
            staff.staff_id,
            staff.employee_id || '',
            staff.first_name || '',
            staff.last_name || '',
            staff.email || '',
            staff.role || '',
            staff.department || '',
            staff.date_joined || '',
            staff.phone || '',
            staff.address || ''
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
        return this.currentFilters.role || this.currentFilters.search;
    }

    /**
     * Refresh staff list
     */
    refresh() {
        this.loadStaff();
    }

    canEdit() {
        return ['admin', 'it_staff'].includes(StaffConfig.userRole);
    }

    canDelete() {
        return StaffConfig.userRole === 'admin';
    }
}

// Global functions for backward compatibility
window.clearFilters = () => {
    if (window.staffManager) {
        window.staffManager.currentFilters = { role: '', search: '' };
        const roleFilter = document.getElementById('roleFilter');
        const searchFilter = document.getElementById('searchFilter');
        if (roleFilter) roleFilter.value = '';
        if (searchFilter) searchFilter.value = '';
        window.staffManager.applyFilters();
    }
};

window.viewStaff = (staffId) => window.staffManager?.viewStaff(staffId);
window.editStaff = (staffId) => window.staffManager?.editStaff(staffId);
window.deleteStaff = (staffId) => window.staffManager?.deleteStaff(staffId);
window.refreshStaff = () => window.staffManager?.refresh();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.staffManager = new StaffManager();
});
