/**
 * Assign Doctor Modal Controller
 * Handles the assign doctor modal functionality
 */

const AssignDoctorModal = {
    modal: null,
    form: null,
    currentPatientId: null,
    doctorsCache: null,

    /**
     * Initialize the modal
     */
    init() {
        this.modal = document.getElementById('assignDoctorModal');
        this.form = document.getElementById('assignDoctorForm');
        
        if (!this.modal || !this.form) return;
        
        this.bindEvents();
    },

    /**
     * Bind event listeners
     */
    bindEvents() {
        // Form submission
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // Close modal when clicking outside
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.close();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal.style.display === 'flex') {
                this.close();
            }
        });
    },

    /**
     * Open the modal
     */
    async open(patientId) {
        if (!this.modal || !patientId) return;
        
        this.currentPatientId = patientId;
        this.modal.style.display = 'flex';
        
        await this.loadPatientInfo(patientId);
        await this.loadDoctors();
    },

    /**
     * Close the modal
     */
    close() {
        if (this.modal) {
            this.modal.style.display = 'none';
            this.resetForm();
            this.currentPatientId = null;
        }
    },

    /**
     * Handle modal shown event
     */
    async onModalShown() {
        await this.loadDoctors();
    },

    /**
     * Handle modal hidden event
     */
    onModalHidden() {
        this.resetForm();
        this.currentPatientId = null;
    },

    /**
     * Reset form to initial state
     */
    resetForm() {
        if (this.form) {
            this.form.reset();
            
            // Clear validation states
            this.form.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
            this.form.querySelectorAll('.invalid-feedback').forEach(el => {
                el.textContent = '';
            });
        }
    },

    /**
     * Load patient information
     */
    async loadPatientInfo(patientId) {
        try {
            const response = await PatientUtils.makeRequest(
                PatientConfig.getUrl(`${PatientConfig.endpoints.patientGet}/${patientId}`)
            );
            
            if (response.status === 'success') {
                const patient = response.data;
                const fullName = PatientUtils.formatFullName(patient.first_name, patient.middle_name, patient.last_name);
                
                document.getElementById('assign_patient_id').value = patientId;
                document.getElementById('patient_name_display').value = fullName;
            } else {
                throw new Error(response.message || 'Failed to load patient information');
            }
        } catch (error) {
            console.error('Error loading patient info:', error);
            PatientUtils.showNotification('Failed to load patient information: ' + error.message, 'error');
            this.close();
        }
    },

    /**
     * Load available doctors
     */
    async loadDoctors() {
        const doctorSelect = document.getElementById('doctor_id');
        if (!doctorSelect) return;

        try {
            if (this.doctorsCache) {
                this.populateDoctorsSelect(this.doctorsCache);
                return;
            }

            doctorSelect.innerHTML = '<option value="">Loading doctors...</option>';

            const response = await PatientUtils.makeRequest(
                PatientConfig.getUrl(PatientConfig.endpoints.doctorsApi)
            );

            if (response.status === 'success') {
                this.doctorsCache = response.data || [];
                this.populateDoctorsSelect(this.doctorsCache);
            } else {
                throw new Error(response.message || 'Failed to load doctors');
            }
        } catch (error) {
            console.error('Error loading doctors:', error);
            doctorSelect.innerHTML = '<option value="">Failed to load doctors</option>';
        }
    },

    /**
     * Populate doctors select dropdown
     */
    populateDoctorsSelect(doctors) {
        const doctorSelect = document.getElementById('doctor_id');
        if (!doctorSelect) return;

        doctorSelect.innerHTML = '<option value="">Select Doctor</option>';
        
        doctors.forEach(doctor => {
            const option = document.createElement('option');
            option.value = doctor.staff_id || doctor.id;
            option.textContent = `Dr. ${doctor.first_name} ${doctor.last_name}${doctor.department ? ' - ' + doctor.department : ''}${doctor.specialization ? ' (' + doctor.specialization + ')' : ''}`;
            doctorSelect.appendChild(option);
        });
    },

    /**
     * Handle form submission
     */
    async handleSubmit(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('assignDoctorBtn');
        const originalText = submitBtn.innerHTML;
        
        try {
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Assigning...';
            
            // Collect form data
            const formData = this.collectFormData();
            
            // Validate form data
            const errors = this.validateFormData(formData);
            if (Object.keys(errors).length > 0) {
                PatientUtils.displayFormErrors(errors, this.form);
                return;
            }
            
            // Submit form
            const response = await PatientUtils.makeRequest(
                PatientConfig.getUrl(`${PatientConfig.endpoints.assignDoctor}/${this.currentPatientId}/assign-doctor`),
                {
                    method: 'POST',
                    body: JSON.stringify(formData)
                }
            );
            
            if (response.status === 'success') {
                PatientUtils.showNotification('Doctor assigned successfully!', 'success');
                this.close();
                
                // Refresh patient list
                if (window.patientManager) {
                    window.patientManager.refresh();
                }
            } else {
                throw new Error(response.message || 'Failed to assign doctor');
            }
            
        } catch (error) {
            console.error('Error assigning doctor:', error);
            PatientUtils.showNotification('Failed to assign doctor: ' + error.message, 'error');
        } finally {
            // Restore button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    },

    /**
     * Collect form data
     */
    collectFormData() {
        const formData = new FormData(this.form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        return data;
    },

    /**
     * Validate form data
     */
    validateFormData(data) {
        const rules = {
            doctor_id: { required: true, label: 'Doctor' }
        };
        
        return PatientUtils.validateForm(data, rules);
    }
};

// Export to global scope
window.AssignDoctorModal = AssignDoctorModal;

// Global function for close button
window.closeAssignDoctorModal = function() {
    if (window.AssignDoctorModal) {
        window.AssignDoctorModal.close();
    }
};
