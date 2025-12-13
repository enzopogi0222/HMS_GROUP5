/**
 * Edit Patient Modal Controller
 * Handles the edit patient modal functionality
 */

const EditPatientModal = {
    modal: null,
    form: null,
    forms: {},
    tabButtons: null,
    formWrapper: null,
    activeFormKey: 'outpatient',
    saveBtn: null,
    currentPatientId: null,
    roomInventory: window.PatientRoomInventory || {},
    roomTypeSelect: null,
    roomNumberSelect: null,
    floorInput: null,
    dailyRateInput: null,
    bedNumberSelect: null,
    currentRoomTypeRooms: [],
    addressControls: {},
    doctorsCache: null,
    admittingDoctorsCache: null,

    /**
     * Initialize the modal
     */
    init() {
        this.modal = document.getElementById('editPatientModal');
        this.forms = {
            outpatient: document.getElementById('editPatientForm'),
            inpatient: document.getElementById('editInpatientForm')
        };
        this.formWrapper = document.querySelector('[data-form-wrapper]');
        this.tabButtons = document.querySelectorAll('.patient-tabs__btn');
        this.saveBtn = document.getElementById('updatePatientBtn');
        this.roomTypeSelect = document.getElementById('edit_room_type');
        this.roomNumberSelect = document.getElementById('edit_room_number');
        this.floorInput = document.getElementById('edit_floor_number');
        this.dailyRateInput = document.getElementById('edit_daily_rate');
        this.bedNumberSelect = document.getElementById('edit_bed_number');
        this.addressControls = {
            outpatient: this.buildAddressControls('edit_outpatient'),
            inpatient: this.buildAddressControls('edit_inpatient')
        };

        // pick default form
        this.form = this.forms.outpatient || this.forms.inpatient || null;
        this.activeFormKey = this.form ? (this.form.dataset.formType || 'outpatient') : 'outpatient';

        if (!this.modal || !this.formWrapper || !this.saveBtn || !this.form) return;
        
        this.bindEvents();
        this.bindTabEvents();
        this.setupRoomAssignmentControls();
        this.setupAddressControls();
        this.updateSaveButtonTarget();
    },

    /**
     * Bind event listeners
     */
    bindEvents() {
        // Form submissions for both outpatient and inpatient
        Object.values(this.forms).forEach(form => {
            if (form) {
                form.addEventListener('submit', (e) => this.handleSubmit(e));
            }
        });

        // Date of birth change - update age display
        const outpatientDobInput = document.getElementById('edit_inpatient_date_of_birth');
        const inpatientDobInput = document.getElementById('edit_date_of_birth');
        
        if (outpatientDobInput) {
            outpatientDobInput.addEventListener('change', () => this.handleDobChange());
        }
        if (inpatientDobInput) {
            inpatientDobInput.addEventListener('change', () => this.handleInpatientDobChange());
        }

        // Weight/height change - update BMI
        const weightInput = document.getElementById('edit_weight_kg');
        const heightInput = document.getElementById('edit_height_cm');
        if (weightInput) {
            weightInput.addEventListener('input', () => this.updateBmi());
        }
        if (heightInput) {
            heightInput.addEventListener('input', () => this.updateBmi());
        }

        // Emergency / guardian relationship "Other" handlers
        const outpatientRelSelect = document.getElementById('edit_emergency_contact_relationship');
        const outpatientRelOther = document.getElementById('edit_emergency_contact_relationship_other');
        if (outpatientRelSelect && outpatientRelOther) {
            outpatientRelSelect.addEventListener('change', () => {
                const isOther = outpatientRelSelect.value === 'Other';
                outpatientRelOther.hidden = !isOther;
                outpatientRelOther.required = isOther;
                if (!isOther) {
                    outpatientRelOther.value = '';
                }
            });
        }

        const guardianRelSelect = document.getElementById('edit_guardian_relationship');
        const guardianRelOther = document.getElementById('edit_guardian_relationship_other');
        if (guardianRelSelect && guardianRelOther) {
            guardianRelSelect.addEventListener('change', () => {
                const isOther = guardianRelSelect.value === 'Other';
                guardianRelOther.hidden = !isOther;
                guardianRelOther.required = isOther;
                if (!isOther) {
                    guardianRelOther.value = '';
                }
            });
        }

        // Clear errors when user interacts with form fields
        this.setupErrorClearing();

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
     * Bind tab navigation buttons
     */
    bindTabEvents() {
        if (!this.tabButtons) return;
        this.tabButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.dataset.tabTarget;
                if (targetId) {
                    this.switchTab(targetId);
                }
            });
        });
    },

    /**
     * Setup error clearing when user interacts with form fields
     */
    setupErrorClearing() {
        Object.values(this.forms).forEach(form => {
            if (!form) return;
            
            const formFields = form.querySelectorAll('input, select, textarea');
            formFields.forEach(field => {
                field.addEventListener('change', () => {
                    this.clearFieldError(field);
                });
                field.addEventListener('input', () => {
                    this.clearFieldError(field);
                });
            });
        });
    },

    /**
     * Clear error for a specific field
     */
    clearFieldError(field) {
        if (!field) return;
        
        field.classList.remove('is-invalid');
        field.classList.remove('error');
        
        const fieldName = field.getAttribute('name');
        if (fieldName) {
            const errorElement = document.getElementById(`err_${fieldName}`);
            if (errorElement) {
                errorElement.textContent = '';
            }
            
            const parentError = field.parentElement?.querySelector('.form-error, .invalid-feedback');
            if (parentError) {
                parentError.textContent = '';
            }
        }
    },

    /**
     * Open the modal
     */
    async open(patientId) {
        if (!this.modal || !patientId) return;
        
        this.currentPatientId = patientId;
        this.modal.style.display = 'flex';
        this.modal.removeAttribute('hidden');
        
        await this.loadPatientData(patientId);
    },

    /**
     * Close the modal
     */
    close() {
        if (this.modal) {
            this.modal.style.display = 'none';
            this.modal.setAttribute('hidden', '');
            this.resetForm();
            this.currentPatientId = null;
        }
    },

    /**
     * Reset form to initial state
     */
    resetForm() {
        Object.values(this.forms).forEach(form => {
            if (!form) return;
            form.reset();
            form.querySelectorAll('.is-invalid, .error').forEach(el => {
                el.classList.remove('is-invalid');
                el.classList.remove('error');
            });
            form.querySelectorAll('.invalid-feedback, .form-error').forEach(el => {
                el.textContent = '';
            });
        });
        this.setActiveFormByType('outpatient');
        this.updateSaveButtonTarget();
        this.resetFloorState();
        this.handleRoomTypeChange();
        const outpatientAge = document.getElementById('edit_inpatient_age');
        if (outpatientAge) {
            outpatientAge.value = '';
        }
        const inpatientAge = document.getElementById('edit_inpatient_age_display');
        if (inpatientAge) {
            inpatientAge.value = '';
        }
        this.admittingDoctorsCache = null;
        this.restoreAdmittingDoctorOptions();
        this.restoreDoctorOptions();
        this.resetAddressSelects();
        this.populateProvincesForAll();
        // Re-enable all tabs when resetting
        this.enableAllTabs();
    },

    /**
     * Load patient data into form
     */
    async loadPatientData(patientId) {
        try {
            // First get basic patient data
            const response = await PatientUtils.makeRequest(
                PatientConfig.getUrl(`${PatientConfig.endpoints.patientGet}/${patientId}`)
            );
            
            if (response.status !== 'success') {
                throw new Error(response.message || 'Failed to load patient data');
            }
            
            let patientData = { ...response.data };
            
            // Try to get comprehensive records to merge additional data
            try {
                const recordsResponse = await PatientUtils.makeRequest(
                    PatientConfig.getUrl(`patients/${patientId}/records`)
                );
                if (recordsResponse.status === 'success' && recordsResponse.records) {
                    // Merge patient data
                    if (recordsResponse.records.patient) {
                        patientData = { ...patientData, ...recordsResponse.records.patient };
                    }
                    
                    // Merge latest outpatient visit data if available
                    if (recordsResponse.records.outpatient_visits && recordsResponse.records.outpatient_visits.length > 0) {
                        const latestVisit = recordsResponse.records.outpatient_visits[0];
                        Object.assign(patientData, latestVisit);
                    }
                    
                    // Merge latest inpatient admission data if available
                    if (recordsResponse.records.inpatient_admissions && recordsResponse.records.inpatient_admissions.length > 0) {
                        const latestAdmission = recordsResponse.records.inpatient_admissions[0];
                        Object.assign(patientData, latestAdmission);
                    }
                }
            } catch (recordsError) {
                console.warn('Could not load comprehensive records, using basic patient data:', recordsError);
            }
            
            console.log('Loaded patient data:', patientData);
            
            // Switch to appropriate tab based on patient type FIRST, before populating
            const patientType = (patientData.patient_type || '').toLowerCase();
            if (patientType === 'inpatient') {
                this.switchTab('editInpatientTab');
                this.disableTab('editOutpatientTabBtn');
            } else {
                this.switchTab('editOutpatientTab');
                this.disableTab('editInpatientTabBtn');
            }
            
            // Wait a bit for the form to be ready and visible
            await new Promise(resolve => setTimeout(resolve, 150));
            
            // Ensure form reference is updated
            const activePanel = document.querySelector('.patient-tabs__panel.active');
            if (activePanel) {
                const activeForm = activePanel.querySelector('form[data-form-type]');
                if (activeForm) {
                    this.form = activeForm;
                    this.setActiveFormByType(activeForm.dataset.formType || 'outpatient');
                }
            }
            
            // Now populate the form
            this.populateForm(patientData);
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
        console.log('=== Starting Form Population ===');
        console.log('Patient data received:', patient);
        console.log('Patient data keys:', Object.keys(patient));
        
        const patientType = (patient.patient_type || '').toLowerCase();
        const isInpatient = patientType === 'inpatient';
        
        // Get the active form
        const activeForm = this.form;
        if (!activeForm) {
            console.error('✗ No active form found');
            // Try to get it from the active panel
            const activePanel = document.querySelector('.patient-tabs__panel.active');
            if (activePanel) {
                const form = activePanel.querySelector('form[data-form-type]');
                if (form) {
                    this.form = form;
                    console.log('✓ Found form in active panel:', form.id);
                }
            }
            if (!this.form) {
                console.error('✗ Still no form found, aborting');
                return;
            }
        }
        
        const formToUse = this.form;
        console.log('Active form:', formToUse.id, 'Is Inpatient:', isInpatient);
        console.log('Form is visible:', formToUse.offsetParent !== null);
        console.log('Form has', formToUse.querySelectorAll('input, select, textarea').length, 'form fields');

        // Determine which prefix to use based on active form
        const addressPrefix = isInpatient ? 'edit_inpatient' : 'edit_outpatient';
        const dobFieldId = isInpatient ? 'edit_date_of_birth' : 'edit_inpatient_date_of_birth';
        
        // Extract nested data if present
        let medicalHistory = {};
        let initialAssessment = {};
        let roomAssignments = {};
        
        if (typeof patient.medical_history === 'string') {
            try {
                medicalHistory = JSON.parse(patient.medical_history);
            } catch (e) {
                medicalHistory = {};
            }
        } else if (typeof patient.medical_history === 'object') {
            medicalHistory = patient.medical_history || {};
        }
        
        if (typeof patient.initial_assessment === 'string') {
            try {
                initialAssessment = JSON.parse(patient.initial_assessment);
            } catch (e) {
                initialAssessment = {};
            }
        } else if (typeof patient.initial_assessment === 'object') {
            initialAssessment = patient.initial_assessment || {};
        }
        
        if (typeof patient.room_assignments === 'string') {
            try {
                roomAssignments = JSON.parse(patient.room_assignments);
            } catch (e) {
                roomAssignments = {};
            }
        } else if (typeof patient.room_assignments === 'object') {
            roomAssignments = patient.room_assignments || {};
        }
        
        // Create a comprehensive field mapping that matches name attributes to patient data
        const fieldNameToPatientData = {
            // Basic patient info
            'patient_id': patient.patient_id,
            'first_name': patient.first_name,
            'middle_name': patient.middle_name,
            'last_name': patient.last_name,
            'gender': (patient.gender || patient.sex) ? String(patient.gender || patient.sex).toLowerCase() : '',
            'date_of_birth': patient.date_of_birth,
            'civil_status': patient.civil_status,
            'phone': patient.contact_no || patient.phone || patient.contact_number,
            'email': patient.email,
            // Address
            'province': patient.province,
            'city': patient.city,
            'barangay': patient.barangay,
            'subdivision': patient.subdivision,
            'house_number': patient.house_number,
            'zip_code': patient.zip_code,
            // Guardian/Emergency Contact
            'guardian_name': patient.guardian_name || patient.emergency_contact || patient.emergency_contact_name,
            'guardian_relationship': patient.guardian_relationship || patient.emergency_contact_relationship,
            'guardian_relationship_other': patient.guardian_relationship_other || patient.emergency_contact_relationship_other,
            'guardian_contact': patient.guardian_contact || patient.emergency_phone || patient.emergency_contact_phone,
            'secondary_contact': patient.secondary_contact,
            // Admission Details
            'admission_number': patient.admission_number,
            'admission_datetime': patient.admission_datetime || patient.appointment_datetime,
            'admission_type': patient.admission_type,
            'admitting_diagnosis': patient.admitting_diagnosis || patient.chief_complaint,
            'admitting_doctor': patient.admitting_doctor_id || patient.admitting_doctor || patient.assigned_doctor_id || patient.assigned_doctor,
            'consent_uploaded': patient.consent_uploaded !== null && patient.consent_uploaded !== undefined ? String(patient.consent_uploaded) : '',
            // Room & Bed
            'room_type': patient.room_type_id || patient.room_type,
            'floor_number': patient.floor_number,
            'room_number': patient.room_number,
            'bed_number': patient.bed_number,
            'daily_rate': patient.daily_rate,
            // Medical History (check nested object first, then direct properties)
            'history_allergies': medicalHistory.allergies || medicalHistory.history_allergies || patient.history_allergies || patient.allergies,
            'past_medical_history': medicalHistory.past_medical_history || medicalHistory.existing_conditions || patient.past_medical_history || patient.existing_conditions,
            'past_surgical_history': medicalHistory.past_surgical_history || patient.past_surgical_history,
            'family_history': medicalHistory.family_history || patient.family_history,
            'history_current_medications': medicalHistory.current_medications || medicalHistory.history_current_medications || patient.history_current_medications || patient.current_medications,
            // Initial Assessment (check nested object first, then direct properties)
            'assessment_bp': initialAssessment.blood_pressure || initialAssessment.assessment_bp || initialAssessment.bp || patient.assessment_bp || patient.blood_pressure,
            'assessment_hr': initialAssessment.heart_rate || initialAssessment.assessment_hr || initialAssessment.hr || patient.assessment_hr || patient.heart_rate,
            'assessment_rr': initialAssessment.respiratory_rate || initialAssessment.assessment_rr || initialAssessment.rr || patient.assessment_rr || patient.respiratory_rate,
            'assessment_temp': initialAssessment.temperature || initialAssessment.assessment_temp || initialAssessment.temp || patient.assessment_temp || patient.temperature,
            'assessment_spo2': initialAssessment.spo2 || initialAssessment.assessment_spo2 || patient.assessment_spo2,
            'assessment_height_cm': initialAssessment.height_cm || initialAssessment.assessment_height_cm || patient.assessment_height_cm || patient.height_cm,
            'assessment_weight_kg': initialAssessment.weight_kg || initialAssessment.assessment_weight_kg || patient.assessment_weight_kg || patient.weight_kg,
            'level_of_consciousness': initialAssessment.level_of_consciousness || initialAssessment.loc || patient.level_of_consciousness,
            'pain_level': initialAssessment.pain_level || patient.pain_level,
            'mode_of_arrival': initialAssessment.mode_of_arrival || patient.mode_of_arrival,
            'skin_condition': initialAssessment.skin_condition || patient.skin_condition,
            'initial_findings': initialAssessment.initial_findings || initialAssessment.findings || patient.initial_findings,
            'assessment_remarks': initialAssessment.assessment_remarks || initialAssessment.remarks || patient.assessment_remarks,
            // HMO / Insurance (these might have different name attributes, so we'll handle them separately)
        };
        
        // Add HMO/Insurance fields - these might use different prefixes in name attributes
        const hmoFields = {
            'insurance_provider': patient.insurance_provider,
            'membership_number': patient.membership_number,
            'hmo_cardholder_name': patient.hmo_cardholder_name,
            'cardholder_name': patient.hmo_cardholder_name,
            'member_type': patient.member_type,
            'relationship': patient.relationship,
            'plan_name': patient.plan_name,
            'mbl': patient.mbl,
            'pre_existing_coverage': patient.pre_existing_coverage,
            'preexisting': patient.pre_existing_coverage,
            'coverage_start_date': patient.coverage_start_date,
            'validity_start': patient.coverage_start_date,
            'coverage_end_date': patient.coverage_end_date,
            'validity_end': patient.coverage_end_date,
            'card_status': patient.card_status,
        };
        
        Object.assign(fieldNameToPatientData, hmoFields);

        // First, populate fields by ID (for fields without name attributes or special cases)
        const idMappings = {
            'edit_patient_id': patient.patient_id,
            'edit_inpatient_patient_id': patient.patient_id,
            'edit_patient_identifier': patient.patient_identifier || patient.patient_id || '',
            'edit_inpatient_patient_identifier': patient.patient_identifier || patient.patient_id || '',
            [dobFieldId]: patient.date_of_birth || '',
        };
        
        for (const [fieldId, value] of Object.entries(idMappings)) {
            const field = formToUse.querySelector(`#${fieldId}`) || document.getElementById(fieldId);
            if (field && value !== null && value !== undefined) {
                if (field.type === 'date' || field.type === 'datetime-local') {
                    if (value) {
                        const dateValue = new Date(value);
                        if (!isNaN(dateValue.getTime())) {
                            if (field.type === 'date') {
                                field.value = dateValue.toISOString().split('T')[0];
                            } else {
                                const year = dateValue.getFullYear();
                                const month = String(dateValue.getMonth() + 1).padStart(2, '0');
                                const day = String(dateValue.getDate()).padStart(2, '0');
                                const hours = String(dateValue.getHours()).padStart(2, '0');
                                const minutes = String(dateValue.getMinutes()).padStart(2, '0');
                                field.value = `${year}-${month}-${day}T${hours}:${minutes}`;
                            }
                        }
                    }
                } else if (!field.readOnly) {
                    field.value = String(value);
                }
            }
        }

        // Now populate all fields by their name attribute
        let populatedCount = 0;
        let skippedCount = 0;
        let errorCount = 0;
        const allInputs = formToUse.querySelectorAll('input, select, textarea');
        
        console.log(`Found ${allInputs.length} total form fields to check`);
        
        allInputs.forEach(input => {
            // Skip hidden fields and readonly auto-calculated fields
            if (input.hidden || input.type === 'hidden' || 
                (input.readOnly && (input.id.includes('age') || input.id.includes('identifier') || input.id.includes('rate')))) {
                skippedCount++;
                return;
            }

            const fieldName = input.name;
            if (!fieldName) {
                skippedCount++;
                return;
            }

            // Get value from patient data
            let value = fieldNameToPatientData[fieldName];
            
            // Try alternative field names
            if (value === null || value === undefined || value === '') {
                // Try common alternatives
                if (fieldName === 'phone' && (patient.contact_no || patient.contact_number)) {
                    value = patient.contact_no || patient.contact_number;
                } else if (fieldName === 'gender' && patient.sex) {
                    value = String(patient.sex).toLowerCase();
                } else if (fieldName === 'guardian_name' && (patient.emergency_contact || patient.emergency_contact_name)) {
                    value = patient.emergency_contact || patient.emergency_contact_name;
                } else if (fieldName === 'guardian_contact' && (patient.emergency_phone || patient.emergency_contact_phone)) {
                    value = patient.emergency_phone || patient.emergency_contact_phone;
                } else {
                    // Try direct patient object lookup
                    value = patient[fieldName];
                    if (!value) {
                        // Try case-insensitive lookup
                        const lowerFieldName = fieldName.toLowerCase();
                        for (const [key, val] of Object.entries(patient)) {
                            if (key.toLowerCase() === lowerFieldName && val !== null && val !== undefined && val !== '') {
                                value = val;
                                break;
                            }
                        }
                    }
                    
                    // Special normalization for gender
                    if (fieldName === 'gender' && value) {
                        value = String(value).toLowerCase();
                    }
                }
            }

            if (value === null || value === undefined || value === '') {
                skippedCount++;
                return; // Skip if no value
            }

            try {
                if (input.type === 'checkbox') {
                    if (Array.isArray(value)) {
                        input.checked = value.includes(input.value);
                    } else {
                        input.checked = String(value) === input.value;
                    }
                    if (input.checked) populatedCount++;
                } else if (input.tagName === 'SELECT') {
                    const stringValue = String(value).trim();
                    if (!stringValue) {
                        skippedCount++;
                        return;
                    }
                    
                    // Try multiple matching strategies
                    let option = null;
                    
                    // Strategy 1: Exact value match
                    option = Array.from(input.options).find(opt => opt.value === stringValue);
                    
                    // Strategy 2: Case-insensitive value match
                    if (!option) {
                        option = Array.from(input.options).find(opt => 
                            opt.value.toLowerCase() === stringValue.toLowerCase()
                        );
                    }
                    
                    // Strategy 3: Text content match (case-insensitive)
                    if (!option) {
                        option = Array.from(input.options).find(opt => 
                            opt.textContent.trim().toLowerCase() === stringValue.toLowerCase() ||
                            opt.textContent.trim() === stringValue
                        );
                    }
                    
                    // Strategy 4: Partial text match
                    if (!option) {
                        option = Array.from(input.options).find(opt => 
                            opt.textContent.trim().toLowerCase().includes(stringValue.toLowerCase()) ||
                            stringValue.toLowerCase().includes(opt.textContent.trim().toLowerCase())
                        );
                    }
                    
                    // Strategy 5: For doctor fields, check data attributes
                    if (!option && input.id.includes('doctor')) {
                        option = Array.from(input.options).find(opt => {
                            const doctorName = opt.getAttribute('data-doctor-name');
                            if (doctorName) {
                                return doctorName.toLowerCase().includes(stringValue.toLowerCase()) ||
                                       stringValue.toLowerCase().includes(doctorName.toLowerCase());
                            }
                            return false;
                        });
                    }
                    
                    // Strategy 6: Special handling for gender field (handle case variations)
                    if (!option && fieldName === 'gender') {
                        const normalizedValue = stringValue.toLowerCase();
                        option = Array.from(input.options).find(opt => 
                            opt.value.toLowerCase() === normalizedValue
                        );
                    }
                    
                    // Strategy 7: For ID-based selects (like doctor_id, room_type_id), try matching by ID
                    if (!option && (stringValue && !isNaN(stringValue))) {
                        // Try to find option where value is the ID
                        option = Array.from(input.options).find(opt => opt.value === stringValue);
                    }
                    
                    if (option && option.value) {
                        input.value = option.value;
                        // Trigger change event to update dependent fields
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                        populatedCount++;
                        console.log(`✓ Populated ${fieldName} (${input.id}) with value: ${option.value}`);
                    } else {
                        console.warn(`✗ Could not find option for ${fieldName} (${input.id}) with value: "${stringValue}". Available options:`, 
                            Array.from(input.options).map(opt => `${opt.value}="${opt.textContent}"`));
                    }
                } else if (input.type === 'date' || input.type === 'datetime-local') {
                    // Skip invalid date values
                    const stringValue = String(value).trim();
                    if (!stringValue || stringValue === '0' || stringValue === '0000-00-00' || stringValue === '0000-00-00 00:00:00') {
                        skippedCount++;
                        return;
                    }
                    
                    const dateValue = new Date(value);
                    if (!isNaN(dateValue.getTime()) && dateValue.getFullYear() > 1900) {
                        if (input.type === 'date') {
                            input.value = dateValue.toISOString().split('T')[0];
                            populatedCount++;
                            console.log(`✓ Populated ${fieldName} (${input.id}) with date: ${input.value}`);
                        } else {
                            const year = dateValue.getFullYear();
                            const month = String(dateValue.getMonth() + 1).padStart(2, '0');
                            const day = String(dateValue.getDate()).padStart(2, '0');
                            const hours = String(dateValue.getHours()).padStart(2, '0');
                            const minutes = String(dateValue.getMinutes()).padStart(2, '0');
                            input.value = `${year}-${month}-${day}T${hours}:${minutes}`;
                            populatedCount++;
                            console.log(`✓ Populated ${fieldName} (${input.id}) with datetime: ${input.value}`);
                        }
                    } else {
                        console.warn(`✗ Invalid date value for ${fieldName} (${input.id}): ${value}`);
                        skippedCount++;
                    }
                } else {
                    // Regular input/textarea
                    input.value = String(value);
                    populatedCount++;
                    console.log(`✓ Populated ${fieldName} (${input.id}) with value: ${input.value.substring(0, 50)}`);
                }
            } catch (error) {
                errorCount++;
                console.error(`✗ Error populating field ${fieldName} (${input.id}):`, error, value);
            }
        });
        
        console.log(`\n=== Population Summary ===`);
        console.log(`✓ Successfully populated: ${populatedCount} fields`);
        console.log(`⊘ Skipped (no value): ${skippedCount} fields`);
        console.log(`✗ Errors: ${errorCount} fields`);
        console.log(`Total fields checked: ${allInputs.length}`);

        // Handle guardian/emergency contact relationship "Other" field visibility
        const guardianRelSelect = formToUse.querySelector('#edit_guardian_relationship') || document.getElementById('edit_guardian_relationship');
        const guardianRelOther = formToUse.querySelector('#edit_guardian_relationship_other') || document.getElementById('edit_guardian_relationship_other');
        if (guardianRelSelect && guardianRelOther) {
            const relValue = guardianRelSelect.value;
            if (relValue === 'Other') {
                guardianRelOther.hidden = false;
                guardianRelOther.required = true;
            } else {
                guardianRelOther.hidden = true;
                guardianRelOther.required = false;
            }
        }

        // Handle address selects after a delay to allow province/city/barangay to be set
        // First, make sure province is set, then load cities/barangays
        setTimeout(() => {
            const provinceSelect = formToUse.querySelector(`#${addressPrefix}_province`);
            if (provinceSelect && patient.province && provinceSelect.value) {
                // Province is already set, now load cities
                this.populateAddressSelects(addressPrefix, patient);
            } else if (provinceSelect && patient.province) {
                // Set province first, then load cities
                const provinceValue = String(patient.province).trim();
                const provinceOption = Array.from(provinceSelect.options).find(opt => 
                    opt.value === provinceValue || 
                    opt.textContent.trim().toLowerCase() === provinceValue.toLowerCase()
                );
                if (provinceOption) {
                    provinceSelect.value = provinceOption.value;
                    provinceSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    // Wait for cities to load, then set city and barangay
                    setTimeout(() => {
                        this.populateAddressSelects(addressPrefix, patient);
                    }, 500);
                }
            }
        }, 300);

        // Calculate and display age
        if (patient.date_of_birth) {
            setTimeout(() => {
                if (isInpatient) {
                    this.handleInpatientDobChange();
                } else {
                    this.handleDobChange();
                }
            }, 100);
        }

        // Handle room assignment for both forms (since both have room assignment now)
        // This needs to happen after room type is set and floors/rooms are loaded
        if (patient.room_type_id || patient.room_type || patient.room_type) {
            setTimeout(() => {
                const roomTypeSelect = formToUse.querySelector('#edit_room_type') || document.getElementById('edit_room_type');
                if (roomTypeSelect && roomTypeSelect.closest('form') === formToUse) {
                    // Set room type first
                    const roomTypeValue = String(patient.room_type_id || patient.room_type || '');
                    if (roomTypeValue) {
                        // Try to find matching option
                        let roomTypeOption = Array.from(roomTypeSelect.options).find(
                            opt => opt.value === roomTypeValue
                        );
                        if (!roomTypeOption) {
                            // Try text match
                            roomTypeOption = Array.from(roomTypeSelect.options).find(
                                opt => opt.textContent.trim().toLowerCase().includes(roomTypeValue.toLowerCase()) ||
                                       roomTypeValue.toLowerCase().includes(opt.textContent.trim().toLowerCase())
                            );
                        }
                        if (roomTypeOption && roomTypeOption.value) {
                            roomTypeSelect.value = roomTypeOption.value;
                            roomTypeSelect.dispatchEvent(new Event('change', { bubbles: true }));
                            console.log('✓ Set room type to:', roomTypeOption.value);
                            
                            // Wait for room type change to process and load floors
                            setTimeout(() => {
                                const floorInput = formToUse.querySelector('#edit_floor_number') || document.getElementById('edit_floor_number');
                                const roomSelect = formToUse.querySelector('#edit_room_number') || document.getElementById('edit_room_number');
                                const bedSelect = formToUse.querySelector('#edit_bed_number') || document.getElementById('edit_bed_number');
                                
                                if (patient.floor_number && floorInput && !floorInput.disabled && floorInput.options.length > 1) {
                                    const floorValue = String(patient.floor_number);
                                    const floorOption = Array.from(floorInput.options).find(
                                        opt => opt.value === floorValue
                                    );
                                    if (floorOption) {
                                        floorInput.value = floorValue;
                                        floorInput.dispatchEvent(new Event('change', { bubbles: true }));
                                        console.log('✓ Set floor to:', floorValue);
                                        
                                        // Wait for floor change to process and load rooms
                                        setTimeout(() => {
                                            if (patient.room_number && roomSelect && !roomSelect.disabled && roomSelect.options.length > 1) {
                                                const roomValue = String(patient.room_number);
                                                const roomOption = Array.from(roomSelect.options).find(
                                                    opt => opt.value === roomValue || 
                                                           opt.textContent.includes(roomValue)
                                                );
                                                if (roomOption) {
                                                    roomSelect.value = roomOption.value;
                                                    roomSelect.dispatchEvent(new Event('change', { bubbles: true }));
                                                    console.log('✓ Set room to:', roomOption.value);
                                                    
                                                    // Wait for room change to process and load beds
                                                    setTimeout(() => {
                                                        if (patient.bed_number && bedSelect && !bedSelect.disabled && bedSelect.options.length > 1) {
                                                            const bedValue = String(patient.bed_number);
                                                            const bedOption = Array.from(bedSelect.options).find(
                                                                opt => opt.value === bedValue || 
                                                                       opt.textContent.includes(bedValue)
                                                            );
                                                            if (bedOption) {
                                                                bedSelect.value = bedOption.value;
                                                                console.log('✓ Set bed to:', bedOption.value);
                                                            } else {
                                                                console.warn('✗ Could not find bed option:', bedValue);
                                                            }
                                                        }
                                                    }, 300);
                                                } else {
                                                    console.warn('✗ Could not find room option:', roomValue);
                                                }
                                            }
                                        }, 500);
                                    } else {
                                        console.warn('✗ Could not find floor option:', floorValue);
                                    }
                                }
                            }, 500);
                        } else {
                            console.warn('✗ Could not find room type option:', roomTypeValue);
                        }
                    }
                }
            }, 600);
        }

        // Handle coverage type checkboxes
        if (patient.plan_coverage_types) {
            const coverageLabel = formToUse.querySelector(`#${addressPrefix}_coverage_type_label`);
            if (coverageLabel) {
                const coverageCheckboxes = coverageLabel.parentElement?.querySelectorAll('.coverage-checkbox-group input[type="checkbox"]') || 
                                          formToUse.querySelectorAll(`#${addressPrefix}_coverage_type_label ~ .coverage-checkbox-group input[type="checkbox"]`);
                if (coverageCheckboxes.length > 0) {
                    if (Array.isArray(patient.plan_coverage_types)) {
                        coverageCheckboxes.forEach(checkbox => {
                            checkbox.checked = patient.plan_coverage_types.includes(checkbox.value);
                        });
                    } else {
                        coverageCheckboxes.forEach(checkbox => {
                            checkbox.checked = String(patient.plan_coverage_types) === checkbox.value;
                        });
                    }
                    console.log(`Populated ${coverageCheckboxes.length} coverage type checkboxes`);
                }
            }
        }

        // Final pass: ensure all visible fields in the active form are populated
        // This catches any fields that might have been missed
        setTimeout(() => {
            const allInputs = formToUse.querySelectorAll('input, select, textarea');
            let finalPassCount = 0;
            
            allInputs.forEach(input => {
                // Skip hidden fields and readonly fields that are auto-calculated
                if (input.hidden || input.type === 'hidden' || 
                    (input.readOnly && (input.id.includes('age') || input.id.includes('identifier') || input.id.includes('rate')))) {
                    return;
                }

                // If field is empty and we have patient data, try to populate from alternative field names
                if (!input.value && input.name) {
                    const fieldName = input.name.toLowerCase();
                    let value = null;

                    // Try to find matching value from patient data
                    for (const [key, val] of Object.entries(patient)) {
                        if (val !== null && val !== undefined && val !== '') {
                            const patientKey = key.toLowerCase();
                            // Match field names (e.g., "phone" matches "contact_no")
                            if (fieldName === patientKey || 
                                fieldName.includes(patientKey) || 
                                patientKey.includes(fieldName) ||
                                (fieldName === 'phone' && (patientKey === 'contact_no' || patientKey === 'contact_number')) ||
                                (fieldName === 'contact_no' && patientKey === 'phone')) {
                                value = val;
                                break;
                            }
                        }
                    }

                    if (value !== null && value !== undefined && value !== '') {
                        try {
                            if (input.tagName === 'SELECT') {
                                const option = Array.from(input.options).find(opt => 
                                    opt.value === String(value) || 
                                    opt.textContent.trim().toLowerCase() === String(value).toLowerCase()
                                );
                                if (option) {
                                    input.value = option.value;
                                    finalPassCount++;
                                }
                            } else {
                                input.value = String(value);
                                finalPassCount++;
                            }
                        } catch (error) {
                            console.error(`Error in final pass for ${input.id}:`, error);
                        }
                    }
                }
            });
            
            if (finalPassCount > 0) {
                console.log(`Final pass populated ${finalPassCount} additional fields`);
            }
        }, 600);
    },

    /**
     * Populate address selects (province, city, barangay)
     */
    async populateAddressSelects(prefix, patient) {
        const controls = this.addressControls[prefix === 'edit_outpatient' ? 'outpatient' : 'inpatient'];
        if (!controls || !controls.provinceSelect) return;

        try {
            // First populate provinces
            await this.populateProvinces(controls);
            
            // Wait a bit for provinces to load
            await new Promise(resolve => setTimeout(resolve, 100));
            
            // Then set province if available
            if (patient.province && controls.provinceSelect) {
                const provinceValue = String(patient.province).trim();
                if (provinceValue) {
                    // Try to find matching province by value or text
                    let provinceOption = Array.from(controls.provinceSelect.options).find(
                        opt => opt.value === provinceValue || 
                               opt.value.toLowerCase() === provinceValue.toLowerCase() ||
                               opt.textContent.trim() === provinceValue ||
                               opt.textContent.trim().toLowerCase() === provinceValue.toLowerCase()
                    );
                    
                    if (provinceOption) {
                        controls.provinceSelect.value = provinceOption.value;
                        controls.provinceSelect.dispatchEvent(new Event('change', { bubbles: true }));
                        
                        // Wait for cities to load
                        await new Promise(resolve => setTimeout(resolve, 200));
                        
                        // Set city if available
                        if (patient.city && controls.citySelect) {
                            const cityValue = String(patient.city).trim();
                            if (cityValue && !controls.citySelect.disabled) {
                                let cityOption = Array.from(controls.citySelect.options).find(
                                    opt => opt.value === cityValue ||
                                           opt.value.toLowerCase() === cityValue.toLowerCase() ||
                                           opt.textContent.trim() === cityValue ||
                                           opt.textContent.trim().toLowerCase() === cityValue.toLowerCase()
                                );
                                
                                if (cityOption) {
                                    controls.citySelect.value = cityOption.value;
                                    controls.citySelect.dispatchEvent(new Event('change', { bubbles: true }));
                                    
                                    // Wait for barangays to load
                                    await new Promise(resolve => setTimeout(resolve, 200));
                                    
                                    // Set barangay if available
                                    if (patient.barangay && controls.barangaySelect) {
                                        const barangayValue = String(patient.barangay).trim();
                                        if (barangayValue && !controls.barangaySelect.disabled) {
                                            let barangayOption = Array.from(controls.barangaySelect.options).find(
                                                opt => opt.value === barangayValue ||
                                                       opt.value.toLowerCase() === barangayValue.toLowerCase() ||
                                                       opt.textContent.trim() === barangayValue ||
                                                       opt.textContent.trim().toLowerCase() === barangayValue.toLowerCase()
                                            );
                                            
                                            if (barangayOption) {
                                                controls.barangaySelect.value = barangayOption.value;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } catch (error) {
            console.error('Error populating address selects:', error);
        }
    },

    /**
     * Handle form submission
     */
    async handleSubmit(e) {
        e.preventDefault();
        const form = e.currentTarget;
        if (!form) return;
        this.setActiveFormByType(form.dataset.formType || 'outpatient');

        const submitBtn = this.saveBtn;
        const originalText = submitBtn.innerHTML;
        
        try {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            
            const formData = this.collectFormData(form);
            const errors = this.validateFormData(formData, form.dataset.formType);
            if (Object.keys(errors).length > 0) {
                PatientUtils.displayFormErrors(errors, form);
                PatientUtils.showNotification('Please correct the highlighted fields before saving.', 'error');
                return;
            }
            
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
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    },

    collectFormData(form) {
        const formData = new FormData(form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            // Handle array values (like checkboxes)
            if (data[key]) {
                if (!Array.isArray(data[key])) {
                    data[key] = [data[key], value];
                } else {
                    data[key].push(value);
                }
            } else {
                data[key] = value;
            }
        }

        this.normalizeInpatientPayload(data);
        this.normalizeOutpatientPayload(data);
        
        return data;
    },

    /**
     * Validate form data
     */
<<<<<<< HEAD
    validateFormData(data, formType = 'outpatient') {
        const typeValue = (formType || data.patient_type || 'outpatient').toLowerCase();
        let rules = {};

        if (typeValue === 'outpatient') {
            rules = {
                first_name: { required: true, label: 'First Name' },
                last_name: { required: true, label: 'Last Name' },
                gender: { required: true, label: 'Sex' },
                date_of_birth: { required: true, label: 'Date of Birth' },
                civil_status: { required: true, label: 'Civil Status' },
                phone: { required: true, label: 'Contact Number' },
                emergency_contact_name: { required: true, label: 'Emergency Contact Name' },
                emergency_contact_relationship: { required: true, label: 'Emergency Contact Relationship' },
                emergency_contact_phone: { required: true, label: 'Emergency Contact Phone' },
                chief_complaint: { required: true, label: 'Chief Complaint' },
                department: { required: true, label: 'Department' },
                appointment_datetime: { required: true, label: 'Appointment Date & Time' },
                visit_type: { required: true, label: 'Visit Type' },
                payment_type: { required: true, label: 'Payment Type' },
                email: { email: true, label: 'Email Address' }
            };
        } else {
            rules = {
                last_name: { required: true, label: 'Last Name' },
                first_name: { required: true, label: 'First Name' },
                gender: { required: true, label: 'Sex' },
                phone: { required: true, label: 'Contact Number' },
                civil_status: { required: true, label: 'Civil Status' },
                guardian_name: { required: true, label: 'Guardian Name' },
                guardian_relationship: { required: true, label: 'Guardian Relationship' },
                guardian_contact: { required: true, label: 'Guardian Contact' },
                admission_datetime: { required: true, label: 'Admission Date & Time' },
                admission_type: { required: true, label: 'Admission Type' },
                admitting_diagnosis: { required: true, label: 'Admitting Diagnosis' },
                admitting_doctor: { required: true, label: 'Admitting Doctor' },
                room_type: { required: true, label: 'Room Type' },
                floor_number: { required: true, label: 'Floor Number' },
                room_number: { required: true, label: 'Room Number' },
                bed_number: { required: true, label: 'Bed Number' },
                level_of_consciousness: { required: true, label: 'Level of Consciousness' }
            };
        }

=======
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
        
>>>>>>> 4083aca29ad024810e89cc84868565c55bbef4db
        return PatientUtils.validateForm(data, rules);
    },

    // Room assignment methods (similar to AddPatientModal)
    setupRoomAssignmentControls() {
        if (!this.roomTypeSelect || !this.roomNumberSelect || !this.floorInput || !this.bedNumberSelect) {
            return;
        }

        this.roomTypeSelect.addEventListener('change', () => this.handleRoomTypeChange());
        this.floorInput.addEventListener('change', () => this.handleFloorChange());
        this.roomNumberSelect.addEventListener('change', () => {
            this.syncSelectedRoomDetails();
            this.updateBedOptionsForSelectedRoom();
        });
        this.handleRoomTypeChange();
    },

    resetFloorState(message = 'Select a floor...') {
        if (!this.floorInput) return;
        this.floorInput.innerHTML = `<option value="">${message}</option>`;
        this.floorInput.disabled = true;
        this.floorInput.value = '';
    },

    resetRoomNumberState(message = 'Select a room...') {
        if (!this.roomNumberSelect) return;
        this.roomNumberSelect.innerHTML = `<option value="">${message}</option>`;
        this.roomNumberSelect.disabled = true;
        this.resetBedState();
    },

    resetBedState(message = 'Select a room first') {
        if (!this.bedNumberSelect) return;
        this.bedNumberSelect.innerHTML = `<option value="">${message}</option>`;
        const lower = (message || '').toLowerCase();
        const shouldDisable = lower.includes('no beds') || lower.includes('unavailable');
        this.bedNumberSelect.disabled = shouldDisable;
    },

    buildAddressControls(prefix) {
        return {
            provinceSelect: document.getElementById(`${prefix}_province`),
            citySelect: document.getElementById(`${prefix}_city`),
            barangaySelect: document.getElementById(`${prefix}_barangay`)
        };
    },

    setupAddressControls() {
        Object.entries(this.addressControls).forEach(([formKey, controls]) => {
            if (!controls.provinceSelect || !controls.citySelect || !controls.barangaySelect) {
                return;
            }

            this.setAddressLoadingState(controls);
            GeoDataLoader.loadProvinces()
                .then(() => {
                    controls.provinceSelect.addEventListener('change', () => this.handleProvinceChange(controls));
                    controls.citySelect.addEventListener('change', () => this.handleCityChange(controls));
                    this.populateProvinces(controls);
                })
                .catch(error => {
                    console.error('Failed to load geographic data', error);
                    this.setAddressErrorState(controls);
                });
        });
    },

    resetAddressSelects(formKey = null) {
        const targets = formKey ? { [formKey]: this.addressControls[formKey] } : this.addressControls;
        Object.values(targets).forEach(controls => {
            if (!controls) return;
            if (controls.provinceSelect) {
                controls.provinceSelect.innerHTML = '<option value="">Select a province...</option>';
                controls.provinceSelect.disabled = true;
            }
            if (controls.citySelect) {
                controls.citySelect.innerHTML = '<option value="">Select a city or municipality...</option>';
                controls.citySelect.disabled = true;
            }
            if (controls.barangaySelect) {
                controls.barangaySelect.innerHTML = '<option value="">Select a barangay...</option>';
                controls.barangaySelect.disabled = true;
            }
        });
    },

    setAddressLoadingState(controls) {
        if (controls.provinceSelect) {
            controls.provinceSelect.innerHTML = '<option value="">Loading provinces...</option>';
            controls.provinceSelect.disabled = true;
        }
        if (controls.citySelect) {
            controls.citySelect.innerHTML = '<option value="">Select a city or municipality...</option>';
            controls.citySelect.disabled = true;
        }
        if (controls.barangaySelect) {
            controls.barangaySelect.innerHTML = '<option value="">Select a barangay...</option>';
            controls.barangaySelect.disabled = true;
        }
    },

    setAddressErrorState(controls) {
        if (controls.provinceSelect) {
            controls.provinceSelect.innerHTML = '<option value="">Failed to load provinces</option>';
            controls.provinceSelect.disabled = true;
        }
        if (controls.citySelect) {
            controls.citySelect.innerHTML = '<option value="">Unavailable</option>';
            controls.citySelect.disabled = true;
        }
        if (controls.barangaySelect) {
            controls.barangaySelect.innerHTML = '<option value="">Unavailable</option>';
            controls.barangaySelect.disabled = true;
        }
    },

    populateProvincesForAll() {
        Object.values(this.addressControls).forEach(controls => {
            if (controls?.provinceSelect) {
                this.populateProvinces(controls);
            }
        });
    },

    populateProvinces(controls) {
        if (!controls || !controls.provinceSelect) return;

        this.setAddressLoadingState(controls);
        GeoDataLoader.loadProvinces()
            .then(provinces => {
                controls.provinceSelect.innerHTML = '<option value="">Select a province...</option>';
                provinces.forEach(province => {
                    const opt = document.createElement('option');
                    opt.value = this.formatLocationName(province.name || province.provDesc);
                    opt.textContent = this.formatLocationName(province.name || province.provDesc);
                    opt.dataset.code = province.code || province.provCode;
                    controls.provinceSelect.appendChild(opt);
                });
                controls.provinceSelect.disabled = false;
                if (controls.citySelect) {
                    controls.citySelect.innerHTML = '<option value="">Select a city or municipality...</option>';
                    controls.citySelect.disabled = true;
                }
                if (controls.barangaySelect) {
                    controls.barangaySelect.innerHTML = '<option value="">Select a barangay...</option>';
                    controls.barangaySelect.disabled = true;
                }
            })
            .catch(error => {
                console.error('Failed to populate provinces', error);
                this.setAddressErrorState(controls);
            });
    },

    handleProvinceChange(controls) {
        if (!controls?.provinceSelect || !controls.citySelect || !controls.barangaySelect) return;
        const provinceCode = this.getSelectedOptionCode(controls.provinceSelect);

        controls.citySelect.innerHTML = provinceCode
            ? '<option value="">Loading cities...</option>'
            : '<option value="">Select a city or municipality...</option>';
        controls.citySelect.disabled = true;
        controls.barangaySelect.innerHTML = '<option value="">Select a barangay...</option>';
        controls.barangaySelect.disabled = true;

        if (!provinceCode) {
            return;
        }

        GeoDataLoader.loadCities(provinceCode)
            .then(cities => {
                if (this.getSelectedOptionCode(controls.provinceSelect) !== provinceCode) {
                    return;
                }
                controls.citySelect.innerHTML = '<option value="">Select a city or municipality...</option>';
                cities.forEach(city => {
                    const opt = document.createElement('option');
                    opt.value = this.formatLocationName(city.name || city.citymunDesc);
                    opt.textContent = this.formatLocationName(city.name || city.citymunDesc);
                    opt.dataset.code = city.code || city.citymunCode;
                    controls.citySelect.appendChild(opt);
                });
                controls.citySelect.disabled = cities.length === 0;
                controls.barangaySelect.innerHTML = '<option value="">Select a barangay...</option>';
                controls.barangaySelect.disabled = true;
            })
            .catch(error => {
                console.error('Failed to load cities', error);
                controls.citySelect.innerHTML = '<option value="">Unable to load cities</option>';
                controls.citySelect.disabled = true;
            });
    },

    handleCityChange(controls) {
        if (!controls?.citySelect || !controls.barangaySelect) return;
        const cityCode = this.getSelectedOptionCode(controls.citySelect);

        controls.barangaySelect.innerHTML = cityCode
            ? '<option value="">Loading barangays...</option>'
            : '<option value="">Select a barangay...</option>';
        controls.barangaySelect.disabled = true;

        if (!cityCode) {
            return;
        }

        GeoDataLoader.loadBarangays(cityCode)
            .then(barangays => {
                if (this.getSelectedOptionCode(controls.citySelect) !== cityCode) {
                    return;
                }
                controls.barangaySelect.innerHTML = '<option value="">Select a barangay...</option>';
                barangays.forEach(brgy => {
                    const opt = document.createElement('option');
                    opt.value = this.formatLocationName(brgy.name || brgy.brgyDesc);
                    opt.textContent = this.formatLocationName(brgy.name || brgy.brgyDesc);
                    opt.dataset.code = brgy.code || brgy.brgyCode;
                    controls.barangaySelect.appendChild(opt);
                });
                controls.barangaySelect.disabled = barangays.length === 0;
            })
            .catch(error => {
                console.error('Failed to load barangays', error);
                controls.barangaySelect.innerHTML = '<option value="">Unable to load barangays</option>';
                controls.barangaySelect.disabled = true;
            });
    },

    getSelectedOptionCode(selectEl) {
        if (!selectEl) return null;
        const option = selectEl.options[selectEl.selectedIndex];
        return option?.dataset?.code || null;
    },

    formatLocationName(name = '') {
        return name
            .toLowerCase()
            .replace(/\b([a-z])/g, letter => letter.toUpperCase())
            .replace(/\bIi\b/g, 'II')
            .replace(/\bIii\b/g, 'III');
    },

    handleRoomTypeChange() {
        if (!this.roomTypeSelect) return;

        const selectedOption = this.roomTypeSelect.options[this.roomTypeSelect.selectedIndex];
        const typeId = this.roomTypeSelect.value || '';
        const rooms = (this.roomInventory?.[typeId]) ?? (this.roomInventory?.[Number(typeId)]) ?? [];
        const hasRooms = Array.isArray(rooms) && rooms.length > 0;
        this.currentRoomTypeRooms = rooms;

        this.updateDailyRateDisplay(selectedOption);
        this.resetRoomNumberState(hasRooms ? 'Select a room...' : 'No rooms available');
        this.resetFloorState(hasRooms ? 'Select a floor...' : 'No floors available');

        if (!hasRooms) {
            return;
        }

        const uniqueFloors = Array.from(new Set(rooms.map(room => (room.floor_number ?? '').toString().trim()).filter(Boolean)));
        const floorFragment = document.createDocumentFragment();
        uniqueFloors.forEach(floor => {
            const opt = document.createElement('option');
            opt.value = floor;
            opt.textContent = floor;
            floorFragment.appendChild(opt);
        });

        this.floorInput.appendChild(floorFragment);
        this.floorInput.disabled = false;

        if (uniqueFloors.length === 1) {
            this.floorInput.value = uniqueFloors[0];
            this.handleFloorChange();
        } else {
            this.resetRoomNumberState('Select a floor first');
        }
    },

    handleFloorChange() {
        if (!this.floorInput) return;

        const selectedFloor = this.floorInput.value || '';
        const rooms = Array.isArray(this.currentRoomTypeRooms) ? this.currentRoomTypeRooms : [];
        const filteredRooms = selectedFloor
            ? rooms.filter(room => (room.floor_number ?? '').toString().trim() === selectedFloor)
            : rooms;

        if (!filteredRooms.length) {
            this.resetRoomNumberState(selectedFloor ? 'No rooms on this floor' : 'Select a room...');
            return;
        }

        const fragment = document.createDocumentFragment();
        filteredRooms.forEach(room => {
            const opt = document.createElement('option');
            const roomNumber = room.room_number || '';
            const roomLabel = room.room_name ? `${roomNumber} – ${room.room_name}` : roomNumber;
            opt.value = roomNumber;
            opt.textContent = roomLabel || 'Room';
            if (room.floor_number) {
                opt.dataset.floor = room.floor_number;
            }
            if (room.room_id) {
                opt.dataset.roomId = room.room_id;
            }
            if (room.status) {
                opt.dataset.status = room.status;
            }
            if (typeof room.bed_capacity !== 'undefined') {
                opt.dataset.bedCapacity = room.bed_capacity;
            }
            fragment.appendChild(opt);
        });

        this.roomNumberSelect.innerHTML = '<option value="">Select a room...</option>';
        this.roomNumberSelect.appendChild(fragment);
        this.roomNumberSelect.disabled = false;
        this.resetBedState('Select a room...');

        if (filteredRooms.length === 1) {
            this.roomNumberSelect.value = filteredRooms[0].room_number || filteredRooms[0].room_name || '';
            this.syncSelectedRoomDetails();
            this.updateBedOptionsForSelectedRoom();
        }
    },

    syncSelectedRoomDetails() {
        if (!this.roomNumberSelect || !this.floorInput) return;
        const selectedRoomOption = this.roomNumberSelect.options[this.roomNumberSelect.selectedIndex];

        if (!selectedRoomOption) {
            return;
        }

        const floor = selectedRoomOption.dataset.floor || '';
        if (floor && this.floorInput.value !== floor) {
            this.floorInput.value = floor;
        }
    },

    updateBedOptionsForSelectedRoom() {
        if (!this.roomNumberSelect || !this.bedNumberSelect) return;

        const selectedRoomNumber = this.roomNumberSelect.value || '';
        const selectedRoomOption = this.roomNumberSelect.options[this.roomNumberSelect.selectedIndex];
        if (!selectedRoomNumber || !selectedRoomOption) {
            this.resetBedState('Select a room first');
            return;
        }

        const rooms = Array.isArray(this.currentRoomTypeRooms) ? this.currentRoomTypeRooms : [];
        const room = rooms.find(r => (r.room_number || '').toString() === selectedRoomNumber.toString());

        const bedNames = room && Array.isArray(room.bed_names) ? room.bed_names : [];

        let capacity = selectedRoomOption.dataset.bedCapacity
            ? parseInt(selectedRoomOption.dataset.bedCapacity, 10)
            : (room && Number.isFinite(Number(room.bed_capacity))
                ? parseInt(room.bed_capacity, 10)
                : 0);

        if ((!capacity || capacity <= 0) && bedNames.length > 0) {
            capacity = bedNames.length;
        }

        if (!capacity || capacity <= 0) {
            capacity = 1;
        }

        const fragment = document.createDocumentFragment();
        for (let i = 0; i < capacity; i++) {
            const opt = document.createElement('option');
            const label = bedNames[i] ? String(bedNames[i]) : `Bed ${i + 1}`;
            opt.value = label;
            opt.textContent = label;
            fragment.appendChild(opt);
        }

        const totalBeds = bedNames.length > 0 ? bedNames.length : capacity;
        const capacityLabel = totalBeds === 1
            ? '1 bed in this room'
            : `${totalBeds} beds in this room`;
        this.bedNumberSelect.innerHTML = `<option value="">Select a bed... (${capacityLabel})</option>`;
        this.bedNumberSelect.appendChild(fragment);
        this.bedNumberSelect.disabled = false;
    },

    updateDailyRateDisplay(roomTypeOption) {
        if (!this.dailyRateInput) return;
        const rate = roomTypeOption?.dataset?.rate?.trim();
        this.dailyRateInput.value = rate || 'Auto-calculated';
    },

    handleDobChange() {
        const dobInput = document.getElementById('edit_inpatient_date_of_birth');
        const ageDisplay = document.getElementById('edit_inpatient_age');
        if (!dobInput || !ageDisplay) return;

        const dobValue = dobInput.value;
        if (!dobValue) {
            ageDisplay.value = '';
            return;
        }

        const ageYears = this.calculateAgeYears(dobValue);
        if (ageYears === null) {
            ageDisplay.value = '';
        } else if (ageYears < 1) {
            ageDisplay.value = 'Newborn / < 1 year';
        } else {
            ageDisplay.value = `${ageYears} year${ageYears !== 1 ? 's' : ''}`;
        }
    },

    handleInpatientDobChange() {
        const dobInput = document.getElementById('edit_date_of_birth');
        const ageInput = document.getElementById('edit_inpatient_age_display');
        if (!dobInput || !ageInput) return;

        const dobValue = dobInput.value;
        if (!dobValue) {
            ageInput.value = '';
            this.filterAdmittingDoctors(null);
            return;
        }

        const ageYears = this.calculateAgeYears(dobValue);
        if (ageYears === null) {
            ageInput.value = '';
            this.filterAdmittingDoctors(null);
        } else if (ageYears < 1) {
            ageInput.value = 'Newborn / < 1 year';
            this.filterAdmittingDoctors(ageYears);
        } else {
            ageInput.value = `${ageYears} year${ageYears !== 1 ? 's' : ''}`;
            this.filterAdmittingDoctors(ageYears);
        }
    },

    calculateAgeYears(dob) {
        try {
            const dobDate = new Date(dob);
            if (!dobDate || isNaN(dobDate.getTime())) return null;
            const today = new Date();
            let age = today.getFullYear() - dobDate.getFullYear();
            const m = today.getMonth() - dobDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < dobDate.getDate())) {
                age--;
            }
            if (age < 0) return null;
            return age;
        } catch (e) {
            console.error('Invalid DOB for age calculation', e);
            return null;
        }
    },

    updateBmi() {
        const weightInput = document.getElementById('edit_weight_kg');
        const heightInput = document.getElementById('edit_height_cm');
        const bmiInput = document.getElementById('edit_bmi');
        if (!weightInput || !heightInput || !bmiInput) return;

        const weight = parseFloat(weightInput.value);
        const heightCm = parseFloat(heightInput.value);
        if (!weight || !heightCm || weight <= 0 || heightCm <= 0) {
            bmiInput.value = '';
            return;
        }

        const heightM = heightCm / 100.0;
        const bmi = weight / (heightM * heightM);
        if (!isFinite(bmi)) {
            bmiInput.value = '';
            return;
        }
        bmiInput.value = bmi.toFixed(2);
    },

    filterAdmittingDoctors(ageYears) {
        const doctorSelect = document.getElementById('edit_admitting_doctor');
        if (!doctorSelect) return;

        if (!this.admittingDoctorsCache) {
            this.admittingDoctorsCache = Array.from(doctorSelect.options).map(opt => ({
                value: opt.value,
                text: opt.textContent,
                specialization: opt.getAttribute('data-specialization') || '',
                doctorName: opt.getAttribute('data-doctor-name') || ''
            }));
        }

        if (ageYears === null) {
            this.restoreAdmittingDoctorOptions();
            return;
        }

        const isPediatricAge = ageYears < 18;
        const pediatricKeywords = ['pediatric', 'pediatrics', 'pediatrician', 'neonatal', 'neonatology'];

        const filtered = this.admittingDoctorsCache.filter(opt => {
            if (opt.value === '') return true;

            const specialization = (opt.specialization || '').toLowerCase();
            const text = (opt.text || '').toLowerCase();

            if (isPediatricAge) {
                return pediatricKeywords.some(k => 
                    specialization.includes(k) || text.includes(k)
                );
            } else {
                return !pediatricKeywords.some(k => 
                    specialization.includes(k) || text.includes(k)
                );
            }
        });

        doctorSelect.innerHTML = '';
        filtered.forEach(optData => {
            const opt = document.createElement('option');
            opt.value = optData.value;
            opt.textContent = optData.text;
            if (optData.specialization) {
                opt.setAttribute('data-specialization', optData.specialization);
            }
            if (optData.doctorName) {
                opt.setAttribute('data-doctor-name', optData.doctorName);
            }
            doctorSelect.appendChild(opt);
        });

        if (filtered.length <= 1 && filtered[0]?.value === '') {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = isPediatricAge 
                ? 'No pediatric doctors available' 
                : 'No adult doctors available';
            opt.disabled = true;
            opt.selected = true;
            doctorSelect.appendChild(opt);
        }
    },

    restoreAdmittingDoctorOptions() {
        const doctorSelect = document.getElementById('edit_admitting_doctor');
        if (!doctorSelect || !this.admittingDoctorsCache) return;

        doctorSelect.innerHTML = '';
        this.admittingDoctorsCache.forEach(optData => {
            const opt = document.createElement('option');
            opt.value = optData.value;
            opt.textContent = optData.text;
            if (optData.specialization) {
                opt.setAttribute('data-specialization', optData.specialization);
            }
            if (optData.doctorName) {
                opt.setAttribute('data-doctor-name', optData.doctorName);
            }
            doctorSelect.appendChild(opt);
        });
    },

    restoreDoctorOptions() {
        const doctorSelect = document.getElementById('edit_assigned_doctor');
        if (!doctorSelect || !this.doctorsCache) return;

        doctorSelect.innerHTML = '';
        this.doctorsCache.forEach(optData => {
            const opt = document.createElement('option');
            opt.value = optData.value;
            opt.textContent = optData.text;
            if (optData.department) {
                opt.setAttribute('data-department', optData.department);
            }
            doctorSelect.appendChild(opt);
        });
    }
};

EditPatientModal.normalizeInpatientPayload = function(data) {
    if ((data.patient_type || '').toLowerCase() !== 'inpatient') {
        return;
    }

    if ((!data.first_name || !data.last_name) && data.full_name) {
        const parsed = this.parseFullName(data.full_name);
        if (parsed.firstName && !data.first_name) data.first_name = parsed.firstName;
        if (parsed.middleName && !data.middle_name) data.middle_name = parsed.middleName;
        if (parsed.lastName && !data.last_name) data.last_name = parsed.lastName;
    }

    if (data.contact_number && !data.phone) {
        data.phone = data.contact_number;
    }

    if (!data.address) {
        const addressParts = [
            data.house_number,
            data.building_name,
            data.subdivision,
            data.street_name,
            data.barangay
        ].filter(Boolean);
        if (addressParts.length) {
            data.address = addressParts.join(', ');
        }
    }

    if (!data.city && data.city_municipality) {
        data.city = data.city_municipality;
    }

    if (!data.province && data.province_name) {
        data.province = data.province_name;
    }
};

EditPatientModal.normalizeOutpatientPayload = function(data) {
    if ((data.patient_type || '').toLowerCase() !== 'outpatient') {
        return;
    }

    if (!data.address) {
        const addressParts = [
            data.house_number,
            data.building_name,
            data.subdivision,
            data.street_name,
            data.barangay,
            data.city,
            data.province
        ].filter(Boolean);

        if (addressParts.length) {
            data.address = addressParts.join(', ');
        }
    }
};

EditPatientModal.parseFullName = function(fullName) {
    const result = { firstName: '', middleName: '', lastName: '' };
    if (!fullName) {
        return result;
    }

    const trimmed = fullName.trim();
    if (!trimmed) {
        return result;
    }

    if (trimmed.includes(',')) {
        const [last, rest] = trimmed.split(',');
        result.lastName = last.trim();
        const restParts = rest ? rest.trim().split(/\s+/) : [];
        result.firstName = restParts.shift() || '';
        result.middleName = restParts.join(' ');
        return result;
    }

    const parts = trimmed.split(/\s+/);
    if (parts.length === 1) {
        result.firstName = parts[0];
        return result;
    }

    result.lastName = parts.pop();
    result.firstName = parts.shift() || '';
    result.middleName = parts.join(' ');
    return result;
};

// Tab helpers
EditPatientModal.switchTab = function(targetPanelId) {
    if (!this.formWrapper) return;
    const panels = this.formWrapper.querySelectorAll('.patient-tabs__panel');
    const targetPanel = document.getElementById(targetPanelId);
    if (!targetPanel) return;

    panels.forEach(panel => {
        const isActive = panel.id === targetPanelId;
        panel.classList.toggle('active', isActive);
        if (isActive) {
            panel.removeAttribute('hidden');
        } else {
            panel.setAttribute('hidden', '');
        }
    });

    if (this.tabButtons) {
        this.tabButtons.forEach(btn => {
            const isActive = btn.dataset.tabTarget === targetPanelId;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
    }

    const form = targetPanel.querySelector('form[data-form-type]');
    if (form) {
        this.setActiveFormByType(form.dataset.formType || 'outpatient');
    }
};

EditPatientModal.disableTab = function(tabButtonId) {
    const tabButton = document.getElementById(tabButtonId);
    if (tabButton) {
        tabButton.disabled = true;
        tabButton.style.opacity = '0.5';
        tabButton.style.cursor = 'not-allowed';
        tabButton.setAttribute('aria-disabled', 'true');
    }
};

EditPatientModal.enableAllTabs = function() {
    if (this.tabButtons) {
        this.tabButtons.forEach(btn => {
            btn.disabled = false;
            btn.style.opacity = '';
            btn.style.cursor = '';
            btn.removeAttribute('aria-disabled');
        });
    }
};

EditPatientModal.setActiveFormByType = function(formType) {
    if (!formType) return;
    const normalized = formType.toLowerCase();
    const selectedForm = this.forms[normalized];
    if (!selectedForm) return;
    this.activeFormKey = normalized;
    this.form = selectedForm;
    this.updateSaveButtonTarget();
};

EditPatientModal.updateSaveButtonTarget = function() {
    if (!this.saveBtn || !this.form) return;
    this.saveBtn.setAttribute('form', this.form.id);
    this.saveBtn.dataset.activeForm = this.form.id;
};

// Export to global scope
window.EditPatientModal = EditPatientModal;

// Global function for close button
window.closeEditPatientModal = function() {
    if (window.EditPatientModal) {
        window.EditPatientModal.close();
    }
};
