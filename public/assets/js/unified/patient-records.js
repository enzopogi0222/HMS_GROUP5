(function() {
    'use strict';

    const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let currentPatientId = null;
    let patientRecordsData = null;

    // Initialize Vital Signs Button (defined early to avoid hoisting issues)
    function initializeVitalSignsButton() {
        // Use event delegation to handle dynamically created buttons
        // The function reference will be resolved when the event fires, not when the listener is set up
        document.addEventListener('click', function(e) {
            const button = e.target.closest('#addVitalSignsBtn');
            if (button) {
                e.preventDefault();
                e.stopPropagation();
                // Call the function when button is clicked (it will be defined by then)
                if (typeof openAddVitalSignsModal === 'function') {
                    openAddVitalSignsModal();
                } else if (typeof window.openAddVitalSignsModal === 'function') {
                    window.openAddVitalSignsModal();
                } else {
                    console.error('openAddVitalSignsModal function not found');
                }
            }
        });

        // Also attach directly if button exists (for immediate attachment)
        const button = document.getElementById('addVitalSignsBtn');
        if (button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                // Call the function when button is clicked (it will be defined by then)
                if (typeof openAddVitalSignsModal === 'function') {
                    openAddVitalSignsModal();
                } else if (typeof window.openAddVitalSignsModal === 'function') {
                    window.openAddVitalSignsModal();
                } else {
                    console.error('openAddVitalSignsModal function not found');
                }
            });
        }
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        initializePatientSearch();
        initializePatientSelection();
        initializeTabs();
        initializeVitalSignsButton();
    });

    // Patient Search
    function initializePatientSearch() {
        const searchInput = document.getElementById('patientSearch');
        if (!searchInput) return;

        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const patientItems = document.querySelectorAll('.patient-item');

            patientItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }

    // Patient Selection
    function initializePatientSelection() {
        const patientItems = document.querySelectorAll('.patient-item');
        
        patientItems.forEach(item => {
            item.addEventListener('click', function() {
                const patientId = this.dataset.patientId;
                if (!patientId) {
                    console.error('No patient ID found on clicked item');
                    return;
                }

                console.log('Loading records for patient ID:', patientId);

                // Update active state
                patientItems.forEach(i => i.classList.remove('active'));
                this.classList.add('active');

                // Load patient records
                loadPatientRecords(patientId);
            });
        });

        // Auto-select first patient if available
        if (patientItems.length > 0) {
            const firstItem = patientItems[0];
            const firstPatientId = firstItem.dataset.patientId;
            if (firstPatientId) {
                console.log('Auto-selecting first patient:', firstPatientId);
                firstItem.click();
            }
        }
    }

    // Load Patient Records
    async function loadPatientRecords(patientId) {
        if (!patientId) return;

        currentPatientId = patientId;
        const detailContainer = document.getElementById('patientRecordsDetail');
        const noSelection = document.getElementById('noPatientSelected');

        // Show loading state - create a loading overlay instead of replacing innerHTML
        if (detailContainer) {
            detailContainer.style.display = 'block';
            
            // Hide all content sections
            const contentSections = detailContainer.querySelectorAll('.record-section, .records-tabs, .tab-content');
            contentSections.forEach(section => {
                section.style.display = 'none';
            });
            
            // Create or show loading indicator
            let loadingDiv = detailContainer.querySelector('.loading-indicator');
            if (!loadingDiv) {
                loadingDiv = document.createElement('div');
                loadingDiv.className = 'loading-indicator';
                loadingDiv.innerHTML = '<div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Loading patient records...</p></div>';
                detailContainer.insertBefore(loadingDiv, detailContainer.firstChild);
            } else {
                loadingDiv.style.display = 'block';
            }
        }
        if (noSelection) {
            noSelection.style.display = 'none';
        }

        try {
            const response = await fetch(`${baseUrl}/patient-management/records/${patientId}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                const errorText = await response.text();
                console.error('API Error:', response.status, errorText);
                showError(`Failed to load patient records (${response.status}). Please check console for details.`);
                return;
            }

            const result = await response.json();
            console.log('Patient records response:', result);

            if (result.status === 'success' && result.records) {
                patientRecordsData = result.records;
                renderPatientRecords(result.records);
            } else {
                const errorMsg = result.message || 'Failed to load patient records';
                console.error('Error in response:', result);
                showError(errorMsg);
            }
        } catch (error) {
            console.error('Error loading patient records:', error);
            showError('Error loading patient records: ' + error.message);
        }
    }

    // Render Patient Records
    function renderPatientRecords(records) {
        console.log('Rendering patient records:', records);
        
        const detailContainer = document.getElementById('patientRecordsDetail');
        if (!detailContainer) {
            console.error('Patient records detail container not found');
            return;
        }

        // Hide loading indicator
        const loadingDiv = detailContainer.querySelector('.loading-indicator');
        if (loadingDiv) {
            loadingDiv.style.display = 'none';
        }
        
        // Show all content sections
        const contentSections = detailContainer.querySelectorAll('.record-section, .records-tabs, .tab-content');
        contentSections.forEach(section => {
            section.style.display = '';
        });

        const patient = records.patient || {};
        console.log('Patient data:', patient);

        // Store records data globally for use in other functions
        patientRecordsData = records;

        // Render patient info header
        renderPatientInfoHeader(patient);

        // Render all tab contents
        renderOverview(patient, records);
        renderAppointments(records.appointments || []);
        renderPrescriptions(records.prescriptions || []);
        renderLabTests(records.lab_orders || []);
        renderVisits(records.outpatient_visits || []);
        renderAdmissions(records.inpatient_admissions || []);
        renderFinancial(records.financial_records || {});
        renderVitalSigns(records.vital_signs || []);

        console.log('Patient records rendered successfully');
    }

    // Render Patient Info Header
    function renderPatientInfoHeader(patient) {
        const header = document.getElementById('patientInfoHeader');
        if (!header) {
            console.error('Patient info header element not found');
            return;
        }

        const age = patient.age || (patient.date_of_birth 
            ? Math.floor((new Date() - new Date(patient.date_of_birth)) / (365.25 * 24 * 60 * 60 * 1000))
            : 'N/A');
        
        const gender = patient.gender || patient.sex || 'N/A';
        const contact = patient.contact_no || patient.contact_number || 'N/A';
        // Determine patient type from admissions/visits if not in patient record
        const patientType = patient.patient_type || 
            (patientRecordsData?.inpatient_admissions?.length > 0 ? 'Inpatient' : 
             patientRecordsData?.outpatient_visits?.length > 0 ? 'Outpatient' : 'N/A');
        const status = patient.status || 'Active';

        header.innerHTML = `
            <div class="info-item">
                <div class="info-label">Patient ID</div>
                <div class="info-value">#${patient.patient_id || 'N/A'}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Full Name</div>
                <div class="info-value">${formatName(patient)}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Age / Gender</div>
                <div class="info-value">${age} years / ${gender}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Patient Type</div>
                <div class="info-value">
                    <span class="badge badge-info">${patientType}</span>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Status</div>
                <div class="info-value">
                    <span class="badge badge-${status === 'Active' ? 'success' : 'warning'}">${status}</span>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Blood Group</div>
                <div class="info-value">${patient.blood_group || 'N/A'}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Contact</div>
                <div class="info-value">${contact}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Email</div>
                <div class="info-value">${patient.email || 'N/A'}</div>
            </div>
        `;
    }

    // Render Overview
    function renderOverview(patient, records) {
        const container = document.getElementById('overviewContent');
        if (!container) return;

        const appointments = records.appointments || [];
        const prescriptions = records.prescriptions || [];
        const labOrders = records.lab_orders || [];
        const visits = records.outpatient_visits || [];
        const admissions = records.inpatient_admissions || [];

        container.innerHTML = `
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Total Appointments</div>
                    <div class="info-value">${appointments.length}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Total Prescriptions</div>
                    <div class="info-value">${prescriptions.length}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Lab Tests</div>
                    <div class="info-value">${labOrders.length}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Outpatient Visits</div>
                    <div class="info-value">${visits.length}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Inpatient Admissions</div>
                    <div class="info-value">${admissions.length}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Date Registered</div>
                    <div class="info-value">${formatDate(patient.date_registered) || 'N/A'}</div>
                </div>
            </div>
            <div class="record-card">
                <div class="record-card-header">
                    <div class="record-card-title">Address</div>
                </div>
                <div class="record-card-body">
                    ${formatAddress(patient)}
                </div>
            </div>
            ${patient.insurance_provider ? `
            <div class="record-card">
                <div class="record-card-header">
                    <div class="record-card-title">Insurance Information</div>
                </div>
                <div class="record-card-body">
                    <div><strong>Provider:</strong> ${patient.insurance_provider}</div>
                    ${patient.insurance_number ? `<div><strong>Policy Number:</strong> ${patient.insurance_number}</div>` : ''}
                </div>
            </div>
            ` : ''}
            ${patient.emergency_contact ? `
            <div class="record-card">
                <div class="record-card-header">
                    <div class="record-card-title">Emergency Contact</div>
                </div>
                <div class="record-card-body">
                    <div><strong>Name:</strong> ${patient.emergency_contact}</div>
                    ${patient.emergency_phone ? `<div><strong>Phone:</strong> ${patient.emergency_phone}</div>` : ''}
                </div>
            </div>
            ` : ''}
            ${patient.medical_notes ? `
            <div class="record-card">
                <div class="record-card-header">
                    <div class="record-card-title">Medical Notes</div>
                </div>
                <div class="record-card-body">${patient.medical_notes}</div>
            </div>
            ` : ''}
        `;
    }

    // Render Appointments
    function renderAppointments(appointments) {
        const container = document.getElementById('appointmentsContent');
        if (!container) return;

        if (appointments.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-calendar-times"></i><p>No appointments found</p></div>';
            return;
        }

        container.innerHTML = appointments.map(apt => `
            <div class="record-card">
                <div class="record-card-header">
                    <div class="record-card-title">Appointment #${apt.id || apt.appointment_id || 'N/A'}</div>
                    <div class="record-card-date">${formatDateTime(apt.appointment_date, apt.appointment_time)}</div>
                </div>
                <div class="record-card-body">
                    <div><strong>Doctor:</strong> ${apt.doctor_name || 'N/A'}</div>
                    <div><strong>Status:</strong> <span class="badge badge-${getStatusBadgeClass(apt.status)}">${apt.status || 'N/A'}</span></div>
                    ${apt.reason ? `<div><strong>Reason:</strong> ${apt.reason}</div>` : ''}
                    ${apt.notes ? `<div><strong>Notes:</strong> ${apt.notes}</div>` : ''}
                </div>
            </div>
        `).join('');
    }

    // Render Prescriptions
    function renderPrescriptions(prescriptions) {
        const container = document.getElementById('prescriptionsContent');
        if (!container) return;

        if (prescriptions.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-prescription-bottle-alt"></i><p>No prescriptions found</p></div>';
            return;
        }

        container.innerHTML = prescriptions.map(pres => {
            const items = pres.items || [];
            const medications = items.length > 0 
                ? items.map(item => `${item.medication_name || item.medication}`).join('<br>')
                : (pres.medication || 'N/A');

            return `
                <div class="record-card">
                    <div class="record-card-header">
                        <div class="record-card-title">Prescription #${pres.rx_number || pres.prescription_id || pres.id || 'N/A'}</div>
                        <div class="record-card-date">${formatDate(pres.created_at)}</div>
                    </div>
                    <div class="record-card-body">
                        <div><strong>Prescriber:</strong> ${pres.prescriber || 'N/A'}</div>
                        <div><strong>Medication(s):</strong><br>${medications}</div>
                        ${pres.frequency ? `<div><strong>Frequency:</strong> ${pres.frequency}</div>` : ''}
                        ${pres.days_supply || pres.duration ? `<div><strong>Duration:</strong> ${pres.days_supply || pres.duration} days</div>` : ''}
                        <div><strong>Status:</strong> <span class="badge badge-${getStatusBadgeClass(pres.status)}">${pres.status || 'N/A'}</span></div>
                        ${pres.notes ? `<div><strong>Notes:</strong> ${pres.notes}</div>` : ''}
                    </div>
                </div>
            `;
        }).join('');
    }

    // Render Lab Tests
    function renderLabTests(labOrders) {
        const container = document.getElementById('labTestsContent');
        if (!container) return;

        if (labOrders.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-vial"></i><p>No lab tests found</p></div>';
            return;
        }

        container.innerHTML = labOrders.map(order => `
            <div class="record-card">
                <div class="record-card-header">
                    <div class="record-card-title">${order.test_name || 'Lab Test'}</div>
                    <div class="record-card-date">${formatDate(order.ordered_at)}</div>
                </div>
                <div class="record-card-body">
                    ${order.test_code ? `<div><strong>Test Code:</strong> ${order.test_code}</div>` : ''}
                    <div><strong>Status:</strong> <span class="badge badge-${getStatusBadgeClass(order.status)}">${order.status || 'N/A'}</span></div>
                    ${order.priority ? `<div><strong>Priority:</strong> ${order.priority}</div>` : ''}
                    ${order.notes ? `<div><strong>Notes:</strong> ${order.notes}</div>` : ''}
                    ${order.results ? `<div><strong>Results:</strong> ${order.results}</div>` : ''}
                </div>
            </div>
        `).join('');
    }

    // Render Visits
    function renderVisits(visits) {
        const container = document.getElementById('visitsContent');
        if (!container) return;

        if (visits.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-stethoscope"></i><p>No outpatient visits found</p></div>';
            return;
        }

        container.innerHTML = visits.map(visit => `
            <div class="record-card">
                <div class="record-card-header">
                    <div class="record-card-title">Visit #${visit.visit_id || 'N/A'}</div>
                    <div class="record-card-date">${formatDate(visit.created_at || visit.appointment_datetime)}</div>
                </div>
                <div class="record-card-body">
                    ${visit.department ? `<div><strong>Department:</strong> ${visit.department}</div>` : ''}
                    ${visit.assigned_doctor ? `<div><strong>Doctor:</strong> ${visit.assigned_doctor}</div>` : ''}
                    ${visit.visit_type ? `<div><strong>Visit Type:</strong> ${visit.visit_type}</div>` : ''}
                    ${visit.chief_complaint ? `<div><strong>Chief Complaint:</strong> ${visit.chief_complaint}</div>` : ''}
                    ${visit.allergies ? `<div><strong>Allergies:</strong> ${visit.allergies}</div>` : ''}
                    ${visit.existing_conditions ? `<div><strong>Existing Conditions:</strong> ${visit.existing_conditions}</div>` : ''}
                    ${formatVitals(visit)}
                </div>
            </div>
        `).join('');
    }

    // Render Admissions
    function renderAdmissions(admissions) {
        const container = document.getElementById('admissionsContent');
        if (!container) return;

        if (admissions.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-bed"></i><p>No inpatient admissions found</p></div>';
            return;
        }

        container.innerHTML = admissions.map(adm => {
            const history = adm.medical_history || {};
            const assessment = adm.initial_assessment || {};
            const rooms = adm.room_assignments || [];

            return `
                <div class="record-card">
                    <div class="record-card-header">
                        <div class="record-card-title">Admission #${adm.admission_id || 'N/A'}</div>
                        <div class="record-card-date">${formatDate(adm.admission_datetime)}</div>
                    </div>
                    <div class="record-card-body">
                        ${adm.admission_type ? `<div><strong>Admission Type:</strong> ${adm.admission_type}</div>` : ''}
                        ${adm.admitting_diagnosis ? `<div><strong>Diagnosis:</strong> ${adm.admitting_diagnosis}</div>` : ''}
                        ${adm.admitting_doctor ? `<div><strong>Admitting Doctor:</strong> ${adm.admitting_doctor}</div>` : ''}
                        ${adm.department ? `<div><strong>Department:</strong> ${adm.department}</div>` : ''}
                        ${Object.keys(history).length > 0 ? `
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e0e0e0;">
                                <strong>Medical History:</strong>
                                ${history.allergies ? `<div>Allergies: ${history.allergies}</div>` : ''}
                                ${history.past_medical_history ? `<div>Past Medical: ${history.past_medical_history}</div>` : ''}
                                ${history.past_surgical_history ? `<div>Past Surgical: ${history.past_surgical_history}</div>` : ''}
                                ${history.family_history ? `<div>Family History: ${history.family_history}</div>` : ''}
                            </div>
                        ` : ''}
                        ${Object.keys(assessment).length > 0 ? `
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e0e0e0;">
                                <strong>Initial Assessment:</strong>
                                ${formatVitals(assessment)}
                            </div>
                        ` : ''}
                        ${rooms.length > 0 ? `
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e0e0e0;">
                                <strong>Room Assignments:</strong>
                                ${rooms.map(r => `Room ${r.room_number || 'N/A'}, Bed ${r.bed_number || 'N/A'}`).join('<br>')}
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        }).join('');
    }

    // Render Financial
    function renderFinancial(financial) {
        const container = document.getElementById('financialContent');
        if (!container) return;

        const invoices = financial.invoices || [];
        const payments = financial.payments || [];
        const claims = financial.insurance_claims || [];
        const transactions = financial.transactions || [];

        if (invoices.length === 0 && payments.length === 0 && claims.length === 0 && transactions.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-dollar-sign"></i><p>No financial records found</p></div>';
            return;
        }

        let html = '';

        if (invoices.length > 0) {
            html += '<h4 style="margin-top: 20px;">Invoices</h4>';
            html += invoices.map(inv => `
                <div class="record-card">
                    <div class="record-card-header">
                        <div class="record-card-title">Invoice #${inv.invoice_id || inv.id || 'N/A'}</div>
                        <div class="record-card-date">${formatDate(inv.created_at || inv.invoice_date)}</div>
                    </div>
                    <div class="record-card-body">
                        <div><strong>Amount:</strong> ${formatCurrency(inv.total_amount || inv.amount || 0)}</div>
                        <div><strong>Status:</strong> <span class="badge badge-${getStatusBadgeClass(inv.status)}">${inv.status || 'N/A'}</span></div>
                    </div>
                </div>
            `).join('');
        }

        if (payments.length > 0) {
            html += '<h4 style="margin-top: 20px;">Payments</h4>';
            html += payments.map(pay => `
                <div class="record-card">
                    <div class="record-card-header">
                        <div class="record-card-title">Payment #${pay.payment_id || pay.id || 'N/A'}</div>
                        <div class="record-card-date">${formatDate(pay.payment_date || pay.created_at)}</div>
                    </div>
                    <div class="record-card-body">
                        <div><strong>Amount:</strong> ${formatCurrency(pay.amount || 0)}</div>
                        <div><strong>Method:</strong> ${pay.payment_method || 'N/A'}</div>
                        <div><strong>Status:</strong> <span class="badge badge-${getStatusBadgeClass(pay.status)}">${pay.status || 'N/A'}</span></div>
                    </div>
                </div>
            `).join('');
        }

        if (claims.length > 0) {
            html += '<h4 style="margin-top: 20px;">Insurance Claims</h4>';
            html += claims.map(claim => `
                <div class="record-card">
                    <div class="record-card-header">
                        <div class="record-card-title">Claim #${claim.ref_no || claim.id || 'N/A'}</div>
                        <div class="record-card-date">${formatDate(claim.created_at)}</div>
                    </div>
                    <div class="record-card-body">
                        <div><strong>Amount:</strong> ${formatCurrency(claim.claim_amount || 0)}</div>
                        <div><strong>Status:</strong> <span class="badge badge-${getStatusBadgeClass(claim.status)}">${claim.status || 'N/A'}</span></div>
                        ${claim.notes ? `<div><strong>Notes:</strong> ${claim.notes}</div>` : ''}
                    </div>
                </div>
            `).join('');
        }

        container.innerHTML = html;
    }

    // Render Vital Signs
    function renderVitalSigns(vitals) {
        const container = document.getElementById('vitalsContent');
        if (!container) return;

        if (vitals.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-heartbeat"></i><p>No vital signs recorded</p></div>';
            return;
        }

        container.innerHTML = vitals.map(vital => `
            <div class="record-card">
                <div class="record-card-header">
                    <div class="record-card-title">Vital Signs Record</div>
                    <div class="record-card-date">${formatDateTime(vital.recorded_at)}</div>
                </div>
                <div class="record-card-body">
                    ${formatVitals(vital)}
                    ${vital.notes ? `<div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #e5e7eb;"><strong>Notes:</strong> ${escapeHtml(vital.notes)}</div>` : ''}
                </div>
            </div>
        `).join('');
    }

    // Initialize Tabs
    function initializeTabs() {
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');

        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const tabName = this.dataset.tab;

                // Update button states
                tabButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');

                // Update content visibility
                tabContents.forEach(content => content.classList.remove('active'));
                const targetContent = document.getElementById(`tab${tabName.charAt(0).toUpperCase() + tabName.slice(1).replace(/-([a-z])/g, (g) => g[1].toUpperCase())}`);
                if (targetContent) {
                    targetContent.classList.add('active');
                }
            });
        });
    }

    // Helper Functions
    function formatName(patient) {
        const parts = [
            patient.first_name,
            patient.middle_name,
            patient.last_name
        ].filter(Boolean);
        return parts.join(' ') || 'N/A';
    }

    function formatAddress(patient) {
        const parts = [
            patient.address,
            patient.barangay,
            patient.city,
            patient.province,
            patient.zip_code
        ].filter(Boolean);
        return parts.join(', ') || 'N/A';
    }

    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        } catch (e) {
            return dateString;
        }
    }

    function formatDateTime(dateString, timeString = null) {
        if (!dateString) return 'N/A';
        try {
            const date = new Date(dateString);
            let formatted = date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
            if (timeString) {
                formatted += ' ' + timeString;
            } else {
                formatted += ' ' + date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            }
            return formatted;
        } catch (e) {
            return dateString;
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount || 0);
    }

    function formatVitals(data) {
        const vitals = [];
        
        // Blood Pressure
        if (data.blood_pressure_systolic && data.blood_pressure_diastolic) {
            vitals.push(`<strong>BP:</strong> ${data.blood_pressure_systolic}/${data.blood_pressure_diastolic} mmHg`);
        } else if (data.blood_pressure || data.bp) {
            vitals.push(`<strong>BP:</strong> ${data.blood_pressure || data.bp}`);
        }
        
        // Pulse Rate / Heart Rate
        if (data.pulse_rate) {
            vitals.push(`<strong>HR:</strong> ${data.pulse_rate} bpm`);
        } else if (data.heart_rate || data.hr) {
            vitals.push(`<strong>HR:</strong> ${data.heart_rate || data.hr} bpm`);
        }
        
        // Respiratory Rate
        if (data.respiratory_rate || data.rr) {
            vitals.push(`<strong>RR:</strong> ${data.respiratory_rate || data.rr} /min`);
        }
        
        // Temperature
        if (data.temperature || data.temp) {
            vitals.push(`<strong>Temp:</strong> ${data.temperature || data.temp}°C`);
        }
        
        // Oxygen Saturation
        if (data.oxygen_saturation) {
            vitals.push(`<strong>SpO2:</strong> ${data.oxygen_saturation}%`);
        } else if (data.spo2) {
            vitals.push(`<strong>SpO2:</strong> ${data.spo2}%`);
        }
        
        // Weight
        if (data.weight) {
            vitals.push(`<strong>Weight:</strong> ${data.weight} kg`);
        }
        
        // Height
        if (data.height) {
            vitals.push(`<strong>Height:</strong> ${data.height} cm`);
        }
        
        // BMI
        if (data.bmi) {
            vitals.push(`<strong>BMI:</strong> ${data.bmi}`);
        }
        
        return vitals.length > 0 ? vitals.join(' | ') : 'N/A';
    }

    function getStatusBadgeClass(status) {
        if (!status) return 'info';
        const s = status.toLowerCase();
        if (s === 'active' || s === 'completed' || s === 'paid' || s === 'approved') return 'success';
        if (s === 'pending' || s === 'scheduled') return 'warning';
        if (s === 'cancelled' || s === 'rejected' || s === 'failed') return 'danger';
        return 'info';
    }

    function showError(message) {
        const container = document.getElementById('patientRecordsDetail');
        if (container) {
            // Hide loading indicator
            const loadingDiv = container.querySelector('.loading-indicator');
            if (loadingDiv) {
                loadingDiv.style.display = 'none';
            }
            
            // Hide all content sections
            const contentSections = container.querySelectorAll('.record-section, .records-tabs, .tab-content');
            contentSections.forEach(section => {
                section.style.display = 'none';
            });
            
            // Show error message
            let errorDiv = container.querySelector('.error-indicator');
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.className = 'error-indicator';
                container.insertBefore(errorDiv, container.firstChild);
            }
            errorDiv.innerHTML = `<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>${message}</p></div>`;
            errorDiv.style.display = 'block';
        }
    }

    // Vital Signs Modal Functions
    function openAddVitalSignsModal() {
        if (!currentPatientId) {
            alert('Please select a patient first');
            return;
        }

        const modal = document.getElementById('addVitalSignsModal');
        const patientIdInput = document.getElementById('vitalSignsPatientId');
        
        if (!modal) {
            console.error('Vital signs modal not found');
            alert('Modal not found. Please refresh the page.');
            return;
        }

        if (!patientIdInput) {
            console.error('Patient ID input not found in modal');
            return;
        }

        patientIdInput.value = currentPatientId;
        modal.classList.add('active');
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    // Expose to window for inline onclick handlers (backup)
    window.openAddVitalSignsModal = openAddVitalSignsModal;

    function closeAddVitalSignsModal() {
        const modal = document.getElementById('addVitalSignsModal');
        if (modal) {
            modal.classList.remove('active');
            modal.hidden = true;
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            // Reset form
            const form = document.getElementById('addVitalSignsForm');
            if (form) {
                form.reset();
                // Reset recorded_at to current datetime
                const recordedAtInput = document.getElementById('vital_recorded_at');
                if (recordedAtInput) {
                    recordedAtInput.value = new Date().toISOString().slice(0, 16);
                }
            }
        }
    }

    // Expose to window for inline onclick handlers (backup)
    window.closeAddVitalSignsModal = closeAddVitalSignsModal;

    // Handle vital signs form submission
    document.addEventListener('DOMContentLoaded', function() {
        const vitalSignsForm = document.getElementById('addVitalSignsForm');
        if (vitalSignsForm) {
            vitalSignsForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                const submitBtn = document.getElementById('saveVitalSignsBtn');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Recording...';

                try {
                    const formData = new FormData(this);
                    const data = {};
                    
                    // Convert FormData to object, only including non-empty values
                    for (let [key, value] of formData.entries()) {
                        if (value && value.trim() !== '') {
                            if (key === 'temperature' || key === 'oxygen_saturation' || key === 'weight' || key === 'height') {
                                data[key] = parseFloat(value);
                            } else if (key === 'blood_pressure_systolic' || key === 'blood_pressure_diastolic' || 
                                      key === 'pulse_rate' || key === 'respiratory_rate') {
                                data[key] = parseInt(value);
                            } else {
                                data[key] = value;
                            }
                        }
                    }

                    // Convert datetime-local to proper format
                    if (data.recorded_at) {
                        const date = new Date(data.recorded_at);
                        data.recorded_at = date.toISOString().slice(0, 19).replace('T', ' ');
                    }

                    const patientId = data.patient_id || currentPatientId;
                    if (!patientId) {
                        throw new Error('Patient ID is required');
                    }

                    const response = await fetch(`${baseUrl}/patients/${patientId}/vital-signs`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();

                    if (result.status === 'success') {
                        // Close modal
                        closeAddVitalSignsModal();
                        
                        // Reload patient records to show new vital signs
                        if (currentPatientId) {
                            await loadPatientRecords(currentPatientId);
                            
                            // Switch to vital signs tab
                            const vitalsTab = document.querySelector('.tab-button[data-tab="vitals"]');
                            if (vitalsTab) {
                                vitalsTab.click();
                            }
                        }

                        // Show success message
                        showNotification('Vital signs recorded successfully', 'success');
                    } else {
                        throw new Error(result.message || 'Failed to record vital signs');
                    }
                } catch (error) {
                    console.error('Error recording vital signs:', error);
                    showNotification(error.message || 'Failed to record vital signs. Please try again.', 'error');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            });
        }

        // Close modal on overlay click
        const modal = document.getElementById('addVitalSignsModal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeAddVitalSignsModal();
                }
            });
        }
    });

    // Helper function to show notifications
    function showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 10000;
            animation: slideIn 0.3s ease-out;
        `;
        notification.textContent = message;

        document.body.appendChild(notification);

        // Remove after 3 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
})();

