/**
 * View Resource Modal
 */
(function() {
    const modalId = 'viewResourceModal';
    const modal = document.getElementById(modalId);
    
    if (!modal) return;

    function init() {
        // Close button handlers
        const closeBtn = document.getElementById('closeViewResourceModal');
        const closeBtnFooter = document.getElementById('closeViewResourceBtn');
        
        if (closeBtn) {
            closeBtn.addEventListener('click', close);
        }
        if (closeBtnFooter) {
            closeBtnFooter.addEventListener('click', close);
        }

        // Click outside to close
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                close();
            }
        });

        // Edit button handler
        const editBtn = document.getElementById('editFromViewResourceBtn');
        if (editBtn) {
            editBtn.addEventListener('click', () => {
                const resourceId = modal.dataset.resourceId;
                if (resourceId && typeof editResource === 'function') {
                    close();
                    editResource(parseInt(resourceId));
                }
            });
        }

        // ESC key to close
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.style.display !== 'none') {
                close();
            }
        });
    }

    function open(resourceId) {
        if (!modal) return;

        // Load resource data
        loadResource(resourceId);
        
        modal.style.display = 'flex';
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        
        // Store resource ID for edit button
        modal.dataset.resourceId = resourceId;
    }

    function close() {
        if (!modal) return;
        
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    function loadResource(resourceId) {
        // Get resource from global resourcesById object or fetch it
        const resource = (window.resourcesById || {})[resourceId];
        
        if (resource) {
            displayResourceDetails(resource);
        } else {
            // Fetch resource if not in global object
            fetchResource(resourceId);
        }
    }

    async function fetchResource(resourceId) {
        try {
            const baseUrl = (window.HMS?.baseUrl || '').replace(/\/+$/, '') + '/';
            const resourceBaseUrl = window.HMS?.resourceManagementBaseUrl || 'admin/resource-management';
            
            const response = await fetch(`${baseUrl}${resourceBaseUrl}/get/${resourceId}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success && data.resource) {
                    displayResourceDetails(data.resource);
                } else {
                    alert('Resource not found');
                    close();
                }
            } else {
                alert('Failed to load resource details');
                close();
            }
        } catch (error) {
            console.error('Error fetching resource:', error);
            alert('Error loading resource details');
            close();
        }
    }

    function displayResourceDetails(resource) {
        // Helper function to set value
        const setValue = (id, value, formatter = null) => {
            const el = document.getElementById(id);
            if (el) {
                if (formatter) {
                    el.textContent = formatter(value);
                } else {
                    el.textContent = value || '-';
                }
            }
        };

        // Helper to format currency
        const formatCurrency = (value) => {
            if (!value || value === '0' || value === 0 || value === null) return '-';
            return '₱' + parseFloat(value).toFixed(2);
        };

        // Helper to format date
        const formatDate = (value) => {
            if (!value) return '-';
            try {
                const date = new Date(value);
                return date.toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
            } catch (e) {
                return value;
            }
        };

        // Helper to format datetime
        const formatDateTime = (value) => {
            if (!value) return '-';
            try {
                const date = new Date(value);
                return date.toLocaleString('en-US', { 
                    year: 'numeric', 
                    month: 'short', 
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } catch (e) {
                return value;
            }
        };

        // Basic Information
        setValue('viewResourceName', resource.equipment_name || resource.name);
        setValue('viewResourceCategory', resource.category);
        setValue('viewResourceQuantity', resource.quantity || '0');
        setValue('viewResourceLocation', resource.location);
        setValue('viewResourceSerialNumber', resource.serial_number);

        // Status with badge
        const statusEl = document.getElementById('viewResourceStatus');
        if (statusEl) {
            const status = resource.status || 'Stock In';
            const badgeClass = status === 'Stock In' ? 'badge-success' : 'badge-danger';
            statusEl.className = `status-badge ${badgeClass}`;
            statusEl.textContent = status;
        }

        // Medication Details (show only if category is Medications)
        const isMedication = resource.category === 'Medications';
        const medDetailsSection = document.getElementById('viewMedicationDetails');
        if (medDetailsSection) {
            medDetailsSection.style.display = isMedication ? 'block' : 'none';
        }

        if (isMedication) {
            setValue('viewResourceBatchNumber', resource.batch_number);
            setValue('viewResourceExpiryDate', formatDate(resource.expiry_date));
        }

        // Pricing Details (show only if price or selling_price exists)
        const hasPricing = (resource.price && parseFloat(resource.price) > 0) || 
                          (resource.selling_price && parseFloat(resource.selling_price) > 0);
        const pricingSection = document.getElementById('viewPricingDetails');
        if (pricingSection) {
            pricingSection.style.display = hasPricing ? 'block' : 'none';
        }

        if (hasPricing) {
            setValue('viewResourcePrice', formatCurrency(resource.price));
            setValue('viewResourceSellingPrice', formatCurrency(resource.selling_price));
        }

        // Remarks
        const remarksEl = document.getElementById('viewResourceRemarks');
        if (remarksEl) {
            if (resource.remarks && resource.remarks.trim()) {
                remarksEl.textContent = resource.remarks;
                remarksEl.style.fontStyle = 'normal';
            } else {
                remarksEl.textContent = 'No remarks available';
                remarksEl.style.fontStyle = 'italic';
                remarksEl.style.color = '#666';
            }
        }

        // Timestamps
        setValue('viewResourceCreatedAt', formatDateTime(resource.created_at));
        setValue('viewResourceUpdatedAt', formatDateTime(resource.updated_at));
    }

    // Export functions
    window.ViewResourceModal = {
        open,
        close,
        init
    };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
