/**
 * Edit Lab Order Modal
 */
(function() {
    const baseUrl = document.querySelector('meta[name="base-url"]').content.replace(/\/$/, '') + '/';
    const utils = new LabModalUtils(baseUrl);
    const modalId = 'editLabOrderModal';
    const formId = 'editLabOrderForm';

    const modal = document.getElementById(modalId);
    const form = document.getElementById(formId);
    const submitBtn = document.getElementById('editLabOrderSubmitBtn');
    const orderIdInput = document.getElementById('editLabOrderId');
    const patientSelect = document.getElementById('editLabPatientSelect');
    const testSelect = document.getElementById('editLabTestSelect');
    const prioritySelect = document.getElementById('editLabPriority');

    function init() {
        if (!modal || !form) return;

        utils.setupModalCloseHandlers(modalId);

        if (submitBtn) {
            submitBtn.addEventListener('click', handleSubmit);
        }
    }

    function open(labOrderId) {
        if (!modal || !form) return;

        form.reset();
        utils.open(modalId);
        utils.loadPatients('editLabPatientSelect');
        utils.loadLabTests('editLabTestSelect');
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
                if (orderIdInput) orderIdInput.value = order.lab_order_id;
                if (patientSelect) patientSelect.value = order.patient_id;
                if (testSelect) testSelect.value = order.test_code;
                if (prioritySelect) prioritySelect.value = order.priority || 'routine';
            } else {
                if (typeof showUniversalNotification === 'function') {
                    showUniversalNotification(data.message || 'Failed to load lab order', 'error');
                }
                close();
            }
        } catch (e) {
            console.error('Failed to load lab order', e);
            if (typeof showUniversalNotification === 'function') {
                showUniversalNotification('Failed to load lab order', 'error');
            }
            close();
        }
    }

    async function handleSubmit() {
        if (!form || !orderIdInput || !patientSelect || !testSelect) return;

        const labOrderId = orderIdInput.value;
        const patientId = patientSelect.value;
        const testCode = testSelect.value;
        const priority = prioritySelect ? prioritySelect.value : 'routine';

        if (!patientId || !testCode) {
            if (typeof showUniversalNotification === 'function') {
                showUniversalNotification('Patient and Lab Test are required.', 'error');
            }
            return;
        }

        const selectedOption = testSelect.options[testSelect.selectedIndex];
        const testName = selectedOption?.dataset?.testName || selectedOption?.textContent || null;

        const payload = {
            lab_order_id: parseInt(labOrderId, 10),
            patient_id: parseInt(patientId, 10),
            test_code: testCode,
            test_name: testName,
            priority: priority
        };

        try {
            const res = await fetch(baseUrl + 'labs/update', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
                credentials: 'same-origin'
            });
            const data = await res.json();

            if (typeof showUniversalNotification === 'function') {
                showUniversalNotification(data.message || (data.success ? 'Lab order updated' : 'Failed to update lab order'), data.success ? 'success' : 'error');
            }
            if (data.success) {
                close();
                if (window.LabUI && window.LabUI.refresh) {
                    window.LabUI.refresh();
                }
            }
        } catch (e) {
            console.error('Failed to update lab order', e);
            if (typeof showUniversalNotification === 'function') {
                showUniversalNotification('Failed to update lab order', 'error');
            }
        }
    }

    // Export to global scope
    window.EditLabOrderModal = { init, open, close };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

