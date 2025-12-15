/**
 * Assign Room Modal - Complete Room Assignment
 */
(function() {
    const baseUrl = document.querySelector('meta[name="base-url"]')?.content?.replace(/\/+$/, '') || '';
    const utils = new RoomModalUtils(baseUrl);
    const modalId = 'assignRoomModal';
    const formId = 'assignRoomForm';

    const modal = document.getElementById(modalId);
    const form = document.getElementById(formId);
    const submitBtn = document.getElementById('saveAssignRoomBtn');
    const patientSelect = document.getElementById('assign_patient_id');
    const departmentSelect = document.getElementById('assign_department_id');
    const roomTypeSelect = document.getElementById('assign_room_type');
    const floorSelect = document.getElementById('assign_floor_number');
    const roomSelect = document.getElementById('assign_room_number');
    const bedSelect = document.getElementById('assign_bed_number');
    const dailyRateInput = document.getElementById('assign_daily_rate');
    const assignedAtInput = document.getElementById('assign_assigned_at');

    let roomInventory = window.RoomInventory || {};
    let currentRooms = [];

    function init() {
        if (!modal || !form) return;

        utils.setupModalCloseHandlers(modalId);
        if (submitBtn) {
            submitBtn.addEventListener('click', handleSubmit);
        }

        // Load room inventory from window if available
        if (window.RoomInventory) {
            roomInventory = window.RoomInventory;
        }

        // Set default assignment date/time to now
        if (assignedAtInput) {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            assignedAtInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
        }

        // Setup room type change handler
        if (roomTypeSelect) {
            roomTypeSelect.addEventListener('change', handleRoomTypeChange);
        }

        // Reset dependent room selection when department/accommodation changes
        if (departmentSelect) {
            departmentSelect.addEventListener('change', handleRoomContextChange);
        }

        // Setup floor change handler
        if (floorSelect) {
            floorSelect.addEventListener('change', handleFloorChange);
        }

        // Setup room change handler
        if (roomSelect) {
            roomSelect.addEventListener('change', handleRoomChange);
        }
    }

    function open(roomId = null) {
        if (!modal || !form) return;

        form.reset();
        currentRooms = [];
        
        // Set default assignment date/time
        if (assignedAtInput) {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            assignedAtInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
        }

        // Reset all selects
        resetFloorState();
        resetRoomState();
        resetBedState();
        if (dailyRateInput) dailyRateInput.value = 'Auto-calculated';

        // If roomId is provided, pre-select that room (handled after rooms are loaded)

        utils.open(modalId);
        loadPatients();
    }

    function close() {
        utils.close(modalId);
        if (form) form.reset();
        resetFloorState();
        resetRoomState();
        resetBedState();
        if (patientSelect) patientSelect.innerHTML = '<option value="">Select patient...</option>';
        if (dailyRateInput) dailyRateInput.value = 'Auto-calculated';
    }

    function handleRoomContextChange() {
        // If user changes department/accommodation, the current room list may no longer be valid.
        resetFloorState('Select a room type...');
        resetRoomState('Select a room...');
        resetBedState('Select a room first');
    }

    function resetFloorState(message = 'Select a floor...') {
        if (!floorSelect) return;
        floorSelect.innerHTML = `<option value="">${message}</option>`;
        floorSelect.disabled = true;
        floorSelect.value = '';
        resetRoomState();
    }

    function resetRoomState(message = 'Select a room...') {
        if (!roomSelect) return;
        roomSelect.innerHTML = `<option value="">${message}</option>`;
        roomSelect.disabled = true;
        roomSelect.value = '';
        resetBedState();
    }

    function resetBedState(message = 'Select a room first') {
        if (!bedSelect) return;
        bedSelect.innerHTML = `<option value="">${message}</option>`;
        bedSelect.disabled = true;
        bedSelect.value = '';
    }

    async function loadPatients() {
        if (!patientSelect) return;

        try {
            patientSelect.innerHTML = '<option value="">Loading patients...</option>';
            const response = await fetch(`${baseUrl}/rooms/patients`, {
                headers: { 'Accept': 'application/json' },
            });
            const payload = await response.json();
            const patients = payload?.data || payload?.patients || [];

            if (!patients.length) {
                patientSelect.innerHTML = '<option value="">No patients available</option>';
                return;
            }

            patientSelect.innerHTML = [
                '<option value="">Select patient...</option>',
                ...patients.map(p => {
                    const name = utils.escapeHtml(p.full_name || `${p.first_name || ''} ${p.last_name || ''}`.trim() || `Patient #${p.patient_id}`);
                    let patientType = '';
                    if (p.patient_type && p.patient_type.trim() !== '') {
                        const type = p.patient_type.trim();
                        patientType = ` (${type.charAt(0).toUpperCase() + type.slice(1)})`;
                    }
                    return `<option value="${p.patient_id}">${name}${patientType}</option>`;
                }),
            ].join('');
        } catch (error) {
            console.error('Failed to load patients for room assignment', error);
            if (patientSelect) {
                patientSelect.innerHTML = '<option value="">Error loading patients</option>';
            }
        }
    }

    function handleRoomTypeChange() {
        if (!roomTypeSelect || !floorSelect) return;

        const selectedOption = roomTypeSelect.options[roomTypeSelect.selectedIndex];
        const typeId = roomTypeSelect.value || '';
        const roomsByType = (roomInventory?.[typeId]) ?? (roomInventory?.[Number(typeId)]) ?? [];

        const selectedDepartmentId = (departmentSelect?.value || '').toString().trim();

        const filteredRooms = (Array.isArray(roomsByType) ? roomsByType : []).filter(room => {
            const roomDeptId = (room.department_id ?? '').toString().trim();

            if (selectedDepartmentId && roomDeptId !== selectedDepartmentId) return false;
            return true;
        });

        const hasRooms = filteredRooms.length > 0;
        currentRooms = filteredRooms;

        // Update daily rate
        if (dailyRateInput && selectedOption) {
            const rate = selectedOption.dataset.rate || '';
            if (rate) {
                dailyRateInput.value = parseFloat(rate).toFixed(2);
            } else {
                dailyRateInput.value = 'Auto-calculated';
            }
        }

        resetFloorState(hasRooms ? 'Select a floor...' : 'No floors available');
        resetRoomState();
        resetBedState();

        if (!hasRooms) {
            return;
        }

        // Get unique floors for this room type
        const uniqueFloors = Array.from(new Set(filteredRooms.map(room => (room.floor_number ?? '').toString().trim()).filter(Boolean)));
        
        if (uniqueFloors.length === 0) {
            resetFloorState('No floors available');
            return;
        }

        // Populate floor options
        const fragment = document.createDocumentFragment();
        uniqueFloors.sort((a, b) => {
            const numA = parseInt(a) || 0;
            const numB = parseInt(b) || 0;
            return numA - numB;
        }).forEach(floor => {
            const opt = document.createElement('option');
            opt.value = floor;
            opt.textContent = `Floor ${floor}`;
            fragment.appendChild(opt);
        });

        floorSelect.innerHTML = '<option value="">Select a floor...</option>';
        floorSelect.appendChild(fragment);
        floorSelect.disabled = false;
    }

    function handleFloorChange() {
        if (!floorSelect || !roomSelect) return;

        const selectedFloor = floorSelect.value || '';
        const filteredRooms = selectedFloor
            ? currentRooms.filter(room => (room.floor_number ?? '').toString().trim() === selectedFloor)
            : currentRooms;

        // Filter to only available rooms
        const availableRooms = filteredRooms.filter(room => 
            (room.status ?? '').toLowerCase() === 'available'
        );

        if (!availableRooms.length) {
            resetRoomState('No available rooms on this floor');
            return;
        }

        const fragment = document.createDocumentFragment();
        availableRooms.forEach(room => {
            const opt = document.createElement('option');
            const roomNumber = room.room_number || '';
            opt.value = roomNumber;
            const roomName = (room.room_name || '').toString().trim();
            const floorNumber = (room.floor_number || '').toString().trim();
            const nameSuffix = roomName ? ` - ${roomName}` : '';
            const floorSuffix = floorNumber ? ` (Floor ${floorNumber})` : '';
            opt.textContent = `${roomNumber}${nameSuffix}${floorSuffix}`;
            if (room.room_id) opt.dataset.roomId = room.room_id;
            if (room.bed_capacity) opt.dataset.bedCapacity = room.bed_capacity;
            if (room.bed_names) opt.dataset.bedNames = JSON.stringify(room.bed_names);
            fragment.appendChild(opt);
        });

        roomSelect.innerHTML = '<option value="">Select a room...</option>';
        roomSelect.appendChild(fragment);
        roomSelect.disabled = false;
        resetBedState('Select a room first');
    }

    function handleRoomChange() {
        if (!roomSelect || !bedSelect) return;

        const selectedOption = roomSelect.options[roomSelect.selectedIndex];
        if (!selectedOption || !selectedOption.value) {
            resetBedState('Select a room first');
            return;
        }

        const bedNames = selectedOption.dataset.bedNames 
            ? JSON.parse(selectedOption.dataset.bedNames) 
            : [];
        const bedCapacity = parseInt(selectedOption.dataset.bedCapacity || '1');

        // Generate bed options
        const beds = [];
        if (bedNames && bedNames.length > 0) {
            beds.push(...bedNames);
        } else {
            for (let i = 1; i <= bedCapacity; i++) {
                beds.push(`Bed ${i}`);
            }
        }

        if (beds.length === 0) {
            resetBedState('No beds available');
            return;
        }

        const fragment = document.createDocumentFragment();
        beds.forEach(bedName => {
            const opt = document.createElement('option');
            opt.value = bedName;
            opt.textContent = bedName;
            fragment.appendChild(opt);
        });

        bedSelect.innerHTML = '<option value="">Select a bed...</option>';
        bedSelect.appendChild(fragment);
        bedSelect.disabled = false;
    }

    async function handleSubmit() {
        if (!form || !patientSelect) return;

        const patientId = (patientSelect.value || '').trim();
        const departmentId = (departmentSelect?.value || '').trim();
        const roomType = (roomTypeSelect?.value || '').trim();
        const floorNumber = (floorSelect?.value || '').trim();
        const roomNumber = (roomSelect?.value || '').trim();
        const bedNumber = (bedSelect?.value || '').trim();
        const assignedAt = (assignedAtInput?.value || '').trim();

        // Validation
        if (!patientId) {
            utils.showNotification('Please select a patient.', 'error');
            patientSelect.focus();
            return;
        }

        if (!departmentId) {
            utils.showNotification('Please select a department.', 'error');
            if (departmentSelect) departmentSelect.focus();
            return;
        }

        if (!floorNumber) {
            utils.showNotification('Please select a floor.', 'error');
            if (floorSelect) floorSelect.focus();
            return;
        }

        if (!roomNumber) {
            utils.showNotification('Please select a room.', 'error');
            if (roomSelect) roomSelect.focus();
            return;
        }

        if (!bedNumber) {
            utils.showNotification('Please select a bed.', 'error');
            if (bedSelect) bedSelect.focus();
            return;
        }

        if (!assignedAt) {
            utils.showNotification('Please select assignment date and time.', 'error');
            if (assignedAtInput) assignedAtInput.focus();
            return;
        }

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Assigning...';
        }

        try {
            const formData = new FormData(form);
            formData.set('patient_id', patientId);
            formData.set('department_id', departmentId);
            if (roomType) {
                formData.set('room_type', roomType);
            }
            formData.set('floor_number', floorNumber);
            formData.set('room_number', roomNumber);
            formData.set('bed_number', bedNumber);
            formData.set('assigned_at', assignedAt);
            
            const dailyRate = dailyRateInput?.value || '0';
            if (dailyRate && dailyRate !== 'Auto-calculated') {
                formData.set('daily_rate', dailyRate);
            }

            const response = await fetch(`${baseUrl}/rooms/assign`, {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' },
            });

            const result = await response.json();
            utils.refreshCsrfHash(result?.csrf_hash);

            if (!result.success) {
                throw new Error(result.message || 'Failed to assign room');
            }

            utils.showNotification(result.message || 'Room assigned to patient successfully.', 'success');
            close();
            
            // Refresh room management table
            if (window.RoomManagement && typeof window.RoomManagement.refresh === 'function') {
                window.RoomManagement.refresh();
            }
        } catch (error) {
            console.error('Room assignment error:', error);
            utils.showNotification(error.message || 'Could not assign room right now.', 'error');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Assign Room';
            }
        }
    }

    // Export to global scope
    window.AssignRoomModal = { init, open, close };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
