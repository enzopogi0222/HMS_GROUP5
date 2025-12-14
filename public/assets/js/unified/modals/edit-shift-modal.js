/**
 * Edit Shift Modal Controller
 */

window.EditShiftModal = {
    modal: null,
    form: null,
    config: null,
    currentShift: null,
    
    init() {
        this.modal = document.getElementById('editShiftModal');
        this.form = document.getElementById('editShiftForm');
        this.config = this.getConfig();
        
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.handleSubmit(e));
            
            const doctorSelect = document.getElementById('editDoctorSelect');
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
                update: `${baseUrl}shifts/update`
            }
        };
    },
    
    async open(shiftId) {
        if (!shiftId) {
            this.showNotification('Shift ID is required', 'error');
            return;
        }
        
        if (this.modal) {
            ShiftModalUtils.clearErrors(this.form);
            ShiftModalUtils.openModal('editShiftModal');
            
            try {
                await this.loadShiftDetails(shiftId);
            } catch (error) {
                console.error('Error loading shift details:', error);
                this.showNotification('Failed to load shift details', 'error');
                this.close();
            }
        }
    },
    
    close() {
        if (this.modal) {
            ShiftModalUtils.closeModal('editShiftModal');
            this.currentShift = null;
        }
    },
    
    async loadShiftDetails(shiftId) {
        if (!window.shiftManager) {
            throw new Error('ShiftManager not available');
        }
        
        // First, find the base shift by ID
        const baseShift = window.shiftManager.shifts.find(s => String(s.id) === String(shiftId));
        if (!baseShift) {
            throw new Error('Shift not found');
        }
        
        // If the shift already has a weekdays array, use it
        // Otherwise, find all related shifts (same doctor, time, status) to build weekdays array
        let shift = baseShift;
        let relatedShifts = [];
        
        if (!Array.isArray(shift.weekdays) || shift.weekdays.length === 0) {
            // Find all shifts with the same doctor, start_time, end_time, and status
            const staffId = shift.staff_id || shift.doctor_id || '';
            const startTime = shift.start_time || shift.start || '';
            const endTime = shift.end_time || shift.end || '';
            const status = shift.status || '';
            
            relatedShifts = window.shiftManager.shifts.filter(s => {
                const sStaffId = s.staff_id || s.doctor_id || '';
                const sStartTime = s.start_time || s.start || '';
                const sEndTime = s.end_time || s.end || '';
                const sStatus = s.status || '';
                
                return sStaffId === staffId && 
                       sStartTime === startTime && 
                       sEndTime === endTime && 
                       sStatus === status;
            });
            
            // Collect all weekdays from related shifts
            const weekdays = [];
            relatedShifts.forEach(s => {
                if (s.weekday !== undefined && s.weekday !== null && !weekdays.includes(s.weekday)) {
                    weekdays.push(s.weekday);
                }
            });
            
            // Create a merged shift object with all weekdays
            shift = {
                ...baseShift,
                weekdays: weekdays.sort((a, b) => a - b)
            };
        } else {
            // If weekdays already exist, still need to find related shifts for IDs
            const staffId = shift.staff_id || shift.doctor_id || '';
            const startTime = shift.start_time || shift.start || '';
            const endTime = shift.end_time || shift.end || '';
            const status = shift.status || '';
            
            relatedShifts = window.shiftManager.shifts.filter(s => {
                const sStaffId = s.staff_id || s.doctor_id || '';
                const sStartTime = s.start_time || s.start || '';
                const sEndTime = s.end_time || s.end || '';
                const sStatus = s.status || '';
                
                return sStaffId === staffId && 
                       sStartTime === startTime && 
                       sEndTime === endTime && 
                       sStatus === status;
            });
        }
        
        // Store original shift IDs for all related weekdays (for deletion tracking)
        if (!shift.ids || !Array.isArray(shift.ids) || shift.ids.length === 0) {
            shift.ids = relatedShifts.map(s => s.id).filter(id => id);
            // Fallback to base shift ID if no related shifts found
            if (shift.ids.length === 0 && shift.id) {
                shift.ids = [shift.id];
            }
        }
        
        this.currentShift = shift;
        this.populateForm(shift);
    },
    
    populateForm(shift) {
        const doctorSelect = document.getElementById('editDoctorSelect');
        const startTimeInput = document.getElementById('editShiftStart');
        const endTimeInput = document.getElementById('editShiftEnd');
        const statusSelect = document.getElementById('editShiftStatus');
        const idInput = document.getElementById('editShiftId');

        if (doctorSelect) {
            doctorSelect.value = shift.staff_id || shift.doctor_id || '';
        }
        
        // Handle weekday checkboxes - support both single weekday and array of weekdays
        const weekdayCheckboxes = document.querySelectorAll('#editWeekdays-group input[name="weekdays[]"]');
        if (weekdayCheckboxes && weekdayCheckboxes.length > 0) {
            weekdayCheckboxes.forEach(cb => cb.checked = false);
            
            // If shift has weekdays array, check all of them
            if (Array.isArray(shift.weekdays) && shift.weekdays.length > 0) {
                shift.weekdays.forEach(day => {
                    const dayValue = String(day).trim();
                    const checkbox = Array.from(weekdayCheckboxes).find(cb => cb.value === dayValue);
                    if (checkbox) {
                        checkbox.checked = true;
                        console.log('Checked weekday checkbox:', dayValue);
                    }
                });
            } else if (shift.weekday !== undefined && shift.weekday !== null && shift.weekday !== '') {
                // Single weekday value
                const weekdayValue = String(shift.weekday).trim();
                const checkbox = Array.from(weekdayCheckboxes).find(cb => cb.value === weekdayValue);
                if (checkbox) {
                    checkbox.checked = true;
                    console.log('Checked single weekday checkbox:', weekdayValue);
                }
            }
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
        
        if (idInput) {
            idInput.value = shift.id || '';
        }
    },
    
    async handleSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const data = ShiftModalUtils.collectFormData(form);
        
        // Include original shift IDs and weekdays for comparison
        if (this.currentShift) {
            // Store original weekday IDs and weekday values for deletion tracking
            data.original_ids = this.currentShift.ids || [this.currentShift.id];
            data.original_weekdays = this.currentShift.weekdays || 
                (this.currentShift.weekday ? [this.currentShift.weekday] : []);
        }
        
        data[this.config.csrfToken] = this.config.csrfHash;

        try {
            this.showLoading(true);
            const response = await fetch(this.config.endpoints.update, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.status === 'success') {
                this.showNotification('Shift updated successfully', 'success');
                this.close();
                if (window.shiftManager) {
                    window.shiftManager.loadShifts();
                }
            } else {
                this.showNotification(result.message || 'Failed to update shift', 'error');
                if (result.errors) {
                    ShiftModalUtils.displayErrors(result.errors, 'edit');
                }
            }

            if (result.csrf) {
                this.config.csrfHash = result.csrf.value;
            }
        } catch (error) {
            console.error('Error updating shift:', error);
            this.showNotification('Failed to update shift', 'error');
        } finally {
            this.showLoading(false);
        }
    },
    
    onDoctorChange() {
        const doctorSelect = document.getElementById('editDoctorSelect');
        const departmentSelect = document.getElementById('editShiftDepartment');
        
        if (doctorSelect && departmentSelect) {
            const selectedOption = doctorSelect.options[doctorSelect.selectedIndex];
            if (selectedOption && selectedOption.textContent.includes(' - ')) {
                const department = selectedOption.textContent.split(' - ')[1];
                departmentSelect.value = department;
            }
        }
    },
    
    showLoading(show) {
        const submitBtn = document.getElementById('saveEditShiftBtn');
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
window.openEditShiftModal = (shiftId) => window.EditShiftModal?.open(shiftId);
window.closeEditShiftModal = () => window.EditShiftModal?.close();

