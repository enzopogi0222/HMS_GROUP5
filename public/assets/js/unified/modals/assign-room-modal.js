/**
 * Assign Room Modal
 */
(function() {
    const baseUrl = document.querySelector('meta[name="base-url"]')?.content?.replace(/\/+$/, '') || '';
    const utils = new RoomModalUtils(baseUrl);
    const modalId = 'assignRoomModal';
    const formId = 'assignRoomForm';

    const modal = document.getElementById(modalId);
    const form = document.getElementById(formId);
    const submitBtn = document.getElementById('saveAssignRoomBtn');
    const roomIdInput = document.getElementById('assign_room_id');
    const patientSelect = document.getElementById('assign_patient_id');

    function init() {
        if (!modal || !form) return;

        utils.setupModalCloseHandlers(modalId);
        if (submitBtn) {
            submitBtn.addEventListener('click', handleSubmit);
        }
    }

    function open(roomId) {
        if (!modal || !form || !roomIdInput) return;

        form.reset();
        roomIdInput.value = roomId;
        patientSelect.innerHTML = '<option value="">Loading patients...</option>';

        utils.open(modalId);
        loadPatients();
    }

    function close() {
        utils.close(modalId);
        if (form) form.reset();
        if (patientSelect) patientSelect.innerHTML = '<option value="">Select patient</option>';
    }

    async function loadPatients() {
        if (!patientSelect) return;

        try {
            const response = await fetch(`${baseUrl}/rooms/patients`, {
                headers: { 'Accept': 'application/json' },
            });
            const payload = await response.json();
            const patients = payload?.data || [];

            if (!patients.length) {
                patientSelect.innerHTML = '<option value="">No patients available</option>';
                return;
            }

            patientSelect.innerHTML = [
                '<option value="">Select patient</option>',
                ...patients.map(p => {
                    const name = utils.escapeHtml(p.full_name || `${p.first_name || ''} ${p.last_name || ''}`.trim() || `Patient #${p.patient_id}`);
                    // Display patient type if available (handle both null/undefined and empty string)
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

    async function handleSubmit() {
        if (!form || !patientSelect || !roomIdInput) return;

        const patientId = (patientSelect.value || '').trim();
        const roomId = (roomIdInput.value || '').trim();

        if (!roomId) {
            utils.showNotification('Room information is missing.', 'error');
            return;
        }

        if (!patientId) {
            utils.showNotification('Please select a patient.', 'error');
            patientSelect.focus();
            return;
        }

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Assigning...';
        }

        try {
            const formData = new FormData(form);
            formData.set('room_id', roomId);
            formData.set('patient_id', patientId);

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

            utils.showNotification('Room assigned to patient successfully.', 'success');
            close();
            if (window.RoomManagement && window.RoomManagement.refresh) {
                window.RoomManagement.refresh();
            }
        } catch (error) {
            console.error(error);
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

