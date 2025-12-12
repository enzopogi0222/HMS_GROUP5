/**
 * Unified Patient Management Controller
 * Main controller for patient management functionality across all roles
 */

class PatientManager {
    constructor() {
        this.patients = [];
        this.filteredPatients = [];
        this.currentFilters = {
            status: '',
            type: '',
            search: ''
        };
        
        this.init();
    }

    /**
     * Initialize the patient manager
     */
    init() {
        this.bindEvents();
        this.loadPatients();
        
        // Initialize modals if they exist
        if (window.AddPatientModal) {
            window.AddPatientModal.init();
        }
        if (window.ViewPatientModal) {
            window.ViewPatientModal.init();
        }
        if (window.EditPatientModal) {
            window.EditPatientModal.init();
        }
        if (window.AssignDoctorModal) {
            window.AssignDoctorModal.init();
        }
    }

    /**
     * Bind event listeners
     */
    bindEvents() {
        // Add patient button
        const addPatientBtn = document.getElementById('addPatientBtn');
        if (addPatientBtn) {
            addPatientBtn.addEventListener('click', () => this.openAddPatientModal());
        }

        // Export button
        const exportBtn = document.getElementById('exportBtn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportPatients());
        }

        // Filter controls
        const statusFilter = document.getElementById('statusFilter');
        const typeFilter = document.getElementById('typeFilter');
        const searchFilter = document.getElementById('searchFilter');

        if (statusFilter) {
            statusFilter.addEventListener('change', (e) => {
                this.currentFilters.status = e.target.value;
                this.applyFilters();
            });
        }

        if (typeFilter) {
            typeFilter.addEventListener('change', (e) => {
                this.currentFilters.type = e.target.value;
                this.applyFilters();
            });
        }

        if (searchFilter) {
            const debouncedSearch = PatientUtils.debounce((e) => {
                this.currentFilters.search = e.target.value;
                this.applyFilters();
            }, 300);
            
            searchFilter.addEventListener('input', debouncedSearch);
        }

        // Table row clicks for actions
        document.addEventListener('click', (e) => {
            if (e.target.closest('.action-btn')) {
                const btn = e.target.closest('.action-btn');
                const action = btn.dataset.action;
                const patientId = btn.dataset.patientId;
                
                this.handleAction(action, patientId);
            }
        });
    }

    /**
     * Load patients from API
     */
    async loadPatients() {
        const tableBody = document.getElementById('patientsTableBody');
        
        try {
            PatientUtils.showLoading(tableBody, 'Loading patients...');
            
            const response = await PatientUtils.makeRequest(
                PatientConfig.getUrl(PatientConfig.endpoints.patientsApi)
            );
            
            if (response.status === 'success') {
                this.patients = response.data || [];
                this.filteredPatients = [...this.patients];
                this.renderPatientsTable();
            } else {
                throw new Error(response.message || 'Failed to load patients');
            }
        } catch (error) {
            console.error('Error loading patients:', error);
            PatientUtils.showError(tableBody, 'Failed to load patients. Please try again.');
            PatientUtils.showNotification('Failed to load patients: ' + error.message, 'error');
        }
    }

    /**
     * Apply filters to patient list
     */
    applyFilters() {
        this.filteredPatients = this.patients.filter(patient => {
            // Status filter
            if (this.currentFilters.status && patient.status !== this.currentFilters.status) {
                return false;
            }

            // Type filter
            if (this.currentFilters.type && patient.patient_type !== this.currentFilters.type) {
                return false;
            }

            // Search filter
            if (this.currentFilters.search) {
                const searchTerm = this.currentFilters.search.toLowerCase();
                const searchableText = [
                    patient.first_name,
                    patient.middle_name,
                    patient.last_name,
                    patient.patient_id,
                    patient.contact_no,
                    patient.email
                ].join(' ').toLowerCase();

                if (!searchableText.includes(searchTerm)) {
                    return false;
                }
            }

            return true;
        });

        this.renderPatientsTable();
    }

    /**
     * Render patients table
     */
    renderPatientsTable() {
        const tableBody = document.getElementById('patientsTableBody');
        
        if (this.filteredPatients.length === 0) {
            const colspan = ['admin', 'receptionist', 'it_staff'].includes(PatientConfig.userRole) ? '6' : '5';
            tableBody.innerHTML = `
                <tr>
                    <td colspan="${colspan}" style="text-align: center; padding: 2rem;">
                        <i class="fas fa-users" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;" aria-hidden="true"></i>
                        <p>No patients found.</p>
                        ${this.hasActiveFilters() ? `
                            <button onclick="clearFilters()" class="btn btn-secondary" aria-label="Clear Filters">
                                <i class="fas fa-times" aria-hidden="true"></i> Clear Filters
                            </button>
                        ` : ''}
                    </td>
                </tr>
            `;
            return;
        }

        const rows = this.filteredPatients.map(patient => this.createPatientRow(patient));
        tableBody.innerHTML = rows.join('');
    }

    /**
     * Create HTML for patient table row
     */
    createPatientRow(patient) {
        const fullName = PatientUtils.formatFullName(patient.first_name, patient.middle_name, patient.last_name);
        const statusBadge = PatientUtils.getStatusBadge(patient.status);
        const typeBadge = PatientUtils.getTypeBadge(patient.patient_type);
        
        // Show assigned doctor column for admin, receptionist, and IT staff
        const showDoctorColumn = ['admin', 'receptionist', 'it_staff'].includes(PatientConfig.userRole);
        const doctorColumn = showDoctorColumn ? `
            <td>${PatientUtils.escapeHtml(patient.assigned_doctor_name || 'N/A')}</td>
        ` : '';

        // Format registration date
        const registeredDate = patient.date_registered ? 
            new Date(patient.date_registered).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            }) : 'N/A';

        return `
            <tr class="patient-row">
                <td>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <div>
                            <div style="font-weight: 600;">
                                ${PatientUtils.escapeHtml(fullName)}
                            </div>
                            <div style="font-size: 0.8rem; color: #6b7280;">
                                ${PatientUtils.escapeHtml(patient.email || 'No email')}
                            </div>
                            <div style="font-size: 0.8rem; color: #6b7280;">
                                ID: ${PatientUtils.escapeHtml(patient.patient_id || 'N/A')}
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="role-badge role-${patient.patient_type ? patient.patient_type.toLowerCase().replace(' ', '-') : 'outpatient'}">
                        ${PatientUtils.escapeHtml(patient.patient_type || 'Outpatient')}
                    </span>
                </td>
                ${doctorColumn}
                <td>
                    <i class="fas fa-circle status-${patient.status ? patient.status.toLowerCase() : 'inactive'}" aria-hidden="true"></i> 
                    ${PatientUtils.escapeHtml(patient.status ? patient.status.charAt(0).toUpperCase() + patient.status.slice(1) : 'Inactive')}
                </td>
                <td>${registeredDate}</td>
                <td>
                    <div class="action-buttons">
                        ${this.canEdit() ? `
                            <button class="btn btn-warning btn-small action-btn" 
                                    data-action="edit" 
                                    data-patient-id="${patient.patient_id}"
                                    aria-label="Edit Patient ${PatientUtils.escapeHtml(fullName)}">
                                <i class="fas fa-edit" aria-hidden="true"></i> Edit
                            </button>
                        ` : ''}
                        <button class="btn btn-primary btn-small action-btn" 
                                data-action="view" 
                                data-patient-id="${patient.patient_id}"
                                aria-label="View Patient ${PatientUtils.escapeHtml(fullName)}">
                            <i class="fas fa-eye" aria-hidden="true"></i> View
                        </button>
                        ${this.canDelete() ? `
                            <button class="btn btn-danger btn-small action-btn" 
                                    data-action="delete" 
                                    data-patient-id="${patient.patient_id}"
                                    aria-label="Delete Patient ${PatientUtils.escapeHtml(fullName)}">
                                <i class="fas fa-trash" aria-hidden="true"></i> Delete
                            </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `;
    }

    /**
     * Handle action button clicks
     */
    handleAction(action, patientId) {
        switch (action) {
            case 'view':
                this.viewPatient(patientId);
                break;
            case 'edit':
                this.editPatient(patientId);
                break;
            case 'assign':
                this.assignDoctor(patientId);
                break;
            case 'delete':
                this.deletePatient(patientId);
                break;
        }
    }

    /**
     * Open add patient modal
     */
    openAddPatientModal() {
        if (window.AddPatientModal) {
            window.AddPatientModal.open();
        }
    }

    /**
     * View patient details
     */
    viewPatient(patientId) {
        if (window.ViewPatientModal) {
            window.ViewPatientModal.open(patientId);
        }
    }

    /**
     * Edit patient
     */
    editPatient(patientId) {
        if (window.EditPatientModal) {
            window.EditPatientModal.open(patientId);
        }
    }

    /**
     * Assign doctor to patient
     */
    assignDoctor(patientId) {
        if (window.AssignDoctorModal) {
            window.AssignDoctorModal.open(patientId);
        }
    }

    /**
     * Delete patient
     */
    async deletePatient(patientId) {
        if (!this.canDelete()) {
            PatientUtils.showNotification('You do not have permission to delete patients', 'error');
            return;
        }

        const patient = this.patients.find(p => p.patient_id == patientId);
        if (!patient) {
            PatientUtils.showNotification('Patient not found', 'error');
            return;
        }

        const fullName = PatientUtils.formatFullName(patient.first_name, patient.middle_name, patient.last_name);
        
        if (!confirm(`Are you sure you want to delete patient "${fullName}"? This action cannot be undone.`)) {
            return;
        }

        try {
            const response = await PatientUtils.makeRequest(
                PatientConfig.getUrl(`${PatientConfig.endpoints.patientDelete}/${patientId}`),
                { method: 'DELETE' }
            );

            if (response.status === 'success') {
                PatientUtils.showNotification('Patient deleted successfully', 'success');
                this.loadPatients(); // Reload the list
            } else {
                throw new Error(response.message || 'Failed to delete patient');
            }
        } catch (error) {
            console.error('Error deleting patient:', error);
            PatientUtils.showNotification('Failed to delete patient: ' + error.message, 'error');
        }
    }

    /**
     * Export patients data
     */
    exportPatients() {
        try {
            const csvData = this.generateCSV();
            const blob = new Blob([csvData], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            
            const a = document.createElement('a');
            a.href = url;
            a.download = `patients_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            PatientUtils.showNotification('Patients data exported successfully', 'success');
        } catch (error) {
            console.error('Export error:', error);
            PatientUtils.showNotification('Failed to export data', 'error');
        }
    }

    /**
     * Generate CSV data for export
     */
    generateCSV() {
        const headers = [
            'Patient ID', 'First Name', 'Middle Name', 'Last Name', 'Gender',
            'Date of Birth', 'Age', 'Civil Status', 'Phone', 'Email',
            'Address', 'Province', 'City', 'Barangay', 'ZIP Code',
            'Patient Type', 'Blood Group', 'Status', 'Insurance Provider',
            'Insurance Number', 'Emergency Contact', 'Emergency Phone',
            'Medical Notes', 'Date Registered'
        ];

        const rows = this.filteredPatients.map(patient => [
            patient.patient_id,
            patient.first_name || '',
            patient.middle_name || '',
            patient.last_name || '',
            patient.gender || '',
            patient.date_of_birth || '',
            PatientUtils.calculateAge(patient.date_of_birth),
            patient.civil_status || '',
            patient.contact_no || '',
            patient.email || '',
            patient.address || '',
            patient.province || '',
            patient.city || '',
            patient.barangay || '',
            patient.zip_code || '',
            patient.patient_type || '',
            patient.blood_group || '',
            patient.status || '',
            patient.insurance_provider || '',
            patient.insurance_number || '',
            patient.emergency_contact || '',
            patient.emergency_phone || '',
            patient.medical_notes || '',
            patient.date_registered || ''
        ]);

        const csvContent = [headers, ...rows]
            .map(row => row.map(field => `"${String(field).replace(/"/g, '""')}"`).join(','))
            .join('\n');

        return csvContent;
    }

    /**
     * Refresh patient list
     */
    refresh() {
        this.loadPatients();
    }

    /**
     * Check if there are active filters
     */
    hasActiveFilters() {
        return this.currentFilters.status || this.currentFilters.type || this.currentFilters.search;
    }

    /**
     * Permission checks
     */
    canEdit() {
        return ['admin', 'doctor', 'receptionist', 'it_staff'].includes(PatientConfig.userRole);
    }

    canDelete() {
        return ['admin', 'it_staff'].includes(PatientConfig.userRole);
    }

    canAssignDoctor() {
        return ['admin', 'receptionist', 'it_staff'].includes(PatientConfig.userRole);
    }
}

// Global functions for backward compatibility
window.clearFilters = function() {
    if (window.patientManager) {
        window.patientManager.currentFilters = { status: '', type: '', search: '' };
        
        // Reset filter controls
        const statusFilter = document.getElementById('statusFilter');
        const typeFilter = document.getElementById('typeFilter');
        const searchFilter = document.getElementById('searchFilter');
        
        if (statusFilter) statusFilter.value = '';
        if (typeFilter) typeFilter.value = '';
        if (searchFilter) searchFilter.value = '';
        
        window.patientManager.applyFilters();
    }
};

window.viewPatient = function(patientId) {
    if (window.patientManager) {
        window.patientManager.viewPatient(patientId);
    }
};

window.editPatient = function(patientId) {
    if (window.patientManager) {
        window.patientManager.editPatient(patientId);
    }
};

window.assignDoctor = function(patientId) {
    if (window.patientManager) {
        window.patientManager.assignDoctor(patientId);
    }
};

window.deletePatient = function(patientId) {
    if (window.patientManager) {
        window.patientManager.deletePatient(patientId);
    }
};

window.refreshPatients = function() {
    if (window.patientManager) {
        window.patientManager.refresh();
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.patientManager = new PatientManager();
});
