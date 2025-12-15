/**
 * Staff Modal Mixin
 * Shared functionality for staff modals (add/edit)
 */
const StaffModalMixin = {
    /**
     * Toggle role-specific fields visibility
     */
    toggleRoleFields(prefix = '') {
        const designation = document.getElementById(`${prefix}designation`)?.value || '';
        const roleFields = {
            doctor: document.getElementById(`${prefix}doctorFields`),
            nurse: document.getElementById(`${prefix}nurseFields`),
            pharmacist: document.getElementById(`${prefix}pharmacistFields`),
            laboratorist: document.getElementById(`${prefix}laboratoristFields`),
            accountant: document.getElementById(`${prefix}accountantFields`),
        };

        // Hide all role fields
        Object.values(roleFields).forEach(field => {
            if (field) field.style.display = 'none';
        });

        // Show relevant role field
        if (designation && roleFields[designation]) {
            roleFields[designation].style.display = 'block';
        }
    },

    /**
     * Update employee ID based on role
     */
    async updateEmployeeIdForRole(prefix = '') {
        const designation = document.getElementById(`${prefix}designation`)?.value || '';
        const employeeIdInput = document.getElementById(`${prefix}employee_id`);
        
        if (!employeeIdInput || !designation) {
            if (employeeIdInput) employeeIdInput.value = '';
            return;
        }

        const originalPlaceholder = employeeIdInput.placeholder || '';
        employeeIdInput.placeholder = 'Generating...';

        try {
            const url = StaffConfig.getUrl('staff/next-employee-id') + '?role=' + encodeURIComponent(designation);
            const response = await StaffUtils.makeRequest(url);

            if (response.status === 'success' && response.employee_id) {
                employeeIdInput.value = response.employee_id;
            } else {
                employeeIdInput.value = '';
                employeeIdInput.placeholder = 'Unable to generate ID';
            }
        } catch (error) {
            console.error('Failed to generate employee ID:', error);
            employeeIdInput.value = '';
            employeeIdInput.placeholder = 'Unable to generate ID';
        } finally {
            if (!employeeIdInput.value) {
                employeeIdInput.placeholder = originalPlaceholder || 'e.g., DOC-0001';
            }
        }
    },

    /**
     * Apply DOB age limit
     */
    applyDobAgeLimit(dobElement) {
        if (!dobElement) return;
        const today = new Date();
        today.setFullYear(today.getFullYear() - 18);
        const maxDate = today.toISOString().split('T')[0];
        dobElement.setAttribute('max', maxDate);
    },

    /**
     * Validate DOB and age
     */
    validateDob(formData, errors) {
        const dobRaw = formData.date_of_birth || formData.dob || '';
        if (!dobRaw || String(dobRaw).trim().length === 0) {
            errors.date_of_birth = 'Date of birth is required.';
            return;
        }

        const dob = new Date(dobRaw);
        if (isNaN(dob.getTime())) {
            errors.date_of_birth = 'Please enter a valid date of birth.';
            return;
        }

        const today = new Date();
        today.setHours(0, 0, 0, 0);
        dob.setHours(0, 0, 0, 0);

        if (dob > today) {
            errors.date_of_birth = 'Date of birth cannot be in the future.';
            return;
        }

        const ageDiffMs = today.getTime() - dob.getTime();
        const ageDate = new Date(ageDiffMs);
        const age = Math.abs(ageDate.getUTCFullYear() - 1970);

        if (age < 18) {
            errors.date_of_birth = 'Age not valid: staff must be at least 18 years old.';
        } else if (age > 100) {
            errors.date_of_birth = 'Age not valid: please check the date of birth.';
        }
    },

    /**
     * Collect form data
     */
    collectFormData(form) {
        const formData = new FormData(form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        return data;
    },

    /**
     * Field name mapping for error display
     */
    getFieldNameMap() {
        return {
            dob: 'date_of_birth',
            doctor_specialization: 'doctor_specialization',
            doctor_license_no: 'doctor_license_no',
            doctor_consultation_fee: 'doctor_consultation_fee',
            nurse_license_no: 'nurse_license_no',
            nurse_specialization: 'nurse_specialization',
            accountant_license_no: 'accountant_license_no',
            laboratorist_license_no: 'laboratorist_license_no',
            laboratorist_specialization: 'laboratorist_specialization',
            lab_room_no: 'lab_room_no',
            pharmacist_license_no: 'pharmacist_license_no',
            pharmacist_specialization: 'pharmacist_specialization'
        };
    },

    /**
     * Validate role-specific fields
     */
    validateRoleFields(formData, errors) {
        const designation = formData.designation || formData.role || '';
        
        if (designation === 'doctor') {
            // specialization is optional
        }
        
        if (designation === 'nurse') {
            if (!formData.nurse_license_no || String(formData.nurse_license_no).trim().length < 2) {
                errors.nurse_license_no = 'Nurse license number is required.';
            }
        }
        
        if (designation === 'accountant') {
            if (!formData.accountant_license_no || String(formData.accountant_license_no).trim().length < 2) {
                errors.accountant_license_no = 'Accountant license number is required.';
            }
        }
        
        if (designation === 'laboratorist') {
            if (!formData.laboratorist_license_no || String(formData.laboratorist_license_no).trim().length < 2) {
                errors.laboratorist_license_no = 'Laboratorist license number is required.';
            }
        }
        
        if (designation === 'pharmacist') {
            if (!formData.pharmacist_license_no || String(formData.pharmacist_license_no).trim().length < 2) {
                errors.pharmacist_license_no = 'Pharmacist license number is required.';
            }
        }
    }
};

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = StaffModalMixin;
}

