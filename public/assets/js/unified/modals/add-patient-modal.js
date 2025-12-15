const REGION_XII_CODE = '12';

const GeoDataLoader = {
    regionCode: REGION_XII_CODE,
    provinces: null,
    citiesByProvince: {},
    barangaysByCity: {},
    provincePromise: null,
    cityPromises: {},
    barangayPromises: {},

    async loadProvinces() {
        if (this.provinces) {
            return this.provinces;
        }

        if (!this.provincePromise) {
            this.provincePromise = this.fetchData('api/geo/provinces', { region: this.regionCode })
                .then(data => {
                    this.provinces = data;
                    return data;
                })
                .finally(() => {
                    this.provincePromise = null;
                });
        }

        return this.provincePromise;
    },

    async loadCities(provinceCode) {
        if (!provinceCode) {
            return [];
        }

        if (this.citiesByProvince[provinceCode]) {
            return this.citiesByProvince[provinceCode];
        }

        if (!this.cityPromises[provinceCode]) {
            this.cityPromises[provinceCode] = this.fetchData('api/geo/cities', { province: provinceCode })
                .then(data => {
                    this.citiesByProvince[provinceCode] = data;
                    return data;
                })
                .finally(() => {
                    delete this.cityPromises[provinceCode];
                });
        }

        return this.cityPromises[provinceCode];
    },

    async loadBarangays(cityCode) {
        if (!cityCode) {
            return [];
        }

        if (this.barangaysByCity[cityCode]) {
            return this.barangaysByCity[cityCode];
        }

        if (!this.barangayPromises[cityCode]) {
            this.barangayPromises[cityCode] = this.fetchData('api/geo/barangays', { city: cityCode })
                .then(data => {
                    this.barangaysByCity[cityCode] = data;
                    return data;
                })
                .finally(() => {
                    delete this.barangayPromises[cityCode];
                });
        }

        return this.barangayPromises[cityCode];
    },

    async fetchData(path, params = {}) {
        let endpoint = path;
        const filteredParams = Object.entries(params).filter(([, value]) => value !== undefined && value !== null && value !== '');
        if (filteredParams.length) {
            const search = new URLSearchParams(filteredParams);
            endpoint += (endpoint.includes('?') ? '&' : '?') + search.toString();
        }

        const url = window.PatientConfig?.getUrl ? PatientConfig.getUrl(endpoint) : `${window.location.origin}/${endpoint}`;
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`Failed to fetch ${endpoint}`);
        }

        const payload = await response.json();
        if (payload.status !== 'success') {
            throw new Error(payload.message || 'Geo API responded with error');
        }

        return payload.data || [];
    }
};
/**
 * Add Patient Modal Controller
 * Handles the add patient modal functionality
 */

const AddPatientModal = {
    modal: null,
    form: null,
    doctorsCache: null,
    admittingDoctorsCache: null,
    forms: {},
    tabButtons: null,
    formWrapper: null,
    activeFormKey: 'outpatient',
    saveBtn: null,
    roomInventory: window.PatientRoomInventory || {},
    departmentSelect: null,
    roomTypeSelect: null,
    roomNumberSelect: null,
    floorInput: null,
    dailyRateInput: null,
    bedNumberSelect: null,
    currentRoomTypeRooms: [],
    addressControls: {},

    /**
     * Initialize the modal
     */
    init() {
        this.modal = document.getElementById('addPatientModal');
        this.forms = {
            outpatient: document.getElementById('addPatientForm'),
            inpatient: document.getElementById('addInpatientForm')
        };
        this.formWrapper = document.querySelector('[data-form-wrapper]');
        this.tabButtons = document.querySelectorAll('.patient-tabs__btn');
        this.saveBtn = document.getElementById('savePatientBtn');
        this.departmentSelect = document.getElementById('assign_department_id');
        this.roomTypeSelect = document.getElementById('assign_room_type');
        this.floorInput = document.getElementById('assign_floor_number');
        this.roomNumberSelect = document.getElementById('assign_room_number');
        this.bedNumberSelect = document.getElementById('assign_bed_number');
        this.dailyRateInput = document.getElementById('assign_daily_rate');
        this.addressControls = {
            outpatient: this.buildAddressControls('outpatient'),
            inpatient: this.buildAddressControls('inpatient')
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
        this.switchTab('outpatientTab');
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

        const admittingDoctorInput = document.getElementById('admitting_doctor');
        if (admittingDoctorInput) {
            admittingDoctorInput.addEventListener('input', () => this.clearFieldError(admittingDoctorInput));
            admittingDoctorInput.addEventListener('change', () => this.clearFieldError(admittingDoctorInput));
        }

        // Date of birth change - update age display and pediatric logic
        // Outpatient form uses 'inpatient_date_of_birth' (incorrectly named)
        const outpatientDobInput = document.getElementById('inpatient_date_of_birth');
        // Inpatient form uses 'date_of_birth'
        const inpatientDobInput = document.getElementById('date_of_birth');
        
        if (outpatientDobInput) {
            outpatientDobInput.addEventListener('change', () => this.handleDobChange());
        }
        if (inpatientDobInput) {
            inpatientDobInput.addEventListener('change', () => this.handleInpatientDobChange());
        }

        // Weight/height change - update BMI
        const weightInput = document.getElementById('weight_kg');
        const heightInput = document.getElementById('height_cm');
        if (weightInput) {
            weightInput.addEventListener('input', () => this.updateBmi());
        }
        if (heightInput) {
            heightInput.addEventListener('input', () => this.updateBmi());
        }

        // Emergency / guardian relationship "Other" handlers
        const outpatientRelSelect = document.getElementById('emergency_contact_relationship');
        const outpatientRelOther = document.getElementById('emergency_contact_relationship_other');
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

        const guardianRelSelect = document.getElementById('guardian_relationship');
        const guardianRelOther = document.getElementById('guardian_relationship_other');
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

        // Live contact number validation (per form, since the modal contains duplicate IDs)
        Object.values(this.forms).forEach((formEl) => {
            if (!formEl) return;

            const phoneEl = formEl.querySelector('input[name="phone"]');
            const phoneErrEl = formEl.querySelector('#err_phone');
            PatientUtils.bindLiveContactNoValidation(phoneEl, phoneErrEl);

            const formType = formEl.dataset.formType || '';
            if (formType === 'outpatient') {
                const emerEl = formEl.querySelector('input[name="emergency_contact_phone"]');
                const emerErrEl = formEl.querySelector('#err_emergency_contact_phone');
                PatientUtils.bindLiveContactNoValidation(emerEl, emerErrEl);
            }

            if (formType === 'inpatient') {
                const guardianEl = formEl.querySelector('input[name="guardian_contact"]');
                const guardianErrEl = formEl.querySelector('#err_guardian_contact');
                PatientUtils.bindLiveContactNoValidation(guardianEl, guardianErrEl);

                const secondaryEl = formEl.querySelector('input[name="secondary_contact"]');
                const secondaryErrEl = formEl.querySelector('#err_secondary_contact');
                PatientUtils.bindLiveContactNoValidation(secondaryEl, secondaryErrEl);
            }
        });

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
        // Add event listeners to all form inputs/selects to clear errors on change
        Object.values(this.forms).forEach(form => {
            if (!form) return;
            
            // Get all inputs, selects, and textareas
            const formFields = form.querySelectorAll('input, select, textarea');
            formFields.forEach(field => {
                // Clear error when user changes the field
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
        
        // Remove error classes
        field.classList.remove('is-invalid');
        field.classList.remove('error');
        
        // Clear error message
        const fieldName = field.getAttribute('name');
        if (fieldName) {
            const errorElement = document.getElementById(`err_${fieldName}`);
            if (errorElement) {
                errorElement.textContent = '';
            }
            
            // Also check for error elements in parent
            const parentError = field.parentElement?.querySelector('.form-error, .invalid-feedback');
            if (parentError) {
                parentError.textContent = '';
            }
        }
    },

    /**
     * Open the modal
     */
    async open() {
        if (this.modal) {
            this.modal.style.display = 'flex';
            this.modal.removeAttribute('hidden');
            this.resetForm();
            this.switchTab('outpatientTab');
            await this.loadDoctors();
            // Ensure all doctors are shown for outpatient form
            this.restoreDoctorOptions();
        }
    },

    /**
     * Close the modal
     */
    close() {
        if (this.modal) {
            this.modal.style.display = 'none';
            this.modal.setAttribute('hidden', '');
            this.resetForm();
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
        const inpatientAge = document.getElementById('inpatient_age');
        if (inpatientAge) {
            inpatientAge.value = '';
        }
        const ageDisplay = document.getElementById('age_display');
        if (ageDisplay) {
            ageDisplay.value = '';
        }
        // Reset admitting doctors cache so it refreshes with current options
        this.admittingDoctorsCache = null;
        // Restore admitting doctor dropdown to show all doctors
        this.restoreAdmittingDoctorOptions();
        // Restore all doctors for outpatient form
        this.restoreDoctorOptions();
        this.resetAddressSelects();
        this.populateProvincesForAll();
    },

    /**
     * Show/hide inpatient-only sections based on selected patient type
     */
    updateInpatientVisibility() {
        // No-op with new separated forms (kept for backward compatibility)
    },

    /**
     * Load available doctors
     */
    async loadDoctors() {
        const doctorSelect = document.getElementById('assigned_doctor');
        if (!doctorSelect) {
            console.log('Doctor select element not found');
            return;
        }

        // Only load doctors for roles that can assign them
        if (!['admin', 'receptionist', 'it_staff'].includes(PatientConfig.userRole)) {
            console.log('User role does not allow doctor assignment:', PatientConfig.userRole);
            return;
        }

        console.log('Loading doctors for user role:', PatientConfig.userRole);

        // Check if we already have doctors from PHP
        const existingOptions = doctorSelect.querySelectorAll('option');
        console.log('Existing options:', Array.from(existingOptions).map(opt => ({ value: opt.value, text: opt.textContent })));
        
        const hasRealDoctors = Array.from(existingOptions).some(option => 
            option.value !== "" && 
            option.textContent !== "No doctors available" &&
            option.textContent !== "Loading doctors..."
        );

        if (hasRealDoctors) {
            console.log('Doctors already loaded from PHP');
            // Update the first option text if needed
            const firstOption = doctorSelect.querySelector('option[value=""]');
            if (firstOption && firstOption.textContent.includes('Loading')) {
                firstOption.textContent = "Select Doctor (Optional)";
            }
            return;
        }

        console.log('No doctors found in PHP, showing no doctors available');
        doctorSelect.innerHTML = '<option value="">No doctors available</option>';
    },

    setupRoomAssignmentControls() {
        if (!this.roomTypeSelect || !this.roomNumberSelect || !this.floorInput || !this.bedNumberSelect) {
            return;
        }

        const handleRoomContextChange = () => {
            this.resetFloorState('Select a room type...');
            this.resetRoomNumberState('Select a room...');
            this.resetBedState('Select a room first');
        };

        if (this.departmentSelect) {
            this.departmentSelect.addEventListener('change', handleRoomContextChange);
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

        // Match Assign Room modal behavior: bed list remains disabled until a room is selected.
        this.bedNumberSelect.disabled = true;
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
                    return; // selection changed meanwhile
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
        const roomsByType = (this.roomInventory?.[typeId]) ?? (this.roomInventory?.[Number(typeId)]) ?? [];

        const selectedDepartmentId = (this.departmentSelect?.value || '').toString().trim();

        const filteredRooms = (Array.isArray(roomsByType) ? roomsByType : []).filter(room => {
            const roomDeptId = (room.department_id ?? '').toString().trim();

            if (selectedDepartmentId && roomDeptId !== selectedDepartmentId) return false;
            return true;
        });

        const hasRooms = filteredRooms.length > 0;
        this.currentRoomTypeRooms = filteredRooms;

        this.updateDailyRateDisplay(selectedOption);
        this.resetRoomNumberState(hasRooms ? 'Select a room...' : 'No rooms available');
        this.resetFloorState(hasRooms ? 'Select a floor...' : 'No floors available');

        if (!hasRooms) {
            return;
        }

        const uniqueFloors = Array.from(new Set(filteredRooms.map(room => (room.floor_number ?? '').toString().trim()).filter(Boolean)));
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
            // Also populate beds when a single room is auto-selected
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

        // Prefer capacity from the selected option's data attribute; fall back to room object or bedNames length.
        let capacity = selectedRoomOption.dataset.bedCapacity
            ? parseInt(selectedRoomOption.dataset.bedCapacity, 10)
            : (room && Number.isFinite(Number(room.bed_capacity))
                ? parseInt(room.bed_capacity, 10)
                : 0);

        // If capacity is missing or zero, but we have named beds, derive capacity from names.
        if ((!capacity || capacity <= 0) && bedNames.length > 0) {
            capacity = bedNames.length;
        }

        // Final safety: ensure at least one bed so the dropdown is usable.
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

    /**
     * Handle DOB change: update age display and filter doctors for newborns
     */
    handleDobChange() {
        const dobInput = document.getElementById('inpatient_date_of_birth');
        const ageDisplay = document.getElementById('inpatient_age');
        if (!dobInput || !ageDisplay) return;

        const dobValue = dobInput.value;
        if (!dobValue) {
            ageDisplay.value = '';
            this.applyPediatricLogic(null);
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

        this.applyPediatricLogic(ageYears);
    },

    handleInpatientDobChange() {
        const dobInput = document.getElementById('date_of_birth');
        const ageInput = document.getElementById('age_display');
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

    /**
     * Calculate age in years from a date string (YYYY-MM-DD)
     */
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

    /**
     * Update BMI when weight or height changes
     */
    updateBmi() {
        const weightInput = document.getElementById('weight_kg');
        const heightInput = document.getElementById('height_cm');
        const bmiInput = document.getElementById('bmi');
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

    /**
     * Apply pediatric logic: for newborns, filter doctors to pediatricians and show previous pediatrician field
     * For outpatients: always show all doctors regardless of age
     */
    applyPediatricLogic(ageYears) {
        const doctorSelect = document.getElementById('assigned_doctor');
        const pastPedWrapper = document.getElementById('pastPediatricianWrapper');

        const isNewborn = ageYears !== null && ageYears < 1;
        const isPediatricAge = ageYears !== null && ageYears < 18;

        // Toggle previous pediatrician field visibility only for newborns
        if (pastPedWrapper) {
            pastPedWrapper.style.display = isNewborn ? '' : 'none';
        }

        if (!doctorSelect) return;

        // Cache all doctor options the first time (including department from data attribute)
        if (!this.doctorsCache) {
            this.doctorsCache = Array.from(doctorSelect.options).map(opt => ({
                value: opt.value,
                text: opt.textContent,
                department: opt.getAttribute('data-department') || ''
            }));
        }

        // For outpatients: always show all doctors regardless of age
        // Check if we're on the outpatient form by checking activeFormKey or form context
        const isOutpatientForm = this.activeFormKey === 'outpatient' || 
                                 (this.form && this.form.id === 'addPatientForm');
        
        if (isOutpatientForm) {
            // Always restore all doctors for outpatients
            this.restoreDoctorOptions();
            return;
        }

        // For inpatients: apply pediatric filtering logic
        // If not pediatric age, restore full list
        if (!isPediatricAge) {
            this.restoreDoctorOptions();
            return;
        }

        // Filter to pediatric doctors for pediatric-age patients (< 18 years)
        const pediatricKeywords = ['pedia', 'pediatric', 'pediatrics', 'neonatal', 'neonatology'];
        const filtered = this.doctorsCache.filter(opt => {
            const text = (opt.text || '').toLowerCase();

            // Expect labels like "Name - Specialization"
            const parts = text.split('-');
            const specialization = parts[1] ? parts[1].trim() : '';
            const isPediatricSpecialization = ['pediatrics', 'pediatrician', 'pediatric'].includes(specialization);

            return (
                opt.value === '' ||
                isPediatricSpecialization ||
                pediatricKeywords.some(k => text.includes(k))
            );
        });

        // If no pediatric doctors found
        if (filtered.length <= 1) {
            // For newborns, do NOT fall back to adult specialists
            if (isNewborn) {
                doctorSelect.innerHTML = '';
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = 'No pediatric doctors available';
                opt.disabled = true;
                opt.selected = true;
                doctorSelect.appendChild(opt);
                return;
            }

            // For older pediatric-age patients, fall back to full list
            this.restoreDoctorOptions();
            return;
        }

        doctorSelect.innerHTML = '';
        filtered.forEach(optData => {
            const opt = document.createElement('option');
            opt.value = optData.value;
            opt.textContent = optData.text;
            doctorSelect.appendChild(opt);
        });
    },

    restoreDoctorOptions() {
        const doctorSelect = document.getElementById('assigned_doctor');
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
    },

    /**
     * Filter admitting doctors based on patient age and specialization
     */
    filterAdmittingDoctors(ageYears) {
        const datalist = document.getElementById('admitting_doctor_list');
        if (!datalist) return;

        // Cache all doctor options the first time
        if (!this.admittingDoctorsCache) {
            this.admittingDoctorsCache = Array.from(datalist.querySelectorAll('option')).map(opt => ({
                value: opt.value,
                text: opt.textContent || opt.value,
                specialization: opt.getAttribute('data-specialization') || ''
            }));
        }

        // If no age provided, restore full list
        if (ageYears === null) {
            this.restoreAdmittingDoctorOptions();
            return;
        }

        const isPediatricAge = ageYears < 18;
        const pediatricKeywords = ['pediatric', 'pediatrics', 'pediatrician', 'neonatal', 'neonatology'];

        // Filter doctors based on age
        const filtered = this.admittingDoctorsCache.filter(opt => {
            // Always include the empty option
            if (opt.value === '') return true;

            const specialization = (opt.specialization || '').toLowerCase();
            const text = (opt.text || '').toLowerCase();

            // For pediatric patients (< 18), show pediatric doctors
            if (isPediatricAge) {
                return pediatricKeywords.some(k => 
                    specialization.includes(k) || text.includes(k)
                );
            } else {
                // For adult patients (>= 18), exclude pediatric-only doctors
                return !pediatricKeywords.some(k => 
                    specialization.includes(k) || text.includes(k)
                );
            }
        });

        // Update the suggestion list
        datalist.innerHTML = '';
        filtered.forEach(optData => {
            const opt = document.createElement('option');
            opt.value = optData.value;
            if (optData.text) {
                opt.textContent = optData.text;
            }
            datalist.appendChild(opt);
        });
    },

    /**
     * Restore all admitting doctor options
     */
    restoreAdmittingDoctorOptions() {
        const datalist = document.getElementById('admitting_doctor_list');
        if (!datalist || !this.admittingDoctorsCache) return;

        datalist.innerHTML = '';
        this.admittingDoctorsCache.forEach(optData => {
            const opt = document.createElement('option');
            opt.value = optData.value;
            if (optData.text) {
                opt.textContent = optData.text;
            }
            datalist.appendChild(opt);
        });
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
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            
            const formData = this.collectFormData(form);
            const errors = this.validateFormData(formData, form.dataset.formType);
            if (Object.keys(errors).length > 0) {
                PatientUtils.displayFormErrors(errors, form);
                PatientUtils.showNotification('Please correct the highlighted fields before saving.', 'error');
                return;
            }
            
            const response = await PatientUtils.makeRequest(
                PatientConfig.getUrl(PatientConfig.endpoints.patientCreate),
                {
                    method: 'POST',
                    body: JSON.stringify(formData)
                }
            );
            
            if (response.status === 'success') {
                PatientUtils.showNotification('Patient added successfully!', 'success');
                this.close();
                
                if (window.patientManager) {
                    window.patientManager.refresh();
                }
            } else {
                throw new Error(response.message || 'Failed to add patient');
            }
        } catch (error) {
            console.error('Error adding patient:', error);
            PatientUtils.showNotification('Failed to add patient: ' + error.message, 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    },
    collectFormData(form) {
        const formData = new FormData(form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }

        this.normalizeInpatientPayload(data);
        this.normalizeOutpatientPayload(data);
        
        return data;
    },

    /**
     * Validate form data
     */
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
                address: { required: true, label: 'Address' },
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
            // Inpatient form uses first_name/last_name/phone instead of a single full_name/contact_number
            // Only require fields that actually exist as inputs on the inpatient form.
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
                level_of_consciousness: { required: true, label: 'Level of Consciousness' }
            };
        }

        // Apply phone/contact number format validation for PH numbers
        rules.phone = {
            ...(rules.phone || { required: true, label: 'Contact Number' }),
            pattern: /^09\d{9}$/,
            message: 'Contact number must start with 09 and be exactly 11 digits.'
        };

        if (typeValue === 'outpatient') {
            rules.emergency_contact_phone = {
                ...(rules.emergency_contact_phone || { required: true, label: 'Emergency Contact Phone' }),
                pattern: /^09\d{9}$/,
                message: 'Emergency contact number must start with 09 and be exactly 11 digits.'
            };
        } else {
            rules.guardian_contact = {
                ...(rules.guardian_contact || { required: true, label: 'Guardian Contact' }),
                pattern: /^09\d{9}$/,
                message: 'Guardian contact number must start with 09 and be exactly 11 digits.'
            };
            rules.secondary_contact = {
                required: false,
                label: 'Secondary Contact',
                pattern: /^09\d{9}$/,
                message: 'Secondary contact number must start with 09 and be exactly 11 digits.'
            };
        }

        return PatientUtils.validateForm(data, rules);
    }
};

AddPatientModal.normalizeInpatientPayload = function(data) {
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

AddPatientModal.normalizeOutpatientPayload = function(data) {
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

AddPatientModal.parseFullName = function(fullName) {
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
AddPatientModal.switchTab = function(targetPanelId) {
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
        // Restore all doctors when switching to outpatient tab
        if ((form.dataset.formType || 'outpatient').toLowerCase() === 'outpatient') {
            this.restoreDoctorOptions();
        }
    }
};

AddPatientModal.setActiveFormByType = function(formType) {
    if (!formType) return;
    const normalized = formType.toLowerCase();
    const selectedForm = this.forms[normalized];
    if (!selectedForm) return;
    this.activeFormKey = normalized;
    this.form = selectedForm;
    this.updateSaveButtonTarget();
};

AddPatientModal.updateSaveButtonTarget = function() {
    if (!this.saveBtn || !this.form) return;
    this.saveBtn.setAttribute('form', this.form.id);
    this.saveBtn.dataset.activeForm = this.form.id;
};

// Export to global scope
window.AddPatientModal = AddPatientModal;

// Global function for close button
window.closeAddPatientModal = function() {
    if (window.AddPatientModal) {
        window.AddPatientModal.close();
    }
};
