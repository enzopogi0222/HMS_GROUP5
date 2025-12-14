/**
 * View Shift Modal Controller
 */

window.ViewShiftModal = {
    modal: null,
    currentShift: null,
    
    init() {
        this.modal = document.getElementById('viewShiftModal');
        ShiftModalUtils.setupModalCloseHandlers(this.modal, () => this.close());
    },
    
    async open(shiftId) {
        if (!shiftId) {
            this.showNotification('Shift ID is required', 'error');
            return;
        }
        
        if (this.modal) {
            ShiftModalUtils.openModal('viewShiftModal');
            
            try {
                await this.loadShiftDetails(shiftId);
                // Ensure form is populated after modal is visible
                if (this.currentShift) {
                    // Use requestAnimationFrame to ensure DOM is ready
                    requestAnimationFrame(() => {
                        this.populateForm(this.currentShift);
                    });
                }
            } catch (error) {
                console.error('Error loading shift details:', error);
                this.showNotification('Failed to load shift details', 'error');
                this.close();
            }
        }
    },
    
    close() {
        if (this.modal) {
            ShiftModalUtils.closeModal('viewShiftModal');
            this.currentShift = null;
            this.clearForm();
        }
    },
    
    async loadShiftDetails(shiftId) {
        if (!window.shiftManager) {
            throw new Error('ShiftManager not available');
        }
        
        const shift = window.shiftManager.shifts.find(s => String(s.id) === String(shiftId));
        if (!shift) {
            throw new Error('Shift not found');
        }
        
        this.currentShift = shift;
        // populateForm will be called after modal is opened
    },
    
    populateForm(shift) {
        console.log('Populating view form with shift data:', shift);
        
        // Helper function to format time (handles HH:mm:ss, HH:mm, or other formats)
        const formatTime = (timeStr) => {
            if (!timeStr) return 'N/A';
            // Remove any whitespace
            timeStr = String(timeStr).trim();
            // If it's already in HH:mm format, return it
            if (timeStr.match(/^\d{2}:\d{2}$/)) {
                return timeStr;
            }
            // If it's in HH:mm:ss format, extract HH:mm
            if (timeStr.match(/^\d{2}:\d{2}:\d{2}/)) {
                return timeStr.substring(0, 5);
            }
            // Try to extract time from any format
            const timeMatch = timeStr.match(/(\d{2}):(\d{2})/);
            if (timeMatch) {
                return timeMatch[1] + ':' + timeMatch[2];
            }
            return timeStr;
        };
        
        // Populate doctor name
        const doctorInput = document.getElementById('viewDoctorName');
        if (doctorInput) {
            const doctorName = (shift.doctor_name || shift.first_name + ' ' + shift.last_name || 'N/A') + 
                              (shift.specialization ? ' - ' + shift.specialization : '');
            doctorInput.value = doctorName;
        }
        
        // Populate weekday checkboxes
        const weekdayCheckboxes = document.querySelectorAll('#viewWeekdays-group input[name="view_weekdays[]"]');
        if (weekdayCheckboxes && weekdayCheckboxes.length > 0) {
            // Clear all checkboxes first
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
                // Single weekday value - convert to string and match
                const weekdayValue = String(shift.weekday).trim();
                const checkbox = Array.from(weekdayCheckboxes).find(cb => cb.value === weekdayValue);
                if (checkbox) {
                    checkbox.checked = true;
                    console.log('Checked weekday checkbox:', weekdayValue);
                } else {
                    console.warn('Weekday checkbox not found for value:', weekdayValue);
                }
            } else {
                console.warn('No weekday data found in shift:', shift);
            }
        } else {
            console.warn('Weekday checkboxes not found in DOM');
        }
        
        // Populate start time
        const startTimeInput = document.getElementById('viewShiftStart');
        if (startTimeInput) {
            const startVal = shift.start_time || shift.start || shift.shift_start || '';
            const formattedStart = formatTime(startVal);
            startTimeInput.value = formattedStart;
            console.log('Set start time:', formattedStart, 'from:', startVal);
        }
        
        // Populate end time
        const endTimeInput = document.getElementById('viewShiftEnd');
        if (endTimeInput) {
            const endVal = shift.end_time || shift.end || shift.shift_end || '';
            const formattedEnd = formatTime(endVal);
            endTimeInput.value = formattedEnd;
            console.log('Set end time:', formattedEnd, 'from:', endVal);
        }
        
        // Populate status
        const statusInput = document.getElementById('viewShiftStatus');
        if (statusInput) {
            const status = ShiftModalUtils ? ShiftModalUtils.normalizeStatus(shift.status) : (shift.status || 'Scheduled');
            statusInput.value = status;
            console.log('Set status:', status);
        }
    },
    
    clearForm() {
        // Clear text inputs
        ['viewDoctorName', 'viewShiftStart', 'viewShiftEnd', 'viewShiftStatus'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        
        // Clear weekday checkboxes
        const weekdayCheckboxes = document.querySelectorAll('#viewWeekdays-group input[name="view_weekdays[]"]');
        if (weekdayCheckboxes) {
            weekdayCheckboxes.forEach(cb => cb.checked = false);
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
window.openViewShiftModal = (shiftId) => window.ViewShiftModal?.open(shiftId);
window.closeViewShiftModal = () => window.ViewShiftModal?.close();

