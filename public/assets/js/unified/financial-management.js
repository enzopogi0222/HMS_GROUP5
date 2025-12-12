/**
 * Financial Management - Main Controller
 */
(function() {
    const baseUrl = document.querySelector('meta[name="base-url"]')?.content?.replace(/\/+$/, '') || '';
    const utils = new BillingModalUtils(baseUrl);
    const tableBody = document.getElementById('financialTableBody');

    // Notification functions
    window.showFinancialNotification = function(message, type = 'success') {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => showFinancialNotification(message, type));
            return;
        }

        let container = document.getElementById('financialNotification');
        let iconEl = document.getElementById('financialNotificationIcon');
        let textEl = document.getElementById('financialNotificationText');
        
        // If container doesn't exist, create it
        if (!container) {
            const mainContainer = document.querySelector('.main-container');
            
            container = document.createElement('div');
            container.id = 'financialNotification';
            container.setAttribute('role', 'alert');
            container.setAttribute('aria-live', 'polite');
            container.style.cssText = 'display: none; margin: 0.75rem auto 0 auto; padding: 0.75rem 1rem; max-width: 1180px; border-radius: 6px; align-items: center; gap: 0.5rem; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.15); font-size: 0.95rem; font-weight: 500; position: relative; z-index: 1000;';
            
            iconEl = document.createElement('i');
            iconEl.id = 'financialNotificationIcon';
            iconEl.setAttribute('aria-hidden', 'true');
            iconEl.style.cssText = 'font-size: 1.1rem; flex-shrink: 0;';
            
            textEl = document.createElement('span');
            textEl.id = 'financialNotificationText';
            textEl.style.cssText = 'flex: 1;';
            
            const closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.setAttribute('aria-label', 'Dismiss notification');
            closeBtn.onclick = dismissFinancialNotification;
            closeBtn.style.cssText = 'margin-left:auto; background:transparent; border:none; cursor:pointer; color:inherit; padding: 0.25rem; flex-shrink: 0;';
            closeBtn.innerHTML = '<i class="fas fa-times" style="font-size: 0.9rem;"></i>';
            
            container.appendChild(iconEl);
            container.appendChild(textEl);
            container.appendChild(closeBtn);
            
            if (mainContainer && mainContainer.parentNode) {
                mainContainer.parentNode.insertBefore(container, mainContainer);
            } else {
                document.body.insertBefore(container, document.body.firstChild);
            }
        }
        
        // If icon or text elements don't exist, create them
        if (!iconEl) {
            iconEl = document.createElement('i');
            iconEl.id = 'financialNotificationIcon';
            iconEl.setAttribute('aria-hidden', 'true');
            iconEl.style.cssText = 'font-size: 1.1rem; flex-shrink: 0;';
            container.insertBefore(iconEl, container.firstChild);
        }
        
        if (!textEl) {
            textEl = document.createElement('span');
            textEl.id = 'financialNotificationText';
            textEl.style.cssText = 'flex: 1;';
            if (iconEl.nextSibling) {
                container.insertBefore(textEl, iconEl.nextSibling);
            } else {
                container.appendChild(textEl);
            }
        }

        const isError = type === 'error' || type === 'warning';

        // Set styling based on type
        container.style.border = isError ? '1px solid #fecaca' : '1px solid #86efac';
        container.style.background = isError ? '#fee2e2' : '#dcfce7';
        container.style.color = isError ? '#991b1b' : '#166534';
        container.style.display = 'flex';

        // Set icon
        iconEl.className = 'fas ' + (isError ? 'fa-exclamation-triangle' : 'fa-check-circle');
        textEl.textContent = String(message || '');

        // Scroll to top to show notification
        window.scrollTo({ top: 0, behavior: 'smooth' });

        // Clear existing timeout
        if (window.financialNotificationTimeout) {
            clearTimeout(window.financialNotificationTimeout);
        }

        // Auto-hide after 5 seconds
        window.financialNotificationTimeout = setTimeout(dismissFinancialNotification, 5000);
    };

    window.dismissFinancialNotification = function() {
        const container = document.getElementById('financialNotification');
        if (container) {
            container.style.display = 'none';
        }
    };

    function handleTableClick(event) {
        const btn = event.target.closest('button[data-action]');
        if (!btn) return;

        const action = btn.dataset.action;
        const billingId = btn.dataset.billingId;

        if (action === 'view') {
            const patientName = btn.dataset.patientName;
            if (window.ViewBillingAccountModal && window.ViewBillingAccountModal.open) {
                window.ViewBillingAccountModal.open(billingId, patientName);
            }
        } else if (action === 'mark-paid') {
            markBillingAccountPaid(billingId);
        } else if (action === 'delete') {
            deleteBillingAccount(billingId);
        }
    }

    function markBillingAccountPaid(billingId) {
        if (!billingId || !confirm('Mark this billing account as PAID?')) return;

        fetch(`${baseUrl}/financial/billing-accounts/${billingId}/paid`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ billing_id: billingId })
        })
            .then(resp => resp.json())
            .then(result => {
                const ok = result && (result.success === true || result.status === 'success');
                utils.showNotification(
                    result.message || (ok ? 'Billing account marked as paid.' : 'Failed to mark billing account as paid.'),
                    ok ? 'success' : 'error'
                );
                if (ok) window.location.reload();
            })
            .catch(err => {
                console.error('Failed to mark billing account paid', err);
                utils.showNotification('Failed to mark billing account as paid.', 'error');
            });
    }

    function deleteBillingAccount(billingId) {
        if (!billingId || !confirm('Delete this billing account and all its items? This action cannot be undone.')) return;

        fetch(`${baseUrl}/financial/billing-accounts/${billingId}/delete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ billing_id: billingId })
        })
            .then(resp => resp.json())
            .then(result => {
                const ok = result && (result.success === true || result.status === 'success');
                utils.showNotification(
                    result.message || (ok ? 'Billing account deleted successfully.' : 'Failed to delete billing account.'),
                    ok ? 'success' : 'error'
                );
                if (ok) window.location.reload();
            })
            .catch(err => {
                console.error('Failed to delete billing account', err);
                utils.showNotification('Failed to delete billing account.', 'error');
            });
    }

    // Check for URL parameters to show notification (e.g., when coming from appointments page)
    function checkUrlParams() {
        const urlParams = new URLSearchParams(window.location.search);
        const success = urlParams.get('billing_added');
        const error = urlParams.get('billing_error');
        
        if (success === 'true') {
            showFinancialNotification('Appointment successfully added to billing account!', 'success');
            // Clean URL
            window.history.replaceState({}, document.title, window.location.pathname);
        } else if (error) {
            showFinancialNotification(decodeURIComponent(error), 'error');
            // Clean URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    }

    // Initialize
    if (tableBody) {
        tableBody.addEventListener('click', handleTableClick);
    }
    
    // Check URL params on page load
    document.addEventListener('DOMContentLoaded', checkUrlParams);
})();
