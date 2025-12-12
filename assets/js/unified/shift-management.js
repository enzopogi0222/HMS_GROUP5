/**
 * Shift Management JavaScript
 * Handles all client-side functionality for the shift management system
 */

// Global variables
let shiftsData = [];
let currentFilters = {};

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Shift Management JS initialized');
    initializeShiftManagement();
});

/**
 * Initialize shift management functionality
 */
function initializeShiftManagement() {
    // Load initial schedule data
    loadShifts();

    // Ensure add shift modal is hidden on page load
    const addModal = document.getElementById('shiftModal');
    if (addModal) {
        addModal.classList.remove('active');
        // Force hide with inline style as backup
        addModal.style.display = 'none';
        console.log('Add shift modal hidden on page load');
    }
    
    setupEventListeners();
    setupModals();
}

function formatWeekday(weekday) {
    const labels = {
        1: 'Monday',
        2: 'Tuesday',
        3: 'Wednesday',
        4: 'Thursday',
        5: 'Friday',
        6: 'Saturday',
        7: 'Sunday',
    };
    const w = parseInt(weekday, 10);
    return labels[w] || 'N/A';
}

function formatSlot(slot) {
    if (!slot) return 'N/A';
    const map = {
        'morning': 'Morning',
        'afternoon': 'Afternoon',
        'night': 'Night',
        'all_day': 'All Day',
    };
    return map[slot] || slot;
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Create shift button
    const createBtn = document.getElementById('createShiftBtn');
    console.log('Create shift button found:', !!createBtn);
    
    if (createBtn) {
        createBtn.addEventListener('click', function() {
            console.log('Create shift button clicked!');
            showCreateShiftModal();
        });
    }

    // Export button
    const exportBtn = document.getElementById('exportBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', exportShifts);
    }

    // Form submit handlers
    const createForm = document.getElementById('shiftForm');
    if (createForm) {
        createForm.addEventListener('submit', handleCreateShift);
    }

    const editForm = document.getElementById('editShiftForm');
    if (editForm) {
        editForm.addEventListener('submit', handleEditShift);
    }

    // Filter controls (if they exist)
    setupFilterListeners();
}

/**
 * Setup modal functionality
 */
function setupModals() {
    // Close modal on overlay background click only
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-overlay')) {
            closeAllModals();
        }
    });

    // Specific close button handlers
    const closeButtons = ['closeShiftModal', 'closeEditShiftModal', 'cancelShiftBtn', 'cancelEditShiftBtn'];
    closeButtons.forEach(id => {
        const btn = document.getElementById(id);
        if (btn) {
            btn.addEventListener('click', closeAllModals);
        }
    });

    // Escape key to close modals
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });
}

/**
 * Load shifts from API
 */
async function loadShifts() {
    try {
        showLoadingState();
        
        const response = await fetch(`${getBaseUrl()}shifts/api?${new URLSearchParams(currentFilters)}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const result = await response.json();
        
        if (result.status === 'success') {
            shiftsData = result.data;
            renderShiftsTable(shiftsData);
            hideLoadingState();
        } else {
            showError('Failed to load shifts: ' + result.message);
        }
    } catch (error) {
        console.error('Error loading shifts:', error);
        showError('Error loading shifts. Please try again.');
    }
}

/**
 * Render shifts table
 */
function renderShiftsTable(shifts) {
    console.log('Rendering shifts table with data:', shifts);
    
    const tbody = document.getElementById('shiftsTableBody');
    if (!tbody) {
        console.error('Shifts table body not found!');
        return;
    }

    if (shifts.length === 0) {
        console.log('No shifts to display');
        tbody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; padding: 2rem;">
                    <i class="fas fa-calendar-times" style="font-size: 2rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                    <p style="color: #6b7280;">No shifts found</p>
                </td>
            </tr>
        `;
        return;
    }

    console.log('Rendering', shifts.length, 'shifts');
    tbody.innerHTML = shifts.map(shift => `
        <tr>
            <td>${escapeHtml(shift.doctor_name || 'N/A')}</td>
            <td>${formatWeekday(shift.weekday)}</td>
            <td>${formatSlot(shift.slot)}</td>
            <td>${escapeHtml(shift.department || 'N/A')}</td>
            <td>${escapeHtml(shift.shift_type || 'N/A')}</td>
            <td>
                <span class="status-badge ${shift.status || 'scheduled'}">
                    ${escapeHtml(shift.status || 'scheduled')}
                </span>
            </td>
            <td>
                <div class="action-buttons">
                    ${canEditShift(shift) ? `
                        <button class="btn btn-warning btn-small action-btn" onclick="editShift(${shift.id})" title="Edit">
                            <i class="fas fa-edit" aria-hidden="true"></i> Edit
                        </button>
                    ` : ''}
                    ${canDeleteShift(shift) ? `
                        <button class="btn btn-danger btn-small action-btn" onclick="deleteShift(${shift.id})" title="Delete">
                            <i class="fas fa-trash" aria-hidden="true"></i> Delete
                        </button>
                    ` : ''}
                </div>
            </td>
        </tr>
    `).join('');
    
    console.log('Table rendered successfully');
}

/**
 * Show loading state
 */
function showLoadingState() {
    const tbody = document.getElementById('shiftsTableBody');
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; padding: 2rem;">
                    <div class="loading-spinner">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Loading shifts...</p>
                    </div>
                </td>
            </tr>
        `;
    }
}

/**
 * Load shift modal dynamically
 */
async function loadShiftModal(type) {
    try {
        // For add modal, load the specific modal file
        let modalUrl;
        if (type === 'add') {
            modalUrl = `${getBaseUrl()}unified/modals/add-shift-modal.php`;
        } else {
            modalUrl = `${getBaseUrl()}shifts/modal/${type}`;
        }
        
        const response = await fetch(modalUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (response.ok) {
            const modalHtml = await response.text();
            
            // Create a temporary div to parse the HTML
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = modalHtml;
            
            // Find the modal in the response
            const modal = tempDiv.querySelector('.modal-overlay');
            if (modal) {
                // Add to document body
                document.body.appendChild(modal);
                
                // Setup event listeners for the new modal
                setupModalEventListeners();
                
                // Show the modal
                if (type === 'add') {
                    resetCreateForm();
                    const titleElement = document.getElementById('modalTitle');
                    if (titleElement) {
                        titleElement.textContent = 'Create Shift';
                    }
                } else if (type === 'edit') {
                    // Handle edit modal setup
                    const shiftId = modal.getAttribute('data-shift-id');
                    if (shiftId) {
                        populateEditForm(shiftsData.find(s => s.id === parseInt(shiftId)));
                    }
                }
                
                modal.classList.add('active');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }
        } else {
            console.error('Failed to load modal');
            showError('Failed to load modal. Please try again.');
        }
    } catch (error) {
        console.error('Error loading modal:', error);
        showError('Error loading modal. Please try again.');
    }
}

/**
 * Setup modal event listeners for dynamically loaded modals
 */
function setupModalEventListeners() {
    // Close modal on overlay background click only
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-overlay')) {
            closeAllModals();
        }
    });

    // Specific close button handlers
    const closeButtons = ['closeShiftModal', 'closeEditShiftModal', 'cancelShiftBtn', 'cancelEditShiftBtn'];
    closeButtons.forEach(id => {
        const btn = document.getElementById(id);
        if (btn) {
            btn.addEventListener('click', closeAllModals);
        }
    });

    // Form submit handlers
    const createForm = document.getElementById('shiftForm');
    if (createForm) {
        createForm.addEventListener('submit', handleCreateShift);
    }

    const editForm = document.getElementById('editShiftForm');
    if (editForm) {
        editForm.addEventListener('submit', handleEditShift);
    }
}

/**
 * Show create shift modal
 */
function showCreateShiftModal() {
    console.log('showCreateShiftModal called');
    
    const modal = document.getElementById('shiftModal');
    console.log('Modal element found:', !!modal);
    
    if (modal) {
        // Use the same approach that works for the yellow test button
        modal.classList.add('active');
        modal.style.display = 'flex';
        modal.style.position = 'fixed';
        modal.style.top = '0';
        modal.style.left = '0';
        modal.style.width = '100vw';
        modal.style.height = '100vh';
        modal.style.zIndex = '9999';
        modal.style.alignItems = 'center';
        modal.style.justifyContent = 'center';
        modal.style.background = 'rgba(15, 23, 42, 0.55)';
        
        document.body.style.overflow = 'hidden';
        
        console.log('Modal classes after adding active:', modal.className);
        console.log('Modal display style:', window.getComputedStyle(modal).display);
        console.log('Modal shown successfully - active class added');
    } else {
        console.error('Modal not found!');
    }
}

/**
 * Load edit shift modal dynamically
 */
async function loadEditShiftModal(shiftId) {
    try {
        const response = await fetch(`${getBaseUrl()}shifts/modal/edit/${shiftId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'text/html',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (response.ok) {
            const modalHtml = await response.text();
            
            // Create a temporary div to parse the HTML
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = modalHtml;
            
            // Find the modal in the response
            const modal = tempDiv.querySelector('.modal-overlay');
            if (modal) {
                // Add to document body
                document.body.appendChild(modal);
                
                // Setup event listeners for the new modal
                setupModalEventListeners();
                
                // Populate and show the modal
                const shift = shiftsData.find(s => s.id === shiftId);
                if (shift) {
                    populateEditForm(shift);
                }
                
                modal.classList.add('active');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }
        } else {
            console.error('Failed to load edit modal');
            showError('Failed to load edit modal. Please try again.');
        }
    } catch (error) {
        console.error('Error loading edit modal:', error);
        showError('Error loading edit modal. Please try again.');
    }
}

/**
 * Show edit shift modal
 */
function editShift(shiftId) {
    const shift = shiftsData.find(s => s.id === shiftId);
    if (!shift) return;

    // Find the modal in the hidden container
    const hiddenContainer = document.querySelector('div[style*="display: none"]');
    let modal = document.getElementById('editShiftModal');
    
    if (hiddenContainer && modal) {
        // Move modal to body if it's in the hidden container
        if (modal.parentElement.style.display === 'none') {
            document.body.appendChild(modal);
        }
    }
    
    modal = document.getElementById('editShiftModal'); // Get reference again after moving
    if (modal) {
        populateEditForm(shift);
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
}


/**
 * Delete shift
 */
async function deleteShift(shiftId) {
    if (!confirm('Are you sure you want to delete this shift?')) return;

    try {
        const formData = new FormData();
        formData.append('id', shiftId);
        
        const response = await fetch(`${getBaseUrl()}shifts/delete`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });

        const result = await response.json();
        
        if (result.status === 'success') {
            showSuccess('Shift deleted successfully');
            loadShifts();
        } else {
            showError('Failed to delete shift: ' + result.message);
        }
    } catch (error) {
        console.error('Error deleting shift:', error);
        showError('Error deleting shift. Please try again.');
    }
}

/**
 * Close all modals
 */
function closeAllModals() {
    const modals = document.querySelectorAll('.modal-overlay');
    modals.forEach(modal => {
        modal.classList.remove('active');
        modal.style.display = 'none';
    });
    document.body.style.overflow = '';
}

/**
 * Setup filter listeners
 */
function setupFilterListeners() {
    // Date filter
    const dateFilter = document.getElementById('dateFilter');
    if (dateFilter) {
        dateFilter.addEventListener('change', function() {
            currentFilters.date = this.value;
            loadShifts();
        });
    }

    // Status filter
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            currentFilters.status = this.value;
            loadShifts();
        });
    }

    // Department filter
    const deptFilter = document.getElementById('departmentFilter');
    if (deptFilter) {
        deptFilter.addEventListener('change', function() {
            currentFilters.department = this.value;
            loadShifts();
        });
    }

    // Search filter
    const searchFilter = document.getElementById('searchFilter');
    if (searchFilter) {
        let searchTimeout;
        searchFilter.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentFilters.search = this.value;
                loadShifts();
            }, 300);
        });
    }
}

/**
 * Export shifts functionality
 */
function exportShifts() {
    // Simple CSV export
    const headers = ['Doctor', 'Date', 'Start Time', 'End Time', 'Department', 'Type', 'Status'];
    const rows = shiftsData.map(shift => [
        shift.doctor_name || '',
        shift.shift_date || '',
        shift.start_time || '',
        shift.end_time || '',
        shift.department || '',
        shift.shift_type || '',
        shift.status || ''
    ]);

    let csv = headers.join(',') + '\n';
    rows.forEach(row => {
        csv += row.map(cell => `"${cell}"`).join(',') + '\n';
    });

    downloadCSV(csv, 'shifts-export.csv');
}

/**
 * Download CSV file
 */
function downloadCSV(csv, filename) {
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

/**
 * Helper functions
 */
function getBaseUrl() {
    const meta = document.querySelector('meta[name="base-url"]');
    return meta ? meta.getAttribute('content') : '/';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
}

function formatTime(timeString) {
    if (!timeString) return 'N/A';
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
}

function calculateDuration(startTime, endTime) {
    if (!startTime || !endTime) return 'N/A';
    
    try {
        const [startHours, startMinutes] = startTime.split(':').map(Number);
        const [endHours, endMinutes] = endTime.split(':').map(Number);
        
        let startTotalMinutes = startHours * 60 + startMinutes;
        let endTotalMinutes = endHours * 60 + endMinutes;
        
        // Handle overnight shifts
        if (endTotalMinutes < startTotalMinutes) {
            endTotalMinutes += 24 * 60;
        }
        
        const durationMinutes = endTotalMinutes - startTotalMinutes;
        const hours = Math.floor(durationMinutes / 60);
        const minutes = durationMinutes % 60;
        
        if (minutes === 0) {
            return `${hours} hour${hours > 1 ? 's' : ''}`;
        } else {
            return `${hours}h ${minutes}m`;
        }
    } catch (error) {
        return 'N/A';
    }
}

function canEditShift(shift) {
    // Check user permissions here
    const userRole = window.userRole || document.querySelector('meta[name="user-role"]')?.getAttribute('content');
    return ['admin', 'it_staff'].includes(userRole);
}

function canDeleteShift(shift) {
    // Check user permissions here
    const userRole = window.userRole || document.querySelector('meta[name="user-role"]')?.getAttribute('content');
    return userRole === 'admin';
}

function showSuccess(message) {
    // Show success message (could use a toast library)
    alert('Success: ' + message);
}

function showError(message) {
    // Show error message (could use a toast library)
    alert('Error: ' + message);
}

function resetCreateForm() {
    const form = document.getElementById('shiftForm');
    if (form) {
        form.reset();
        // Reset hidden ID field
        const idField = document.getElementById('shiftId');
        if (idField) {
            idField.value = '';
        }
    }
}

function populateEditForm(shift) {
    // Populate edit form with shift data
    const form = document.getElementById('editShiftForm');
    if (form) {
        // Set form values based on shift data
        const doctorSelect = form.querySelector('#editDoctorSelect');
        const startTimeInput = form.querySelector('#editShiftStart');
        const endTimeInput = form.querySelector('#editShiftEnd');
        const statusSelect = form.querySelector('#editShiftStatus');
        const idField = form.querySelector('#editShiftId');

        if (idField) idField.value = shift.id || '';
        if (doctorSelect) doctorSelect.value = shift.doctor_id || shift.staff_id || '';
        
        // Handle weekday checkboxes
        const weekdayCheckboxes = form.querySelectorAll('#editWeekdays-group input[name="weekdays[]"]');
        if (weekdayCheckboxes && weekdayCheckboxes.length > 0) {
            weekdayCheckboxes.forEach(cb => cb.checked = false);
            
            // If shift has weekdays array, check all of them
            if (Array.isArray(shift.weekdays)) {
                shift.weekdays.forEach(day => {
                    const checkbox = Array.from(weekdayCheckboxes).find(cb => cb.value === String(day));
                    if (checkbox) checkbox.checked = true;
                });
            } else if (shift.weekday) {
                // Single weekday value
                const weekdayValue = String(shift.weekday);
                const checkbox = Array.from(weekdayCheckboxes).find(cb => cb.value === weekdayValue);
                if (checkbox) checkbox.checked = true;
            }
        }
        
        if (startTimeInput) {
            const startVal = shift.start_time || shift.start || '';
            startTimeInput.value = startVal ? startVal.slice(0,5) : '';
        }
        if (endTimeInput) {
            const endVal = shift.end_time || shift.end || '';
            endTimeInput.value = endVal ? endVal.slice(0,5) : '';
        }
        if (statusSelect) statusSelect.value = shift.status || 'Scheduled';
    }
}


/**
 * Handle create shift form submission
 */
async function handleCreateShift(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    
    try {
        const response = await fetch(`${getBaseUrl()}shifts/create`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });

        const result = await response.json();
        
        if (result.status === 'success') {
            showSuccess('Shift created successfully');
            closeAllModals();
            loadShifts();
        } else {
            showError('Failed to create shift: ' + result.message);
        }
    } catch (error) {
        console.error('Error creating shift:', error);
        showError('Error creating shift. Please try again.');
    }
}

/**
 * Handle edit shift form submission
 */
async function handleEditShift(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    
    try {
        const response = await fetch(`${getBaseUrl()}shifts/update`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });

        const result = await response.json();
        
        if (result.status === 'success') {
            showSuccess('Shift updated successfully');
            closeAllModals();
            loadShifts();
        } else {
            showError('Failed to update shift: ' + result.message);
        }
    } catch (error) {
        console.error('Error updating shift:', error);
        showError('Error updating shift. Please try again.');
    }
}
