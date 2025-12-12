/**
 * Resource Management - Main Controller
 */
(function() {
    'use strict';

    const HMS = window.HMS || {};
    const baseUrl = (HMS.baseUrl || '').replace(/\/+$/, '') + '/';
    const csrf = HMS.csrf || {};

    // Preload resources data
    (function() {
        try {
            const list = window.__RESOURCES__ || [];
            const byId = {};
            if (Array.isArray(list)) {
                for (let i = 0; i < list.length; i++) {
                    const r = list[i];
                    if (r && (r.id || r.resource_id)) {
                        byId[r.id || r.resource_id] = r;
                    }
                }
            }
            window.resourcesById = byId;
        } catch (e) {
            window.resourcesById = {};
        }
    })();

    // Modal controls
    document.getElementById('addResourceBtn')?.addEventListener('click', () => {
        if (window.AddResourceModal && window.AddResourceModal.open) {
            window.AddResourceModal.open();
        }
    });

    // Edit and Delete functions
    window.editResource = function(id) {
        if (window.EditResourceModal && window.EditResourceModal.open) {
            window.EditResourceModal.open(id);
        }
    };

    window.deleteResource = function(id) {
        if (!confirm('Delete this resource?')) return;

        const p = new URLSearchParams();
        p.append('id', id);
        try {
            if (csrf.token && csrf.hash) {
                p.append('csrf_token', csrf.hash);
            }
        } catch (e) {}

        const resourceBaseUrl = HMS.resourceManagementBaseUrl || 'admin/resource-management';
        fetch(baseUrl + resourceBaseUrl + '/delete', {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: p
        })
        .then(r => r.json().catch(() => ({ status: 'error' })))
        .then(res => {
            if (res && res.success === true) {
                showNotification('Resource deleted successfully', 'success');
                window.location.reload();
            } else {
                showNotification(res?.message || 'Failed to delete', 'error');
            }
        })
        .catch(() => showNotification('Failed to delete', 'error'));
    };

    // Export functionality
    document.getElementById('exportBtn')?.addEventListener('click', function() {
        const searchInput = document.getElementById('searchResource');
        const categoryFilter = document.getElementById('filterCategory');
        const statusFilter = document.getElementById('filterStatus');
        
        const params = new URLSearchParams();
        if (searchInput?.value) params.append('search', searchInput.value);
        if (categoryFilter?.value) params.append('category', categoryFilter.value);
        if (statusFilter?.value) params.append('status', statusFilter.value);
        
        const resourceBaseUrl = HMS.resourceManagementBaseUrl || 'admin/resource-management';
        let url = baseUrl + resourceBaseUrl + '/export';
        if (params.toString()) url += '?' + params.toString();
        
        window.location.href = url;
    });

    // Search and Filter Functionality
    (function() {
        const searchInput = document.getElementById('searchResource');
        const categoryFilter = document.getElementById('filterCategory');
        const statusFilter = document.getElementById('filterStatus');
        const clearFiltersBtn = document.getElementById('clearFilters');
        const tableBody = document.getElementById('resourcesTableBody');

        if (!tableBody) return;

        const allResources = [];
        // Get all resources from table rows
        (function() {
            const rows = tableBody.querySelectorAll('tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length > 0) {
                    const name = cells[0].textContent.trim();
                    const category = cells[1].textContent.trim();
                    const quantity = cells[2].textContent.trim();
                    const statusEl = cells[3].querySelector('.badge');
                    const status = statusEl ? statusEl.textContent.trim() : '';
                    const location = cells[4].textContent.trim();
                    
                    allResources.push({
                        row: row,
                        name: name.toLowerCase(),
                        category: category,
                        status: status,
                        location: location.toLowerCase(),
                        searchText: (name + ' ' + location).toLowerCase()
                    });
                }
            });
        })();

        function filterResources() {
            const searchTerm = (searchInput?.value || '').toLowerCase().trim();
            const selectedCategory = categoryFilter?.value || '';
            const selectedStatus = statusFilter?.value || '';

            allResources.forEach(resource => {
                const matchesSearch = !searchTerm || resource.searchText.includes(searchTerm);
                const matchesCategory = !selectedCategory || resource.category === selectedCategory;
                const matchesStatus = !selectedStatus || resource.status === selectedStatus;

                resource.row.style.display = (matchesSearch && matchesCategory && matchesStatus) ? '' : 'none';
            });
        }

        function clearFilters() {
            if (searchInput) searchInput.value = '';
            if (categoryFilter) categoryFilter.value = '';
            if (statusFilter) statusFilter.value = '';
            filterResources();
        }

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(window.searchTimeout);
                window.searchTimeout = setTimeout(filterResources, 300);
            });
        }
        if (categoryFilter) categoryFilter.addEventListener('change', filterResources);
        if (statusFilter) statusFilter.addEventListener('change', filterResources);
        if (clearFiltersBtn) clearFiltersBtn.addEventListener('click', clearFilters);
    })();

    // Notification helper
    function showNotification(message, type) {
        const container = document.getElementById('resourcesNotification');
        const iconEl = document.getElementById('resourcesNotificationIcon');
        const textEl = document.getElementById('resourcesNotificationText');

        if (container && iconEl && textEl) {
            const isError = type === 'error';
            const isSuccess = type === 'success';

            container.style.border = isError ? '1px solid #fecaca' : '1px solid #bbf7d0';
            container.style.background = isError ? '#fee2e2' : '#ecfdf5';
            container.style.color = isError ? '#991b1b' : '#166534';

            const iconClass = isError ? 'fa-exclamation-triangle' : (isSuccess ? 'fa-check-circle' : 'fa-info-circle');
            iconEl.className = 'fas ' + iconClass;

            textEl.textContent = String(message || '');
            container.style.display = 'flex';

            setTimeout(() => {
                if (container.style.display !== 'none') {
                    container.style.display = 'none';
                }
            }, 4000);
            return;
        }

        alert(message || (type === 'error' ? 'Error' : 'Notice'));
    }

    window.dismissResourcesNotification = function() {
        const container = document.getElementById('resourcesNotification');
        if (container) container.style.display = 'none';
    };
})();

