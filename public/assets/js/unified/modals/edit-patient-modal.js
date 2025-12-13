/**
 * Edit Patient Modal Controller
 * Handles the edit patient modal functionality
 */

const EditPatientModal = {
    modal: null,
    form: null,
    currentPatientId: null,

    /**
     * Initialize the modal
     */
    init() {
        this.modal = document.getElementById('editPatientModal');
        this.form = document.getElementById('editPatientForm');
        
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
        
        await this.loadPatientData(patientId);
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
     * Load patient data into form
     */
    async loadPatientData(patientId) {
        try {
            const response = await PatientUtils.makeRequest(
                PatientConfig.getUrl(`${PatientConfig.endpoints.patientGet}/${patientId}`)
            );
            
            if (response.status === 'success') {
                this.populateForm(response.data);
            } else {
                throw new Error(response.message || 'Failed to load patient data');
            }
        } catch (error) {
            console.error('Error loading patient data:', error);
            PatientUtils.showNotification('Failed to load patient data: ' + error.message, 'error');
            this.close();
        }
    },

    /**
     * Populate form with patient data
     */
    populateForm(patient) {
        const fields = {
            'edit_patient_id': patient.patient_id,
            'edit_first_name': patient.first_name,
            'edit_middle_name': patient.middle_name,
            'edit_last_name': patient.last_name,
            'edit_gender': patient.gender?.toLowerCase(),
            'edit_date_of_birth': patient.date_of_birth,
            'edit_civil_status': patient.civil_status,
            'edit_phone': patient.contact_no,
            'edit_email': patient.email,
            'edit_address': patient.address,
            'edit_province': patient.province,
            'edit_city': patient.city,
            'edit_barangay': patient.barangay,
            'edit_zip_code': patient.zip_code,
            'edit_patient_type': patient.patient_type,
            'edit_blood_group': patient.blood_group,
            'edit_status': patient.status,
            'edit_insurance_provider': patient.insurance_provider,
            'edit_insurance_number': patient.insurance_number,
            'edit_emergency_contact_name': patient.emergency_contact,
            'edit_emergency_contact_phone': patient.emergency_phone,
            'edit_medical_notes': patient.medical_notes
        };

        for (const [fieldId, value] of Object.entries(fields)) {
            const field = document.getElementById(fieldId);
            if (field && value !== null && value !== undefined) {
                field.value = value;
            }
        }
    },

    /**
     * Handle form submission
     */
    async handleSubmit(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('updatePatientBtn');
        const originalText = submitBtn.innerHTML;
        
        try {
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            
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
                PatientConfig.getUrl(`${PatientConfig.endpoints.patientUpdate}/${this.currentPatientId}`),
                {
                    method: 'PUT',
                    body: JSON.stringify(formData)
                }
            );
            
            if (response.status === 'success') {
                PatientUtils.showNotification('Patient updated successfully!', 'success');
                this.close();
                
                // Refresh patient list
                if (window.patientManager) {
                    window.patientManager.refresh();
                }
            } else {
                throw new Error(response.message || 'Failed to update patient');
            }
            
        } catch (error) {
            console.error('Error updating patient:', error);
            PatientUtils.showNotification('Failed to update patient: ' + error.message, 'error');
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
            // Remove 'edit_' prefix from field names
            const cleanKey = key.replace(/^edit_/, '');
            data[cleanKey] = value;
        }
        
        return data;
    },

    /**
     * Validate form data
     */
    validateFormData(data) {
        const rules = {
            first_name: { required: true, label: 'First Name' },
            last_name: { required: true, label: 'Last Name' },
            gender: { required: true, label: 'Gender' },
            date_of_birth: { required: true, label: 'Date of Birth' },
            civil_status: { required: true, label: 'Civil Status' },
            phone: { required: true, label: 'Phone Number', pattern: /^09\d{9}$/, message: 'Contact number must start with 09 and be exactly 11 digits.' },
            address: { required: true, label: 'Address' },
            province: { required: true, label: 'Province' },
            city: { required: true, label: 'City' },
            barangay: { required: true, label: 'Barangay' },
            zip_code: { required: true, label: 'ZIP Code' },
            emergency_contact_name: { required: true, label: 'Emergency Contact Name' },
            emergency_contact_phone: { required: true, label: 'Emergency Contact Phone', pattern: /^09\d{9}$/, message: 'Emergency contact number must start with 09 and be exactly 11 digits.' },
            email: { email: true, label: 'Email' }
        };
        
        return PatientUtils.validateForm(data, rules);
    }
};

// Export to global scope
window.EditPatientModal = EditPatientModal;

// Global function for close button
window.closeEditPatientModal = function() {
    if (window.EditPatientModal) {
        window.EditPatientModal.close();
    }
};
