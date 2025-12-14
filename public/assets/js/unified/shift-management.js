/**
 * Unified Shift Management JavaScript
 * Handles all shift management functionality across different user roles
 */

class ShiftManager {
    constructor() {
        this.config = this.getConfig();
        this.calendar = null;
        this.currentView = 'list';
        this.filters = {};
        this.shifts = [];
        
        this.init();
    }

    getConfig() {
        const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const csrfHash = document.querySelector('meta[name="csrf-hash"]')?.content || '';
        const userRole = document.querySelector('meta[name="user-role"]')?.content || '';

        return {
            baseUrl: baseUrl.replace(/\/$/, ''),
            csrfToken,
            csrfHash,
            userRole,
            endpoints: {
                shifts: `${baseUrl}shifts/api`,
                create: `${baseUrl}shifts/create`,
                update: `${baseUrl}shifts/update`,
                delete: `${baseUrl}shifts/delete`,
                getShift: `${baseUrl}shifts`,
                updateStatus: `${baseUrl}shifts`,
                availableStaff: `${baseUrl}shifts/available-staff`
            }
        };
    }

    init() {
        this.bindEvents();
        this.loadShifts();
        this.initCalendar();
        this.setupAutoRefresh();
    }

    bindEvents() {
        // Modal events
        this.bindModalEvents();
        
        // Filter events
        this.bindFilterEvents();
        
        // View toggle events
        this.bindViewToggleEvents();
        
        // Form events
        this.bindFormEvents();
        
        // Action button events
        this.bindActionEvents();
    }

    bindModalEvents() {
        const createBtn = document.getElementById('createShiftBtn');
        if (createBtn) {
            // Only allow creation if user has permission
            if (this.canCreateShift()) {
                createBtn.addEventListener('click', () => this.openCreateModal());
            } else {
                // Hide button if user doesn't have permission
                createBtn.style.display = 'none';
            }
        }
        
        // Initialize modals
        if (window.AddShiftModal) window.AddShiftModal.init();
        if (window.EditShiftModal) window.EditShiftModal.init();
    }

    bindFilterEvents() {
        const dateFilter = document.getElementById('dateFilter');
        const statusFilter = document.getElementById('statusFilter');
        const departmentFilter = document.getElementById('departmentFilter');
        const searchFilter = document.getElementById('searchFilter');
        const clearFilters = document.getElementById('clearFilters');

        if (dateFilter) {
            dateFilter.addEventListener('change', () => this.applyFilters());
        }

        if (statusFilter) {
            statusFilter.addEventListener('change', () => this.applyFilters());
        }

        if (departmentFilter) {
            departmentFilter.addEventListener('change', () => this.applyFilters());
        }

        if (searchFilter) {
            let searchTimeout;
            searchFilter.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => this.applyFilters(), 300);
            });
        }

        if (clearFilters) {
            clearFilters.addEventListener('click', () => this.clearFilters());
        }
    }

    bindViewToggleEvents() {
        const listViewBtn = document.getElementById('listViewBtn');
        const calendarViewBtn = document.getElementById('calendarViewBtn');

        if (listViewBtn) {
            listViewBtn.addEventListener('click', () => this.switchView('list'));
        }

        if (calendarViewBtn) {
            calendarViewBtn.addEventListener('click', () => this.switchView('calendar'));
        }
    }

    bindFormEvents() {
        // Form events are now handled by individual modal files
    }

    bindActionEvents() {
        document.addEventListener('click', (e) => {
            const actions = {
                '.btn-edit': (btn) => this.editShift(btn.dataset.shiftId),
                '.btn-delete': (btn) => {
                    // Check if we have multiple IDs (for deleting all weekdays)
                    if (btn.dataset.shiftIds) {
                        try {
                            const ids = JSON.parse(btn.dataset.shiftIds);
                            this.deleteShift(ids);
                        } catch (err) {
                            // Fallback to single ID if parsing fails
                            this.deleteShift(btn.dataset.shiftId);
                        }
                    } else {
                        this.deleteShift(btn.dataset.shiftId);
                    }
                },
                '.btn-status': (btn) => this.updateShiftStatus(btn.dataset.shiftId, btn.dataset.status)
            };
            
            for (const [selector, handler] of Object.entries(actions)) {
                const btn = e.target.matches(selector) ? e.target : e.target.closest(selector);
                if (btn && (btn.dataset.shiftId || btn.dataset.shiftIds)) {
                    handler(btn);
                    break;
                }
            }
        });
    }

    async loadShifts() {
        try {
            this.showLoading(true);
            
            // Construct URL - use the endpoint directly as it's already constructed with baseUrl
            let urlString = this.config.endpoints.shifts;
            
            // Build query string from filters
            const params = new URLSearchParams();
            Object.keys(this.filters).forEach(key => {
                if (this.filters[key]) {
                    params.append(key, this.filters[key]);
                }
            });
            
            // Append query string if there are filters
            if (params.toString()) {
                urlString += (urlString.includes('?') ? '&' : '?') + params.toString();
            }

            console.log('Loading shifts from:', urlString);
            const response = await fetch(urlString);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('Shifts API response:', data);

            if (data.status === 'success') {
                this.shifts = data.data || [];
                console.log('Loaded shifts:', this.shifts.length, this.shifts);
                this.renderShifts();
                this.updateCalendar();
            } else {
                this.showError('Failed to load shifts: ' + (data.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error loading shifts:', error);
            this.showError('Failed to load shifts: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    }

    renderShifts() {
        const tbody = document.getElementById('shiftsTableBody');
        if (!tbody) return;

        if (this.shifts.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="empty-state" style="text-align: center; padding: 2rem;">
                        <i class="fas fa-calendar-times" style="font-size: 2rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                        <h3 style="color: #6b7280; margin: 0.5rem 0;">No shifts found</h3>
                        <p style="color: #9ca3af;">No shifts match your current filters.</p>
                    </td>
                </tr>
            `;
            return;
        }

        // Group shifts by doctor + time range + status so multiple weekdays
        // with the same time range appear as a single row in the UI.
        const groupsMap = new Map();

        this.shifts.forEach(shift => {
            const staffId   = shift.staff_id || shift.doctor_id || '';
            const startVal  = shift.start_time || shift.start || '';
            const endVal    = shift.end_time || shift.end || '';
            const rawStatus = (shift.status || 'active').toString().toLowerCase();

            const key = `${staffId}|${startVal}|${endVal}|${rawStatus}`;

            if (!groupsMap.has(key)) {
                const base = { ...shift };
                base.weekdays = [];
                base.ids = [];
                base.primaryId = shift.id;
                groupsMap.set(key, base);
            }

            const group = groupsMap.get(key);
            if (typeof shift.weekday !== 'undefined' && shift.weekday !== null) {
                group.weekdays.push(shift.weekday);
            }
            group.ids.push(shift.id);
        });

        const groupedShifts = Array.from(groupsMap.values());

        tbody.innerHTML = groupedShifts.map(shift => this.renderShiftRow(shift)).join('');
    }

    renderShiftRow(shift) {
        // Map schedule data (weekday/time/status) to the table row

        // If this is a grouped shift, we may have multiple weekdays.
        let weekdayLabel = '-';
        if (Array.isArray(shift.weekdays) && shift.weekdays.length) {
            const uniqueWeekdays = Array.from(new Set(shift.weekdays))
                .filter(wd => typeof wd !== 'undefined' && wd !== null)
                .sort();
            weekdayLabel = uniqueWeekdays
                .map(wd => this.formatWeekday(wd))
                .join(', ');
        } else if (shift.weekday) {
            weekdayLabel = this.formatWeekday(shift.weekday);
        }
        const timeLabel = (shift.start_time || shift.start || '') && (shift.end_time || shift.end || '')
            ? `${(shift.start_time || shift.start || '').slice(0,5)} - ${(shift.end_time || shift.end || '').slice(0,5)}`
            : '-';

        const rawStatus = (shift.status || 'active').toString().toLowerCase();
        let displayStatus = 'Scheduled';
        let statusClass = 'scheduled';

        if (rawStatus === 'active' || rawStatus === 'scheduled') {
            displayStatus = 'Scheduled';
            statusClass = 'scheduled';
        } else if (rawStatus === 'completed' || rawStatus === 'done' || rawStatus === 'finished') {
            displayStatus = 'Completed';
            statusClass = 'completed';
        } else if (rawStatus === 'cancelled' || rawStatus === 'canceled' || rawStatus === 'inactive') {
            displayStatus = 'Cancelled';
            statusClass = 'cancelled';
        } else {
            // Fallback: title-case any other status value
            displayStatus = rawStatus.charAt(0).toUpperCase() + rawStatus.slice(1);
        }

        const canEdit = this.canEditShift(shift);
        const canDelete = this.canDeleteShift(shift);

        // For grouped rows use a primary id for actions. This id represents
        // one underlying schedule entry; edit/delete will operate on that
        // specific entry while the UI shows the combined weekdays.
        const primaryId = shift.primaryId || shift.id;
        
        // For delete, use all IDs to delete all weekdays at once
        const allIds = (shift.ids && shift.ids.length > 0) ? shift.ids : [shift.id];
        const idsJson = JSON.stringify(allIds);

        return `
            <tr class="fade-in">
                <td>
                    <div class="doctor-info">
                        <div class="doctor-name">${this.escapeHtml(shift.doctor_name || 'Unknown')}</div>
                    </div>
                </td>
                <td>${this.escapeHtml(weekdayLabel)}</td>
                <td>${this.escapeHtml(timeLabel)}</td>
                <td>
                    <span class="status-badge ${statusClass}">
                        ${this.escapeHtml(displayStatus)}
                    </span>
                </td>
                <td>
                    <div class="action-buttons">
                        ${canEdit ? `
                            <button type="button" class="btn btn-sm btn-edit" data-shift-id="${primaryId}" title="Edit Shift">
                                <i class="fas fa-edit"></i>
                            </button>
                        ` : ''}
                        ${canDelete ? `
                            <button type="button" class="btn btn-sm btn-delete" data-shift-ids='${idsJson}' title="Delete All Weekdays">
                                <i class="fas fa-trash"></i>
                            </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `;
    }

    initCalendar() {
        const calendarEl = document.getElementById('calendar');
        if (!calendarEl) return;

        this.calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: [],
            eventClick: (info) => {
                const shiftId = info.event.id;
                if (shiftId) {
                    this.editShift(shiftId);
                }
            },
            eventDidMount: (info) => {
                const shift = this.shifts.find(s => s.id == info.event.id);
                if (shift) {
                    info.el.classList.add(`shift-${shift.status?.toLowerCase() || 'scheduled'}`);
                }
            }
        });

        this.calendar.render();
    }

    updateCalendar() {
        if (!this.calendar) return;

        const events = this.shifts.map(shift => ({
            id: shift.id,
            title: `${shift.doctor_name || 'Unknown'} - ${shift.department || ''}`,
            start: `${shift.date}T${shift.start}`,
            end: `${shift.date}T${shift.end}`,
            backgroundColor: this.getEventColor(shift.status),
            borderColor: this.getEventColor(shift.status),
            extendedProps: {
                shift: shift
            }
        }));

        this.calendar.removeAllEvents();
        this.calendar.addEventSource(events);
    }

    getEventColor(status) {
        const colors = {
            'Scheduled': '#3b82f6',
            'Completed': '#10b981',
            'Cancelled': '#ef4444'
        };
        return colors[status] || colors['Scheduled'];
    }

    switchView(view) {
        this.currentView = view;
        
        const listView = document.getElementById('listView');
        const calendarView = document.getElementById('calendarView');
        const listViewBtn = document.getElementById('listViewBtn');
        const calendarViewBtn = document.getElementById('calendarViewBtn');

        if (view === 'list') {
            if (listView) listView.style.display = 'block';
            if (calendarView) calendarView.style.display = 'none';
            if (listViewBtn) listViewBtn.classList.add('active');
            if (calendarViewBtn) calendarViewBtn.classList.remove('active');
        } else {
            if (listView) listView.style.display = 'none';
            if (calendarView) calendarView.style.display = 'block';
            if (listViewBtn) listViewBtn.classList.remove('active');
            if (calendarViewBtn) calendarViewBtn.classList.add('active');
            
            // Refresh calendar when switching to calendar view
            if (this.calendar) {
                setTimeout(() => this.calendar.updateSize(), 100);
            }
        }
    }

    applyFilters() {
        this.filters = {
            date: document.getElementById('dateFilter')?.value || '',
            status: document.getElementById('statusFilter')?.value || '',
            department: document.getElementById('departmentFilter')?.value || '',
            search: document.getElementById('searchFilter')?.value || ''
        };

        this.loadShifts();
    }

    clearFilters() {
        const dateFilter = document.getElementById('dateFilter');
        const statusFilter = document.getElementById('statusFilter');
        const departmentFilter = document.getElementById('departmentFilter');
        const searchFilter = document.getElementById('searchFilter');

        if (dateFilter) dateFilter.value = '';
        if (statusFilter) statusFilter.value = '';
        if (departmentFilter) departmentFilter.value = '';
        if (searchFilter) searchFilter.value = '';

        this.filters = {};
        this.loadShifts();
    }

    canCreateShift() {
        const userRole = this.config.userRole;
        return ['admin', 'it_staff'].includes(userRole);
    }

    openCreateModal() {
        // Double-check permission before opening modal
        if (!this.canCreateShift()) {
            alert('You do not have permission to create schedules. Only administrators and IT staff can create schedules.');
            return;
        }
        if (window.AddShiftModal) {
            window.AddShiftModal.open();
        }
    }

    async editShift(shiftId) {
        const shift = this.shifts.find(s => String(s.id) === String(shiftId));
        if (!shift) {
            this.showError('Failed to load shift details');
            return;
        }
        
        if (window.EditShiftModal) {
            window.EditShiftModal.open(shiftId);
        } else if (window.AddShiftModal) {
            // Fallback: use add modal for editing
            window.AddShiftModal.open(shift);
        }
    }


    async deleteShift(shiftIdOrIds) {
        // Handle both single ID and array of IDs
        const ids = Array.isArray(shiftIdOrIds) ? shiftIdOrIds : [shiftIdOrIds];
        const count = ids.length;
        
        const confirmMessage = count > 1 
            ? `Are you sure you want to delete all ${count} weekday entries for this schedule? This action cannot be undone.`
            : 'Are you sure you want to delete this shift? This action cannot be undone.';
        
        if (!confirm(confirmMessage)) {
            return;
        }

        try {
            const response = await fetch(this.config.endpoints.delete, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    id: count === 1 ? ids[0] : ids, // Send single ID or array
                    [this.config.csrfToken]: this.config.csrfHash
                })
            });

            const data = await response.json();

            if (data.status === 'success') {
                const successMessage = count > 1 
                    ? `Successfully deleted ${count} weekday entries`
                    : 'Shift deleted successfully';
                this.showSuccess(successMessage);
                this.loadShifts();
            } else {
                this.showError(data.message || 'Failed to delete shift');
            }

            // Update CSRF hash
            if (data.csrf) {
                this.config.csrfHash = data.csrf.value;
            }
        } catch (error) {
            console.error('Error deleting shift:', error);
            this.showError('Failed to delete shift');
        }
    }

    async updateShiftStatus(shiftId, status) {
        try {
            const response = await fetch(`${this.config.endpoints.updateStatus}/${shiftId}/status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    status: status,
                    [this.config.csrfToken]: this.config.csrfHash
                })
            });

            const data = await response.json();

            if (data.status === 'success') {
                this.showSuccess(`Shift marked as ${status.toLowerCase()}`);
                this.loadShifts();
            } else {
                this.showError(data.message || 'Failed to update shift status');
            }

            // Update CSRF hash
            if (data.csrf) {
                this.config.csrfHash = data.csrf.value;
            }
        } catch (error) {
            console.error('Error updating shift status:', error);
            this.showError('Failed to update shift status');
        }
    }


    setupAutoRefresh() {
        // Refresh shifts every 5 minutes
        setInterval(() => {
            this.loadShifts();
        }, 5 * 60 * 1000);
    }

    // Permission methods
    canEditShift(shift) {
        return ['admin', 'it_staff'].includes(this.config.userRole) || (this.config.userRole === 'doctor' && shift.staff_id === this.getCurrentStaffId());
    }

    canDeleteShift(shift) {
        return this.config.userRole === 'admin';
    }

    canUpdateStatus(shift) {
        return ['admin', 'it_staff'].includes(this.config.userRole) || (this.config.userRole === 'doctor' && shift.staff_id === this.getCurrentStaffId() && shift.status === 'Scheduled');
    }

    getCurrentStaffId() {
        // This would typically come from session data or user context
        // For now, we'll assume it's available in the page context
        return window.currentStaffId || null;
    }

    // Utility methods
    showLoading(show, element = null) {
        if (element) {
            element.classList.toggle('loading', show);
        } else {
            const tbody = document.getElementById('shiftsTableBody');
            if (tbody && show) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="loading-row">
                            <i class="fas fa-spinner fa-spin"></i> Loading shifts...
                        </td>
                    </tr>
                `;
            }
        }
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    showNotification(message, type = 'info') {
        const container = document.getElementById('scheduleNotification');
        const iconEl = document.getElementById('scheduleNotificationIcon');
        const textEl = document.getElementById('scheduleNotificationText');
        
        if (container && iconEl && textEl) {
            const isError = type === 'error';
            const isSuccess = type === 'success';
            container.style.border = isError ? '1px solid #fecaca' : '1px solid #bbf7d0';
            container.style.background = isError ? '#fee2e2' : '#ecfdf5';
            container.style.color = isError ? '#991b1b' : '#166534';
            iconEl.className = 'fas ' + (isError ? 'fa-exclamation-triangle' : (isSuccess ? 'fa-check-circle' : 'fa-info-circle'));
            textEl.textContent = this.escapeHtml(message || '');
            container.style.display = 'flex';
            setTimeout(() => { if (container.style.display !== 'none') container.style.display = 'none'; }, 4000);
            return;
        }
        
        // Fallback: create floating notification
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        const isError = type === 'error';
        const isSuccess = type === 'success';
        notification.style.cssText = `position: fixed; top: 20px; left: 20px; padding: 1rem 1.5rem; border-radius: 8px; color: ${isError ? '#991b1b' : '#166534'}; font-weight: 500; z-index: 10050; max-width: 400px; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.25); border: ${isError ? '1px solid #fecaca' : '1px solid #bbf7d0'}; background: ${isError ? '#fee2e2' : '#ecfdf5'}; display: flex; align-items: center; gap: 0.5rem; transform: translateX(-100%); transition: transform 0.3s ease;`;
        const iconClass = isError ? 'fa-exclamation-triangle' : (isSuccess ? 'fa-check-circle' : 'fa-info-circle');
        notification.innerHTML = `<div style="display: flex; align-items: center; gap: 0.5rem; width: 100%;"><i class="fas ${iconClass}" style="flex-shrink: 0;"></i><span style="flex: 1;">${this.escapeHtml(message)}</span><button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: inherit; margin-left: auto; cursor: pointer; padding: 0.25rem; flex-shrink: 0;"><i class="fas fa-times"></i></button></div>`;
        document.body.appendChild(notification);
        setTimeout(() => notification.style.transform = 'translateX(0)', 100);
        setTimeout(() => { notification.style.transform = 'translateX(-100%)'; setTimeout(() => notification.remove(), 300); }, 5000);
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatWeekday(weekday) {
        // Use ShiftModalUtils if available, otherwise use local implementation
        if (window.ShiftModalUtils && typeof window.ShiftModalUtils.formatWeekday === 'function') {
            return window.ShiftModalUtils.formatWeekday(weekday);
        }
        // Fallback implementation
        const labels = {1: 'Monday', 2: 'Tuesday', 3: 'Wednesday', 4: 'Thursday', 5: 'Friday', 6: 'Saturday', 7: 'Sunday'};
        return labels[parseInt(weekday, 10)] || 'N/A';
    }
}

// Global dismiss function for schedule notifications
function dismissScheduleNotification() {
    const container = document.getElementById('scheduleNotification');
    if (container) {
        container.style.display = 'none';
        if (window.shiftManager && window.shiftManager.config) {
            // Clear any timeout if exists
            if (window.shiftManager.notificationTimeout) {
                clearTimeout(window.shiftManager.notificationTimeout);
            }
        }
    }
}

// Initialize ShiftManager when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        // Initialize if schedule table exists (for all roles including doctors)
        if (document.getElementById('shiftsTableBody') || document.getElementById('createShiftBtn') || document.querySelector('.shift-table')) {
            window.shiftManager = new ShiftManager();
        }
    });
} else {
    // Initialize if schedule table exists (for all roles including doctors)
    if (document.getElementById('shiftsTableBody') || document.getElementById('createShiftBtn') || document.querySelector('.shift-table')) {
        window.shiftManager = new ShiftManager();
    }
}

// Global functions for backward compatibility
window.editShift = (id) => window.shiftManager?.editShift(id);
window.deleteShift = (id) => window.shiftManager?.deleteShift(id);
window.updateShiftStatus = (id, status) => window.shiftManager?.updateShiftStatus(id, status);
window.closeAddShiftModal = () => window.AddShiftModal?.close();
window.closeEditShiftModal = () => window.EditShiftModal?.close();
