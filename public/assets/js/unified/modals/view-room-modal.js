/**
 * View Room Modal
 */
(function() {
    const baseUrl = document.querySelector('meta[name="base-url"]')?.content?.replace(/\/+$/, '') || '';
    const utils = new RoomModalUtils(baseUrl);
    const modalId = 'viewRoomModal';
    let currentRoomId = null;

    const modal = document.getElementById(modalId);
    const contentDiv = document.getElementById('viewRoomContent');
    const editBtn = document.getElementById('editFromViewRoomBtn');

    function init() {
        if (!modal) return;

        utils.setupModalCloseHandlers(modalId);

        if (editBtn) {
            editBtn.addEventListener('click', () => {
                if (currentRoomId && window.AddRoomModal && window.AddRoomModal.open) {
                    close();
                    // Fetch room data and open edit modal
                    fetchRoomDetails(currentRoomId).then(room => {
                        if (room && window.AddRoomModal.open) {
                            window.AddRoomModal.open(room);
                        }
                    });
                }
            });
        }
    }

    function open(roomId) {
        if (!modal || !contentDiv) return;

        currentRoomId = roomId;
        utils.open(modalId);
        loadRoomDetails(roomId);
    }

    function close() {
        utils.close(modalId);
        currentRoomId = null;
        if (contentDiv) {
            contentDiv.innerHTML = `
                <div style="text-align: center; padding: 3rem;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #cbd5e0; margin-bottom: 1rem;" aria-hidden="true"></i>
                    <p style="color: #64748b;">Loading room details...</p>
                </div>
            `;
        }
        if (editBtn) {
            editBtn.style.display = 'none';
        }
    }

    async function fetchRoomDetails(roomId) {
        try {
            const response = await fetch(`${baseUrl}/rooms/${roomId}`, {
                headers: { 'Accept': 'application/json' },
            });

            const result = await response.json();
            
            if (!result.success && result.status !== 'success') {
                throw new Error(result.message || 'Failed to load room details');
            }

            return result.data || result;
        } catch (error) {
            console.error('Error fetching room details:', error);
            throw error;
        }
    }

    async function loadRoomDetails(roomId) {
        if (!contentDiv) return;

        try {
            const room = await fetchRoomDetails(roomId);
            displayRoomDetails(room);
        } catch (error) {
            contentDiv.innerHTML = `
                <div style="text-align: center; padding: 3rem; color: #ef4444;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                    <p>Failed to load room details: ${utils.escapeHtml(error.message || 'Unknown error')}</p>
                </div>
            `;
        }
    }

    function displayRoomDetails(room) {
        if (!contentDiv) return;

        const bedNames = room.bed_names ? (Array.isArray(room.bed_names) ? room.bed_names : JSON.parse(room.bed_names || '[]')) : [];
        const statusColor = {
            'available': '#10b981',
            'occupied': '#3b82f6',
            'maintenance': '#f59e0b'
        }[room.status?.toLowerCase()] || '#6b7280';

        const statusBadge = room.status ? `
            <span style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 500; background-color: ${statusColor}20; color: ${statusColor};">
                ${utils.escapeHtml(room.status.charAt(0).toUpperCase() + room.status.slice(1))}
            </span>
        ` : '—';

        contentDiv.innerHTML = `
            <div class="form-section">
                <div class="section-header">
                    <div>
                        <h4>Room Information</h4>
                        <p class="section-subtitle">Basic room details and configuration.</p>
                    </div>
                </div>
                <div class="form-grid">
                    <div>
                        <label class="form-label">Room Number</label>
                        <div class="detail-value">${utils.escapeHtml(room.room_number || '—')}</div>
                    </div>
                    <div>
                        <label class="form-label">Room Type</label>
                        <div class="detail-value">${utils.escapeHtml(room.type_name || room.room_type || '—')}</div>
                    </div>
                    <div>
                        <label class="form-label">Floor Number</label>
                        <div class="detail-value">${utils.escapeHtml(room.floor_number || '—')}</div>
                    </div>
                    <div>
                        <label class="form-label">Department</label>
                        <div class="detail-value">${utils.escapeHtml(room.department_name || '—')}</div>
                    </div>
                    <div>
                        <label class="form-label">Accommodation Type</label>
                        <div class="detail-value">${utils.escapeHtml(room.accommodation_type || '—')}</div>
                    </div>
                    <div>
                        <label class="form-label">Status</label>
                        <div class="detail-value">${statusBadge}</div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="section-header">
                    <div>
                        <h4>Bed Configuration</h4>
                        <p class="section-subtitle">Bed capacity and bed names.</p>
                    </div>
                </div>
                <div class="form-grid">
                    <div>
                        <label class="form-label">Bed Capacity</label>
                        <div class="detail-value">${room.bed_capacity ?? '—'}</div>
                    </div>
                    <div>
                        <label class="form-label">Bed Names</label>
                        <div class="detail-value">
                            ${bedNames.length > 0 
                                ? `<ul style="margin: 0; padding-left: 1.5rem; list-style-type: disc;">
                                    ${bedNames.map(bed => `<li>${utils.escapeHtml(bed)}</li>`).join('')}
                                   </ul>`
                                : '—'}
                        </div>
                    </div>
                </div>
            </div>

            ${room.assignment ? `
            <div class="form-section">
                <div class="section-header">
                    <div>
                        <h4>Current Assignment</h4>
                        <p class="section-subtitle">Patient currently assigned to this room.</p>
                    </div>
                </div>
                <div class="form-grid">
                    ${room.assignment.patient ? `
                    <div class="full">
                        <label class="form-label">Patient Name</label>
                        <div class="detail-value" style="font-weight: 600; color: #1e293b;">
                            ${utils.escapeHtml(room.assignment.patient.full_name || 
                                `${room.assignment.patient.first_name || ''} ${room.assignment.patient.last_name || ''}`.trim() || 
                                `Patient #${room.assignment.patient.patient_id}`)}
                        </div>
                    </div>
                    ` : ''}
                    ${room.assignment.bed_number ? `
                    <div>
                        <label class="form-label">Bed Number</label>
                        <div class="detail-value">${utils.escapeHtml(room.assignment.bed_number)}</div>
                    </div>
                    ` : ''}
                    ${room.assignment.daily_rate ? `
                    <div>
                        <label class="form-label">Daily Rate</label>
                        <div class="detail-value">₱${parseFloat(room.assignment.daily_rate || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                    </div>
                    ` : ''}
                    ${room.assignment.assigned_at || room.assignment.admission_datetime ? `
                    <div>
                        <label class="form-label">Assigned At</label>
                        <div class="detail-value">${utils.escapeHtml(new Date(room.assignment.assigned_at || room.assignment.admission_datetime).toLocaleString())}</div>
                    </div>
                    ` : ''}
                    ${room.assignment.admission_id ? `
                    <div>
                        <label class="form-label">Admission ID</label>
                        <div class="detail-value">#${room.assignment.admission_id}</div>
                    </div>
                    ` : ''}
                </div>
            </div>
            ` : ''}

            ${room.created_at || room.updated_at ? `
            <div class="form-section">
                <div class="section-header">
                    <div>
                        <h4>Timestamps</h4>
                        <p class="section-subtitle">Record creation and update information.</p>
                    </div>
                </div>
                <div class="form-grid">
                    ${room.created_at ? `
                    <div>
                        <label class="form-label">Created At</label>
                        <div class="detail-value">${utils.escapeHtml(new Date(room.created_at).toLocaleString())}</div>
                    </div>
                    ` : ''}
                    ${room.updated_at ? `
                    <div>
                        <label class="form-label">Updated At</label>
                        <div class="detail-value">${utils.escapeHtml(new Date(room.updated_at).toLocaleString())}</div>
                    </div>
                    ` : ''}
                </div>
            </div>
            ` : ''}
        `;

        // Show edit button
        if (editBtn) {
            editBtn.style.display = 'inline-flex';
        }
    }

    // Export to global scope
    window.ViewRoomModal = { init, open, close };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

