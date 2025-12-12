/**
 * View Patient Modal Controller
 * Handles the view patient modal functionality
 */

const ViewPatientModal = {
    modal: null,
    currentPatientId: null,

    /**
     * Initialize the modal
     */
    init() {
        this.modal = document.getElementById('viewPatientModal');
        
        if (!this.modal) return;
        
        this.bindEvents();
    },

    /**
     * Bind event listeners
     */
    bindEvents() {
        // Edit from view button
        const editFromViewBtn = document.getElementById('editFromViewBtn');
        if (editFromViewBtn) {
            editFromViewBtn.addEventListener('click', () => this.editFromView());
        }
        
        // Close modal when clicking outside
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.close();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal && !this.modal.hasAttribute('hidden')) {
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
        this.modal.removeAttribute('hidden');
        
        await this.loadPatientDetails(patientId);
    },

    /**
     * Close the modal
     */
    close() {
        if (this.modal) {
            this.modal.style.display = 'none';
            this.modal.setAttribute('hidden', '');
            this.currentPatientId = null;
        }
    },

    /**
     * Load patient details
     */
    async loadPatientDetails(patientId) {
        const contentDiv = document.getElementById('viewPatientContent');
        
        try {
            PatientUtils.showLoading(contentDiv, 'Loading patient details...');
            
            // Try to get comprehensive patient records first
            let patientData = null;
            try {
                const recordsResponse = await PatientUtils.makeRequest(
                    PatientConfig.getUrl(`patients/${patientId}/records`)
                );
                if (recordsResponse.status === 'success' && recordsResponse.records && recordsResponse.records.patient) {
                    patientData = { ...recordsResponse.records.patient };
                    // Merge related data if available
                    if (recordsResponse.records.outpatient_visits && recordsResponse.records.outpatient_visits.length > 0) {
                        const latestVisit = recordsResponse.records.outpatient_visits[0];
                        Object.assign(patientData, latestVisit);
                    }
                    if (recordsResponse.records.inpatient_admissions && recordsResponse.records.inpatient_admissions.length > 0) {
                        const latestAdmission = recordsResponse.records.inpatient_admissions[0];
                        Object.assign(patientData, latestAdmission);
                        
                        // Merge room assignment data if available
                        if (latestAdmission.room_assignments && latestAdmission.room_assignments.length > 0) {
                            const latestRoom = latestAdmission.room_assignments[0];
                            Object.assign(patientData, {
                                room_type: latestRoom.room_type,
                                floor_number: latestRoom.floor_number,
                                room_number: latestRoom.room_number,
                                bed_number: latestRoom.bed_number,
                                daily_rate: latestRoom.daily_rate
                            });
                        }
                        
                        // Merge medical history if available
                        if (latestAdmission.medical_history) {
                            Object.assign(patientData, {
                                history_allergies: latestAdmission.medical_history.allergies,
                                past_medical_history: latestAdmission.medical_history.past_medical_history,
                                past_surgical_history: latestAdmission.medical_history.past_surgical_history,
                                family_history: latestAdmission.medical_history.family_history,
                                history_current_medications: latestAdmission.medical_history.current_medications
                            });
                        }
                        
                        // Merge initial assessment if available
                        if (latestAdmission.initial_assessment) {
                            Object.assign(patientData, {
                                assessment_bp: latestAdmission.initial_assessment.blood_pressure,
                                assessment_hr: latestAdmission.initial_assessment.heart_rate,
                                assessment_rr: latestAdmission.initial_assessment.respiratory_rate,
                                assessment_temp: latestAdmission.initial_assessment.temperature,
                                assessment_spo2: latestAdmission.initial_assessment.spo2,
                                level_of_consciousness: latestAdmission.initial_assessment.level_of_consciousness,
                                pain_level: latestAdmission.initial_assessment.pain_level,
                                initial_findings: latestAdmission.initial_assessment.initial_findings,
                                assessment_remarks: latestAdmission.initial_assessment.remarks
                            });
                        }
                    }
                    if (recordsResponse.records.emergency_contacts && recordsResponse.records.emergency_contacts.length > 0) {
                        const emergencyContact = recordsResponse.records.emergency_contacts[0];
                        Object.assign(patientData, {
                            emergency_contact_name: emergencyContact.name,
                            emergency_contact_relationship: emergencyContact.relationship,
                            emergency_contact_phone: emergencyContact.contact_number
                        });
                    }
                }
            } catch (e) {
                console.log('Comprehensive records not available, using basic patient data:', e);
            }
            
            // Fallback to basic patient data if comprehensive records not available
            if (!patientData) {
            const response = await PatientUtils.makeRequest(
                PatientConfig.getUrl(`${PatientConfig.endpoints.patientGet}/${patientId}`)
            );
            
            if (response.status === 'success') {
                    patientData = response.data;
            } else {
                throw new Error(response.message || 'Failed to load patient details');
            }
            }
            
            // Debug: Log patient data to see what fields are available
            console.log('Patient data loaded:', patientData);
            console.log('Address fields check:', {
                province: patientData.province,
                city: patientData.city,
                barangay: patientData.barangay,
                subdivision: patientData.subdivision,
                house_number: patientData.house_number,
                zip_code: patientData.zip_code
            });
            
            this.displayPatientDetails(patientData);
        } catch (error) {
            console.error('Error loading patient details:', error);
            PatientUtils.showError(contentDiv, 'Failed to load patient details');
        }
    },

    /**
     * Display patient details
     */
    displayPatientDetails(patient) {
        const contentDiv = document.getElementById('viewPatientContent');
        
        const fullName = PatientUtils.formatFullName(patient.first_name, patient.middle_name, patient.last_name);
        const age = PatientUtils.calculateAge(patient.date_of_birth);
        const statusBadge = this.getStatusBadgeHtml(patient.status);
        const typeBadge = this.getTypeBadgeHtml(patient.patient_type);
        
        contentDiv.innerHTML = `
            <div class="form-section">
                <div class="section-header">
                    <div>
                        <h4>1. Personal Information</h4>
                        <p class="section-subtitle">Patient's basic demographic details.</p>
                    </div>
                </div>
                <div class="form-grid">
                    <div>
                        <label class="form-label">Full Name</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(fullName)}</div>
                    </div>
                    <div>
                        <label class="form-label">Patient ID</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.patient_id || 'N/A')}</div>
                    </div>
                    <div>
                        <label class="form-label">Gender / Sex</label>
                        <div class="detail-value">${PatientUtils.escapeHtml((patient.gender || patient.sex) ? (patient.gender || patient.sex).charAt(0).toUpperCase() + (patient.gender || patient.sex).slice(1) : 'N/A')}</div>
                    </div>
                    <div>
                        <label class="form-label">Date of Birth</label>
                        <div class="detail-value">${PatientUtils.formatDate(patient.date_of_birth) || 'N/A'}</div>
                    </div>
                    <div>
                        <label class="form-label">Age</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(age)} ${age !== 'N/A' ? 'years old' : ''}</div>
                    </div>
                    <div>
                        <label class="form-label">Civil Status</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.civil_status || 'N/A')}</div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="section-header">
                    <div>
                        <h4>2. Contact Information</h4>
                        <p class="section-subtitle">Patient's contact and address details.</p>
                    </div>
                </div>
                <div class="form-grid">
                    <div>
                        <label class="form-label">Phone Number</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.phone || patient.contact_number || patient.contact_no || 'N/A')}</div>
                </div>
                    <div>
                        <label class="form-label">Email Address</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.email || 'N/A')}</div>
                    </div>
                    <div>
                        <label class="form-label">Province</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.province && patient.province.trim() !== '' ? patient.province : 'N/A')}</div>
                    </div>
                    <div>
                        <label class="form-label">City / Municipality</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.city && patient.city.trim() !== '' ? patient.city : 'N/A')}</div>
                    </div>
                    <div>
                        <label class="form-label">Barangay</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.barangay && patient.barangay.trim() !== '' ? patient.barangay : 'N/A')}</div>
                    </div>
                    <div>
                        <label class="form-label">Subdivision / Village</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.subdivision && patient.subdivision.trim() !== '' ? patient.subdivision : 'N/A')}</div>
                    </div>
                    <div>
                        <label class="form-label">House / Lot / Block / Unit No.</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.house_number && patient.house_number.trim() !== '' ? patient.house_number : 'N/A')}</div>
                    </div>
                    <div>
                        <label class="form-label">ZIP Code</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.zip_code && patient.zip_code.trim() !== '' ? patient.zip_code : 'N/A')}</div>
                    </div>
                    <div class="full">
                        <label class="form-label">Complete Address</label>
                        <div class="detail-value">${this.formatCompleteAddress(patient)}</div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="section-header">
                    <div>
                        <h4>3. Emergency Contact</h4>
                        <p class="section-subtitle">Emergency contact information.</p>
                    </div>
                </div>
                <div class="form-grid">
                    <div>
                        <label class="form-label">Contact Person Name</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.emergency_contact_name || patient.guardian_name || patient.emergency_contact || 'N/A')}</div>
                    </div>
                    <div>
                        <label class="form-label">Relationship</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.emergency_contact_relationship || patient.guardian_relationship || 'N/A')}</div>
                    </div>
                    <div>
                        <label class="form-label">Contact Number</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.emergency_contact_phone || patient.emergency_contact_phone || patient.guardian_contact || patient.emergency_phone || 'N/A')}</div>
                    </div>
                    ${patient.secondary_contact ? `
                    <div>
                        <label class="form-label">Secondary Contact</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.secondary_contact)}</div>
                        </div>
                    ` : ''}
                </div>
            </div>

            <div class="form-section">
                <div class="section-header">
                    <div>
                        <h4>4. Medical & Administrative Information</h4>
                        <p class="section-subtitle">Patient classification and medical details.</p>
                    </div>
                </div>
                <div class="form-grid">
                    <div>
                        <label class="form-label">Patient Type</label>
                        <div class="detail-value">${typeBadge}</div>
                    </div>
                    <div>
                        <label class="form-label">Blood Group</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.blood_group || 'N/A')}</div>
                    </div>
                    <div>
                        <label class="form-label">Status</label>
                        <div class="detail-value">${statusBadge}</div>
                    </div>
                    ${patient.assigned_doctor_name || patient.primary_doctor_id ? `
                    <div>
                        <label class="form-label">Assigned Doctor</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.assigned_doctor_name || 'N/A')}</div>
                    </div>
                    ` : ''}
                    <div>
                        <label class="form-label">Date Registered</label>
                        <div class="detail-value">${PatientUtils.formatDate(patient.date_registered || patient.created_at) || 'N/A'}</div>
                    </div>
                </div>
            </div>

            ${patient.department || patient.appointment_datetime || patient.visit_type || patient.payment_type ? `
            <div class="form-section">
                <div class="section-header">
                    <div>
                        <h4>5. Outpatient Visit Details</h4>
                        <p class="section-subtitle">Visit and appointment information.</p>
                    </div>
                </div>
                <div class="form-grid">
                    ${patient.department ? `
                    <div>
                        <label class="form-label">Department / Clinic</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.department)}</div>
                    </div>
                    ` : ''}
                    ${patient.appointment_datetime ? `
                    <div>
                        <label class="form-label">Appointment Date & Time</label>
                        <div class="detail-value">${this.formatDateTime(patient.appointment_datetime)}</div>
                    </div>
                    ` : ''}
                    ${patient.visit_type ? `
                    <div>
                        <label class="form-label">Visit Type</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.visit_type)}</div>
                    </div>
                    ` : ''}
                    ${patient.payment_type ? `
                    <div>
                        <label class="form-label">Payment Type</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.payment_type)}</div>
                        </div>
                    ` : ''}
                </div>
            </div>
            ` : ''}

            ${patient.insurance_provider || patient.insurance_number || patient.insurance_card_number || patient.hmo_member_id ? `
            <div class="form-section">
                <div class="section-header">
                    <div>
                        <h4>Insurance / HMO Information</h4>
                        <p class="section-subtitle">Insurance and coverage details.</p>
                    </div>
                </div>
                <div class="form-grid">
                    <div>
                        <label class="form-label">Insurance Provider</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.insurance_provider || 'N/A')}</div>
                    </div>
                    <div>
                        <label class="form-label">Insurance Card Number</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.insurance_card_number || patient.insurance_number || 'N/A')}</div>
                    </div>
                    <div>
                        <label class="form-label">Insurance Validity</label>
                        <div class="detail-value">${patient.insurance_validity ? PatientUtils.formatDate(patient.insurance_validity) : 'N/A'}</div>
                    </div>
                    <div>
                        <label class="form-label">HMO Member ID</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.hmo_member_id || 'N/A')}</div>
                </div>
                    <div>
                        <label class="form-label">HMO Approval Code / LOA Number</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.hmo_approval_code || 'N/A')}</div>
                    </div>
                    <div>
                        <label class="form-label">HMO Cardholder Name</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.hmo_cardholder_name || 'N/A')}</div>
                    </div>
                    <div>
                        <label class="form-label">HMO Coverage Type</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.hmo_coverage_type || 'N/A')}</div>
                    </div>
                    <div>
                        <label class="form-label">HMO Expiry Date</label>
                        <div class="detail-value">${patient.hmo_expiry_date ? PatientUtils.formatDate(patient.hmo_expiry_date) : 'N/A'}</div>
                    </div>
                </div>
            </div>
            ` : ''}

            ${patient.chief_complaint || patient.allergies || patient.existing_conditions || patient.current_medications ? `
            <div class="form-section">
                <div class="section-header">
                    <div>
                        <h4>Medical Information</h4>
                        <p class="section-subtitle">Current complaints and medical history.</p>
                    </div>
                </div>
                <div class="form-grid">
                    ${patient.chief_complaint ? `
                    <div class="full">
                        <label class="form-label">Chief Complaint / Reason for Visit</label>
                        <div class="detail-value" style="white-space: pre-wrap; line-height: 1.6;">${PatientUtils.escapeHtml(patient.chief_complaint)}</div>
                    </div>
                    ` : ''}
                    ${patient.allergies ? `
                    <div class="full">
                        <label class="form-label">Allergies</label>
                        <div class="detail-value" style="white-space: pre-wrap; line-height: 1.6;">${PatientUtils.escapeHtml(patient.allergies)}</div>
                    </div>
                    ` : ''}
                    ${patient.existing_conditions ? `
                    <div class="full">
                        <label class="form-label">Existing Conditions</label>
                        <div class="detail-value" style="white-space: pre-wrap; line-height: 1.6;">${PatientUtils.escapeHtml(patient.existing_conditions)}</div>
                    </div>
                    ` : ''}
                    ${patient.current_medications ? `
                    <div class="full">
                        <label class="form-label">Current Medications</label>
                        <div class="detail-value" style="white-space: pre-wrap; line-height: 1.6;">${PatientUtils.escapeHtml(patient.current_medications)}</div>
                    </div>
                    ` : ''}
                </div>
            </div>
            ` : ''}

            ${patient.admission_datetime || patient.admission_type || patient.admitting_diagnosis || patient.admitting_doctor ? `
            <div class="form-section">
                <div class="section-header">
                    <div>
                        <h4>Admission Details</h4>
                        <p class="section-subtitle">Information gathered during admission intake.</p>
                    </div>
                </div>
                <div class="form-grid">
                    ${patient.admission_number ? `
                    <div>
                        <label class="form-label">Admission Number</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.admission_number)}</div>
                    </div>
                    ` : ''}
                    ${patient.admission_datetime ? `
                    <div>
                        <label class="form-label">Date & Time of Admission</label>
                        <div class="detail-value">${this.formatDateTime(patient.admission_datetime)}</div>
                    </div>
                    ` : ''}
                    ${patient.admission_type ? `
                    <div>
                        <label class="form-label">Admission Type</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.admission_type)}</div>
                    </div>
                    ` : ''}
                    ${patient.admitting_diagnosis ? `
                    <div class="full">
                        <label class="form-label">Admitting Diagnosis</label>
                        <div class="detail-value" style="white-space: pre-wrap; line-height: 1.6;">${PatientUtils.escapeHtml(patient.admitting_diagnosis)}</div>
                    </div>
                    ` : ''}
                    ${patient.admitting_doctor ? `
                    <div>
                        <label class="form-label">Admitting Doctor</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.admitting_doctor)}</div>
                    </div>
                    ` : ''}
                    ${patient.consent_uploaded !== undefined && patient.consent_uploaded !== null ? `
                    <div>
                        <label class="form-label">Consent Form Uploaded</label>
                        <div class="detail-value">${patient.consent_uploaded == 1 || patient.consent_uploaded === '1' ? 'Yes' : 'No'}</div>
                    </div>
                    ` : ''}
                </div>
            </div>
            ` : ''}

            ${patient.room_number || patient.bed_number || patient.room_type ? `
            <div class="form-section">
                <div class="section-header">
                    <div>
                        <h4>Room & Bed Assignment</h4>
                        <p class="section-subtitle">Current room and bed allocation.</p>
                    </div>
                </div>
                <div class="form-grid">
                    ${patient.room_type ? `
                    <div>
                        <label class="form-label">Room Type</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.room_type)}</div>
                    </div>
                    ` : ''}
                    ${patient.floor_number ? `
                    <div>
                        <label class="form-label">Floor Number</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.floor_number)}</div>
                    </div>
                    ` : ''}
                    ${patient.room_number ? `
                    <div>
                        <label class="form-label">Room Number</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.room_number)}</div>
                    </div>
                    ` : ''}
                    ${patient.bed_number ? `
                    <div>
                        <label class="form-label">Bed Number</label>
                        <div class="detail-value">${PatientUtils.escapeHtml(patient.bed_number)}</div>
                    </div>
                    ` : ''}
                    ${patient.daily_rate ? `
                    <div>
                        <label class="form-label">Daily Room Rate</label>
                        <div class="detail-value">₱${parseFloat(patient.daily_rate || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                    </div>
                    ` : ''}
                </div>
            </div>
            ` : ''}

            ${patient.medical_notes || patient.history_allergies || patient.past_medical_history || patient.past_surgical_history || patient.family_history || patient.history_current_medications ? `
            <div class="form-section">
                <div class="section-header">
                    <div>
                        <h4>Medical History & Notes</h4>
                        <p class="section-subtitle">Additional medical information and notes.</p>
                    </div>
                </div>
                <div class="form-grid">
                    ${patient.history_allergies ? `
                    <div class="full">
                        <label class="form-label">Allergies</label>
                        <div class="detail-value" style="white-space: pre-wrap; line-height: 1.6;">${PatientUtils.escapeHtml(patient.history_allergies)}</div>
                    </div>
                    ` : ''}
                    ${patient.past_medical_history ? `
                    <div class="full">
                        <label class="form-label">Past Medical History</label>
                        <div class="detail-value" style="white-space: pre-wrap; line-height: 1.6;">${PatientUtils.escapeHtml(patient.past_medical_history)}</div>
                    </div>
                    ` : ''}
                    ${patient.past_surgical_history ? `
                    <div class="full">
                        <label class="form-label">Past Surgical History</label>
                        <div class="detail-value" style="white-space: pre-wrap; line-height: 1.6;">${PatientUtils.escapeHtml(patient.past_surgical_history)}</div>
                    </div>
                    ` : ''}
                    ${patient.family_history ? `
                    <div class="full">
                        <label class="form-label">Family History</label>
                        <div class="detail-value" style="white-space: pre-wrap; line-height: 1.6;">${PatientUtils.escapeHtml(patient.family_history)}</div>
                    </div>
                    ` : ''}
                    ${patient.history_current_medications ? `
                    <div class="full">
                        <label class="form-label">Current Medications</label>
                        <div class="detail-value" style="white-space: pre-wrap; line-height: 1.6;">${PatientUtils.escapeHtml(patient.history_current_medications)}</div>
                    </div>
                    ` : ''}
                    ${patient.medical_notes ? `
                    <div class="full">
                        <label class="form-label">Medical Notes</label>
                        <div class="detail-value" style="white-space: pre-wrap; line-height: 1.6;">${PatientUtils.escapeHtml(patient.medical_notes)}</div>
                    </div>
                    ` : ''}
                </div>
            </div>
            ` : ''}
        `;
    },

    /**
     * Get status badge HTML
     */
    getStatusBadgeHtml(status) {
        if (!status) return '<span style="color: #64748b;">N/A</span>';
        const statusLower = status.toLowerCase();
        const isActive = statusLower === 'active';
        return `<span style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 500; background: ${isActive ? '#dcfce7' : '#fee2e2'}; color: ${isActive ? '#166534' : '#991b1b'};">${PatientUtils.escapeHtml(status)}</span>`;
    },

    /**
     * Get type badge HTML
     */
    getTypeBadgeHtml(type) {
        if (!type) return '<span style="color: #64748b;">N/A</span>';
        const typeLower = type.toLowerCase();
        const colors = {
            'outpatient': { bg: '#dbeafe', color: '#1e40af' },
            'inpatient': { bg: '#fef3c7', color: '#92400e' },
            'emergency': { bg: '#fee2e2', color: '#991b1b' }
        };
        const colorScheme = colors[typeLower] || { bg: '#f1f5f9', color: '#475569' };
        return `<span style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 500; background: ${colorScheme.bg}; color: ${colorScheme.color};">${PatientUtils.escapeHtml(type)}</span>`;
    },

    /**
     * Format complete address from patient data
     */
    formatCompleteAddress(patient) {
        const parts = [];
        if (patient.house_number) parts.push(patient.house_number);
        if (patient.subdivision) parts.push(patient.subdivision);
        if (patient.barangay) parts.push(patient.barangay);
        if (patient.city) parts.push(patient.city);
        if (patient.province) parts.push(patient.province);
        if (patient.zip_code) parts.push(patient.zip_code);
        
        if (parts.length > 0) {
            return PatientUtils.escapeHtml(parts.join(', '));
        }
        
        return PatientUtils.escapeHtml(patient.address || 'N/A');
    },

    /**
     * Format date and time
     */
    formatDateTime(dateTimeString) {
        if (!dateTimeString) return 'N/A';
        try {
            const date = new Date(dateTimeString);
            if (isNaN(date.getTime())) return 'N/A';
            return date.toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (e) {
            return PatientUtils.escapeHtml(dateTimeString);
        }
    },


    /**
     * Edit patient from view modal
     */
    editFromView() {
        if (this.currentPatientId && window.EditPatientModal) {
            this.close();
            window.EditPatientModal.open(this.currentPatientId);
        }
    }
};

// Export to global scope
window.ViewPatientModal = ViewPatientModal;

// Global function for close button
window.closeViewPatientModal = function() {
    if (window.ViewPatientModal) {
        window.ViewPatientModal.close();
    }
};
