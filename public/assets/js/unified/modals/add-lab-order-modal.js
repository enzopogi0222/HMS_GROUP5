/**
 * Add Lab Order Modal
 */
(function() {
    const baseUrl = document.querySelector('meta[name="base-url"]').content.replace(/\/$/, '') + '/';
    const utils = new LabModalUtils(baseUrl);
    const modalId = 'addLabOrderModal';
    const formId = 'addLabOrderForm';

    const modal = document.getElementById(modalId);
    const form = document.getElementById(formId);
    const submitBtn = document.getElementById('addLabOrderSubmitBtn');
    const patientSelect = document.getElementById('addLabPatientSelect');
    const testSelect = document.getElementById('addLabTestSelect');
    const prioritySelect = document.getElementById('addLabPriority');

    function init() {
        if (!modal || !form) return;

        utils.setupModalCloseHandlers(modalId);

        if (submitBtn) {
            submitBtn.addEventListener('click', handleSubmit);
        }
    }

    function open() {
        if (!modal || !form) return;

        form.reset();
        if (prioritySelect) prioritySelect.value = 'routine';

        utils.open(modalId);
        utils.loadPatients('addLabPatientSelect');
        utils.loadLabTests('addLabTestSelect');
    }

    function close() {
        utils.close(modalId);
    }

    async function handleSubmit() {
        if (!form || !patientSelect || !testSelect) return;

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
            patient_id: parseInt(patientId, 10),
            test_code: testCode,
            test_name: testName,
            priority: priority
        };

        try {
            const res = await fetch(baseUrl + 'labs/create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
                credentials: 'same-origin'
            });
            const data = await res.json();

            if (typeof showUniversalNotification === 'function') {
                showUniversalNotification(data.message || (data.success ? 'Lab order created' : 'Failed to create lab order'), data.success ? 'success' : 'error');
            }
            if (data.success) {
                close();
                if (window.LabUI && window.LabUI.refresh) {
                    window.LabUI.refresh();
                }
            }
        } catch (e) {
            console.error('Failed to create lab order', e);
            if (typeof showUniversalNotification === 'function') {
                showUniversalNotification('Failed to create lab order', 'error');
            }
        }
    }

    // Export to global scope
    window.AddLabOrderModal = { init, open, close };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

