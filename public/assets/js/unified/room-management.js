/**
 * Room Management - Main Controller
 */
(function() {
    const baseUrl = document.querySelector('meta[name="base-url"]')?.content?.replace(/\/+$/, '') || '';
    const roomsTableBody = document.getElementById('roomsTableBody');
    const addRoomBtn = document.getElementById('addRoomBtn');
    let roomsData = [];

    const utils = new RoomModalUtils(baseUrl);

    // Notification functions
    window.showRoomsNotification = function(message, type = 'success') {
        const container = document.getElementById('roomsNotification');
        const messageEl = document.getElementById('departmentsNotificationMessage') || container?.querySelector('span');
        if (!container || !messageEl) {
            if (typeof showUniversalNotification === 'function') {
                showUniversalNotification(message, 'error');
            }
            return;
        }

        if (window.roomsNotificationTimeout) {
            clearTimeout(window.roomsNotificationTimeout);
        }

        container.className = `notification ${type}`;
        messageEl.textContent = String(message || '');
        container.style.display = 'flex';

        window.roomsNotificationTimeout = setTimeout(dismissRoomsNotification, 5000);
    };

    window.dismissRoomsNotification = function() {
        const container = document.getElementById('roomsNotification');
        if (container) {
            container.style.display = 'none';
        }
    };

    function escapeHtml(value) {
        if (!value) return '';
        return value.toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function capitalize(value) {
        return value ? value.charAt(0).toUpperCase() + value.slice(1) : '—';
    }

    async function fetchRooms() {
        try {
            const response = await fetch(`${baseUrl}/rooms/api`, {
                headers: { 'Accept': 'application/json' },
            });
            const payload = await response.json();
            if (!payload?.data) throw new Error('Invalid API response');

            roomsData = payload.data;
            applyFilters();
        } catch (error) {
            console.error('Failed to load rooms', error);
            if (roomsTableBody) {
                roomsTableBody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align:center; color:#b91c1c;">
                            <i class="fas fa-exclamation-triangle"></i> Could not load rooms.
                        </td>
                    </tr>
                `;
            }
        }
    }

    function applyFilters() {
        const searchInput = document.getElementById('searchRoom');
        const statusFilter = document.getElementById('statusFilterRoom');

        const searchTerm = (searchInput?.value || '').toLowerCase().trim();
        const statusValue = (statusFilter?.value || '').toLowerCase();

        const filtered = roomsData.filter(room => {
            // Search filter
            if (searchTerm) {
                const searchableText = [
                    room.room_number || '',
                    room.type_name || '',
                    room.department_name || '',
                    room.room_type || '',
                    room.assigned_patient || '', // Include assigned patient name in search
                    room.assigned_bed || '' // Include bed number in search
                ].join(' ').toLowerCase();

                if (!searchableText.includes(searchTerm)) {
                    return false;
                }
            }

            // Status filter
            if (statusValue && (room.status || '').toLowerCase() !== statusValue) {
                return false;
            }

            return true;
        });

        renderRooms(filtered);
    }

    function clearFilters() {
        const searchInput = document.getElementById('searchRoom');
        const statusFilter = document.getElementById('statusFilterRoom');

        if (searchInput) searchInput.value = '';
        if (statusFilter) statusFilter.value = '';

        applyFilters();
    }

    function renderRooms(rooms) {
        if (!roomsTableBody) return;

        if (!rooms.length) {
            roomsTableBody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align:center;">
                        <i class="fas fa-bed" aria-hidden="true"></i> No rooms yet.
                    </td>
                </tr>
            `;
            return;
        }

        roomsTableBody.innerHTML = rooms.map(room => {
            const isOccupied = (room.status || '').toLowerCase() === 'occupied';
            return `
            <tr>
                <td>
                    ${escapeHtml(room.room_number)}
                    ${room.room_type ? `<small style="display:block; color:#6b7280;">${escapeHtml(room.room_type)}</small>` : ''}
                    ${isOccupied && room.assigned_patient ? `
                    <small style="display:block; color:#3b82f6; margin-top: 0.25rem; font-weight: 500;">
                        <i class="fas fa-user" style="margin-right: 0.25rem;"></i>${escapeHtml(room.assigned_patient)}
                        ${room.assigned_bed ? ` - Bed ${escapeHtml(room.assigned_bed)}` : ''}
                    </small>
                    ` : ''}
                </td>
                <td>${escapeHtml(room.type_name || '—')}</td>
                <td>${escapeHtml(room.department_name || '—')}</td>
                <td>${room.bed_capacity ?? '—'}</td>
                <td>${capitalize(room.status || 'unknown')}</td>
                <td>
                    <div class="table-actions">
                        <button class="btn btn-sm btn-outline" data-action="view" data-room-id="${room.room_id}" aria-label="View room ${escapeHtml(room.room_number)}">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline" data-action="edit" data-room-id="${room.room_id}" aria-label="Edit room ${escapeHtml(room.room_number)}">
                            <i class="fas fa-pen"></i>
                        </button>
                        ${!isOccupied ? `<button class="btn btn-sm btn-primary" data-action="assign" data-room-id="${room.room_id}" aria-label="Assign room ${escapeHtml(room.room_number)}">
                            <i class="fas fa-user-plus"></i> Assign
                        </button>` : ''}
                        ${isOccupied ? `<button class="btn btn-sm btn-outline" data-action="discharge" data-room-id="${room.room_id}" aria-label="Discharge room ${escapeHtml(room.room_number)}">
                            <i class="fas fa-door-open"></i>
                        </button>` : ''}
                        <button class="btn btn-sm btn-outline btn-danger" data-action="delete" data-room-id="${room.room_id}" aria-label="Delete room ${escapeHtml(room.room_number)}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
        }).join('');
    }

    function handleTableClick(event) {
        const actionBtn = event.target.closest('button[data-action]');
        if (!actionBtn) return;

        const { action, roomId } = actionBtn.dataset;
        const room = roomsData.find(item => String(item.room_id) === String(roomId));
        if (!room) {
            utils.showNotification('Room not found in current list.', 'error');
            return;
        }

        if (action === 'view') {
            if (window.ViewRoomModal && window.ViewRoomModal.open) {
                window.ViewRoomModal.open(room.room_id);
            }
        } else if (action === 'edit') {
            if (window.AddRoomModal && window.AddRoomModal.open) {
                window.AddRoomModal.open(room);
            }
        } else if (action === 'assign') {
            if (window.AssignRoomModal && window.AssignRoomModal.open) {
                window.AssignRoomModal.open(room.room_id);
            }
        } else if (action === 'discharge') {
            confirmDischargeRoom(room);
        } else if (action === 'delete') {
            confirmDeleteRoom(room);
        }
    }

    function confirmDeleteRoom(room) {
        if (!confirm(`Delete room ${room.room_number}? This cannot be undone.`)) return;
        deleteRoom(room.room_id);
    }

    function confirmDischargeRoom(room) {
        if (!confirm(`Discharge room ${room.room_number}? This will free the room and finalize the stay.`)) return;
        dischargeRoom(room.room_id);
    }

    async function dischargeRoom(roomId) {
        const csrf = utils.getCsrfToken();
        const formData = new FormData();
        formData.set('room_id', roomId);
        formData.set(csrf.name, csrf.hash);

        try {
            const response = await fetch(`${baseUrl}/rooms/discharge`, {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' },
            });

            const result = await response.json();
            utils.refreshCsrfHash(result?.csrf_hash);

            if (!result.success) {
                throw new Error(result.message || 'Failed to discharge room');
            }

            const msg = result.billing_message || 'Room discharged successfully.';
            utils.showNotification(msg, 'success');
            fetchRooms();
        } catch (error) {
            console.error(error);
            utils.showNotification(error.message || 'Could not discharge room right now.', 'error');
        }
    }

    async function deleteRoom(roomId) {
        const csrf = utils.getCsrfToken();
        const payload = new URLSearchParams();
        payload.append(csrf.name, csrf.hash);

        try {
            const response = await fetch(`${baseUrl}/rooms/${roomId}/delete`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                },
                body: payload.toString(),
            });

            const result = await response.json();
            utils.refreshCsrfHash(result?.csrf_hash);

            if (!result.success) {
                throw new Error(result.message || 'Unable to delete room');
            }

            utils.showNotification('Room deleted successfully.', 'success');
            fetchRooms();
        } catch (error) {
            console.error(error);
            utils.showNotification(error.message || 'Could not delete room.', 'error');
        }
    }

    // Export refresh function for modals
    window.RoomManagement = { refresh: fetchRooms };

    // Initialize search and filters
    function initializeFilters() {
        const searchInput = document.getElementById('searchRoom');
        const statusFilter = document.getElementById('statusFilterRoom');
        const clearBtn = document.getElementById('clearFiltersRoom');

        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(applyFilters, 300);
            });
        }

        if (statusFilter) {
            statusFilter.addEventListener('change', applyFilters);
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', clearFilters);
        }
    }

    // Initialize
    if (roomsTableBody) {
        fetchRooms();
        roomsTableBody.addEventListener('click', handleTableClick);
        initializeFilters();
    }

    if (addRoomBtn) {
        addRoomBtn.addEventListener('click', () => {
            if (window.AddRoomModal && window.AddRoomModal.open) {
                window.AddRoomModal.open();
            }
        });
    }

    const assignRoomBtn = document.getElementById('assignRoomBtn');
    if (assignRoomBtn) {
        assignRoomBtn.addEventListener('click', () => {
            if (window.AssignRoomModal && window.AssignRoomModal.open) {
                window.AssignRoomModal.open();
            }
        });
    }
})();
