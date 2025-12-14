/**
 * Lab Management - Main Controller
 */
(function() {
    const baseUrl = document.querySelector('meta[name="base-url"]').content.replace(/\/$/, '') + '/';
    const userRole = (document.querySelector('meta[name="user-role"]')?.content || '').toLowerCase();

    const tableBody = document.getElementById('labOrdersTableBody');
    const statusFilter = document.getElementById('labStatusFilter');
    const dateFilter = document.getElementById('labDateFilter');
    const searchInput = document.getElementById('labSearch');
    const refreshBtn = document.getElementById('labRefreshBtn');
    const createBtn = document.getElementById('createLabOrderBtn');

    const totalTodayEl = document.getElementById('labTotalToday');
    const inProgressEl = document.getElementById('labInProgress');
    const completedEl = document.getElementById('labCompleted');

    // Lab tests admin section
    const labTestForm = document.getElementById('labTestForm');
    const labTestIdInput = document.getElementById('labTestId');
    const labTestCodeInput = document.getElementById('labTestCode');
    const labTestNameInput = document.getElementById('labTestName');
    const labTestPriceInput = document.getElementById('labTestPrice');
    const labTestCategoryInput = document.getElementById('labTestCategory');
    const labTestStatusSelect = document.getElementById('labTestStatus');
    const labTestSaveBtn = document.getElementById('labTestSaveBtn');
    const labTestResetBtn = document.getElementById('labTestResetBtn');
    const labTestsTableBody = document.getElementById('labTestsTableBody');

    async function fetchLabOrders() {
        try {
            if (!tableBody) return;

            const params = new URLSearchParams();
            if (statusFilter?.value) params.append('status', statusFilter.value);
            if (dateFilter?.value) params.append('date', dateFilter.value);
            if (searchInput?.value) params.append('search', searchInput.value);

            const res = await fetch(baseUrl + 'labs/api?' + params.toString(), { credentials: 'same-origin' });
            const data = await res.json();

            renderLabOrders(Array.isArray(data) ? data : []);
        } catch (e) {
            console.error('Failed to load lab orders', e);
            if (tableBody) {
                tableBody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:1.5rem; color:#b91c1c;">Failed to load lab orders.</td></tr>';
            }
        }
    }

    function renderLabOrders(orders) {
        if (!tableBody) return;

        if (!orders.length) {
            tableBody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:1.5rem; color:#6b7280;">No lab orders found.</td></tr>';
            if (totalTodayEl) totalTodayEl.textContent = '0';
            if (inProgressEl) inProgressEl.textContent = '0';
            if (completedEl) completedEl.textContent = '0';
            return;
        }

        let totalToday = 0, inProgress = 0, completed = 0;
        const todayStr = new Date().toISOString().split('T')[0];

        const rows = orders.map(order => {
            const orderedAt = order.ordered_at || order.created_at || '';
            const orderedDate = orderedAt.substring(0, 10);
            if (orderedDate === todayStr) totalToday++;
            if (order.status === 'in_progress' || order.status === 'ordered') inProgress++;
            if (order.status === 'completed') completed++;

            const patientName = order.patient_name || `${order.first_name || ''} ${order.last_name || ''}`.trim() || 'Unknown';
            const testLabel = order.test_name || order.test_code || 'N/A';
            const priority = order.priority || 'routine';
            // Use actual status from database, don't auto-change to 'in_progress'
            const status = order.status || 'ordered';

            let badgeClass = 'badge-info';
            if (status === 'completed') badgeClass = 'badge-success';
            else if (status === 'in_progress') badgeClass = 'badge-warning';
            else if (status === 'cancelled') badgeClass = 'badge-danger';
            else if (status === 'ordered') badgeClass = 'badge-secondary';

            const canAct = ['admin', 'doctor', 'laboratorist', 'it_staff'].includes(userRole);
            const canEdit = ['admin', 'doctor', 'it_staff'].includes(userRole);
            const canComplete = ['admin', 'laboratorist'].includes(userRole); // Admin and lab staff can complete lab orders
            const canDelete = userRole === 'admin';
            const labOrderId = order.lab_order_id;
            const currentStatus = order.status || 'ordered';
            const statusLower = currentStatus.toLowerCase();
            
            const actions = [];
            
            // View button - always available
            if (window.ViewLabOrderModal && window.ViewLabOrderModal.open) {
                actions.push(`<button class="btn btn-primary" style="padding:0.3rem 0.6rem;font-size:0.75rem;" onclick="LabUI.viewOrder(${labOrderId})" title="View Details"><i class="fas fa-eye"></i> View</button>`);
            }
            
            // Edit button - for admin, doctor, it_staff
            if (canEdit && window.EditLabOrderModal && window.EditLabOrderModal.open && statusLower !== 'completed' && statusLower !== 'cancelled') {
                actions.push(`<button class="btn btn-warning" style="padding:0.3rem 0.6rem;font-size:0.75rem;" onclick="LabUI.editOrder(${labOrderId})" title="Edit Lab Order"><i class="fas fa-edit"></i> Edit</button>`);
            }
            
            // Start Processing button - for admin and laboratorist when status is 'ordered'
            if (canComplete && statusLower === 'ordered') {
                actions.push(`<button class="btn btn-info" style="padding:0.3rem 0.6rem;font-size:0.75rem;" onclick="LabUI.updateStatus(${labOrderId}, 'in_progress')" title="Start Processing"><i class="fas fa-play"></i> Start</button>`);
            }
            
            // Complete button - for admin and laboratorist when status is 'in_progress'
            if (canComplete && statusLower === 'in_progress') {
                actions.push(`<button class="btn btn-success" style="padding:0.3rem 0.6rem;font-size:0.75rem;" onclick="LabUI.updateStatus(${labOrderId}, 'completed')" title="Mark as Completed"><i class="fas fa-check"></i> Complete</button>`);
            }
            
            // Cancel button - for non-completed, non-cancelled orders
            if (canAct && statusLower !== 'completed' && statusLower !== 'cancelled') {
                actions.push(`<button class="btn btn-danger" style="padding:0.3rem 0.6rem;font-size:0.75rem;" onclick="LabUI.updateStatus(${labOrderId}, 'cancelled')" title="Cancel Lab Order"><i class="fas fa-times"></i> Cancel</button>`);
            }
            
            // Delete button - admin only
            if (canDelete) {
                actions.push(`<button class="btn btn-danger" style="padding:0.3rem 0.6rem;font-size:0.75rem;" onclick="LabUI.deleteOrder(${labOrderId})" title="Delete Lab Order"><i class="fas fa-trash"></i> Delete</button>`);
            }

            return `<tr>
                <td>${escapeHtml(orderedAt)}</td>
                <td>${escapeHtml(patientName)}</td>
                <td>${escapeHtml(testLabel)}</td>
                <td>${escapeHtml(priority.charAt(0).toUpperCase() + priority.slice(1))}</td>
                <td><span class="badge ${badgeClass}">${escapeHtml(status.replace('_', ' '))}</span></td>
                <td><div style="display:flex;gap:0.25rem;flex-wrap:wrap;">${actions.join(' ')}</div></td>
            </tr>`;
        });

        tableBody.innerHTML = rows.join('');
        if (totalTodayEl) totalTodayEl.textContent = String(totalToday);
        if (inProgressEl) inProgressEl.textContent = String(inProgress);
        if (completedEl) completedEl.textContent = String(completed);
    }

    async function updateStatus(labOrderId, status) {
        if (!labOrderId || !status) return;

        // Special handling for 'in_progress' status
        if (status === 'in_progress') {
            const confirmText = `Start lab test #${labOrderId}? For outpatients, payment must be verified before starting.`;
            if (!window.confirm(confirmText)) return;
        } else {
            const confirmText = `Change status of lab order #${labOrderId} to ${status.replace('_', ' ')}?`;
            if (!window.confirm(confirmText)) return;
        }

        try {
            const res = await fetch(baseUrl + 'labs/' + labOrderId + '/status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status }),
                credentials: 'same-origin'
            });
            const data = await res.json();

            // Show error message if payment is required
            if (!data.success && data.requires_payment) {
                alert(data.message || 'Payment required before starting lab test for outpatients.');
                if (data.billing_id) {
                    const goToBilling = window.confirm('Would you like to go to Financial Management to process payment?');
                    if (goToBilling) {
                        // Navigate to financial management if available
                        const financialUrl = baseUrl + 'financial-management';
                        window.location.href = financialUrl;
                    }
                }
            } else {
                alert(data.message || (data.success ? 'Status updated' : 'Failed to update status'));
            }
            
            if (data.success) {
                fetchLabOrders();
            }
        } catch (e) {
            console.error('Failed to update lab status', e);
            alert('Failed to update status');
        }
    }

    // Lab Tests Admin Functions
    async function fetchLabTestsForAdmin() {
        if (!labTestsTableBody || userRole !== 'admin') return;

        try {
            labTestsTableBody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:1.5rem; color:#6b7280;">Loading lab tests...</td></tr>';

            const res = await fetch(baseUrl + 'labs/tests', { credentials: 'same-origin' });
            const data = await res.json();

            if (data.status !== 'success' || !Array.isArray(data.data) || !data.data.length) {
                labTestsTableBody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:1.5rem; color:#6b7280;">No lab tests found.</td></tr>';
                return;
            }

            const rows = data.data.map(test => {
                const statusBadge = test.status === 'inactive'
                    ? '<span class="badge badge-danger">Inactive</span>'
                    : '<span class="badge badge-success">Active</span>';

                return `<tr data-test-id="${test.lab_test_id}">
                    <td>${escapeHtml(test.test_code)}</td>
                    <td>${escapeHtml(test.test_name)}</td>
                    <td>${escapeHtml(test.category || '')}</td>
                    <td>${escapeHtml(test.default_price)}</td>
                    <td>${statusBadge}</td>
                    <td>
                        <div style="display:flex;gap:0.25rem;flex-wrap:wrap;">
                            <button class="btn btn-warning" style="padding:0.3rem 0.6rem;font-size:0.75rem;" data-action="edit-test" data-id="${test.lab_test_id}">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-danger" style="padding:0.3rem 0.6rem;font-size:0.75rem;" data-action="delete-test" data-id="${test.lab_test_id}">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </td>
                </tr>`;
            });

            labTestsTableBody.innerHTML = rows.join('');
        } catch (e) {
            console.error('Failed to load lab tests', e);
            labTestsTableBody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:1.5rem; color:#b91c1c;">Failed to load lab tests.</td></tr>';
        }
    }

    function resetLabTestForm() {
        if (!labTestForm) return;
        labTestForm.reset();
        if (labTestIdInput) labTestIdInput.value = '';
        if (labTestStatusSelect) labTestStatusSelect.value = 'active';
    }

    function populateLabTestFormFromRow(row) {
        if (!row || !labTestForm) return;

        const id = row.getAttribute('data-test-id');
        const cells = row.querySelectorAll('td');
        if (!cells.length) return;

        if (labTestIdInput) labTestIdInput.value = id || '';
        if (labTestCodeInput) labTestCodeInput.value = cells[0].textContent.trim();
        if (labTestNameInput) labTestNameInput.value = cells[1].textContent.trim();
        if (labTestCategoryInput) labTestCategoryInput.value = cells[2].textContent.trim();
        if (labTestPriceInput) labTestPriceInput.value = cells[3].textContent.trim();

        if (labTestStatusSelect) {
            const statusSpan = cells[4].querySelector('.badge-danger');
            labTestStatusSelect.value = statusSpan ? 'inactive' : 'active';
        }
    }

    async function saveLabTest() {
        if (!labTestForm) return;

        const isEdit = !!(labTestIdInput && labTestIdInput.value);
        const url = isEdit ? baseUrl + 'labs/tests/' + labTestIdInput.value : baseUrl + 'labs/tests';

        const payload = {
            test_code: labTestCodeInput?.value?.trim() || '',
            test_name: labTestNameInput?.value?.trim() || '',
            default_price: labTestPriceInput?.value || 0,
            category: labTestCategoryInput?.value?.trim() || null,
            status: labTestStatusSelect?.value || 'active',
        };

        if (!payload.test_code || !payload.test_name) {
            alert('Test code and name are required.');
            return;
        }

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
                credentials: 'same-origin',
            });
            const data = await res.json();

            alert(data.message || (data.status === 'success' ? 'Saved' : 'Failed to save'));
            if (data.status === 'success') {
                resetLabTestForm();
                fetchLabTestsForAdmin();
            }
        } catch (e) {
            console.error('Failed to save lab test', e);
            alert('Failed to save lab test');
        }
    }

    async function deleteLabTest(id) {
        if (!id) return;
        if (!window.confirm('Delete this lab test?')) return;

        try {
            const res = await fetch(baseUrl + 'labs/tests/' + id, {
                method: 'DELETE',
                credentials: 'same-origin',
            });
            const data = await res.json();

            alert(data.message || (data.status === 'success' ? 'Deleted' : 'Failed to delete'));
            if (data.status === 'success') {
                fetchLabTestsForAdmin();
            }
        } catch (e) {
            console.error('Failed to delete lab test', e);
            alert('Failed to delete lab test');
        }
    }

    function escapeHtml(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function initTabs() {
        const tabButtons = document.querySelectorAll('.lab-tab-button');
        const tabContents = document.querySelectorAll('.lab-tab-content');

        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                const targetTab = button.getAttribute('data-tab');

                // Remove active class from all buttons and contents
                tabButtons.forEach(btn => {
                    btn.classList.remove('active');
                    btn.setAttribute('aria-selected', 'false');
                });
                tabContents.forEach(content => {
                    content.classList.remove('active');
                });

                // Add active class to clicked button and corresponding content
                button.classList.add('active');
                button.setAttribute('aria-selected', 'true');

                // Map tab names to content IDs
                const tabIdMap = {
                    'lab-orders': 'tabLabOrders',
                    'lab-master-list': 'tabLabMasterList'
                };

                const targetContentId = tabIdMap[targetTab];
                if (targetContentId) {
                    const targetContent = document.getElementById(targetContentId);
                    if (targetContent) {
                        targetContent.classList.add('active');
                        
                        // Load lab tests when Lab Master List tab is activated
                        if (targetTab === 'lab-master-list' && userRole === 'admin') {
                            fetchLabTestsForAdmin();
                        }
                    }
                }
            });
        });
    }

    function initEvents() {
        // Initialize tabs
        initTabs();

        if (statusFilter) statusFilter.addEventListener('change', fetchLabOrders);
        if (dateFilter) dateFilter.addEventListener('change', fetchLabOrders);
        if (searchInput) searchInput.addEventListener('keyup', (e) => { if (e.key === 'Enter') fetchLabOrders(); });
        if (refreshBtn) refreshBtn.addEventListener('click', fetchLabOrders);
        if (createBtn) createBtn.addEventListener('click', () => {
            if (window.AddLabOrderModal && window.AddLabOrderModal.open) {
                window.AddLabOrderModal.open();
            }
        });

        // Lab tests admin
        if (labTestSaveBtn && userRole === 'admin') {
            labTestSaveBtn.addEventListener('click', saveLabTest);
        }
        if (labTestResetBtn && userRole === 'admin') {
            labTestResetBtn.addEventListener('click', resetLabTestForm);
        }
        if (labTestsTableBody && userRole === 'admin') {
            labTestsTableBody.addEventListener('click', (e) => {
                const target = e.target.closest('button[data-action]');
                if (!target) return;

                const id = target.getAttribute('data-id');
                const action = target.getAttribute('data-action');
                const row = target.closest('tr');

                if (action === 'edit-test') {
                    populateLabTestFormFromRow(row);
                } else if (action === 'delete-test') {
                    deleteLabTest(id);
                }
            });
        }
    }

    async function viewOrder(labOrderId) {
        if (!labOrderId) return;
        if (window.ViewLabOrderModal && window.ViewLabOrderModal.open) {
            window.ViewLabOrderModal.open(labOrderId);
        }
    }

    async function editOrder(labOrderId) {
        if (!labOrderId) return;
        if (window.EditLabOrderModal && window.EditLabOrderModal.open) {
            window.EditLabOrderModal.open(labOrderId);
        }
    }

    async function addToBilling(labOrderId) {
        if (!labOrderId) return;
        
        const order = await fetch(baseUrl + 'labs/' + labOrderId, { credentials: 'same-origin' })
            .then(res => res.json())
            .catch(() => null);
        
        if (!order || !order.success || !order.data) {
            alert('Failed to load lab order details');
            return;
        }

        const testName = order.data.test_name || order.data.test_code || 'Lab Test';
        const defaultPrice = prompt(`Enter price for ${testName}:`, '500.00');
        
        if (defaultPrice === null) return; // User cancelled
        
        const price = parseFloat(defaultPrice);
        if (isNaN(price) || price <= 0) {
            alert('Please enter a valid price');
            return;
        }

        try {
            const res = await fetch(baseUrl + 'labs/' + labOrderId + '/billing', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ unit_price: price }),
                credentials: 'same-origin'
            });
            const data = await res.json();
            
            alert(data.message || (data.success ? 'Lab order added to billing successfully' : 'Failed to add to billing'));
            if (data.success) {
                fetchLabOrders();
            }
        } catch (e) {
            console.error('Failed to add lab order to billing', e);
            alert('Failed to add lab order to billing');
        }
    }

    async function deleteOrder(labOrderId) {
        if (!labOrderId) return;
        
        if (!window.confirm(`Are you sure you want to delete lab order #${labOrderId}? This action cannot be undone.`)) {
            return;
        }

        try {
            const res = await fetch(baseUrl + 'labs/' + labOrderId, {
                method: 'DELETE',
                credentials: 'same-origin'
            });
            const data = await res.json();
            
            alert(data.message || (data.success ? 'Lab order deleted successfully' : 'Failed to delete lab order'));
            if (data.success) {
                fetchLabOrders();
            }
        } catch (e) {
            console.error('Failed to delete lab order', e);
            alert('Failed to delete lab order');
        }
    }

    window.LabUI = {
        refresh: fetchLabOrders,
        updateStatus,
        viewOrder,
        editOrder,
        addToBilling,
        deleteOrder,
    };

    document.addEventListener('DOMContentLoaded', function() {
        initEvents();
        fetchLabOrders();
        // Only load lab tests if user is admin and on initial load (will reload when tab is clicked)
        if (userRole === 'admin') {
            // Check if Lab Master List tab is active on page load
            const labMasterListTab = document.querySelector('.lab-tab-button[data-tab="lab-master-list"]');
            if (labMasterListTab && labMasterListTab.classList.contains('active')) {
                fetchLabTestsForAdmin();
            }
        }
    });
})();
