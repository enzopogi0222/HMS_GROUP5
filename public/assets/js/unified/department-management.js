/**
 * Department Management - Main Controller
 */
(function() {
    const baseUrl = document.querySelector('meta[name="base-url"]')?.content?.replace(/\/+$/, '') || '';
    let allDepartmentRows = [];

    // Initialize add department modal button
    document.getElementById('addDepartmentBtn')?.addEventListener('click', () => {
        if (window.AddDepartmentModal && window.AddDepartmentModal.open) {
            window.AddDepartmentModal.open();
        }
    });

    // Fetch departments from API
    async function fetchDepartments() {
        const tableBody = document.getElementById('departmentsTableBody');
        if (!tableBody) return;

        try {
            // Show loading state
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align: center; padding: 2rem;">
                        <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #ccc; margin-bottom: 1rem;" aria-hidden="true"></i>
                        <p>Loading departments...</p>
                    </td>
                </tr>
            `;

            const apiUrl = baseUrl + (baseUrl.endsWith('/') ? '' : '/') + 'departments/api';
            const response = await fetch(apiUrl, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.status === 'success' && Array.isArray(data.data)) {
                renderDepartments(data.data);
            } else {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2rem; color: #b91c1c;">
                            <i class="fas fa-exclamation-triangle" style="margin-right: 0.5rem;"></i>
                            ${data.message || 'Failed to load departments'}
                        </td>
                    </tr>
                `;
            }
        } catch (error) {
            console.error('Failed to fetch departments:', error);
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align: center; padding: 2rem; color: #b91c1c;">
                        <i class="fas fa-exclamation-triangle" style="margin-right: 0.5rem;"></i>
                        Failed to load departments. Please refresh the page.
                    </td>
                </tr>
            `;
        }
    }

    // Render departments in the table
    function renderDepartments(departments) {
        const tableBody = document.getElementById('departmentsTableBody');
        if (!tableBody) return;

        if (!departments || departments.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align: center; padding: 2rem; color: #6b7280;">
                        <i class="fas fa-building" style="font-size: 2rem; color: #ccc; margin-bottom: 1rem;" aria-hidden="true"></i>
                        <p>No departments found. Click "Add Department" to create one.</p>
                    </td>
                </tr>
            `;
            allDepartmentRows = [];
            return;
        }

        const rows = departments.map(dept => {
            const deptId = dept.department_id || dept.id || 'N/A';
            const name = escapeHtml(dept.name || 'Unnamed Department');
            const code = dept.code ? `<span class="text-muted">(${escapeHtml(dept.code)})</span>` : '';
            const type = dept.type ? `<span class="badge badge-info">${escapeHtml(dept.type)}</span>` : '';
            const status = dept.status || 'Active';
            const statusClass = status.toLowerCase() === 'active' ? 'badge-success' : 'badge-danger';
            const head = dept.department_head_id ? 'Assigned' : '<span class="text-muted">Not assigned</span>';
            const floor = dept.floor ? escapeHtml(dept.floor) : '-';
            const description = dept.description ? escapeHtml(dept.description.substring(0, 50) + (dept.description.length > 50 ? '...' : '')) : '-';

            return `
                <tr data-department-id="${deptId}">
                    <td>
                        <div>
                            <strong>${name}</strong> ${code}
                            ${type ? '<br>' + type : ''}
                        </div>
                    </td>
                    <td>${head}</td>
                    <td>-</td>
                    <td>${description}</td>
                    <td><span class="badge ${statusClass}">${escapeHtml(status)}</span></td>
                    <td>
                        <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                            <button class="btn btn-primary btn-sm" onclick="viewDepartment(${deptId})" title="View Details">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button class="btn btn-warning btn-sm" onclick="editDepartment(${deptId})" title="Edit Department">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteDepartment(${deptId})" title="Delete Department">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });

        tableBody.innerHTML = rows.join('');
        allDepartmentRows = Array.from(tableBody.querySelectorAll('tr'));
    }

    // Helper function to escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Department action functions
    window.viewDepartment = function(deptId) {
        if (!deptId) {
            showDepartmentsNotification('Invalid department ID', 'error');
            return;
        }
        console.log('Opening view modal for department:', deptId);
        if (window.ViewDepartmentModal && window.ViewDepartmentModal.open) {
            window.ViewDepartmentModal.open(deptId);
        } else {
            console.error('ViewDepartmentModal not available', window.ViewDepartmentModal);
            showDepartmentsNotification('View modal not available', 'error');
        }
    };

    window.editDepartment = function(deptId) {
        if (!deptId) {
            showDepartmentsNotification('Invalid department ID', 'error');
            return;
        }
        if (window.AddDepartmentModal && window.AddDepartmentModal.openForEdit) {
            window.AddDepartmentModal.openForEdit(deptId);
        } else {
            showDepartmentsNotification('Edit modal not available', 'error');
        }
    };

    window.deleteDepartment = async function(deptId) {
        if (!deptId) {
            showDepartmentsNotification('Invalid department ID', 'error');
            return;
        }

        if (!confirm('Are you sure you want to delete this department? This action cannot be undone.')) {
            return;
        }

        try {
            const response = await fetch(`${baseUrl}/departments/delete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ id: deptId }),
            });

            const result = await response.json().catch(() => ({ status: 'error', message: 'Invalid response' }));

            if (response.ok && result.status === 'success') {
                showDepartmentsNotification('Department deleted successfully', 'success');
                // Reload the departments list
                setTimeout(() => fetchDepartments(), 500);
            } else {
                const message = result.message || 'Failed to delete department';
                showDepartmentsNotification(message, 'error');
            }
        } catch (error) {
            console.error('Failed to delete department:', error);
            showDepartmentsNotification('Server error while deleting department', 'error');
        }
    };

    // Fetch departments on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fetchDepartments);
    } else {
        fetchDepartments();
    }

    // Search and filter functionality
    function initializeSearch() {
        const searchInput = document.getElementById('searchDepartment');
        const clearBtn = document.getElementById('clearFiltersDepartment');
        const tableBody = document.getElementById('departmentsTableBody');

        if (!tableBody) return;

        // Store all rows initially
        allDepartmentRows = Array.from(tableBody.querySelectorAll('tr'));

        function filterDepartments() {
            const searchTerm = (searchInput?.value || '').toLowerCase().trim();

            allDepartmentRows.forEach(row => {
                if (row.querySelector('td[colspan]')) {
                    // Skip placeholder/loading rows
                    return;
                }

                const cells = row.querySelectorAll('td');
                if (cells.length === 0) return;

                const searchableText = Array.from(cells)
                    .map(cell => cell.textContent || '')
                    .join(' ')
                    .toLowerCase();

                const matches = !searchTerm || searchableText.includes(searchTerm);
                row.style.display = matches ? '' : 'none';
            });
        }

        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(filterDepartments, 300);
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                if (searchInput) searchInput.value = '';
                filterDepartments();
            });
        }
    }

    // Initialize search when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeSearch);
    } else {
        initializeSearch();
    }

    // Notification functions
    window.showDepartmentsNotification = function(message, type = 'success') {
        const container = document.getElementById('departmentsNotification');
        const messageEl = document.getElementById('departmentsNotificationMessage');
        if (!container || !messageEl) {
            if (typeof showUniversalNotification === 'function') {
                showUniversalNotification(message, 'error');
            }
            return;
        }

        if (window.departmentsNotificationTimeout) {
            clearTimeout(window.departmentsNotificationTimeout);
        }

        container.className = `notification ${type}`;
        messageEl.textContent = String(message || '');
        container.style.display = 'flex';

        window.departmentsNotificationTimeout = setTimeout(dismissDepartmentsNotification, 5000);
    };

    window.dismissDepartmentsNotification = function() {
        const container = document.getElementById('departmentsNotification');
        if (container) {
            container.style.display = 'none';
        }
    };

})();
