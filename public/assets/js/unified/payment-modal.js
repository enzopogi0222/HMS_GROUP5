/**
 * Payment Modal Management
 */

const PaymentModal = {
    modal: null,
    form: null,

    init() {
        this.modal = document.getElementById('paymentModal');
        this.form = document.getElementById('paymentForm');
        
        this.bindEvents();
        this.setDefaultDate();
    },

    bindEvents() {
        // Modal close events
        const closeBtn = document.getElementById('closePaymentModal');
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
        this.setDefaultDate();
    },

    setDefaultDate() {
        const dateInput = document.getElementById('pay_date');
        if (dateInput && !dateInput.value) {
            dateInput.value = new Date().toISOString().split('T')[0];
        }
    },

    collectFormData() {
        if (!this.form) return null;
        
        const formData = new FormData(this.form);
        return {
            patient_identifier: formData.get('patient_identifier'),
            bill_ref: formData.get('bill_ref'),
            amount: parseFloat(formData.get('amount')) || 0,
            payment_method: formData.get('method'),
            payment_date: formData.get('date'),
            notes: formData.get('notes')
        };
    },

    async save() {
        if (!FinancialUtils.validateForm(this.form)) {
            FinancialUtils.showNotification('Please fill in all required fields', 'error');
            return;
        }
        
        const data = this.collectFormData();
        if (!data) return;
        
        if (data.amount <= 0) {
            FinancialUtils.showNotification('Please enter a valid payment amount', 'error');
            return;
        }
        
        const saveBtn = this.form.querySelector('.btn-success');
        if (saveBtn) {
            saveBtn.dataset.originalText = saveBtn.innerHTML;
            FinancialUtils.setLoading(saveBtn, true);
        }
        
        try {
            const response = await FinancialUtils.makeRequest('financial/payment/process', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            
            if (response.success) {
                FinancialUtils.showNotification(response.message || 'Payment processed successfully', 'success');
                this.close();
                
                // Refresh financial data if function exists
                if (typeof refreshFinancialData === 'function') {
                    refreshFinancialData();
                }
            } else {
                FinancialUtils.showNotification(response.message || 'Failed to process payment', 'error');
            }
        } catch (error) {
            console.error('Error processing payment:', error);
            FinancialUtils.showNotification('Error processing payment. Please try again.', 'error');
        } finally {
            if (saveBtn) {
                FinancialUtils.setLoading(saveBtn, false);
            }
        }
    }
};

// Global functions for backward compatibility
function openPaymentModal() {
    PaymentModal.open();
}

function closePaymentModal() {
    PaymentModal.close();
}

function savePayment() {
    PaymentModal.save();
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    PaymentModal.init();
});

// Export to global scope
window.PaymentModal = PaymentModal;
