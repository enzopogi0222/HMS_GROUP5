/**
 * Billing Modal Management
 */

const BillingModal = {
    modal: null,
    form: null,
    servicesBody: null,
    totalInput: null,
    serviceRowCount: 0,

    init() {
        this.modal = document.getElementById('billingModal');
        this.form = document.getElementById('billingForm');
        this.servicesBody = document.getElementById('servicesBody');
        this.totalInput = document.getElementById('bill_total');
        
        this.bindEvents();
        this.addServiceRow(); // Add initial service row
    },

    bindEvents() {
        // Modal close events
        const closeBtn = document.getElementById('closeBillingModal');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.close());
        }

        // Close on background click
        this.modal?.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.close();
            }
        });

        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal?.classList.contains('show')) {
                this.close();
            }
        });

        // Form submission
        this.form?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.save();
        });
    },

    open() {
        if (!this.modal) return;
        
        this.reset();
        this.modal.classList.add('show');
        
        // Focus first input
        const firstInput = this.form?.querySelector('input:not([readonly])');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
    },

    close() {
        if (!this.modal) return;
        
        this.modal.classList.remove('show');
        this.reset();
    },

    reset() {
        if (this.form) {
            this.form.reset();
            FinancialUtils.clearFormErrors(this.form);
        }
        
        // Clear services table
        if (this.servicesBody) {
            this.servicesBody.innerHTML = '';
            this.serviceRowCount = 0;
            this.addServiceRow();
        }
        
        this.updateTotal();
    },

    addServiceRow() {
        if (!this.servicesBody) return;
        
        this.serviceRowCount++;
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <select name="services[${this.serviceRowCount}][type]" class="form-select" required>
                    <option value="">Select Type</option>
                    <option value="consultation">Consultation</option>
                    <option value="procedure">Medical Procedure</option>
                    <option value="medication">Medication</option>
                    <option value="laboratory">Laboratory Test</option>
                    <option value="imaging">Imaging/X-ray</option>
                    <option value="room">Room Charges</option>
                    <option value="other">Other</option>
                </select>
            </td>
            <td>
                <input type="text" name="services[${this.serviceRowCount}][description]" 
                       class="form-input" placeholder="Description" required>
            </td>
            <td>
                <input type="number" name="services[${this.serviceRowCount}][quantity]" 
                       class="form-input quantity-input" min="1" value="1" required>
            </td>
            <td>
                <input type="number" name="services[${this.serviceRowCount}][unit_price]" 
                       class="form-input price-input" min="0" step="0.01" placeholder="0.00" required>
            </td>
            <td>
                <input type="number" name="services[${this.serviceRowCount}][line_total]" 
                       class="form-input line-total" readonly>
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm" onclick="BillingModal.removeServiceRow(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        
        this.servicesBody.appendChild(row);
        
        // Bind calculation events
        const quantityInput = row.querySelector('.quantity-input');
        const priceInput = row.querySelector('.price-input');
        const lineTotalInput = row.querySelector('.line-total');
        
        [quantityInput, priceInput].forEach(input => {
            input?.addEventListener('input', () => {
                this.calculateLineTotal(quantityInput, priceInput, lineTotalInput);
                this.updateTotal();
            });
        });
    },

    removeServiceRow(button) {
        const row = button.closest('tr');
        if (row && this.servicesBody.children.length > 1) {
            row.remove();
            this.updateTotal();
        }
    },

    calculateLineTotal(quantityInput, priceInput, lineTotalInput) {
        const quantity = parseFloat(quantityInput.value) || 0;
        const price = parseFloat(priceInput.value) || 0;
        const lineTotal = quantity * price;
        
        lineTotalInput.value = lineTotal.toFixed(2);
    },

    updateTotal() {
        if (!this.totalInput || !this.servicesBody) return;
        
        const lineTotals = this.servicesBody.querySelectorAll('.line-total');
        let total = 0;
        
        lineTotals.forEach(input => {
            total += parseFloat(input.value) || 0;
        });
        
        this.totalInput.value = total.toFixed(2);
    },

    collectFormData() {
        if (!this.form) return null;
        
        const formData = new FormData(this.form);
        const data = {
            patient_identifier: formData.get('patient_identifier'),
            doctor: formData.get('doctor'),
            department: formData.get('department'),
            payment_status: formData.get('payment_status'),
            total_amount: parseFloat(formData.get('bill_total')) || 0,
            services: []
        };
        
        // Collect services data
        const serviceRows = this.servicesBody.querySelectorAll('tr');
        serviceRows.forEach((row, index) => {
            const type = row.querySelector(`[name*="[type]"]`)?.value;
            const description = row.querySelector(`[name*="[description]"]`)?.value;
            const quantity = parseFloat(row.querySelector(`[name*="[quantity]"]`)?.value) || 0;
            const unit_price = parseFloat(row.querySelector(`[name*="[unit_price]"]`)?.value) || 0;
            
            if (type && description && quantity > 0 && unit_price > 0) {
                data.services.push({
                    type,
                    description,
                    quantity,
                    unit_price,
                    line_total: quantity * unit_price
                });
            }
        });
        
        return data;
    },

    async save() {
        if (!FinancialUtils.validateForm(this.form)) {
            FinancialUtils.showNotification('Please fill in all required fields', 'error');
            return;
        }
        
        const data = this.collectFormData();
        if (!data) return;
        
        if (data.services.length === 0) {
            FinancialUtils.showNotification('Please add at least one service', 'error');
            return;
        }
        
        const saveBtn = this.form.querySelector('.btn-success');
        if (saveBtn) {
            saveBtn.dataset.originalText = saveBtn.innerHTML;
            FinancialUtils.setLoading(saveBtn, true);
        }
        
        try {
            const response = await FinancialUtils.makeRequest('financial/bill/create', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            
            if (response.success) {
                FinancialUtils.showNotification(response.message || 'Bill created successfully', 'success');
                this.close();
                
                // Refresh financial data if function exists
                if (typeof refreshFinancialData === 'function') {
                    refreshFinancialData();
                }
            } else {
                FinancialUtils.showNotification(response.message || 'Failed to create bill', 'error');
            }
        } catch (error) {
            console.error('Error creating bill:', error);
            FinancialUtils.showNotification('Error creating bill. Please try again.', 'error');
        } finally {
            if (saveBtn) {
                FinancialUtils.setLoading(saveBtn, false);
            }
        }
    }
};

// Global functions for backward compatibility
function openBillingModal() {
    BillingModal.open();
}

function closeBillingModal() {
    BillingModal.close();
}

function addServiceRow() {
    BillingModal.addServiceRow();
}

function saveBill() {
    BillingModal.save();
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    BillingModal.init();
});

// Export to global scope
window.BillingModal = BillingModal;
