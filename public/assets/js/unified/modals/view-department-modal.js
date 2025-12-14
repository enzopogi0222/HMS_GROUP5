/**
 * View Department Modal Controller
 */
(function() {
    const baseUrl = document.querySelector('meta[name="base-url"]')?.content?.replace(/\/+$/, '') || '';
    const modalId = 'viewDepartmentModal';
    let currentDepartmentId = null;

    const modal = document.getElementById(modalId);
    const content = document.getElementById('viewDepartmentContent');
    const editBtn = document.getElementById('editFromViewDepartmentBtn');

    function init() {
        if (!modal) return;

        // Setup close handlers
        const closeBtn = modal.querySelector('[onclick="closeViewDepartmentModal()"]');
        if (closeBtn) {
            closeBtn.addEventListener('click', close);
        }

        // Close button in footer
        const footerCloseBtn = modal.querySelector('.hms-modal-actions .btn-secondary');
        if (footerCloseBtn && footerCloseBtn.onclick) {
            footerCloseBtn.addEventListener('click', close);
        }

        // Edit button handler
        if (editBtn) {
            editBtn.addEventListener('click', () => {
                if (currentDepartmentId) {
                    close();
                    if (window.editDepartment) {
                        window.editDepartment(currentDepartmentId);
                    }
                }
            });
        }

        // Close on overlay click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                close();
            }
        });

        // Escape key to close
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal && !modal.hasAttribute('hidden')) {
                close();
            }
        });
    }

    function open(departmentId) {
        if (!modal || !departmentId) return;
        currentDepartmentId = departmentId;

        // Remove hidden attribute and set display
        modal.removeAttribute('hidden');
        modal.style.display = 'flex';
        modal.setAttribute('aria-hidden', 'false');
        
        // Show loading state
        if (content) {
            content.innerHTML = `
                <div style="text-align: center; padding: 3rem;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #cbd5e0; margin-bottom: 1rem;" aria-hidden="true"></i>
                    <p style="color: #64748b;">Loading department details...</p>
                </div>
            `;
        }

        // Show edit button if user has permission
        if (editBtn) {
            const userRole = document.querySelector('meta[name="user-role"]')?.content || '';
            editBtn.style.display = ['admin', 'it_staff'].includes(userRole) ? 'block' : 'none';
        }

        loadDepartmentDetails(departmentId);
    }

    function close() {
        if (!modal) return;
        modal.setAttribute('hidden', 'hidden');
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        currentDepartmentId = null;
        if (content) {
            content.innerHTML = '';
        }
    }

    async function loadDepartmentDetails(departmentId) {
        try {
            const url = `${baseUrl}${baseUrl.endsWith('/') ? '' : '/'}departments/${departmentId}`;
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (result.status !== 'success') {
                throw new Error(result.message || 'Failed to load department details');
            }

            const department = result.data || {};
            if (!department || (!department.department_id && !department.id)) {
                throw new Error('Invalid department data received');
            }

            populateContent(department);
        } catch (error) {
            console.error('Error loading department details:', error);
            if (content) {
                content.innerHTML = `
                    <div style="text-align: center; padding: 3rem; color: #b91c1c;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <p>${escapeHtml(error.message || 'Failed to load department details')}</p>
                        <button type="button" class="btn btn-secondary" onclick="closeViewDepartmentModal()" style="margin-top: 1rem;">Close</button>
                    </div>
                `;
            }
        }
    }

    function populateContent(dept) {
        if (!content) return;

        const deptId = dept.department_id || dept.id || 'N/A';
        const name = dept.name || 'Unnamed Department';
        const code = dept.code || '-';
        const type = dept.type || '-';
        const floor = dept.floor || '-';
        const description = dept.description || 'No description available';
        const status = dept.status || 'Active';
        const statusClass = status.toLowerCase() === 'active' ? 'badge-success' : 'badge-danger';
        const headId = dept.department_head_id || null;
        
        // Display department head name if available
        let headName = 'Not assigned';
        if (headId) {
            if (dept.department_head_name) {
                headName = dept.department_head_name;
                if (dept.department_head_position) {
                    headName += ' - ' + dept.department_head_position;
                }
            } else {
                // Fallback to ID if name not available
                headName = 'Assigned (ID: ' + headId + ')';
            }
        }
        
        const createdAt = dept.created_at ? new Date(dept.created_at).toLocaleDateString() : '-';
        const updatedAt = dept.updated_at ? new Date(dept.updated_at).toLocaleDateString() : '-';

        content.innerHTML = `
            <div class="detail-section" style="margin-bottom: 1.5rem;">
                <h4 style="margin-bottom: 1rem; color: #1e293b; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-info-circle" style="color: #4f46e5;"></i>
                    Department Information
                </h4>
                <div class="detail-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <div class="detail-item">
                        <label style="font-weight: 600; color: #64748b; display: block; margin-bottom: 0.25rem;">Department ID:</label>
                        <span style="color: #1e293b;">${escapeHtml(deptId)}</span>
                    </div>
                    <div class="detail-item">
                        <label style="font-weight: 600; color: #64748b; display: block; margin-bottom: 0.25rem;">Name:</label>
                        <span style="color: #1e293b; font-weight: 500;">${escapeHtml(name)}</span>
                    </div>
                    <div class="detail-item">
                        <label style="font-weight: 600; color: #64748b; display: block; margin-bottom: 0.25rem;">Code:</label>
                        <span style="color: #1e293b;">${escapeHtml(code)}</span>
                    </div>
                    <div class="detail-item">
                        <label style="font-weight: 600; color: #64748b; display: block; margin-bottom: 0.25rem;">Type:</label>
                        <span style="color: #1e293b;">${escapeHtml(type)}</span>
                    </div>
                    <div class="detail-item">
                        <label style="font-weight: 600; color: #64748b; display: block; margin-bottom: 0.25rem;">Floor:</label>
                        <span style="color: #1e293b;">${escapeHtml(floor)}</span>
                    </div>
                    <div class="detail-item">
                        <label style="font-weight: 600; color: #64748b; display: block; margin-bottom: 0.25rem;">Status:</label>
                        <span class="badge ${statusClass}">${escapeHtml(status)}</span>
                    </div>
                    <div class="detail-item">
                        <label style="font-weight: 600; color: #64748b; display: block; margin-bottom: 0.25rem;">Department Head:</label>
                        <span style="color: #1e293b;">${escapeHtml(headName)}</span>
                    </div>
                    <div class="detail-item">
                        <label style="font-weight: 600; color: #64748b; display: block; margin-bottom: 0.25rem;">Created At:</label>
                        <span style="color: #1e293b;">${escapeHtml(createdAt)}</span>
                    </div>
                    <div class="detail-item">
                        <label style="font-weight: 600; color: #64748b; display: block; margin-bottom: 0.25rem;">Updated At:</label>
                        <span style="color: #1e293b;">${escapeHtml(updatedAt)}</span>
                    </div>
                </div>
            </div>
            <div class="detail-section">
                <h4 style="margin-bottom: 1rem; color: #1e293b; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-align-left" style="color: #4f46e5;"></i>
                    Description
                </h4>
                <div style="background: #f8fafc; padding: 1rem; border-radius: 6px; color: #475569; line-height: 1.6;">
                    ${escapeHtml(description)}
                </div>
            </div>
        `;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Export to global scope
    window.ViewDepartmentModal = { init, open, close };
    window.closeViewDepartmentModal = close;

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
