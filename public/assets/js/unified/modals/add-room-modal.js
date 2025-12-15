/**
 * Add/Edit Room Modal
 */
(function() {
    const baseUrl = document.querySelector('meta[name="base-url"]')?.content?.replace(/\/+$/, '') || '';
    const utils = new RoomModalUtils(baseUrl);
    const modalId = 'addRoomModal';
    const formId = 'addRoomForm';

    const modal = document.getElementById(modalId);
    const form = document.getElementById(formId);
    const submitBtn = document.getElementById('saveRoomBtn');
    const roomTypeInput = document.getElementById('modal_room_type');
    const accommodationSelect = document.getElementById('modal_accommodation_type');
    const departmentSelect = document.getElementById('modal_department');
    const floorInput = document.getElementById('modal_floor');
    const roomNumberInput = document.getElementById('modal_room_number');
    const bedCapacityInput = document.getElementById('modal_bed_capacity');
    const bedNamesContainer = document.getElementById('modal_bed_names_container');
    const modalTitle = document.getElementById('addRoomTitle');

    let editingRoomId = null;
    const existingRoomNumbers = new Set();

    const departmentAccommodationMap = {
        'Internal Medicine / General Medicine': {
            'General Ward': ['Ward Room'],
            'Semi-Private': ['Semi-Private Room'],
            'Private': ['Private Room'],
        },
        'Pediatrics': {
            'Pediatric Ward': ['Pediatric Ward Room'],
            'PICU': ['Pediatric ICU Room'],
            'Private': ['Private Room'],
        },
        'Surgery': {
            'Surgical Ward': ['Ward Room'],
            'Private': ['Private Room'],
            'ICU': ['ICU Room'],
        },
        'Orthopedics': {
            'Orthopedic Ward': ['Ward Room'],
            'Semi-Private': ['Semi-Private Room'],
            'Private': ['Private Room'],
        },
        'Obstetrics & Gynecology (OB/GYN)': {
            'Maternity Ward': ['Ward Room'],
            'Labor & Delivery': ['Delivery Room'],
            'Private': ['Private Room'],
        },
        'Ophthalmology': {
            'Day Care': ['Day Care / Recovery Room'],
            'Private': ['Private Room'],
        },
        'ENT (Ear, Nose, Throat)': {
            'General Ward': ['Ward Room'],
            'Private': ['Private Room'],
        },
        'Cardiology': {
            'Cardiac Ward': ['Ward Room'],
            'ICU / CCU': ['ICU Room'],
            'Private': ['Private Room'],
        },
        'Neurology': {
            'Neurology Ward': ['Ward Room'],
            'ICU': ['ICU Room'],
            'Private': ['Private Room'],
        },
        'Dermatology': {
            'Day Care': ['Day Care Room'],
            'General Ward': ['Ward Room'],
        },
        'Psychiatry / Mental Health': {
            'Psychiatric Ward': ['Psychiatric Room'],
            'Isolation': ['Isolation Room'],
        },
        'Radiology / Imaging': {
            '—': ['No inpatient room'],
        },
        'Pathology / Laboratory': {
            '—': ['No inpatient room'],
        },
        'Anesthesiology': {
            'Recovery': ['Recovery Room (PACU)'],
            'ICU': ['ICU Room'],
        },
        'Emergency / Accident & Trauma': {
            'Emergency': ['Emergency / Trauma Room'],
            'ICU': ['ICU Room'],
        },
        'Oncology': {
            'Oncology Ward': ['Ward Room'],
            'Private': ['Private Room'],
            'Isolation': ['Isolation Room'],
        },
        'Urology': {
            'General Ward': ['Ward Room'],
            'Private': ['Private Room'],
        },
        'Gastroenterology': {
            'General Ward': ['Ward Room'],
            'Private': ['Private Room'],
        },
        'Nephrology': {
            'Dialysis': ['Dialysis Room'],
            'General Ward': ['Ward Room'],
        },
        'Pulmonology / Respiratory Medicine': {
            'Pulmonary Ward': ['Ward Room'],
            'ICU': ['ICU Room'],
            'Isolation': ['Isolation Room'],
        },
    };

    function normalizeDepartmentName(rawName) {
        const name = (rawName || '').trim();
        if (!name) return '';

        const aliases = {
            'Internal Medicine': 'Internal Medicine / General Medicine',
            'General Medicine': 'Internal Medicine / General Medicine',
            'Internal Medicine / General Medicine': 'Internal Medicine / General Medicine',
            'OB-GYN': 'Obstetrics & Gynecology (OB/GYN)',
            'OB/GYN': 'Obstetrics & Gynecology (OB/GYN)',
            'Obstetrics & Gynecology': 'Obstetrics & Gynecology (OB/GYN)',
            'ENT': 'ENT (Ear, Nose, Throat)',
            'Psychiatry': 'Psychiatry / Mental Health',
            'Pulmonology': 'Pulmonology / Respiratory Medicine',
            'Respiratory Medicine': 'Pulmonology / Respiratory Medicine',
            'Emergency Department': 'Emergency / Accident & Trauma',
            'Emergency': 'Emergency / Accident & Trauma',
            'Radiology': 'Radiology / Imaging',
            'Laboratory': 'Pathology / Laboratory',
            'Pathology': 'Pathology / Laboratory',
        };

        return aliases[name] || name;
    }

    function getSelectedDepartmentKey() {
        if (!departmentSelect) return '';
        const selectedOption = departmentSelect.options[departmentSelect.selectedIndex];
        const selectedName = selectedOption ? selectedOption.textContent : '';
        return normalizeDepartmentName(selectedName);
    }

    function populateAccommodationOptionsForDepartment(departmentKey, preferValue = '') {
        if (!accommodationSelect) return;

        accommodationSelect.innerHTML = '<option value="">Select accommodation type</option>';
        const accommodations = Object.keys(departmentAccommodationMap[departmentKey] || {});

        accommodations.forEach(label => {
            const opt = document.createElement('option');
            opt.value = label;
            opt.textContent = label;
            accommodationSelect.appendChild(opt);
        });

        if (preferValue && accommodations.includes(preferValue)) {
            accommodationSelect.value = preferValue;
        } else {
            accommodationSelect.value = '';
        }
    }

    function populateRoomTypeOptionsForDepartment(departmentKey, accommodationValue, preferValue = '') {
        if (!roomTypeInput) return;

        roomTypeInput.innerHTML = '<option value="">Select room type</option>';
        const roomTypes = (departmentAccommodationMap[departmentKey] || {})[accommodationValue] || [];

        roomTypes.forEach(label => {
            const opt = document.createElement('option');
            opt.value = label;
            opt.textContent = label;
            roomTypeInput.appendChild(opt);
        });

        if (preferValue && roomTypes.includes(preferValue)) {
            roomTypeInput.value = preferValue;
        } else {
            roomTypeInput.value = '';
        }
    }

    function init() {
        if (!modal || !form) return;

        utils.setupModalCloseHandlers(modalId);
        form.addEventListener('submit', handleSubmit);
        if (submitBtn) submitBtn.addEventListener('click', () => form.requestSubmit());

        if (departmentSelect) {
            departmentSelect.addEventListener('change', () => {
                const selectedOption = departmentSelect.options[departmentSelect.selectedIndex];
                if (floorInput && selectedOption) {
                    floorInput.value = selectedOption.getAttribute('data-floor') || '';
                }

                const deptKey = getSelectedDepartmentKey();
                populateAccommodationOptionsForDepartment(deptKey);
                populateRoomTypeOptionsForDepartment(deptKey, accommodationSelect?.value || '');
            });
        }

        if (accommodationSelect) {
            accommodationSelect.addEventListener('change', () => {
                const deptKey = getSelectedDepartmentKey();
                populateRoomTypeOptionsForDepartment(deptKey, accommodationSelect.value);
            });
        }

        if (bedCapacityInput && bedNamesContainer) {
            bedCapacityInput.addEventListener('input', syncBedNameInputsFromCapacity);
            bedCapacityInput.addEventListener('change', syncBedNameInputsFromCapacity);
        }
    }

    function open(room = null) {
        if (!modal || !form) return;

        form.reset();
        editingRoomId = null;
        form.removeAttribute('data-room-id');
        syncBedNameInputsFromCapacity();

        if (room) {
            editingRoomId = room.room_id;
            form.setAttribute('data-room-id', room.room_id);
            populateForm(room);
            applyDepartmentFilters(room.accommodation_type || '', room.room_type || '');
            if (modalTitle) {
                modalTitle.innerHTML = '<i class="fas fa-hotel" style="color:#0ea5e9"></i> Edit Room';
            }
        } else {
            applyDepartmentFilters();
            if (modalTitle) {
                modalTitle.innerHTML = '<i class="fas fa-hotel" style="color:#0ea5e9"></i> Add New Room';
            }
        }

        utils.open(modalId);
    }

    function close() {
        utils.close(modalId);
    }

    function populateForm(room) {
        if (roomTypeInput) roomTypeInput.value = room.room_type || '';
        if (roomNumberInput) roomNumberInput.value = room.room_number || '';
        if (floorInput) floorInput.value = room.floor_number || '';
        if (departmentSelect) departmentSelect.value = room.department_id || '';
        if (accommodationSelect) accommodationSelect.value = room.accommodation_type || '';
        if (bedCapacityInput) bedCapacityInput.value = room.bed_capacity || '';
        if (document.getElementById('modal_status')) {
            document.getElementById('modal_status').value = room.status || 'available';
        }

        const bedNames = room.bed_names ? (Array.isArray(room.bed_names) ? room.bed_names : JSON.parse(room.bed_names || '[]')) : [];
        syncBedNameInputsFromCapacity(bedNames);
    }

    function applyDepartmentFilters(preferAccommodation = '', preferRoomType = '') {
        const deptKey = getSelectedDepartmentKey();
        populateAccommodationOptionsForDepartment(deptKey, preferAccommodation);
        const accommodationValue = accommodationSelect?.value || '';
        populateRoomTypeOptionsForDepartment(deptKey, accommodationValue, preferRoomType);
    }

    function syncBedNameInputsFromCapacity(existingNames = []) {
        if (!bedCapacityInput || !bedNamesContainer) return;

        const capacity = parseInt(bedCapacityInput.value, 10);
        bedNamesContainer.innerHTML = '';

        if (!Number.isFinite(capacity) || capacity <= 0) return;

        for (let i = 0; i < capacity; i++) {
            const wrapper = document.createElement('div');
            wrapper.className = 'mb-1';
            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'bed_names[]';
            input.className = 'form-input';
            input.placeholder = `e.g. 101-${String.fromCharCode(65 + i)}`;
            if (Array.isArray(existingNames) && typeof existingNames[i] === 'string') {
                input.value = existingNames[i];
            }
            wrapper.appendChild(input);
            bedNamesContainer.appendChild(wrapper);
        }
    }

    async function handleSubmit(e) {
        e.preventDefault();
        if (!form) return;

        const selectedRoomTypeLabel = (roomTypeInput?.value || '').trim();
        if (!selectedRoomTypeLabel) {
            utils.showNotification('Please select a room type.', 'error');
            roomTypeInput?.focus();
            return;
        }

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        }

        try {
            const formData = new FormData(form);
            formData.set('room_type_id', '');
            formData.set('custom_room_type', selectedRoomTypeLabel);

            const endpoint = editingRoomId
                ? `${baseUrl}/rooms/${editingRoomId}/update`
                : `${baseUrl}/rooms/create`;

            const response = await fetch(endpoint, {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' },
            });

            const result = await response.json();
            utils.refreshCsrfHash(result?.csrf_hash);

            if (!result.success) {
                throw new Error(result.message || 'Failed to save room');
            }

            utils.showNotification(`Room ${editingRoomId ? 'updated' : 'saved'} successfully.`, 'success');
            close();
            if (window.RoomManagement && window.RoomManagement.refresh) {
                window.RoomManagement.refresh();
            }
        } catch (error) {
            console.error(error);
            utils.showNotification(error.message || 'Could not process room right now.', 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Room';
            }
        }
    }

    // Export to global scope
    window.AddRoomModal = { init, open, close };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

