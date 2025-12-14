/**
 * Add Shift Modal Controller
 */

window.AddShiftModal = {
    modal: null,
    form: null,
    config: null,
    
    init() {
        this.modal = document.getElementById('shiftModal');
        this.form = document.getElementById('shiftForm');
        this.config = this.getConfig();
        
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.handleSubmit(e));
            
            const doctorSelect = document.getElementById('doctorSelect');
            if (doctorSelect) {
                doctorSelect.addEventListener('change', () => this.onDoctorChange());
            }
        }
        
        ShiftModalUtils.setupModalCloseHandlers(this.modal, () => this.close());
    },
    
    getConfig() {
        const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const csrfHash = document.querySelector('meta[name="csrf-hash"]')?.content || '';
        
        return {
            baseUrl: baseUrl.replace(/\/$/, ''),
            csrfToken,
            csrfHash,
            endpoints: {
                create: `${baseUrl}shifts/create`,
                update: `${baseUrl}shifts/update`
            }
        };
    },
    
    open(shift = null) {
        if (this.modal) {
            this.resetForm();
            const titleEl = document.getElementById('modalTitle');
            const idInput = document.getElementById('shiftId');
            
            if (shift) {
                // Edit mode
                if (titleEl) titleEl.textContent = 'Edit Shift';
                if (idInput) idInput.value = shift.id || '';
                this.populateForm(shift);
            } else {
                // Create mode
                if (titleEl) titleEl.textContent = 'Create Shift';
                if (idInput) idInput.value = '';
            }
            
            ShiftModalUtils.openModal('shiftModal');
        }
    },
    
    populateForm(shift) {
        const doctorSelect = document.getElementById('doctorSelect');
        const startTimeInput = document.getElementById('startTime');
        const endTimeInput = document.getElementById('endTime');
        const statusSelect = document.getElementById('shiftStatus');

        if (doctorSelect) {
            doctorSelect.value = shift.staff_id || shift.doctor_id || '';
        }

        const weekdayCheckboxes = document.querySelectorAll('input[name="weekdays[]"]');
        if (weekdayCheckboxes && typeof shift.weekday !== 'undefined') {
            const weekdayValue = String(shift.weekday || '');
            weekdayCheckboxes.forEach(cb => cb.checked = cb.value === weekdayValue);
        }

        if (startTimeInput) {
            const startVal = shift.start_time || shift.start || '';
            startTimeInput.value = startVal ? startVal.slice(0,5) : '';
        }

        if (endTimeInput) {
            const endVal = shift.end_time || shift.end || '';
            endTimeInput.value = endVal ? endVal.slice(0,5) : '';
        }

        if (statusSelect) {
            statusSelect.value = ShiftModalUtils.normalizeStatus(shift.status);
        }
    },
    
    close() {
        if (this.modal) {
            ShiftModalUtils.closeModal('shiftModal');
            this.resetForm();
        }
    },
    
    resetForm() {
        if (this.form) {
            this.form.reset();
            ShiftModalUtils.clearErrors(this.form);
        }
    },
    
    async handleSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const data = ShiftModalUtils.collectFormData(form);
        const isEdit = !!data.id;
        data[this.config.csrfToken] = this.config.csrfHash;

        const endpoint = isEdit ? this.config.endpoints.update : this.config.endpoints.create;
        if (isEdit && !this.config.endpoints.update) {
            this.config.endpoints.update = this.config.baseUrl + '/shifts/update';
        }

        try {
            this.showLoading(true);
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.status === 'success') {
                this.showNotification(isEdit ? 'Shift updated successfully' : 'Shift created successfully', 'success');
                this.close();
                if (window.shiftManager) {
                    window.shiftManager.loadShifts();
                }
            } else {
                this.showNotification(result.message || (isEdit ? 'Failed to update shift' : 'Failed to create shift'), 'error');
                if (result.errors) {
                    ShiftModalUtils.displayErrors(result.errors);
                }
            }

            if (result.csrf) {
                this.config.csrfHash = result.csrf.value;
            }
        } catch (error) {
            console.error('Error saving shift:', error);
            this.showNotification(isEdit ? 'Failed to update shift' : 'Failed to create shift', 'error');
        } finally {
            this.showLoading(false);
        }
    },
    
    onDoctorChange() {
        const doctorSelect = document.getElementById('doctorSelect');
        const departmentSelect = document.getElementById('shiftDepartment');
        
        if (doctorSelect && departmentSelect) {
            const selectedOption = doctorSelect.options[doctorSelect.selectedIndex];
            if (selectedOption && selectedOption.textContent.includes(' - ')) {
                const department = selectedOption.textContent.split(' - ')[1];
                departmentSelect.value = department;
            }
        }
    },
    
    showLoading(show) {
        const submitBtn = document.getElementById('saveShiftBtn');
        if (submitBtn) {
            submitBtn.disabled = show;
            submitBtn.innerHTML = show 
                ? '<i class="fas fa-spinner fa-spin"></i> Saving...' 
                : '<i class="fas fa-save"></i> Save Shift';
        }
    },
    
    showNotification(message, type) {
        if (window.shiftManager) {
            window.shiftManager.showNotification(message, type);
        } else {
            if (typeof showUniversalNotification === 'function') {
                showUniversalNotification(message, 'error');
            }
        }
    }
};

// Global functions for backward compatibility
window.openAddShiftModal = () => window.AddShiftModal?.open();
window.closeAddShiftModal = () => window.AddShiftModal?.close();

