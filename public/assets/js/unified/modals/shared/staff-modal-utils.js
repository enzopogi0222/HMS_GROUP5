/**
 * Shared utilities for staff modals
 */
window.StaffModalUtils = {
    departmentSpecializations: {
        'Internal Medicine / General Medicine': ['General Internist', 'Endocrinology', 'Infectious Disease'],
        'Pediatrics': ['General Pediatrician', 'Neonatology'],
        'Surgery': ['General Surgery', 'Laparoscopic Surgery'],
        'Orthopedics': ['Trauma Orthopedics', 'Joint & Bone Specialist'],
        'Obstetrics & Gynecology (OB/GYN)': ['Obstetrics', 'Gynecology'],
        'Ophthalmology': ['General Ophthalmology', 'Cataract Specialist'],
        'ENT (Ear, Nose, Throat)': ['Otology', 'Rhinology', 'Laryngology'],
        'Cardiology': ['Interventional Cardiology', 'Non-Invasive Cardiology'],
        'Neurology': ['General Neurology', 'Stroke Specialist'],
        'Dermatology': ['Clinical Dermatology', 'Cosmetic Dermatology'],
        'Psychiatry / Mental Health': ['General Psychiatry', 'Child Psychiatry'],
        'Radiology / Imaging': ['Diagnostic Radiology', 'Interventional Radiology'],
        'Pathology / Laboratory': ['Clinical Pathology', 'Anatomic Pathology'],
        'Anesthesiology': ['General Anesthesia', 'Pain Management'],
        'Emergency / Accident & Trauma': ['Emergency Medicine', 'Trauma Care'],
        'Oncology': ['Medical Oncology', 'Radiation Oncology'],
        'Urology': ['General Urology', 'Endo-Urology'],
        'Gastroenterology': ['General Gastroenterology', 'Hepatology'],
        'Nephrology': ['General Nephrology', 'Dialysis Specialist'],
        'Pulmonology / Respiratory Medicine': ['General Pulmonology', 'Critical Care']
    },

    normalizeDepartmentKey(department) {
        return String(department || '').trim();
    },

    populateDoctorSpecializations(prefix = '', selectedDepartment = '', selectedValue = '') {
        const deptKey = StaffModalUtils.normalizeDepartmentKey(selectedDepartment);
        const selectEl = document.getElementById(`${prefix}doctor_specialization`);
        if (!selectEl) return;

        const currentValue = selectedValue !== undefined && selectedValue !== null
            ? String(selectedValue)
            : (selectEl.value || '');

        const specs = StaffModalUtils.departmentSpecializations[deptKey] || [];

        selectEl.innerHTML = '<option value="">Select specialization</option>';
        specs.forEach((spec) => {
            const opt = document.createElement('option');
            opt.value = spec;
            opt.textContent = spec;
            selectEl.appendChild(opt);
        });

        // Preserve current selection if it still exists; otherwise clear.
        if (currentValue && specs.includes(currentValue)) {
            selectEl.value = currentValue;
        } else {
            selectEl.value = '';
        }
    },

    /**
     * Toggle role-specific fields visibility
     */
    toggleRoleFields(prefix = '') {
        const designation = document.getElementById(`${prefix}designation`)?.value || '';
        const roles = ['doctor', 'nurse', 'accountant', 'laboratorist', 'pharmacist'];
        roles.forEach(role => {
            const field = document.getElementById(`${prefix}${role}Fields`);
            if (field) field.style.display = designation === role ? 'block' : 'none';
        });

        if (designation === 'doctor') {
            const deptEl = document.getElementById(`${prefix}department`);
            StaffModalUtils.populateDoctorSpecializations(prefix, deptEl?.value || '');
        }
    },

    /**
     * Validate DOB and age
     */
    validateDob(formData, errors, prefix = '') {
        const dobRaw = formData.date_of_birth || formData.dob || '';
        if (!dobRaw || String(dobRaw).trim().length === 0) {
            if (prefix === '') errors.date_of_birth = 'Date of birth is required.';
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

        const age = Math.abs(new Date(today.getTime() - dob.getTime()).getUTCFullYear() - 1970);
        if (age < 18) {
            errors.date_of_birth = 'Age not valid: staff must be at least 18 years old.';
        } else if (age > 100) {
            errors.date_of_birth = 'Age not valid: please check the date of birth.';
        }
    },

    /**
     * Display form errors
     */
    displayErrors(errors, prefix = '') {
        const fieldMap = {
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

        for (const [rawField, message] of Object.entries(errors)) {
            const field = fieldMap[rawField] || rawField;
            const errorElement = document.getElementById(`${prefix}err_${field}`);
            if (errorElement) {
                errorElement.textContent = Array.isArray(message) ? message[0] : message;
            }
        }
    },

    /**
     * Clear all form errors
     */
    clearErrors(form, prefix = '') {
        form?.querySelectorAll(`[id^="${prefix}err_"]`).forEach(el => el.textContent = '');
    },

    /**
     * Apply DOB age limit to input
     */
    applyDobAgeLimit(dobElement) {
        if (!dobElement) return;
        const today = new Date();
        today.setFullYear(today.getFullYear() - 18);
        dobElement.setAttribute('max', today.toISOString().split('T')[0]);
    },

    /**
     * Validate role-specific required fields
     */
    validateRoleFields(formData, errors) {
        const roleValidations = {
            doctor: { field: 'doctor_specialization', msg: 'Doctor specialization is required.' },
            nurse: { field: 'nurse_license_no', msg: 'Nurse license number is required.' },
            accountant: { field: 'accountant_license_no', msg: 'Accountant license number is required.' },
            laboratorist: { field: 'laboratorist_license_no', msg: 'Laboratorist license number is required.' },
            pharmacist: { field: 'pharmacist_license_no', msg: 'Pharmacist license number is required.' }
        };

        const validation = roleValidations[formData.designation];
        if (validation && (!formData[validation.field] || String(formData[validation.field]).trim().length < 2)) {
            errors[validation.field] = validation.msg;
        }
    },

    /**
     * Validate PH contact number: must start with 09 and be exactly 11 digits.
     */
    validateContactNo(formData, errors, prefix = '') {
        const raw = formData.contact_no ?? '';
        const contactNo = String(raw).trim();
        if (contactNo.length === 0) return; // permit empty
        if (!/^09\d{9}$/.test(contactNo)) {
            errors.contact_no = 'Contact number must start with 09 and be exactly 11 digits.';
        }
    },

    sanitizeContactNo(value) {
        return String(value ?? '').replace(/\D/g, '').slice(0, 11);
    },

    getContactNoInlineError(value) {
        const v = String(value ?? '').trim();
        if (v.length === 0) return '';

        if (!/^\d+$/.test(v)) return 'Contact number must contain digits only.';
        if (v.length === 1 && v !== '0') return 'Contact number must start with 09.';
        if (v.length >= 2 && v.slice(0, 2) !== '09') return 'Contact number must start with 09.';
        if (v.length < 11) return 'Contact number must be 11 digits.';
        if (!/^09\d{9}$/.test(v)) return 'Contact number must start with 09 and be exactly 11 digits.';

        return '';
    },

    bindLiveContactNoValidation(inputEl, errorEl) {
        if (!inputEl || !errorEl || inputEl.__boundContactNoValidation) return;
        inputEl.__boundContactNoValidation = true;

        const run = () => {
            const sanitized = StaffModalUtils.sanitizeContactNo(inputEl.value);
            if (sanitized !== inputEl.value) inputEl.value = sanitized;
            errorEl.textContent = StaffModalUtils.getContactNoInlineError(inputEl.value);
        };

        inputEl.addEventListener('input', run);
        inputEl.addEventListener('blur', run);
    },

    /**
     * Setup modal close handlers
     */
    setupModalCloseHandlers(modal, closeFn) {
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal && !modal.getAttribute('aria-hidden')) {
                closeFn();
            }
        });
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeFn();
            });
        }
    }
};

