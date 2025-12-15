/**
 * Financial Management - Main Controller
 */
(function() {
    const baseUrl = document.querySelector('meta[name="base-url"]')?.content?.replace(/\/+$/, '') || '';
    const utils = new BillingModalUtils(baseUrl);
    const tableBody = document.getElementById('financialTableBody');

    // Transaction details modal helpers
    function openTransactionDetails(transactionId) {
        if (!transactionId) return;

        const modal = document.getElementById('transactionDetailsModal');
        const content = document.getElementById('transactionDetailsContent');
        if (!modal || !content) return;

        content.innerHTML = `
            <div class="loading-row">
                <i class="fas fa-spinner"></i> Loading transaction details...
            </div>
        `;

        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');

        fetch(`${baseUrl}/financial-management/transactions/${encodeURIComponent(transactionId)}`, {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin'
        })
            .then(resp => resp.json())
            .then(result => {
                if (!result.success || !result.data) {
                    content.innerHTML = `
                        <div class="loading-row">
                            <i class="fas fa-exclamation-triangle"></i>
                            ${escapeHtml(result.message || 'Failed to load transaction details.')}
                        </div>
                    `;
                    return;
                }

                const t = result.data;
                const isStockTransaction = ['stock_in', 'stock_out'].includes(t.type || '');

                const patientOrResource = isStockTransaction
                    ? (t.resource_name || 'N/A')
                    : (((t.patient_first_name || '') + ' ' + (t.patient_last_name || '')).trim() || (t.patient_id ? `Patient #${t.patient_id}` : 'N/A'));

                const amountQuantity = isStockTransaction
                    ? `${t.type === 'stock_out' ? '-' : '+'}${t.quantity || 0} unit(s)`
                    : `${t.type === 'expense' ? '-' : '+'}₱${parseFloat(t.amount || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')}`;

                const paymentMethod = isStockTransaction
                    ? 'N/A'
                    : ((t.payment_method || 'N/A').replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()));

                content.innerHTML = `
                    <div>
                        <strong>Transaction ID</strong>
                        <div>${escapeHtml(t.transaction_id || 'N/A')}</div>
                    </div>
                    <div>
                        <strong>Date & Time</strong>
                        <div>${escapeHtml(t.transaction_date || 'N/A')} ${t.transaction_time ? '(' + escapeHtml(t.transaction_time) + ')' : ''}</div>
                    </div>
                    <div>
                        <strong>Type</strong>
                        <div>${escapeHtml((t.type || 'N/A').replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()))}</div>
                    </div>
                    <div>
                        <strong>Patient / Resource</strong>
                        <div>${escapeHtml(patientOrResource)}</div>
                    </div>
                    <div>
                        <strong>Amount / Quantity</strong>
                        <div>${escapeHtml(amountQuantity)}</div>
                    </div>
                    <div>
                        <strong>Payment Method</strong>
                        <div>${escapeHtml(paymentMethod)}</div>
                    </div>
                    <div>
                        <strong>Status</strong>
                        <div>${escapeHtml((t.payment_status || 'Pending').charAt(0).toUpperCase() + (t.payment_status || 'Pending').slice(1))}</div>
                    </div>
                    <div>
                        <strong>Description</strong>
                        <div>${escapeHtml(t.description || 'N/A')}</div>
                    </div>
                `;
            })
            .catch(err => {
                console.error('Failed to load transaction details', err);
                content.innerHTML = `
                    <div class="loading-row">
                        <i class="fas fa-exclamation-triangle"></i>
                        Failed to load transaction details.
                    </div>
                `;
            });
    }

    // ==========================
    // Billing accounts filtering
    // ==========================

    function normaliseString(value) {
        return String(value || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/\p{Diacritic}+/gu, '');
    }

    function applyBillingFilters() {
        if (!tableBody) return;

        const dateInput = document.getElementById('dateFilter');
        const categoryInput = document.getElementById('categoryFilter');
        const searchInput = document.getElementById('searchFilter');

        const selectedDate = (dateInput && dateInput.value) ? dateInput.value : '';
        const selectedCategory = (categoryInput && categoryInput.value) ? categoryInput.value : '';
        const searchTerm = normaliseString(searchInput && searchInput.value ? searchInput.value : '');

        const rows = tableBody.querySelectorAll('tr');
        rows.forEach(row => {
            // Skip placeholder rows without billing data
            const billingIdCell = row.querySelector('td');
            if (!billingIdCell) return;

            const rowDate = row.getAttribute('data-created-at') || '';
            const rowCategory = row.getAttribute('data-category') || '';
            const rowPatientName = row.getAttribute('data-patient-name') || '';

            const matchesDate = !selectedDate || rowDate === selectedDate;
            const matchesCategory = !selectedCategory || rowCategory === selectedCategory;

            let matchesSearch = true;
            if (searchTerm) {
                const patient = normaliseString(rowPatientName);
                const billingIdText = normaliseString(billingIdCell.textContent || '');
                matchesSearch = patient.includes(searchTerm) || billingIdText.includes(searchTerm);
            }

            const visible = matchesDate && matchesCategory && matchesSearch;
            row.style.display = visible ? '' : 'none';
        });
    }

    function clearBillingFilters() {
        const dateInput = document.getElementById('dateFilter');
        const categoryInput = document.getElementById('categoryFilter');
        const searchInput = document.getElementById('searchFilter');

        if (dateInput) dateInput.value = '';
        if (categoryInput) categoryInput.value = '';
        if (searchInput) searchInput.value = '';

        applyBillingFilters();
    }

    window.closeTransactionDetailsModal = function() {
        const modal = document.getElementById('transactionDetailsModal');
        if (!modal) return;
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
    };

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

        // Get CSRF token if available
        const csrfTokenName = document.querySelector('meta[name="csrf-token-name"]')?.content || 'csrf_token';
        const csrfHash = document.querySelector('meta[name="csrf-token"]')?.content || '';
        
        const requestBody = { billing_id: billingId };
        if (csrfHash) {
            requestBody[csrfTokenName] = csrfHash;
        }

        fetch(`${baseUrl}/financial/billing-accounts/${billingId}/paid`, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify(requestBody)
        })
            .then(async resp => {
                // Try to parse JSON response regardless of status code
                let result;
                try {
                    const text = await resp.text();
                    result = text ? JSON.parse(text) : {};
                } catch (e) {
                    console.error('Failed to parse response:', e);
                    result = { success: false, message: `Server error: ${resp.status} ${resp.statusText}` };
                }
                
                // Check if the operation was actually successful (even if status code is 400)
                // Sometimes the backend returns 400 but the operation succeeds
                const ok = result && (result.success === true || result.status === 'success');
                
                // If we got a 400 but the operation succeeded, treat it as success
                if (!resp.ok && !ok) {
                    // Check if the message indicates it's already paid (which is actually a success case)
                    const message = (result.message || '').toLowerCase();
                    if (message.includes('already') || message.includes('already set')) {
                        // This is actually a success - the account is already paid
                        utils.showNotification(
                            'Billing account is already marked as paid.',
                            'success'
                        );
                        // Update CSRF token if provided
                        if (result.csrf && result.csrf.value) {
                            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                            if (csrfMeta) {
                                csrfMeta.setAttribute('content', result.csrf.value);
                            }
                        }
                        window.location.reload();
                        return;
                    }
                    
                    // Real error - show error message
                    utils.showNotification(
                        result.message || `Server error: ${resp.status} ${resp.statusText}`,
                        'error'
                    );
                    return;
                }
                
                // Success case
                utils.showNotification(
                    result.message || 'Billing account marked as paid.',
                    'success'
                );
                
                // Update CSRF token if provided
                if (result.csrf && result.csrf.value) {
                    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                    if (csrfMeta) {
                        csrfMeta.setAttribute('content', result.csrf.value);
                    }
                }
                
                // Reload to show updated status
                window.location.reload();
            })
            .catch(err => {
                console.error('Failed to mark billing account paid', err);
                utils.showNotification(
                    err.message || 'Failed to mark billing account as paid. Please try again.',
                    'error'
                );
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

    // Transaction filtering functionality
    let transactionFilters = {
        type: '',
        payment_status: '',
        date_from: '',
        date_to: '',
        search: ''
    };

    function loadTransactions(filters = {}) {
        const transactionsTableBody = document.getElementById('transactionsTableBody');
        if (!transactionsTableBody) return;

        // Build query string
        const params = new URLSearchParams();
        Object.keys(filters).forEach(key => {
            if (filters[key]) {
                params.append(key, filters[key]);
            }
        });

        fetch(`${baseUrl}/financial-management/transactions?${params.toString()}`, {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin'
        })
            .then(resp => resp.json())
            .then(result => {
                if (result.success && result.data) {
                    renderTransactions(result.data);
                } else {
                    transactionsTableBody.innerHTML = `
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 3rem; color: #6b7280;">
                                <i class="fas fa-exchange-alt" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem; display: block;"></i>
                                <p style="margin: 0.5rem 0; font-size: 1rem; font-weight: 500;">No transactions found</p>
                                <p style="margin: 0; font-size: 0.875rem;">${result.message || 'No transactions match your filters.'}</p>
                            </td>
                        </tr>
                    `;
                }
            })
            .catch(err => {
                console.error('Failed to load transactions', err);
                showFinancialNotification('Failed to load transactions.', 'error');
            });
    }

    function renderTransactions(transactions) {
        const transactionsTableBody = document.getElementById('transactionsTableBody');
        if (!transactionsTableBody) return;

        if (!transactions || transactions.length === 0) {
            transactionsTableBody.innerHTML = `
                <tr>
                    <td colspan="9" style="text-align: center; padding: 3rem; color: #6b7280;">
                        <i class="fas fa-exchange-alt" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem; display: block;"></i>
                        <p style="margin: 0.5rem 0; font-size: 1rem; font-weight: 500;">No transactions found</p>
                        <p style="margin: 0; font-size: 0.875rem;">No transactions match your filters.</p>
                    </td>
                </tr>
            `;
            return;
        }

        transactionsTableBody.innerHTML = transactions.map(transaction => {
            const isStockTransaction = ['stock_in', 'stock_out'].includes(transaction.type || '');
            
            // Determine what to show in Patient/Resource column
            let displayEntity = 'N/A';
            if (isStockTransaction && transaction.resource_name) {
                displayEntity = `<strong>${escapeHtml(transaction.resource_name)}</strong>`;
            } else {
                const patientName = (transaction.patient_first_name || '') + ' ' + (transaction.patient_last_name || '');
                displayEntity = patientName.trim() || (transaction.patient_id ? `Patient #${transaction.patient_id}` : 'N/A');
            }
            
            // Determine what to show in Amount/Quantity column
            let amountQuantityDisplay = '';
            if (isStockTransaction) {
                const quantityColor = transaction.type === 'stock_out' ? '#ef4444' : '#10b981';
                const quantitySign = transaction.type === 'stock_out' ? '-' : '+';
                amountQuantityDisplay = `<strong style="color: ${quantityColor};">
                    ${quantitySign}${escapeHtml(transaction.quantity || 0)} unit(s)
                </strong>`;
            } else {
                const amountColor = transaction.type === 'expense' ? '#ef4444' : '#10b981';
                const amountSign = transaction.type === 'expense' ? '-' : '+';
                amountQuantityDisplay = `<strong style="color: ${amountColor};">
                    ${amountSign}₱${parseFloat(transaction.amount || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                </strong>`;
            }
            
            const paymentMethod = isStockTransaction ? 'N/A' : ((transaction.payment_method || 'N/A').replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()));

            return `
                <tr>
                    <td>${escapeHtml(transaction.transaction_id || 'N/A')}</td>
                    <td>
                        ${escapeHtml(transaction.transaction_date || 'N/A')}<br>
                        <small style="color: #6b7280;">${escapeHtml(transaction.transaction_time || '')}</small>
                    </td>
                    <td>
                        <span class="status-badge ${(transaction.type || '').toLowerCase()}">
                            ${escapeHtml((transaction.type || 'N/A').replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()))}
                        </span>
                    </td>
                    <td>${displayEntity}</td>
                    <td>${amountQuantityDisplay}</td>
                    <td>${escapeHtml(paymentMethod)}</td>
                    <td>
                        <span class="status-badge ${(transaction.payment_status || 'pending').toLowerCase()}">
                            ${escapeHtml((transaction.payment_status || 'Pending').charAt(0).toUpperCase() + (transaction.payment_status || 'Pending').slice(1))}
                        </span>
                    </td>
                    <td>${escapeHtml(transaction.description || 'N/A')}</td>
                    <td>
                        <button
                            class="btn btn-primary btn-small"
                            data-action="view-transaction"
                            data-transaction-id="${escapeHtml(transaction.transaction_id || '')}">
                            <i class="fas fa-eye"></i> View
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function applyTransactionFilters() {
        transactionFilters = {
            type: document.getElementById('transactionTypeFilter')?.value || '',
            payment_status: document.getElementById('transactionStatusFilter')?.value || '',
            date_from: document.getElementById('transactionDateFrom')?.value || '',
            date_to: document.getElementById('transactionDateTo')?.value || '',
            search: document.getElementById('transactionSearch')?.value || ''
        };
        loadTransactions(transactionFilters);
    }

    function clearTransactionFilters() {
        const typeFilter = document.getElementById('transactionTypeFilter');
        const statusFilter = document.getElementById('transactionStatusFilter');
        const dateFromFilter = document.getElementById('transactionDateFrom');
        const dateToFilter = document.getElementById('transactionDateTo');
        const searchFilter = document.getElementById('transactionSearch');

        if (typeFilter) typeFilter.value = '';
        if (statusFilter) statusFilter.value = '';
        if (dateFromFilter) dateFromFilter.value = '';
        if (dateToFilter) dateToFilter.value = '';
        if (searchFilter) searchFilter.value = '';

        transactionFilters = {
            type: '',
            payment_status: '',
            date_from: '',
            date_to: '',
            search: ''
        };
        loadTransactions({});
    }

    // Initialize transaction filters & tabs
    document.addEventListener('DOMContentLoaded', function() {
        const transactionTypeFilter = document.getElementById('transactionTypeFilter');
        const transactionStatusFilter = document.getElementById('transactionStatusFilter');
        const transactionDateFrom = document.getElementById('transactionDateFrom');
        const transactionDateTo = document.getElementById('transactionDateTo');
        const transactionSearch = document.getElementById('transactionSearch');
        const clearTransactionFiltersBtn = document.getElementById('clearTransactionFilters');
        const billingDateFilter = document.getElementById('dateFilter');
        const billingCategoryFilter = document.getElementById('categoryFilter');
        const billingSearchFilter = document.getElementById('searchFilter');
        const billingClearFiltersBtn = document.getElementById('clearFilters');
        const tabButtons = document.querySelectorAll('.financial-tab-button');
        const tabContents = document.querySelectorAll('.financial-tab-content');
        const transactionsTableBody = document.getElementById('transactionsTableBody');

        if (transactionTypeFilter) {
            transactionTypeFilter.addEventListener('change', applyTransactionFilters);
        }
        if (transactionStatusFilter) {
            transactionStatusFilter.addEventListener('change', applyTransactionFilters);
        }
        if (transactionDateFrom) {
            transactionDateFrom.addEventListener('change', applyTransactionFilters);
        }
        if (transactionDateTo) {
            transactionDateTo.addEventListener('change', applyTransactionFilters);
        }
        if (transactionSearch) {
            let searchTimeout;
            transactionSearch.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(applyTransactionFilters, 500);
            });
        }
        if (clearTransactionFiltersBtn) {
            clearTransactionFiltersBtn.addEventListener('click', clearTransactionFilters);
        }

        // Billing filters (billing accounts tab)
        if (billingDateFilter) {
            billingDateFilter.addEventListener('change', applyBillingFilters);
        }
        if (billingCategoryFilter) {
            billingCategoryFilter.addEventListener('change', applyBillingFilters);
        }
        if (billingSearchFilter) {
            let billingSearchTimeout;
            billingSearchFilter.addEventListener('input', function () {
                clearTimeout(billingSearchTimeout);
                billingSearchTimeout = setTimeout(applyBillingFilters, 300);
            });
        }
        if (billingClearFiltersBtn) {
            billingClearFiltersBtn.addEventListener('click', function () {
                clearBillingFilters();
            });
        }

        if (transactionsTableBody) {
            transactionsTableBody.addEventListener('click', function (event) {
                const btn = event.target.closest('button[data-action="view-transaction"]');
                if (!btn) return;
                const transactionId = btn.getAttribute('data-transaction-id');
                openTransactionDetails(transactionId);
            });
        }

        // Tab switching behavior
        if (tabButtons.length && tabContents.length) {
            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const targetId = button.getAttribute('data-tab');
                    if (!targetId) return;

                    // Toggle active class on buttons
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');

                    // Toggle active class on contents
                    tabContents.forEach(content => {
                        if (content.id === targetId) {
                            content.classList.add('active');
                        } else {
                            content.classList.remove('active');
                        }
                    });
                });
            });
        }
    });

    // Initialize
    if (tableBody) {
        tableBody.addEventListener('click', handleTableClick);
    }
    
    // Check URL params on page load
    document.addEventListener('DOMContentLoaded', checkUrlParams);
})();
