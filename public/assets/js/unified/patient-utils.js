/**
 * Unified Patient Management Utilities
 * Shared utility functions for patient management across all roles
 */

// Configuration object
const PatientConfig = {
    baseUrl: '',
    csrfToken: '',
    userRole: '',
    
    // API endpoints
    endpoints: {
        patientsApi: 'patients/api',
        patientCreate: 'patients/create',
        patientGet: 'patients',
        patientUpdate: 'patients',
        patientDelete: 'patients',
        patientStatus: 'patients',
        assignDoctor: 'patients',
        doctorsApi: 'patients/doctors' // Unified doctors API endpoint
    },

    // Initialize configuration from meta tags
    init() {
        this.baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        this.userRole = document.querySelector('meta[name="user-role"]')?.content || 'admin';
        
        // Use unified doctors API endpoint for all roles
        this.endpoints.doctorsApi = 'patients/doctors';
    },

    // Get full URL for endpoint
    getUrl(endpoint) {
        return `${this.baseUrl}${endpoint}`;
    }
};

// Initialize configuration when script loads
PatientConfig.init();

/**
 * Utility Functions
 */
const PatientUtils = {
    
    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    },

    /**
     * Calculate age from date of birth
     */
    calculateAge(dateOfBirth) {
        if (!dateOfBirth) return 'N/A';
        
        const today = new Date();
        const birthDate = new Date(dateOfBirth);
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        
        return age >= 0 ? age : 'N/A';
    },

    /**
     * Get initials from name
     */
    getInitials(firstName, lastName) {
        const first = firstName ? firstName.charAt(0).toUpperCase() : '';
        const last = lastName ? lastName.charAt(0).toUpperCase() : '';
        return first + last || 'P';
    },

    /**
     * Format full name
     */
    formatFullName(firstName, middleName, lastName) {
        const parts = [firstName, middleName, lastName].filter(part => part && part.trim());
        return parts.join(' ') || 'Unknown';
    },

    /**
     * Format phone number
     */
    formatPhone(phone) {
        if (!phone) return 'N/A';
        // Simple phone formatting - can be enhanced based on requirements
        return phone.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
    },

    /**
     * Get status badge HTML
     */
    getStatusBadge(status) {
        return this.escapeHtml(status || 'N/A');
    },

    /**
     * Get patient type badge HTML
     */
    getTypeBadge(type) {
        const cleanType = type ? type.charAt(0).toUpperCase() + type.slice(1).toLowerCase() : 'N/A';
        return this.escapeHtml(cleanType);
    },

    /**
     * Show notification
     * Uses the shared patientsNotification bar when present, otherwise falls back to a floating toast.
     */
    showNotification(message, type = 'info', duration = 5000) {
        const container = document.getElementById('patientsNotification');
        const iconEl = document.getElementById('patientsNotificationIcon');
        const textEl = document.getElementById('patientsNotificationText');

        // Prefer the shared top notification bar when available
        if (container && iconEl && textEl) {
            const isError = type === 'error';
            const isSuccess = type === 'success';

            container.style.border = isError ? '1px solid #fecaca' : '1px solid #bbf7d0';
            container.style.background = isError ? '#fee2e2' : '#ecfdf5';
            container.style.color = isError ? '#991b1b' : '#166534';

            const iconClass = isError
                ? 'fa-exclamation-triangle'
                : (isSuccess ? 'fa-check-circle' : 'fa-info-circle');
            iconEl.className = 'fas ' + iconClass;

            textEl.textContent = this.escapeHtml(message || '');
            container.style.display = 'flex';

            setTimeout(() => {
                if (container.style.display !== 'none') {
                    container.style.display = 'none';
                }
            }, duration || 4000);
            return;
        }

        // Fallback: floating toast notification
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} notification fade-in`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            max-width: 500px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        `;
        
        notification.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-${this.getNotificationIcon(type)} me-2"></i>
                <span>${this.escapeHtml(message)}</span>
                <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, duration);
    },

    /**
     * Get notification icon based on type
     */
    getNotificationIcon(type) {
        const icons = {
            'success': 'check-circle',
            'error': 'exclamation-circle',
            'warning': 'exclamation-triangle',
            'info': 'info-circle'
        };
        return icons[type] || 'info-circle';
    },

    /**
     * Show loading state
     */
    showLoading(element, message = 'Loading...') {
        if (!element) return;
        
        element.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2 mb-0">${this.escapeHtml(message)}</p>
            </div>
        `;
    },

    /**
     * Show error state
     */
    showError(element, message = 'An error occurred') {
        if (!element) return;
        
        element.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
                <p class="text-muted">${this.escapeHtml(message)}</p>
                <button class="btn btn-primary btn-sm" onclick="location.reload()">
                    <i class="fas fa-refresh"></i> Retry
                </button>
            </div>
        `;
    },

    /**
     * Validate form data
     */
    validateForm(formData, rules) {
        const errors = {};
        
        for (const [field, rule] of Object.entries(rules)) {
            const value = formData[field];
            
            if (rule.required && (!value || value.trim() === '')) {
                errors[field] = `${rule.label || field} is required`;
                continue;
            }
            
            if (value && rule.minLength && value.length < rule.minLength) {
                errors[field] = `${rule.label || field} must be at least ${rule.minLength} characters`;
                continue;
            }
            
            if (value && rule.maxLength && value.length > rule.maxLength) {
                errors[field] = `${rule.label || field} must not exceed ${rule.maxLength} characters`;
                continue;
            }
            
            if (value && rule.pattern && !rule.pattern.test(value)) {
                errors[field] = rule.message || `${rule.label || field} format is invalid`;
                continue;
            }
            
            if (value && rule.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                errors[field] = `${rule.label || field} must be a valid email address`;
                continue;
            }
        }
        
        return errors;
    },

    sanitizeContactNo(value) {
        return String(value ?? '').replace(/\D/g, '').slice(0, 11);
    },

    getContactNoInlineError(value) {
        const v = String(value ?? '').trim();
        if (v.length === 0) return '';
        if (!/^\d+$/.test(v)) return 'Contact number must contain digits only.';
        if (v.length === 1 && v !== '0') return 'Contact number must start with 09.';
        if (v.length >= 2 && v.slice(0, 2) !== '09') return 'Contact number must start with 09.';
        if (v.length < 11) return 'Contact number must be 11 digits.';
        if (!/^09\d{9}$/.test(v)) return 'Contact number must start with 09 and be exactly 11 digits.';
        return '';
    },

    bindLiveContactNoValidation(inputEl, errorEl) {
        if (!inputEl || !errorEl || inputEl.__boundContactNoValidation) return;
        inputEl.__boundContactNoValidation = true;

        const run = () => {
            const sanitized = PatientUtils.sanitizeContactNo(inputEl.value);
            if (sanitized !== inputEl.value) inputEl.value = sanitized;
            errorEl.textContent = PatientUtils.getContactNoInlineError(inputEl.value);
        };

        inputEl.addEventListener('input', run);
        inputEl.addEventListener('blur', run);
    },

    /**
     * Display form validation errors
     */
    displayFormErrors(errors, formElement) {
        // Clear previous errors
        formElement.querySelectorAll('.is-invalid, .error').forEach(el => {
            el.classList.remove('is-invalid');
            el.classList.remove('error');
        });
        formElement.querySelectorAll('.invalid-feedback, .form-error').forEach(el => {
            el.textContent = '';
            el.style.display = 'none';
        });
        
        // Display new errors
        for (const [field, message] of Object.entries(errors)) {
            const input = formElement.querySelector(`[name="${field}"]`);
            let feedback = null;

            if (input) {
                // Try multiple error element ID patterns
                // Pattern 1: err_edit_${field} (for edit patient modal)
                let errorById = document.getElementById(`err_edit_${field}`);
                if (!errorById) {
                    // Pattern 2: err_edit_inpatient_${field} (for inpatient form)
                    errorById = document.getElementById(`err_edit_inpatient_${field}`);
                }
                if (!errorById) {
                    // Pattern 3: err_${field} (standard pattern)
                    errorById = document.getElementById(`err_${field}`);
                }
                
                if (errorById) {
                    feedback = errorById;
                } else {
                    // Try direct sibling first
                    const sibling = input.nextElementSibling;
                    if (sibling && sibling.classList && 
                        (sibling.classList.contains('invalid-feedback') || sibling.classList.contains('form-error'))) {
                        feedback = sibling;
                    } else if (input.parentElement) {
                        // Fallback: look within parent container
                        feedback = input.parentElement.querySelector('.invalid-feedback, .form-error');
                    }
                }

                // Add error classes to highlight the field
                input.classList.add('is-invalid');
                input.classList.add('error');
                
                // Also add visual highlight with border
                input.style.borderColor = '#ef4444';
                input.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.1)';
            } else {
                // If input not found, still try to find error element by ID
                let errorById = document.getElementById(`err_edit_${field}`);
                if (!errorById) {
                    errorById = document.getElementById(`err_edit_inpatient_${field}`);
                }
                if (!errorById) {
                    errorById = document.getElementById(`err_${field}`);
                }
                if (errorById) {
                    feedback = errorById;
                }
            }

            if (feedback) {
                const errorMessage = Array.isArray(message) ? message[0] : message;
                feedback.textContent = errorMessage;
                feedback.style.display = 'block';
                feedback.style.color = '#ef4444';
                feedback.style.fontSize = '0.875rem';
                feedback.style.marginTop = '0.25rem';
                feedback.style.fontWeight = '500';
            }
            
            // Log for debugging
            if (!input && !feedback) {
                console.warn(`Could not find input or error element for field: ${field}`);
            }
        }
        
        // Scroll to first error field for better UX
        const firstErrorInput = formElement.querySelector('.is-invalid, .error');
        if (firstErrorInput) {
            setTimeout(() => {
                firstErrorInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstErrorInput.focus();
            }, 100);
        }
    },

    /**
     * Make AJAX request with error handling
     */
    async makeRequest(url, options = {}) {
        try {
            const defaultOptions = {
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': PatientConfig.csrfToken
                }
            };
            
            const response = await fetch(url, { ...defaultOptions, ...options });
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            }
            
            return data;
        } catch (error) {
            console.error('Request failed:', error);
            throw error;
        }
    },

    /**
     * Debounce function for search inputs
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Format date for display
     */
    formatDate(date, format = 'short') {
        if (!date) return 'N/A';
        
        const dateObj = new Date(date);
        if (isNaN(dateObj.getTime())) return 'Invalid Date';
        
        const options = {
            short: { year: 'numeric', month: 'short', day: 'numeric' },
            long: { year: 'numeric', month: 'long', day: 'numeric' },
            time: { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            }
        };
        
        return dateObj.toLocaleDateString('en-US', options[format] || options.short);
    }
};

// Helper for shared patients notification bar
function dismissPatientNotification() {
    const container = document.getElementById('patientsNotification');
    if (container) {
        container.style.display = 'none';
    }
}

// Export to global scope
window.PatientConfig = PatientConfig;
window.PatientUtils = PatientUtils;
