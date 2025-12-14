/**
 * Edit Appointment Modal Controller
 */

window.EditAppointmentModal = {
    modal: null,
    form: null,
    config: null,
    currentAppointment: null,
    
    init() {
        this.modal = document.getElementById('editAppointmentModal');
        this.form = document.getElementById('editAppointmentForm');
        this.config = this.getConfig();
        
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.handleSubmit(e));
            
            const dateInput = document.getElementById('edit_appointment_date');
            if (dateInput) {
                dateInput.addEventListener('change', () => {
                    if (dateInput.value) {
                        this.loadAvailableDoctors(dateInput.value);
                    }
                });
            }
        }
        
        AppointmentModalUtils.setupModalCloseHandlers(this.modal, () => this.close());
    },
    
    getConfig() {
        const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        return { baseUrl: baseUrl.replace(/\/$/, ''), csrfToken, endpoints: { update: `${baseUrl}appointments` } };
    },
    
    async open(appointmentId) {
        if (!appointmentId) {
            this.showNotification('Appointment ID is required', 'error');
            return;
        }
        
        if (this.modal) {
            AppointmentModalUtils.clearErrors(this.form, 'edit_appointment_');
            AppointmentModalUtils.openModal('editAppointmentModal');
            
            try {
                await this.loadAppointmentDetails(appointmentId);
            } catch (error) {
                console.error('Error loading appointment details:', error);
                this.showNotification('Failed to load appointment details', 'error');
                this.close();
            }
        }
    },
    
    close() {
        if (this.modal) {
            AppointmentModalUtils.closeModal('editAppointmentModal');
            this.currentAppointment = null;
            this.resetForm();
        }
    },
    
    resetForm() {
        if (this.form) {
            this.form.reset();
            AppointmentModalUtils.clearErrors(this.form, 'edit_appointment_');
        }
    },
    
    async loadAppointmentDetails(appointmentId) {
        const response = await fetch(`${this.config.baseUrl}/appointments/${appointmentId}`);
        const result = await response.json();
        
        let appt = result;
        if (result && typeof result === 'object' && 'status' in result) {
            if (result.status !== 'success') {
                throw new Error(result.message || 'Failed to load appointment details');
            }
            appt = result.data || result.appointment || {};
        }
        
        if (!appt) {
            throw new Error('Empty appointment response');
        }
        
        this.currentAppointment = appt;
        await this.loadPatients();
        this.populateForm(appt);
    },
    
    async loadPatients() {
        const patientSelect = document.getElementById('edit_appointment_patient');
        if (!patientSelect) return;
        
        try {
            const response = await fetch(`${this.config.baseUrl}/appointments/patients`);
            const data = await response.json();
            if (data.status === 'success') {
                patientSelect.innerHTML = '<option value="">Select Patient...</option>';
                data.data.forEach(patient => {
                    const option = document.createElement('option');
                    option.value = patient.patient_id;
                    option.textContent = `${patient.first_name} ${patient.last_name}`;
                    patientSelect.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error loading patients:', error);
        }
    },
    
    loadAvailableDoctors(date) {
        const doctorSelect = document.getElementById('edit_appointment_doctor');
        const dateHelp = document.getElementById('edit_appointment_date_help');
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
    
    populateForm(appointment) {
        setTimeout(() => {
            const patientSelect = document.getElementById('edit_appointment_patient');
            const doctorSelect = document.getElementById('edit_appointment_doctor');
            const dateInput = document.getElementById('edit_appointment_date');
            const typeSelect = document.getElementById('edit_appointment_type');
            const notesTextarea = document.getElementById('edit_appointment_notes');
            const idInput = document.getElementById('edit_appointment_id');
            
            if (idInput) idInput.value = appointment.appointment_id || appointment.id || '';
            if (patientSelect) patientSelect.value = appointment.patient_id || '';
            
            if (doctorSelect && appointment.doctor_id) {
                const targetValue = String(appointment.doctor_id);
                let foundOption = Array.from(doctorSelect.options).find(opt => String(opt.value) === targetValue);
                if (!foundOption) {
                    const opt = document.createElement('option');
                    opt.value = targetValue;
                    opt.textContent = appointment.doctor_name || `Doctor #${targetValue}`;
                    doctorSelect.appendChild(opt);
                }
                doctorSelect.value = targetValue;
            }
            
            if (dateInput) {
                const dateVal = appointment.appointment_date || appointment.date || '';
                if (dateVal) {
                    const isoDate = dateVal.split('T')[0].split(' ')[0];
                    dateInput.value = isoDate;
                    this.loadAvailableDoctors(isoDate);
                }
            }
            
            if (typeSelect) typeSelect.value = appointment.appointment_type || appointment.type || '';
            if (notesTextarea) notesTextarea.value = appointment.notes || appointment.reason || '';
        }, 300);
    },
    
    async handleSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const data = AppointmentModalUtils.collectFormData(form);
        const appointmentId = data.appointment_id;
        
        if (!appointmentId) {
            this.showNotification('Appointment ID is required', 'error');
            return;
        }
        
        const endpoint = `${this.config.endpoints.update}/${appointmentId}`;
        
        try {
            this.showLoading(true);
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-Token': this.config.csrfToken},
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            const success = result.status === 'success';
            
            if (success) {
                this.showNotification(result.message || 'Appointment updated successfully', 'success');
                this.close();
                if (window.appointmentManager) {
                    window.appointmentManager.refreshAppointments();
                } else {
                    setTimeout(() => location.reload(), 800);
                }
            } else {
                this.showNotification(result.message || 'Failed to update appointment', 'error');
                if (result.errors) {
                    AppointmentModalUtils.displayErrors(result.errors, 'edit_appointment_');
                }
            }
        } catch (error) {
            console.error('Error updating appointment:', error);
            this.showNotification('Failed to update appointment. Please try again.', 'error');
        } finally {
            this.showLoading(false);
        }
    },
    
    showLoading(show) {
        const submitBtn = document.getElementById('saveEditAppointmentBtn');
        if (submitBtn) {
            submitBtn.disabled = show;
            submitBtn.innerHTML = show 
                ? '<i class="fas fa-spinner fa-spin"></i> Updating...' 
                : '<i class="fas fa-save"></i> Save Changes';
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
window.openEditAppointmentModal = (id) => window.EditAppointmentModal?.open(id);
window.closeEditAppointmentModal = () => window.EditAppointmentModal?.close();
window.editAppointment = (id) => window.EditAppointmentModal?.open(id);

