// Export appointments to Excel
    function exportToExcel() {
        const table = document.querySelector('.table').cloneNode(true);
        
        // Remove action buttons column
        const headers = table.querySelectorAll('thead th');
        const lastHeaderIndex = headers.length - 1;
        headers[lastHeaderIndex].remove();
        
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length > 0) {
                cells[cells.length - 1].remove();
            }
        });
        
        // Create Excel content
        let html = '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        html += '<head><meta charset="UTF-8">';
        html += '<style>';
        html += 'table { border-collapse: collapse; width: 100%; }';
        html += 'th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }';
        html += 'th { background-color: #667eea; color: white; font-weight: bold; }';
        html += 'tr:nth-child(even) { background-color: #f2f2f2; }';
        html += '</style></head><body>';
        const todayLabel = new Date().toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
        html += '<h2>Appointment Schedule - ' + todayLabel + '</h2>';
        html += '<table>' + table.innerHTML + '</table>';
        html += '</body></html>';
        
        const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        const date = new Date().toISOString().split('T')[0];
        const userRole = document.querySelector('meta[name="user-role"]')?.content || 'user';
        
        link.setAttribute('href', url);
        link.setAttribute('download', `appointments_${userRole}_${date}.xls`);
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Open billing modal
    function openBillingModal(appointmentId) {
        const modal = document.getElementById('billingModal');
        const idInput = document.getElementById('billing_appointment_id');
        const amountInput = document.getElementById('billing_amount');
        const submitBtn = document.getElementById('billingSubmitBtn');
        
        if (!modal || !idInput || !amountInput) {
            console.error('Billing modal elements not found');
            return;
        }

        idInput.value = appointmentId || '';
        amountInput.value = '';
        
        // Reset submit button state
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-check"></i> Add to Bill';
        }

        modal.classList.add('active');
        modal.removeAttribute('hidden');
        modal.setAttribute('aria-hidden', 'false');
        
        // Focus on amount input for better UX
        setTimeout(() => {
            amountInput.focus();
        }, 100);
    }

    function closeBillingModal() {
        const modal = document.getElementById('billingModal');
        if (!modal) return;
        modal.classList.remove('active');
        modal.setAttribute('hidden', 'true');
        modal.setAttribute('aria-hidden', 'true');
    }

    function submitBillingModal() {
        const idInput = document.getElementById('billing_appointment_id');
        const amountInput = document.getElementById('billing_amount');
        if (!idInput || !amountInput) {
            alert('Form elements not found.');
            return;
        }

        const appointmentId = parseInt(idInput.value, 10);
        const unitPrice = parseFloat(amountInput.value);

        if (!appointmentId || isNaN(appointmentId) || appointmentId <= 0) {
            alert('Invalid appointment ID.');
            return;
        }

        if (isNaN(unitPrice) || unitPrice <= 0) {
            alert('Please enter a valid positive amount.');
            amountInput.focus();
            return;
        }

        // Disable submit button to prevent double submission
        const submitBtn = document.querySelector('#billingModal .btn-primary');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
        }

        addAppointmentToBill(appointmentId, unitPrice);
    }

    // Core function to call backend billing endpoint
    function addAppointmentToBill(appointmentId, unitPrice) {
        if (!appointmentId) {
            console.error('No appointment ID provided');
            return;
        }

        const baseUrlMeta = document.querySelector('meta[name="base-url"]');
        let baseUrl = baseUrlMeta ? baseUrlMeta.content : '';
        // Remove trailing slash if present to avoid double slashes
        if (baseUrl.endsWith('/')) {
            baseUrl = baseUrl.slice(0, -1);
        }
        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfHashMeta = document.querySelector('meta[name="csrf-hash"]');
        const csrfToken = csrfTokenMeta ? csrfTokenMeta.content : '';
        const csrfHash = csrfHashMeta ? csrfHashMeta.content : '';

        const url = `${baseUrl}/appointments/${appointmentId}/bill`;

        // Prepare request body with CSRF token for CodeIgniter
        const requestBody = {
            unit_price: unitPrice,
            quantity: 1
        };
        
        // Add CSRF token to body (CodeIgniter expects it in the body or header)
        if (csrfToken && csrfHash) {
            requestBody[csrfToken] = csrfHash;
        }

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(requestBody)
        })
        .then(async response => {
            // Try to get error message from response
            let errorMessage = `HTTP error! status: ${response.status}`;
            try {
                const errorData = await response.clone().json();
                if (errorData.message) {
                    errorMessage = errorData.message;
                }
            } catch (e) {
                // If response is not JSON, use status text
                errorMessage = response.statusText || errorMessage;
            }
            
            if (!response.ok) {
                throw new Error(errorMessage);
            }
            return response.json();
        })
        .then(result => {
            // Re-enable submit button
            const submitBtn = document.querySelector('#billingModal .btn-primary');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-check"></i> Add to Bill';
            }

            if (result.success) {
                // Check if it's a duplicate message (should be shown as warning)
                const message = result.message || 'Appointment added to billing successfully.';
                const isDuplicate = message.toLowerCase().includes('already') || message.toLowerCase().includes('duplicate');
                const notificationType = isDuplicate ? 'warning' : 'success';
                
                // Show notification
                showAppointmentsNotification(message, notificationType);
                
                // Clear the form
                const amountInput = document.getElementById('billing_amount');
                if (amountInput) {
                    amountInput.value = '';
                }
                
                // Close modal after a short delay to show notification
                setTimeout(() => {
                    closeBillingModal();
                }, 500);
            } else {
                showAppointmentsNotification(result.message || 'Failed to add appointment to billing.', 'error');
            }
        })
        .catch(error => {
            console.error('Error adding appointment to billing:', error);
            console.error('URL:', url);
            console.error('Request body:', requestBody);
            
            // Re-enable submit button
            const submitBtn = document.querySelector('#billingModal .btn-primary');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-check"></i> Add to Bill';
            }
            
            // Show more detailed error message
            let errorMsg = 'Failed to add appointment to billing. ';
            if (error.message) {
                errorMsg += error.message;
            } else {
                errorMsg += 'Please check your connection and try again.';
            }
            
            showAppointmentsNotification(errorMsg, 'error');
        });
    }

    // Dismiss flash notification
    function dismissFlash() {
        const flash = document.getElementById('flashNotice');
        if (flash) flash.remove();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const exportBtn = document.getElementById('exportBtn');
        if (exportBtn) exportBtn.addEventListener('click', exportToExcel);

        // Initialize modals
        if (window.AddAppointmentModal) window.AddAppointmentModal.init();
        if (window.EditAppointmentModal) window.EditAppointmentModal.init();
        if (window.ViewAppointmentModal) window.ViewAppointmentModal.init();

        // Initialize filters
        const dateSelector = document.getElementById('dateSelector');
        if (dateSelector) {
            dateSelector.addEventListener('change', () => {
                refreshAppointments();
                if (calendar) {
                    const selectedDate = dateSelector.value;
                    if (selectedDate) {
                        calendar.gotoDate(selectedDate);
                    }
                }
            });
        }

        const statusFilterAppointment = document.getElementById('statusFilterAppointment');
        if (statusFilterAppointment) {
            statusFilterAppointment.addEventListener('change', applyFilters);
        }

        const searchAppointment = document.getElementById('searchAppointment');
        if (searchAppointment) {
            let searchTimeout;
            searchAppointment.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(applyFilters, 300);
            });
        }

        const clearFiltersBtn = document.getElementById('clearFiltersAppointment');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', clearFilters);
        }

        const refreshBtn = document.getElementById('refreshBtn');
        if (refreshBtn) refreshBtn.addEventListener('click', (e) => { e.preventDefault(); refreshAppointments(); });

        // Handle Enter key in billing modal
        const billingAmountInput = document.getElementById('billing_amount');
        if (billingAmountInput) {
            billingAmountInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    submitBillingModal();
                }
            });
        }

        // Handle form submission in billing modal
        const billingForm = document.getElementById('billingForm');
        if (billingForm) {
            billingForm.addEventListener('submit', function(e) {
                e.preventDefault();
                submitBillingModal();
            });
        }

        // Initialize calendar first
        initializeCalendar();

        // View toggle functionality
        const tableViewBtn = document.getElementById('tableViewBtn');
        const calendarViewBtn = document.getElementById('calendarViewBtn');
        const tableView = document.getElementById('tableView');
        const calendarView = document.getElementById('calendarView');

        if (tableViewBtn && calendarViewBtn) {
            tableViewBtn.addEventListener('click', () => {
                switchView('table');
            });

            calendarViewBtn.addEventListener('click', () => {
                switchView('calendar');
            });
        }

        // Load appointments - will load based on current view
        refreshAppointments();
    });

    // Calendar instance
    let calendar = null;

    function initializeCalendar() {
        const calendarEl = document.getElementById('appointmentsCalendar');
        if (!calendarEl) return;

        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            height: 'auto',
            events: [],
            eventClick: function(info) {
                const appointmentId = info.event.extendedProps.appointmentId;
                if (appointmentId) {
                    viewAppointment(appointmentId);
                }
                info.jsEvent.preventDefault();
            },
            eventContent: function(info) {
                const status = info.event.extendedProps.status || 'scheduled';
                const patientName = info.event.extendedProps.patientName || '';
                const doctorName = info.event.extendedProps.doctorName || '';
                const statusColors = {
                    'completed': '#10b981',
                    'in-progress': '#3b82f6',
                    'cancelled': '#ef4444',
                    'no-show': '#f59e0b',
                    'scheduled': '#6366f1'
                };
                const color = statusColors[status.toLowerCase()] || '#6366f1';

                const eventEl = document.createElement('div');
                eventEl.style.cssText = `
                    background: ${color};
                    color: white;
                    padding: 0.25rem 0.5rem;
                    border-radius: 4px;
                    font-size: 0.75rem;
                    font-weight: 500;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                    cursor: pointer;
                `;
                
                const doctorEl = document.createElement('div');
                doctorEl.textContent = doctorName ? `Dr. ${doctorName}` : 'No Doctor';
                doctorEl.style.cssText = 'font-weight: 600; margin-bottom: 0.125rem; overflow: hidden; text-overflow: ellipsis;';
                
                const nameEl = document.createElement('div');
                nameEl.textContent = patientName;
                nameEl.style.cssText = 'font-size: 0.7rem; opacity: 0.95; overflow: hidden; text-overflow: ellipsis;';
                
                eventEl.appendChild(doctorEl);
                eventEl.appendChild(nameEl);
                
                return { domNodes: [eventEl] };
            },
            dayMaxEvents: 3,
            moreLinkClick: 'popover',
            eventDidMount: function(info) {
                const status = info.event.extendedProps.status || 'scheduled';
                const time = info.event.extendedProps.time || '';
                const tooltip = `
                    <div style="padding: 0.5rem;">
                        <strong>${info.event.extendedProps.patientName || 'Patient'}</strong><br>
                        ${info.event.extendedProps.doctorName ? 'Dr. ' + info.event.extendedProps.doctorName : 'No Doctor'}<br>
                        ${time ? '<small>Time: ' + time + '</small><br>' : ''}
                        <small>Status: ${status.charAt(0).toUpperCase() + status.slice(1)}</small><br>
                        <small>Type: ${info.event.extendedProps.appointmentType || 'N/A'}</small>
                    </div>
                `;
                info.el.setAttribute('title', tooltip);
            }
        });

        calendar.render();
    }

    function switchView(view) {
        const tableView = document.getElementById('tableView');
        const calendarView = document.getElementById('calendarView');
        const tableViewBtn = document.getElementById('tableViewBtn');
        const calendarViewBtn = document.getElementById('calendarViewBtn');

        if (view === 'table') {
            tableView.style.display = 'block';
            calendarView.style.display = 'none';
            if (tableViewBtn) tableViewBtn.classList.add('active');
            if (calendarViewBtn) calendarViewBtn.classList.remove('active');
        } else {
            tableView.style.display = 'none';
            calendarView.style.display = 'block';
            if (tableViewBtn) tableViewBtn.classList.remove('active');
            if (calendarViewBtn) calendarViewBtn.classList.add('active');
            
            // Load all appointments when switching to calendar view
            refreshAppointments();
        }
    }

    function updateCalendarEvents() {
        if (!calendar) return;

        // Only update calendar if calendar view is visible
        const calendarView = document.getElementById('calendarView');
        if (!calendarView || calendarView.style.display === 'none') {
            return;
        }

        const events = allAppointments
            .filter(appt => {
                // Ensure we have valid date and time
                const appointmentDate = appt.appointment_date || appt.date;
                return appointmentDate != null;
            })
            .map(appt => {
                const appointmentDate = appt.appointment_date || appt.date;
                const appointmentTime = appt.appointment_time || appt.time || '00:00:00';
                const dateTime = new Date(appointmentDate + 'T' + appointmentTime);
                
                // Skip invalid dates
                if (isNaN(dateTime.getTime())) {
                    return null;
                }
                
                const patientName = `${appt.patient_first_name || ''} ${appt.patient_last_name || ''}`.trim() || 'Patient';
                const doctorName = appt.doctor_first_name && appt.doctor_last_name 
                    ? `${appt.doctor_first_name} ${appt.doctor_last_name}`.trim()
                    : '';
                const status = (appt.status || 'scheduled').toLowerCase();
                const appointmentId = appt.appointment_id || appt.id;
                const appointmentType = appt.appointment_type || 'N/A';
                
                const formattedTime = formatAppointmentTime(appointmentTime);

                return {
                    title: doctorName ? `Dr. ${doctorName} - ${patientName}` : patientName,
                    start: dateTime,
                    allDay: false,
                    extendedProps: {
                        appointmentId: appointmentId,
                        patientName: patientName,
                        doctorName: doctorName,
                        status: status,
                        appointmentType: appointmentType,
                        time: formattedTime
                    }
                };
            })
            .filter(event => event !== null); // Remove null entries

        calendar.removeAllEvents();
        if (events.length > 0) {
            calendar.addEventSource(events);
        }
        calendar.refetchEvents();
    }


    function showAppointmentsNotification(message, type) {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => showAppointmentsNotification(message, type));
            return;
        }

        let container = document.getElementById('appointmentsNotification');
        let iconEl = document.getElementById('appointmentsNotificationIcon');
        let textEl = document.getElementById('appointmentsNotificationText');
        
        // If container doesn't exist, create it
        if (!container) {
            // Try to find where to insert it (after header, before main-container)
            const header = document.querySelector('header') || document.querySelector('.main-container');
            const mainContainer = document.querySelector('.main-container');
            
            container = document.createElement('div');
            container.id = 'appointmentsNotification';
            container.setAttribute('role', 'alert');
            container.setAttribute('aria-live', 'polite');
            container.style.cssText = 'display: none; position: fixed; top: 20px; right: 20px; padding: 0.75rem 1rem; max-width: 400px; border-radius: 6px; align-items: center; gap: 0.5rem; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.25); font-size: 0.95rem; font-weight: 500; z-index: 10050; flex-direction: row;';
            
            iconEl = document.createElement('i');
            iconEl.id = 'appointmentsNotificationIcon';
            iconEl.setAttribute('aria-hidden', 'true');
            iconEl.style.cssText = 'font-size: 1.1rem; flex-shrink: 0;';
            
            textEl = document.createElement('span');
            textEl.id = 'appointmentsNotificationText';
            textEl.style.cssText = 'flex: 1;';
            
            const closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.setAttribute('aria-label', 'Dismiss notification');
            closeBtn.onclick = dismissAppointmentNotification;
            closeBtn.style.cssText = 'margin-left:auto; background:transparent; border:none; cursor:pointer; color:inherit; padding: 0.25rem; flex-shrink: 0;';
            closeBtn.innerHTML = '<i class="fas fa-times" style="font-size: 0.9rem;"></i>';
            
            container.appendChild(iconEl);
            container.appendChild(textEl);
            container.appendChild(closeBtn);
            
            if (mainContainer && mainContainer.parentNode) {
                mainContainer.parentNode.insertBefore(container, mainContainer);
            } else if (header && header.nextSibling) {
                header.parentNode.insertBefore(container, header.nextSibling);
            } else {
                document.body.insertBefore(container, document.body.firstChild);
            }
        }
        
        // If icon or text elements don't exist, create them
        if (!iconEl) {
            iconEl = document.createElement('i');
            iconEl.id = 'appointmentsNotificationIcon';
            iconEl.setAttribute('aria-hidden', 'true');
            iconEl.style.cssText = 'font-size: 1.1rem; flex-shrink: 0;';
            container.insertBefore(iconEl, container.firstChild);
        }
        
        if (!textEl) {
            textEl = document.createElement('span');
            textEl.id = 'appointmentsNotificationText';
            textEl.style.cssText = 'flex: 1;';
            if (iconEl.nextSibling) {
                container.insertBefore(textEl, iconEl.nextSibling);
            } else {
                container.appendChild(textEl);
            }
        }

        const isError = type === 'error' || type === 'warning';

        // Set styling based on type
        container.style.border = isError ? '1px solid #fecaca' : '1px solid #86efac';
        container.style.background = isError ? '#fee2e2' : '#dcfce7';
        container.style.color = isError ? '#991b1b' : '#166534';
        container.style.display = 'flex';

        // Set icon
        iconEl.className = 'fas ' + (isError ? 'fa-exclamation-triangle' : 'fa-check-circle');
        textEl.textContent = message || '';

        // Scroll to top to show notification
        window.scrollTo({ top: 0, behavior: 'smooth' });

        // Auto-hide after 5 seconds
        if (window.appointmentsNotificationTimeout) {
            clearTimeout(window.appointmentsNotificationTimeout);
        }
        window.appointmentsNotificationTimeout = setTimeout(function() {
            if (container) {
                container.style.display = 'none';
            }
        }, 5000);
    }

    function dismissAppointmentNotification() {
        const container = document.getElementById('appointmentsNotification');
        if (container) {
            container.style.display = 'none';
        }
    }

    function formatAppointmentTime(timeStr) {
        if (!timeStr) return '—';
        const [h, m] = timeStr.split(':');
        let hour = parseInt(h, 10);
        const minutes = m || '00';
        const ampm = hour >= 12 ? 'PM' : 'AM';
        hour = hour % 12;
        if (hour === 0) hour = 12;
        return `${hour}:${minutes} ${ampm}`;
    }

    function getStatusBadgeClass(status) {
        const s = (status || 'scheduled').toLowerCase();
        switch (s) {
            case 'completed': return 'badge-success';
            case 'in-progress': return 'badge-info';
            case 'cancelled': return 'badge-danger';
            case 'no-show': return 'badge-warning';
            default: return 'badge-info';
        }
    }

    let allAppointments = [];

    function refreshAppointments() {
        const baseUrl = document.querySelector('meta[name="base-url"]').content;
        const userRole = document.querySelector('meta[name="user-role"]').content || 'guest';
        const params = new URLSearchParams();

        // Check if calendar view is active
        const calendarView = document.getElementById('calendarView');
        const isCalendarView = calendarView && calendarView.style.display !== 'none';

        // For calendar view, load all appointments (no date filter)
        // For table view, use date filter if set
        if (!isCalendarView) {
            const dateFilterEl = document.getElementById('dateSelector');
            if (dateFilterEl && dateFilterEl.value) {
                params.append('date', dateFilterEl.value);
            }
        }

        const url = params.toString()
            ? `${baseUrl}/appointments/api?${params.toString()}`
            : `${baseUrl}/appointments/api`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    allAppointments = data.data || [];
                    
                    // Filter to show all incoming appointments in calendar (today and future)
                    if (isCalendarView) {
                        const today = new Date();
                        today.setHours(0, 0, 0, 0);
                        allAppointments = allAppointments.filter(appt => {
                            const apptDate = appt.appointment_date || appt.date;
                            if (!apptDate) return false;
                            const appointmentDate = new Date(apptDate);
                            appointmentDate.setHours(0, 0, 0, 0);
                            
                            // Show all appointments from today onwards (incoming appointments)
                            // This includes today, tomorrow, and all future dates
                            return appointmentDate >= today;
                        });
                    }
                    
                    applyFilters();
                    updateCalendarEvents();
                } else {
                    allAppointments = [];
                    renderAppointmentsTable([]);
                    if (calendar) {
                        calendar.removeAllEvents();
                    }
                }

                // Title is already rendered with today's date by PHP
            })
            .catch(error => {
                console.error('Error loading appointments:', error);
                allAppointments = [];
                renderAppointmentsTable([]);
            });
    }

    function applyFilters() {
        const searchInput = document.getElementById('searchAppointment');
        const statusFilter = document.getElementById('statusFilterAppointment');
        
        const searchTerm = (searchInput?.value || '').toLowerCase().trim();
        const statusValue = (statusFilter?.value || '').toLowerCase();

        const filtered = allAppointments.filter(appt => {
            // Search filter
            if (searchTerm) {
                const searchableText = [
                    appt.patient_first_name || '',
                    appt.patient_last_name || '',
                    appt.doctor_first_name || '',
                    appt.doctor_last_name || '',
                    appt.appointment_type || '',
                    appt.doctor_department || ''
                ].join(' ').toLowerCase();

                if (!searchableText.includes(searchTerm)) {
                    return false;
                }
            }

            // Status filter
            if (statusValue && (appt.status || '').toLowerCase() !== statusValue) {
                return false;
            }

            return true;
        });

        renderAppointmentsTable(filtered);
        
        // Update calendar - in calendar view, show all appointments (not filtered)
        // The calendar will show all appointments loaded, not the filtered ones
        updateCalendarEvents();
    }

    function clearFilters() {
        const searchInput = document.getElementById('searchAppointment');
        const statusFilter = document.getElementById('statusFilterAppointment');
        const dateFilter = document.getElementById('dateSelector');

        if (searchInput) searchInput.value = '';
        if (statusFilter) statusFilter.value = '';
        if (dateFilter) {
            const today = new Date();
            dateFilter.value = today.toISOString().split('T')[0];
        }

        refreshAppointments();
    }

    function renderAppointmentsTable(appointments) {
        const tbody = document.getElementById('appointmentsTableBody');
        if (!tbody) return;

        const userRole = document.querySelector('meta[name="user-role"]').content || 'guest';
        const isAdmin = userRole === 'admin';

        while (tbody.firstChild) {
            tbody.removeChild(tbody.firstChild);
        }

        if (!appointments || !appointments.length) {
            const tr = document.createElement('tr');
            const td = document.createElement('td');
            // Match PHP view headers: Patient, [Doctor if admin], Type, Status, Actions
            td.colSpan = isAdmin ? 5 : 4;
            td.style.textAlign = 'center';
            td.style.padding = '2rem';
            td.style.color = '#6b7280';
            td.innerHTML = `
                <i class="fas fa-calendar-times" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                <p>No appointments found for the selected criteria.</p>
            `;
            tr.appendChild(td);
            tbody.appendChild(tr);
            return;
        }

        appointments.forEach(appt => {
            // Support both unified API (appointment_id) and any legacy id field
            const apptId = appt.appointment_id || appt.id;
            const tr = document.createElement('tr');

            const patientTd = document.createElement('td');
            const patientDiv = document.createElement('div');
            const patientStrong = document.createElement('strong');
            const baseUrl = document.querySelector('meta[name="base-url"]').content;
            const link = document.createElement('a');
            link.href = `${baseUrl}/${userRole}/patient-management?patient_id=${appt.patient_id || ''}`;
            link.className = 'patient-link';
            link.style.color = '#3b82f6';
            link.style.textDecoration = 'none';
            link.textContent = `${appt.patient_first_name || ''} ${appt.patient_last_name || ''}`.trim();
            patientStrong.appendChild(link);
            patientDiv.appendChild(patientStrong);
            patientTd.appendChild(patientDiv);
            tr.appendChild(patientTd);

            if (isAdmin) {
                const doctorTd = document.createElement('td');
                const docDiv = document.createElement('div');
                const docStrong = document.createElement('strong');
                docStrong.textContent = `Dr. ${(appt.doctor_first_name || '') + ' ' + (appt.doctor_last_name || '')}`.trim();
                docDiv.appendChild(docStrong);

                const dept = (appt.doctor_department || '').trim();
                if (dept) {
                    const br3 = document.createElement('br');
                    const docSmall = document.createElement('small');
                    docSmall.textContent = dept;
                    docDiv.appendChild(br3);
                    docDiv.appendChild(docSmall);
                }

                doctorTd.appendChild(docDiv);
                tr.appendChild(doctorTd);
            }

            const typeTd = document.createElement('td');
            typeTd.textContent = appt.appointment_type || 'N/A';
            tr.appendChild(typeTd);

            const statusTd = document.createElement('td');
            const badge = document.createElement('span');
            const status = appt.status || 'scheduled';
            badge.className = `badge ${getStatusBadgeClass(status)}`;
            badge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
            statusTd.appendChild(badge);
            tr.appendChild(statusTd);

            const actionsTd = document.createElement('td');
            const actionsDiv = document.createElement('div');
            actionsDiv.style.display = 'flex';
            actionsDiv.style.gap = '0.25rem';
            actionsDiv.style.flexWrap = 'wrap';

            // View Details
            const viewBtn = document.createElement('button');
            viewBtn.className = 'btn btn-primary';
            viewBtn.style.padding = '0.3rem 0.6rem';
            viewBtn.style.fontSize = '0.75rem';
            viewBtn.innerHTML = '<i class="fas fa-eye"></i> View';
            viewBtn.onclick = function() { viewAppointment(apptId); };
            actionsDiv.appendChild(viewBtn);

            const statusLower = (appt.status || 'scheduled').toLowerCase();

            // Complete (status) for admin/doctor when not already completed
            if ((userRole === 'admin' || userRole === 'doctor') && statusLower !== 'completed') {
                const completeBtn = document.createElement('button');
                completeBtn.className = 'btn btn-success';
                completeBtn.style.padding = '0.3rem 0.6rem';
                completeBtn.style.fontSize = '0.75rem';
                completeBtn.innerHTML = '<i class="fas fa-check"></i> Complete';
                completeBtn.onclick = function() { markCompleted(apptId); };
                actionsDiv.appendChild(completeBtn);
            }

            // Edit Details
            if (['admin', 'doctor', 'receptionist'].includes(userRole)) {
                const editBtn = document.createElement('button');
                editBtn.className = 'btn btn-warning';
                editBtn.style.padding = '0.3rem 0.6rem';
                editBtn.style.fontSize = '0.75rem';
                editBtn.innerHTML = '<i class="fas fa-edit"></i> Edit';
                editBtn.onclick = function() { editAppointment(apptId); };
                actionsDiv.appendChild(editBtn);
            }

            // Delete (admin only)
            if (userRole === 'admin') {
                const delBtn = document.createElement('button');
                delBtn.className = 'btn btn-danger';
                delBtn.style.padding = '0.3rem 0.6rem';
                delBtn.style.fontSize = '0.75rem';
                delBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
                delBtn.onclick = function() { deleteAppointment(apptId); };
                actionsDiv.appendChild(delBtn);
            }

            actionsTd.appendChild(actionsDiv);
            tr.appendChild(actionsTd);

            tbody.appendChild(tr);
        });
    }


    // Complete appointment (status update)
    function markCompleted(appointmentId) {
        if (!appointmentId) {
            alert('Missing appointment ID – cannot complete.');
            return;
        }

        if (!confirm('Mark this appointment as completed?')) {
            return;
        }

        const baseUrlMeta = document.querySelector('meta[name="base-url"]');
        let baseUrl = baseUrlMeta ? baseUrlMeta.content : '';
        // Remove trailing slash if present to avoid double slashes
        if (baseUrl.endsWith('/')) {
            baseUrl = baseUrl.slice(0, -1);
        }
        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfHashMeta = document.querySelector('meta[name="csrf-hash"]');
        const csrfToken = csrfTokenMeta ? csrfTokenMeta.content : '';
        const csrfHash = csrfHashMeta ? csrfHashMeta.content : '';

        const url = `${baseUrl}/appointments/${appointmentId}/status`;
        const body = new URLSearchParams();
        body.append('status', 'completed');
        // Add CSRF token to body (CodeIgniter expects it in the body or header)
        if (csrfToken && csrfHash) {
            body.append(csrfToken, csrfHash);
        }

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': csrfHash || csrfToken
            },
            body: body.toString()
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.message || `HTTP error! status: ${response.status}`);
                });
            }
            return response.json();
        })
        .then(result => {
            if (result.success || result.status === 'success') {
                showAppointmentsNotification(result.message || 'Appointment marked as completed.', 'success');
                setTimeout(function() {
                    location.reload();
                }, 600);
            } else {
                showAppointmentsNotification(result.message || 'Failed to update appointment status.', 'error');
            }
        })
        .catch(error => {
            console.error('Error updating appointment status:', error);
            showAppointmentsNotification('Failed to update appointment status: ' + (error.message || 'Please try again.'), 'error');
        });
    }


    // Delete appointment
    function deleteAppointment(appointmentId) {
        if (!appointmentId) {
            alert('Missing appointment ID – cannot delete.');
            return;
        }

        if (!confirm('Are you sure you want to delete this appointment? This action cannot be undone.')) {
            return;
        }

        const baseUrlMeta = document.querySelector('meta[name="base-url"]');
        const baseUrl = baseUrlMeta ? baseUrlMeta.content : '';
        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfTokenMeta ? csrfTokenMeta.content : '';

        const url = `${baseUrl}/appointments/${appointmentId}`;

        fetch(url, {
            method: 'DELETE',
            headers: {
                'X-CSRF-Token': csrfToken
            }
        })
        .then(response => response.json())
        .then(result => {
            if (result.success || result.status === 'success') {
                showAppointmentsNotification(result.message || 'Appointment deleted successfully.', 'success');
                setTimeout(function() {
                    location.reload();
                }, 600);
            } else {
                showAppointmentsNotification(result.message || 'Failed to delete appointment.', 'error');
            }
        })
        .catch(error => {
            console.error('Error deleting appointment:', error);
            showAppointmentsNotification('Failed to delete appointment. Please try again.', 'error');
        });
    }

    // Global functions for backward compatibility
    window.markCompleted = markCompleted;
    window.deleteAppointment = deleteAppointment;
    window.openBillingModal = openBillingModal;
