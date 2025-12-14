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
            container.style.cssText = 'display: none; position: fixed; top: 20px; right: 20px; padding: 0.75rem 1rem; max-width: 400px; border-radius: 6px; align-items: center; gap: 0.5rem; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.25); font-size: 0.95rem; font-weight: 500; z-index: 10050; flex-direction: row;';
            
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

            const userRole = document.querySelector('meta[name="user-role"]')?.content || '';
            const canDelete = ['admin', 'accountant'].includes(userRole);
            
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
                        <div class="action-buttons" style="display: flex; gap: 0.5rem;">
                            <button class="btn btn-primary btn-small btn-view-transaction" 
                                    data-transaction-id="${escapeHtml(transaction.transaction_id || '')}"
                                    data-transaction-type="${escapeHtml(transaction.type || '')}"
                                    title="View Details">
                                <i class="fas fa-eye"></i> View
                            </button>
                            ${canDelete ? `
                                <button class="btn btn-danger btn-small btn-delete-transaction" 
                                        data-transaction-id="${escapeHtml(transaction.transaction_id || '')}"
                                        data-transaction-type="${escapeHtml(transaction.type || '')}"
                                        title="Delete Transaction">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            ` : ''}
                        </div>
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

    // Initialize transaction filters
    document.addEventListener('DOMContentLoaded', function() {
        const transactionTypeFilter = document.getElementById('transactionTypeFilter');
        const transactionStatusFilter = document.getElementById('transactionStatusFilter');
        const transactionDateFrom = document.getElementById('transactionDateFrom');
        const transactionDateTo = document.getElementById('transactionDateTo');
        const transactionSearch = document.getElementById('transactionSearch');
        const clearTransactionFiltersBtn = document.getElementById('clearTransactionFilters');

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
    });

    // Billing Accounts Filtering
    let billingFilters = {
        date: '',
        category: '',
        search: ''
    };

    function filterBillingTable() {
        if (!tableBody) return;

        const rows = tableBody.querySelectorAll('tr');
        let visibleCount = 0;

        rows.forEach(row => {
            if (row.querySelector('.empty-state')) {
                // Skip empty state row
                return;
            }

            const cells = row.querySelectorAll('td');
            if (cells.length < 4) return;

            // Get text content from relevant cells
            const billingId = cells[0]?.textContent?.trim() || '';
            const patientCell = cells[1];
            const patientName = patientCell?.querySelector('strong')?.textContent?.trim() || '';
            const patientId = patientCell?.querySelector('small')?.textContent?.trim() || '';
            const admission = cells[2]?.textContent?.trim() || '';
            const status = cells[3]?.textContent?.trim() || '';

            // Combine all searchable text
            const searchableText = `${billingId} ${patientName} ${patientId} ${admission} ${status}`.toLowerCase();

            // Apply filters
            let matches = true;

            // Date filter (if implemented on backend, this would check created_at)
            // For now, we'll skip date filtering on client side as it requires date parsing

            // Category filter (Income/Expense) - this doesn't directly apply to billing accounts
            // but we'll keep it for consistency

            // Search filter
            if (billingFilters.search) {
                const searchTerm = billingFilters.search.toLowerCase();
                if (!searchableText.includes(searchTerm)) {
                    matches = false;
                }
            }

            // Show/hide row
            if (matches) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Show empty state if no rows visible
        const existingEmptyState = tableBody.querySelector('.empty-state-row');
        if (visibleCount === 0 && !existingEmptyState) {
            const emptyRow = document.createElement('tr');
            emptyRow.className = 'empty-state-row';
            emptyRow.innerHTML = `
                <td colspan="5" style="text-align: center; padding: 3rem; color: #6b7280;">
                    <i class="fas fa-file-invoice-dollar" style="font-size: 3rem; color: #d1d5db; margin-bottom: 1rem; display: block;"></i>
                    <p style="margin: 0.5rem 0; font-size: 1rem; font-weight: 500;">No billing accounts found</p>
                    <p style="margin: 0; font-size: 0.875rem;">No billing accounts match your search criteria.</p>
                </td>
            `;
            tableBody.appendChild(emptyRow);
        } else if (visibleCount > 0 && existingEmptyState) {
            existingEmptyState.remove();
        }
    }

    function applyBillingFilters() {
        billingFilters = {
            date: document.getElementById('dateFilter')?.value || '',
            category: document.getElementById('categoryFilter')?.value || '',
            search: document.getElementById('searchFilter')?.value?.trim() || ''
        };
        filterBillingTable();
    }

    function clearBillingFilters() {
        const dateFilter = document.getElementById('dateFilter');
        const categoryFilter = document.getElementById('categoryFilter');
        const searchFilter = document.getElementById('searchFilter');

        if (dateFilter) dateFilter.value = '';
        if (categoryFilter) categoryFilter.value = '';
        if (searchFilter) searchFilter.value = '';

        billingFilters = {
            date: '',
            category: '',
            search: ''
        };
        filterBillingTable();
    }

    // Initialize billing filters
    document.addEventListener('DOMContentLoaded', function() {
        const dateFilter = document.getElementById('dateFilter');
        const categoryFilter = document.getElementById('categoryFilter');
        const searchFilter = document.getElementById('searchFilter');
        const clearFiltersBtn = document.getElementById('clearFilters');

        if (dateFilter) {
            dateFilter.addEventListener('change', applyBillingFilters);
        }
        if (categoryFilter) {
            categoryFilter.addEventListener('change', applyBillingFilters);
        }
        if (searchFilter) {
            let searchTimeout;
            searchFilter.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(applyBillingFilters, 300);
            });
        }
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', clearBillingFilters);
        }
    });

    // Tab functionality
    function initializeTabs() {
        const tabButtons = document.querySelectorAll('.financial-tab-button');
        const tabContents = document.querySelectorAll('.financial-tab-content');

        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const tabName = this.dataset.tab;

                // Update button states
                tabButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');

                // Update content visibility
                tabContents.forEach(content => content.classList.remove('active'));
                
                // Show the corresponding tab content
                let targetContent;
                if (tabName === 'billing-accounts') {
                    targetContent = document.getElementById('tabBillingAccounts');
                } else if (tabName === 'transactions') {
                    targetContent = document.getElementById('tabTransactions');
                }
                
                if (targetContent) {
                    targetContent.classList.add('active');
                }
            });
        });
    }

    // Transaction action handlers - use event delegation for dynamically loaded content
    function setupTransactionHandlers() {
        const transactionsTableBody = document.getElementById('transactionsTableBody');
        if (!transactionsTableBody) return;
        
        // Use event delegation to handle clicks on dynamically added buttons
        transactionsTableBody.addEventListener('click', function(e) {
            const viewBtn = e.target.closest('.btn-view-transaction');
            const deleteBtn = e.target.closest('.btn-delete-transaction');
            
            if (viewBtn) {
                e.preventDefault();
                e.stopPropagation();
                const transactionId = viewBtn.dataset.transactionId;
                if (transactionId) {
                    viewTransaction(transactionId);
                }
            } else if (deleteBtn) {
                e.preventDefault();
                e.stopPropagation();
                const transactionId = deleteBtn.dataset.transactionId;
                const transactionType = deleteBtn.dataset.transactionType;
                if (transactionId) {
                    deleteTransaction(transactionId, transactionType);
                }
            }
        });
    }

    function viewTransaction(transactionId) {
        if (!transactionId) return;
        
        // Fetch transaction details
        fetch(`${baseUrl}/financial-management/transactions/${transactionId}`, {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin'
        })
            .then(resp => resp.json())
            .then(result => {
                if (result.success && result.data) {
                    showTransactionDetails(result.data);
                } else {
                    if (typeof showUniversalNotification === 'function') {
                        showUniversalNotification(result.message || 'Failed to load transaction details', 'error');
                    } else if (typeof showFinancialNotification === 'function') {
                        showFinancialNotification(result.message || 'Failed to load transaction details', 'error');
                    }
                }
            })
            .catch(err => {
                console.error('Failed to load transaction', err);
                if (typeof showUniversalNotification === 'function') {
                    showUniversalNotification('Failed to load transaction details', 'error');
                } else if (typeof showFinancialNotification === 'function') {
                    showFinancialNotification('Failed to load transaction details', 'error');
                }
            });
    }

    function showTransactionDetails(transaction) {
        // Create a modal to show transaction details
        const isStockTransaction = ['stock_in', 'stock_out'].includes(transaction.type || '');
        
        let detailsHtml = `
            <div style="padding: 1rem;">
                <h3 style="margin-top: 0; color: #1f2937; border-bottom: 2px solid #e5e7eb; padding-bottom: 0.5rem;">
                    <i class="fas fa-exchange-alt"></i> Transaction Details
                </h3>
                <div style="margin-top: 1rem;">
                    <div style="margin-bottom: 0.75rem;">
                        <strong style="color: #6b7280; display: inline-block; min-width: 150px;">Transaction ID:</strong>
                        <span>${escapeHtml(transaction.transaction_id || 'N/A')}</span>
                    </div>
                    <div style="margin-bottom: 0.75rem;">
                        <strong style="color: #6b7280; display: inline-block; min-width: 150px;">Date & Time:</strong>
                        <span>${escapeHtml(transaction.transaction_date || 'N/A')} ${escapeHtml(transaction.transaction_time || '')}</span>
                    </div>
                    <div style="margin-bottom: 0.75rem;">
                        <strong style="color: #6b7280; display: inline-block; min-width: 150px;">Type:</strong>
                        <span class="status-badge ${(transaction.type || '').toLowerCase()}">
                            ${escapeHtml((transaction.type || 'N/A').replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()))}
                        </span>
                    </div>
                    <div style="margin-bottom: 0.75rem;">
                        <strong style="color: #6b7280; display: inline-block; min-width: 150px;">Status:</strong>
                        <span class="status-badge ${(transaction.payment_status || 'pending').toLowerCase()}">
                            ${escapeHtml((transaction.payment_status || 'Pending').charAt(0).toUpperCase() + (transaction.payment_status || 'Pending').slice(1))}
                        </span>
                    </div>
        `;
        
        if (isStockTransaction) {
            detailsHtml += `
                    <div style="margin-bottom: 0.75rem;">
                        <strong style="color: #6b7280; display: inline-block; min-width: 150px;">Resource:</strong>
                        <span>${escapeHtml(transaction.resource_name || 'N/A')}</span>
                    </div>
                    <div style="margin-bottom: 0.75rem;">
                        <strong style="color: #6b7280; display: inline-block; min-width: 150px;">Quantity:</strong>
                        <span style="color: ${transaction.type === 'stock_out' ? '#ef4444' : '#10b981'}; font-weight: 600;">
                            ${transaction.type === 'stock_out' ? '-' : '+'}${escapeHtml(transaction.quantity || 0)} unit(s)
                        </span>
                    </div>
            `;
        } else {
            const patientName = ((transaction.patient_first_name || '') + ' ' + (transaction.patient_last_name || '')).trim() || 'N/A';
            detailsHtml += `
                    <div style="margin-bottom: 0.75rem;">
                        <strong style="color: #6b7280; display: inline-block; min-width: 150px;">Patient:</strong>
                        <span>${escapeHtml(patientName)}</span>
                    </div>
                    <div style="margin-bottom: 0.75rem;">
                        <strong style="color: #6b7280; display: inline-block; min-width: 150px;">Amount:</strong>
                        <span style="color: ${transaction.type === 'expense' ? '#ef4444' : '#10b981'}; font-weight: 600;">
                            ${transaction.type === 'expense' ? '-' : '+'}₱${parseFloat(transaction.amount || 0).toFixed(2)}
                        </span>
                    </div>
                    <div style="margin-bottom: 0.75rem;">
                        <strong style="color: #6b7280; display: inline-block; min-width: 150px;">Payment Method:</strong>
                        <span>${escapeHtml((transaction.payment_method || 'N/A').replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()))}</span>
                    </div>
            `;
        }
        
        detailsHtml += `
                    <div style="margin-bottom: 0.75rem;">
                        <strong style="color: #6b7280; display: inline-block; min-width: 150px;">Description:</strong>
                        <span>${escapeHtml(transaction.description || 'N/A')}</span>
                    </div>
        `;
        
        if (transaction.notes) {
            detailsHtml += `
                    <div style="margin-bottom: 0.75rem;">
                        <strong style="color: #6b7280; display: inline-block; min-width: 150px;">Notes:</strong>
                        <span>${escapeHtml(transaction.notes)}</span>
                    </div>
            `;
        }
        
        detailsHtml += `
                </div>
            </div>
        `;
        
        // Create and show modal
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.style.cssText = 'display: flex; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.55); z-index: 9999; align-items: center; justify-content: center;';
        modal.innerHTML = `
            <div class="modal-content" style="background: white; border-radius: 12px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);">
                ${detailsHtml}
                <div style="padding: 1rem; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="this.closest('.modal').remove()">Close</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Close on background click
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.remove();
            }
        });
    }

    function deleteTransaction(transactionId, transactionType) {
        if (!transactionId) return;
        
        const confirmMessage = `Are you sure you want to delete this ${(transactionType || 'transaction').replace(/_/g, ' ')} transaction? This action cannot be undone.`;
        
        if (!confirm(confirmMessage)) {
            return;
        }
        
        fetch(`${baseUrl}/financial-management/transactions/${transactionId}/delete`, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ transaction_id: transactionId })
        })
            .then(resp => resp.json())
            .then(result => {
                const success = result.success === true || result.status === 'success';
                
                if (typeof showUniversalNotification === 'function') {
                    showUniversalNotification(
                        result.message || (success ? 'Transaction deleted successfully' : 'Failed to delete transaction'),
                        success ? 'success' : 'error'
                    );
                } else if (typeof showFinancialNotification === 'function') {
                    showFinancialNotification(
                        result.message || (success ? 'Transaction deleted successfully' : 'Failed to delete transaction'),
                        success ? 'success' : 'error'
                    );
                }
                
                if (success) {
                    // Reload transactions
                    applyTransactionFilters();
                }
            })
            .catch(err => {
                console.error('Failed to delete transaction', err);
                if (typeof showUniversalNotification === 'function') {
                    showUniversalNotification('Failed to delete transaction', 'error');
                } else if (typeof showFinancialNotification === 'function') {
                    showFinancialNotification('Failed to delete transaction', 'error');
                }
            });
    }

    // Initialize
    if (tableBody) {
        tableBody.addEventListener('click', handleTableClick);
    }
    
    // Initialize transaction click handlers
    document.addEventListener('DOMContentLoaded', function() {
        checkUrlParams();
        initializeTabs();
        setupTransactionHandlers();
    });
})();
