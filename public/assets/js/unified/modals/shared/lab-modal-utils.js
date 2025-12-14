/**
 * Shared utilities for lab order modals
 */
class LabModalUtils {
    constructor(baseUrl) {
        this.baseUrl = baseUrl;
    }

    /**
     * Setup modal close handlers
     */
    setupModalCloseHandlers(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        // Close button handlers
        modal.querySelectorAll(`[data-modal-close="${modalId}"]`).forEach(btn => {
            btn.addEventListener('click', () => this.close(modalId));
        });

        // Click outside to close
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.close(modalId);
            }
        });
    }

    /**
     * Open modal
     */
    open(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        modal.removeAttribute('hidden');
        modal.setAttribute('aria-hidden', 'false');
        modal.style.display = 'flex';
        modal.style.position = 'fixed';
        modal.style.top = '0';
        modal.style.left = '0';
        modal.style.width = '100vw';
        modal.style.height = '100vh';
        modal.style.zIndex = '9999';
        modal.style.alignItems = 'center';
        modal.style.justifyContent = 'center';
        modal.style.background = 'rgba(15, 23, 42, 0.55)';
    }

    /**
     * Close modal
     */
    close(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        modal.setAttribute('aria-hidden', 'true');
        modal.style.display = 'none';
        modal.setAttribute('hidden', 'hidden');
    }

    /**
     * Load patients into select element
     */
    async loadPatients(selectId) {
        const select = document.getElementById(selectId);
        if (!select) return;

        try {
            select.innerHTML = '<option value="">Loading patients...</option>';
            select.disabled = true;

            const res = await fetch(this.baseUrl + 'labs/patients', { credentials: 'same-origin' });
            const data = await res.json();

            if (data.status === 'success' && Array.isArray(data.data)) {
                if (!data.data.length) {
                    select.innerHTML = '<option value="">No patients available</option>';
                } else {
                    select.innerHTML = '<option value="">Select Patient</option>';
                    data.data.forEach(p => {
                        const option = document.createElement('option');
                        option.value = p.patient_id;
                        const fullName = `${p.first_name || ''} ${p.last_name || ''}`.trim() || `Patient #${p.patient_id}`;
                        
                        // Always display patient type
                        let patientType = '';
                        if (p.hasOwnProperty('patient_type') && p.patient_type !== null && p.patient_type !== undefined) {
                            const type = String(p.patient_type).trim();
                            if (type !== '' && type.toLowerCase() !== 'null' && type.toLowerCase() !== 'undefined') {
                                // Capitalize first letter for display
                                patientType = ` (${type.charAt(0).toUpperCase() + type.slice(1).toLowerCase()})`;
                            }
                        }
                        
                        // Default to Outpatient if not set
                        if (!patientType) {
                            patientType = ' (Outpatient)';
                        }
                        
                        option.textContent = `${fullName}${patientType}`;
                        select.appendChild(option);
                    });
                }
            } else {
                select.innerHTML = '<option value="">Failed to load patients</option>';
            }
        } catch (e) {
            console.error('Failed to load patients', e);
            if (select) {
                select.innerHTML = '<option value="">Error loading patients</option>';
            }
        } finally {
            if (select) select.disabled = false;
        }
    }

    /**
     * Load lab tests into select element
     */
    async loadLabTests(selectId) {
        const select = document.getElementById(selectId);
        if (!select) return;

        try {
            select.innerHTML = '<option value="">Loading tests...</option>';
            select.disabled = true;

            const res = await fetch(this.baseUrl + 'labs/tests', { credentials: 'same-origin' });
            const data = await res.json();

            if (data.status === 'success' && Array.isArray(data.data)) {
                if (!data.data.length) {
                    select.innerHTML = '<option value="">No lab tests configured</option>';
                } else {
                    select.innerHTML = '<option value="">Select Lab Test</option>';
                    data.data.forEach(test => {
                        const option = document.createElement('option');
                        option.value = test.test_code;
                        option.textContent = `${test.test_name} (${test.test_code})`;
                        option.dataset.testName = test.test_name;
                        select.appendChild(option);
                    });
                }
            } else {
                select.innerHTML = '<option value="">Failed to load lab tests</option>';
            }
        } catch (e) {
            console.error('Failed to load lab tests', e);
            if (select) {
                select.innerHTML = '<option value="">Error loading lab tests</option>';
            }
        } finally {
            if (select) select.disabled = false;
        }
    }

    /**
     * Collect form data
     */
    collectFormData(formId) {
        const form = document.getElementById(formId);
        if (!form) return {};

        const formData = new FormData(form);
        const data = {};
        for (const [key, value] of formData.entries()) {
            data[key] = value;
        }
        return data;
    }

    /**
     * Display errors
     */
    displayErrors(errors, containerId = null) {
        if (!errors || Object.keys(errors).length === 0) return;

        const errorMsg = Object.values(errors).join(', ');
        if (containerId) {
            const container = document.getElementById(containerId);
            if (container) {
                container.textContent = errorMsg;
                container.style.display = 'block';
            }
        } else {
            alert(errorMsg);
        }
    }

    /**
     * Clear errors
     */
    clearErrors(containerId) {
        const container = document.getElementById(containerId);
        if (container) {
            container.textContent = '';
            container.style.display = 'none';
        }
    }

    /**
     * Escape HTML
     */
    escapeHtml(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
}

