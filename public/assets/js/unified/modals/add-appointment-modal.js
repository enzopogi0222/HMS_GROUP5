/**
 * Add Appointment Modal Controller
 */

window.AddAppointmentModal = {
    modal: null,
    form: null,
    config: null,
    
    init() {
        this.modal = document.getElementById('newAppointmentModal');
        this.form = document.getElementById('newAppointmentForm');
        this.config = this.getConfig();
        
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.handleSubmit(e));
            
            const dateInput = document.getElementById('appointment_date');
            if (dateInput) {
                dateInput.addEventListener('change', () => {
                    if (dateInput.value) {
                        this.loadAvailableDoctors(dateInput.value);
                    }
                });
            }
        }
        
        AppointmentModalUtils.setupModalCloseHandlers(this.modal, () => this.close());
        
        const scheduleBtn = document.getElementById('scheduleAppointmentBtn');
        if (scheduleBtn) {
            scheduleBtn.addEventListener('click', () => {
                // Check permission before opening modal
                if (this.canCreateAppointment()) {
                    this.open();
                } else {
                    if (typeof showAppointmentsNotification === 'function') {
                        showAppointmentsNotification('You do not have permission to create appointments. Only administrators, doctors, and receptionists can create appointments.', 'error');
                    } else if (typeof showUniversalNotification === 'function') {
                        showUniversalNotification('You do not have permission to create appointments. Only administrators, doctors, and receptionists can create appointments.', 'error');
                    }
                }
            });
        }
    },
    
    getConfig() {
        const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const userRole = document.querySelector('meta[name="user-role"]')?.content || '';
        return { baseUrl: baseUrl.replace(/\/$/, ''), csrfToken, userRole, endpoints: { create: `${baseUrl}appointments/create`, update: `${baseUrl}appointments` } };
    },
    
    canCreateAppointment() {
        const userRole = this.config?.userRole || document.querySelector('meta[name="user-role"]')?.content || '';
        return ['admin', 'doctor', 'receptionist'].includes(userRole);
    },
    
    open() {
        if (this.modal) {
            this.resetForm();
            AppointmentModalUtils.openModal('newAppointmentModal');
            this.loadPatients();
            
            const dateInput = document.getElementById('appointment_date');
            const dateValue = dateInput?.value || new Date().toISOString().split('T')[0];
            if (dateInput && !dateInput.value) dateInput.value = dateValue;
            this.loadAvailableDoctors(dateValue);
        }
    },
    
    close() {
        if (this.modal) {
            AppointmentModalUtils.closeModal('newAppointmentModal');
            this.resetForm();
        }
    },
    
    resetForm() {
        if (this.form) {
            this.form.reset();
            AppointmentModalUtils.clearErrors(this.form, 'appointment_');
        }
    },
    
    async loadPatients() {
        const patientSelect = document.getElementById('appointment_patient');
        if (!patientSelect) return;
        
        try {
            const response = await fetch(`${this.config.baseUrl}/appointments/patients`);
            const data = await response.json();
            if (data.status === 'success') {
                patientSelect.innerHTML = '<option value="">Select Patient...</option>';
                data.data.forEach(patient => {
                    const option = document.createElement('option');
                    option.value = patient.patient_id;
                    const fullName = `${patient.first_name} ${patient.last_name}`;
                    // Display patient type if available (handle both null/undefined and empty string)
                    let patientType = '';
                    if (patient.patient_type && patient.patient_type.trim() !== '') {
                        const type = patient.patient_type.trim();
                        patientType = ` (${type.charAt(0).toUpperCase() + type.slice(1)})`;
                    }
                    option.textContent = `${fullName}${patientType}`;
                    patientSelect.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error loading patients:', error);
        }
    },
    
    loadAvailableDoctors(date) {
        const doctorSelect = document.getElementById('appointment_doctor');
        const dateHelp = document.getElementById('appointment_date_help');
        if (!doctorSelect) return;
        
        doctorSelect.innerHTML = '<option value="">Loading available doctors...</option>';
        if (dateHelp) dateHelp.textContent = '';
        
        const weekday = this.getWeekdayName(date);
        const url = `${this.config.baseUrl}/appointments/available-doctors?date=${encodeURIComponent(date)}&weekday=${encodeURIComponent(weekday)}`;
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                doctorSelect.innerHTML = '<option value="">Select Doctor...</option>';
                if (data.status === 'success' && Array.isArray(data.data) && data.data.length) {
                    data.data.forEach(doctor => {
                        const option = document.createElement('option');
                        option.value = doctor.staff_id;
                        option.textContent = `${doctor.first_name} ${doctor.last_name}${doctor.specialization ? ' - ' + doctor.specialization : ''}`;
                        doctorSelect.appendChild(option);
                    });
                    if (dateHelp) dateHelp.textContent = 'Doctors listed are available on this date.';
                } else {
                    if (dateHelp) dateHelp.textContent = 'No doctors are available on this date.';
                }
            })
            .catch(error => {
                console.error('Error loading available doctors:', error);
                doctorSelect.innerHTML = '<option value="">Error loading doctors</option>';
                if (dateHelp) dateHelp.textContent = 'Failed to load doctor availability.';
            });
    },
    
    getWeekdayName(dateStr) {
        const d = new Date(dateStr);
        const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        return isNaN(d.getTime()) ? '' : days[d.getDay()];
    },
    
    async handleSubmit(e) {
        e.preventDefault();
        const form = e.target;
        let data = AppointmentModalUtils.collectFormData(form);
        
        // Map fields for doctor role (backend expects different field names, with defaults like admin)
        if (this.config.userRole === 'doctor') {
            data = {
                patient_id: data.patient_id,
                date: data.appointment_date || data.date,
                time: '09:00:00', // Default time like admin/receptionist
                type: data.appointment_type || data.type,
                duration: 30, // Default duration like admin/receptionist
                reason: data.notes || data.reason || ''
            };
        }
        
        try {
            this.showLoading(true);
            const response = await fetch(this.config.endpoints.create, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-Token': this.config.csrfToken},
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            const success = !!result.success;
            
            if (success) {
                this.showNotification(result.message || 'Appointment scheduled successfully', 'success');
                this.close();
                if (window.appointmentManager) {
                    window.appointmentManager.refreshAppointments();
                } else {
                    setTimeout(() => location.reload(), 800);
                }
            } else {
                this.showNotification(result.message || 'Failed to save appointment', 'error');
                if (result.errors) {
                    // Map backend field names to form field names for display
                    const mappedErrors = {};
                    for (const [field, message] of Object.entries(result.errors)) {
                        let formField = field;
                        if (this.config.userRole === 'doctor') {
                            // Map backend field names to form field names
                            if (field === 'date') formField = 'appointment_date';
                            else if (field === 'type') formField = 'appointment_type';
                            // time and duration are no longer in the form, so skip those errors
                            else if (field === 'time' || field === 'duration') continue;
                        }
                        mappedErrors[formField] = message;
                    }
                    AppointmentModalUtils.displayErrors(mappedErrors, 'appointment_');
                }
            }
        } catch (error) {
            console.error('Error saving appointment:', error);
            this.showNotification('Failed to save appointment. Please try again.', 'error');
        } finally {
            this.showLoading(false);
        }
    },
    
    showLoading(show) {
        const submitBtn = document.getElementById('saveAppointmentBtn');
        if (submitBtn) {
            submitBtn.disabled = show;
            submitBtn.innerHTML = show 
                ? '<i class="fas fa-spinner fa-spin"></i> Saving...' 
                : '<i class="fas fa-calendar-check"></i> Schedule Appointment';
        }
    },
    
    showNotification(message, type) {
        if (window.appointmentManager) {
            window.appointmentManager.showNotification(message, type);
        } else if (typeof showAppointmentsNotification === 'function') {
            showAppointmentsNotification(message, type);
        } else {
            if (typeof showAppointmentsNotification === 'function') {
                showAppointmentsNotification(message, 'error');
            } else if (typeof showUniversalNotification === 'function') {
                showUniversalNotification(message, 'error');
            }
        }
    }
};

// Global functions for backward compatibility
window.openNewAppointmentModal = () => window.AddAppointmentModal?.open();
window.closeNewAppointmentModal = () => window.AddAppointmentModal?.close();

