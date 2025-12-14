/**
 * View Lab Order Modal
 */
(function() {
    const baseUrl = document.querySelector('meta[name="base-url"]').content.replace(/\/$/, '') + '/';
    const utils = new LabModalUtils(baseUrl);
    const modalId = 'viewLabOrderModal';

    const modal = document.getElementById(modalId);
    const orderIdEl = document.getElementById('viewLabOrderId');
    const patientEl = document.getElementById('viewLabPatient');
    const testEl = document.getElementById('viewLabTest');
    const priorityEl = document.getElementById('viewLabPriority');
    const statusEl = document.getElementById('viewLabStatus');
    const orderedAtEl = document.getElementById('viewLabOrderedAt');
    const testCodeEl = document.getElementById('viewLabTestCode');

    function init() {
        if (!modal) return;
        utils.setupModalCloseHandlers(modalId);
    }

    function open(labOrderId) {
        if (!modal) return;

        utils.open(modalId);
        loadLabOrder(labOrderId);
    }

    function close() {
        utils.close(modalId);
    }

    async function loadLabOrder(labOrderId) {
        try {
            const res = await fetch(baseUrl + 'labs/' + labOrderId, { credentials: 'same-origin' });
            const data = await res.json();

            if (data.success && data.data) {
                const order = data.data;
                
                // Order Information
                if (orderIdEl) orderIdEl.textContent = order.lab_order_id || 'N/A';
                
                const orderedAt = order.ordered_at || order.created_at || 'N/A';
                if (orderedAtEl) {
                    if (orderedAt !== 'N/A') {
                        const date = new Date(orderedAt);
                        orderedAtEl.textContent = date.toLocaleString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                    } else {
                        orderedAtEl.textContent = 'N/A';
                    }
                }
                
                const priority = (order.priority || 'routine').charAt(0).toUpperCase() + (order.priority || 'routine').slice(1);
                if (priorityEl) {
                    priorityEl.textContent = priority;
                    priorityEl.className = 'detail-value priority-badge';
                    // Add priority-specific class
                    if (priority.toLowerCase() === 'urgent') {
                        priorityEl.style.background = '#fee2e2';
                        priorityEl.style.color = '#991b1b';
                    } else if (priority.toLowerCase() === 'stat') {
                        priorityEl.style.background = '#fef3c7';
                        priorityEl.style.color = '#92400e';
                    } else {
                        priorityEl.style.background = '#dbeafe';
                        priorityEl.style.color = '#1e40af';
                    }
                }
                
                const status = (order.status || 'ordered').replace('_', ' ');
                if (statusEl) {
                    statusEl.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                    statusEl.className = 'detail-value status-badge';
                    // Add status-specific styling
                    const statusLower = status.toLowerCase();
                    if (statusLower.includes('completed')) {
                        statusEl.style.background = '#d1fae5';
                        statusEl.style.color = '#065f46';
                    } else if (statusLower.includes('progress')) {
                        statusEl.style.background = '#fef3c7';
                        statusEl.style.color = '#92400e';
                    } else if (statusLower.includes('ordered')) {
                        statusEl.style.background = '#dbeafe';
                        statusEl.style.color = '#1e40af';
                    } else if (statusLower.includes('cancelled')) {
                        statusEl.style.background = '#fee2e2';
                        statusEl.style.color = '#991b1b';
                    }
                }
                
                // Patient Information
                if (patientEl) patientEl.textContent = order.patient_name || 'N/A';
                
                // Test Information
                const testName = order.test_name || order.test_code || 'N/A';
                if (testEl) testEl.textContent = testName;
                
                // Show test code if available and different from test name
                const testCodeEl = document.getElementById('viewLabTestCode');
                const testCodeContainer = document.getElementById('viewLabTestCodeContainer');
                if (order.test_code && order.test_name && order.test_code !== order.test_name) {
                    if (testCodeEl) testCodeEl.textContent = order.test_code;
                    if (testCodeContainer) testCodeContainer.style.display = 'block';
                } else {
                    if (testCodeContainer) testCodeContainer.style.display = 'none';
                }
            } else {
                alert(data.message || 'Failed to load lab order');
                close();
            }
        } catch (e) {
            console.error('Failed to load lab order', e);
            alert('Failed to load lab order');
            close();
        }
    }

    // Export to global scope
    window.ViewLabOrderModal = { init, open, close };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

