/**
 * Add Department Modal
 */
(function() {
    const baseUrl = document.querySelector('meta[name="base-url"]')?.content?.replace(/\/+$/, '') || '';
    const utils = new DepartmentModalUtils(baseUrl);
    const modalId = 'addDepartmentModal';
    const formId = 'addDepartmentForm';

    const modal = document.getElementById(modalId);
    const form = document.getElementById(formId);
    const submitBtn = document.getElementById('saveDepartmentBtn');

    function init() {
        if (!modal || !form) return;

        utils.setupModalCloseHandlers(modalId);
        form.addEventListener('submit', handleSubmit);
    }

    function open() {
        if (!modal || !form) return;

        form.reset();
        utils.clearErrors('err_');
        utils.open(modalId);
    }

    function close() {
        utils.close(modalId);
    }

    async function handleSubmit(e) {
        e.preventDefault();
        if (!form) return;

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        }

        try {
            const formData = new FormData(form);
            const payload = Object.fromEntries(formData.entries());

            const response = await fetch(`${baseUrl}/departments/create`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });

            const result = await response.json().catch(() => ({ status: 'error', message: 'Invalid response' }));

            if (response.ok && result.status === 'success') {
                utils.showNotification('Department created successfully', 'success');
                close();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                const message = result.message || 'Failed to create department';
                if (result.errors) {
                    utils.displayErrors(result.errors, 'err_');
                } else {
                    utils.showNotification(message, 'error');
                }
            }
        } catch (error) {
            console.error('Failed to create department', error);
            utils.showNotification('Server error while creating department', 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Department';
            }
        }
    }

    // Export to global scope
    window.AddDepartmentModal = { init, open, close };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

