/**
 * View Appointment Modal Controller
 */

window.ViewAppointmentModal = {
    modal: null,
    currentAppointment: null,
    
    init() {
        this.modal = document.getElementById('viewAppointmentModal');
        AppointmentModalUtils.setupModalCloseHandlers(this.modal, () => this.close());
    },
    
    async open(appointmentId) {
        if (!appointmentId) {
            this.showNotification('Appointment ID is required', 'error');
            return;
        }
        
        if (this.modal) {
            AppointmentModalUtils.openModal('viewAppointmentModal');
            
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
            AppointmentModalUtils.closeModal('viewAppointmentModal');
            this.currentAppointment = null;
            this.clearForm();
        }
    },
    
    async loadAppointmentDetails(appointmentId) {
        const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
        const response = await fetch(`${baseUrl}/appointments/${appointmentId}`);
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
        this.populateForm(appt);
    },
    
    populateForm(appointment) {
        const patientSelect = document.getElementById('view_appointment_patient');
        const doctorSelect = document.getElementById('view_appointment_doctor');
        const dateInput = document.getElementById('view_appointment_date');
        const typeSelect = document.getElementById('view_appointment_type');
        const notesTextarea = document.getElementById('view_appointment_notes');
        const roomInput = document.getElementById('view_appointment_room');
        
        if (patientSelect) {
            patientSelect.innerHTML = '';
            const opt = document.createElement('option');
            opt.value = appointment.patient_id || '';
            const firstName = appointment.patient_first_name || appointment.first_name || '';
            const lastName = appointment.patient_last_name || appointment.last_name || '';
            opt.textContent = `${firstName} ${lastName}`.trim() || (appointment.patient_id ? `Patient #${appointment.patient_id}` : 'Patient');
            patientSelect.appendChild(opt);
        }
        
        if (doctorSelect) {
            doctorSelect.innerHTML = '';
            const opt = document.createElement('option');
            opt.value = appointment.doctor_id || appointment.staff_id || '';
            const docFirst = appointment.doctor_first_name || appointment.staff_first_name || '';
            const docLast = appointment.doctor_last_name || appointment.staff_last_name || '';
            opt.textContent = `${docFirst} ${docLast}`.trim() || (opt.value ? `Doctor #${opt.value}` : 'Doctor');
            doctorSelect.appendChild(opt);
        }
        
        if (dateInput) {
            const dateVal = appointment.appointment_date || appointment.date || '';
            if (dateVal) {
                const isoDate = dateVal.split('T')[0].split(' ')[0];
                dateInput.value = isoDate;
            }
        }
        
        if (typeSelect) {
            typeSelect.value = appointment.appointment_type || appointment.type || '';
        }
        
        if (roomInput) {
            const roomNumber = appointment.current_room_number || '';
            const bedNumber = appointment.current_bed_number || '';
            const floorNumber = appointment.current_floor_number || '';

            let roomParts = [];
            if (roomNumber) {
                roomParts.push('Room ' + roomNumber);
            }
            if (bedNumber) {
                roomParts.push('Bed ' + bedNumber.replace(/^Bed\s*/i, ''));
            }
            if (floorNumber) {
                roomParts.push('Floor ' + floorNumber);
            }

            roomInput.value = roomParts.length ? roomParts.join(' • ') : '';
        }

        if (notesTextarea) {
            const baseNotes = appointment.notes || appointment.reason || '';
            notesTextarea.value = baseNotes;
        }
    },
    
    clearForm() {
        ['view_appointment_patient', 'view_appointment_doctor', 'view_appointment_date', 'view_appointment_type', 'view_appointment_notes', 'view_appointment_room'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                if (el.tagName === 'SELECT') el.innerHTML = '<option value="">Select...</option>';
                else el.value = '';
            }
        });
    },
    
    showNotification(message, type) {
        if (window.appointmentManager) {
            window.appointmentManager.showNotification(message, type);
        } else if (typeof showAppointmentsNotification === 'function') {
            showAppointmentsNotification(message, type);
        } else {
            alert(message);
        }
    }
};

// Global functions for backward compatibility
window.openViewAppointmentModal = (id) => window.ViewAppointmentModal?.open(id);
window.closeViewAppointmentModal = () => window.ViewAppointmentModal?.close();
window.viewAppointment = (id) => window.ViewAppointmentModal?.open(id);

