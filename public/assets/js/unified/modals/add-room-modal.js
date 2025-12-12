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

    const roomTypeOptionsByAccommodation = {
        'General Ward / General Accommodation': ['Ward', 'Semi-Private Room', 'Private Room', 'Deluxe Room', 'Executive Room', 'Suite / VIP Suite'],
        'Intensive / Critical Care Units': ['ICU Bed (Intensive Care Unit)', 'CCU Bed (Coronary Care Unit)', 'NICU Incubator (Neonatal ICU)', 'PICU Bed (Pediatric ICU)', 'SICU Bed (Surgical ICU)', 'MICU Bed (Medical ICU)', 'HDU Bed (High Dependency Unit)'],
        'Maternity / Obstetrics Accommodation': ['Labor Room', 'Delivery Room', 'Birthing Room (LDR – Labor, Delivery, Recovery)', 'Postpartum Room', 'Maternity Ward', 'Nursery Room'],
        'Pediatric Accommodation': ['Pediatric Ward', 'Pediatric Private Room', 'Pediatric Isolation Room', 'PICU Bed', 'Neonatal Room'],
        'Isolation Accommodation': ['Negative Pressure Isolation Room', 'Positive Pressure Isolation Room', 'Isolation Private Room', 'Isolation Ward'],
        'Surgical / Post-Operative Accommodation': ['Recovery Room', 'PACU Bed (Post-Anesthesia Care Unit)', 'Post-Op Ward', 'Post-Op Private Room'],
        'Specialty Units': ['Dialysis Station', 'Oncology Room', 'Rehabilitation / Physical Therapy Room', 'Psychiatric Room', 'TB-DOTS Isolation Room'],
    };

    function init() {
        if (!modal || !form) return;

        utils.setupModalCloseHandlers(modalId);
        form.addEventListener('submit', handleSubmit);
        if (submitBtn) submitBtn.addEventListener('click', () => form.requestSubmit());

        if (departmentSelect && floorInput) {
            departmentSelect.addEventListener('change', () => {
                const selectedOption = departmentSelect.options[departmentSelect.selectedIndex];
                if (selectedOption) {
                    floorInput.value = selectedOption.getAttribute('data-floor') || '';
                }
            });
        }

        if (accommodationSelect && roomTypeInput) {
            accommodationSelect.addEventListener('change', () => {
                populateRoomTypeOptions(accommodationSelect.value);
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
            if (modalTitle) {
                modalTitle.innerHTML = '<i class="fas fa-hotel" style="color:#0ea5e9"></i> Edit Room';
            }
        } else {
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
        if (bedCapacityInput) bedCapacityInput.value = room.bed_capacity || '';
        if (document.getElementById('modal_status')) {
            document.getElementById('modal_status').value = room.status || 'available';
        }

        const bedNames = room.bed_names ? (Array.isArray(room.bed_names) ? room.bed_names : JSON.parse(room.bed_names || '[]')) : [];
        syncBedNameInputsFromCapacity(bedNames);
    }

    function populateRoomTypeOptions(accommodationValue) {
        if (!roomTypeInput) return;

        roomTypeInput.innerHTML = '<option value="">Select room type</option>';
        const labels = roomTypeOptionsByAccommodation[accommodationValue] || [];
        labels.forEach(label => {
            const opt = document.createElement('option');
            opt.value = label;
            opt.textContent = label;
            roomTypeInput.appendChild(opt);
        });
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

