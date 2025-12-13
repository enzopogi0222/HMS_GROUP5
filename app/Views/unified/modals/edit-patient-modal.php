<!-- Edit Patient Modal -->
<div id="editPatientModal" class="hms-modal-overlay" hidden>
    <div class="hms-modal" role="dialog" aria-modal="true" aria-labelledby="editPatientTitle" style="max-width: 900px;">
        <div class="hms-modal-header">
            <div class="hms-modal-title" id="editPatientTitle">
                <i class="fas fa-user-edit" style="color:#4f46e5"></i>
                Edit Patient
            </div>
            <button type="button" class="btn btn-secondary btn-small" onclick="closeEditPatientModal()" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="hms-modal-body">
            <form id="editPatientForm" class="patient-form" data-form-type="inpatient" novalidate enctype="multipart/form-data">
                <input type="hidden" id="edit_inpatient_patient_id" name="patient_id">
                <input type="hidden" name="patient_type" value="Inpatient">
                <input type="hidden" name="country" value="Philippines">
                <input type="hidden" name="region" value="Region XII - SOCCSKSARGEN">

                <!-- Basic Information -->
                <div class="form-section" style="margin-bottom: 1.5rem;">
                    <h4 style="margin-bottom: 1rem; color: #1e293b; font-size: 1.1rem;">Basic Information</h4>
                    <div class="form-grid">
                        <div>
                            <label class="form-label" for="edit_inpatient_patient_identifier">Patient ID</label>
                            <input type="text" id="edit_inpatient_patient_identifier" class="form-input" readonly>
                        </div>
                        <div>
                            <label class="form-label" for="edit_inpatient_last_name">Last Name*</label>
                            <input type="text" id="edit_inpatient_last_name" name="last_name" class="form-input" required>
                            <small id="err_edit_inpatient_last_name" class="form-error"></small>
                        </div>
                        <div>
                            <label class="form-label" for="edit_inpatient_first_name">First Name*</label>
                            <input type="text" id="edit_inpatient_first_name" name="first_name" class="form-input" required>
                            <small id="err_edit_inpatient_first_name" class="form-error"></small>
                        </div>
                        <div>
                            <label class="form-label" for="edit_inpatient_middle_name">Middle Name</label>
                            <input type="text" id="edit_inpatient_middle_name" name="middle_name" class="form-input">
                        </div>
                        <div>
                            <label class="form-label" for="edit_date_of_birth">Date of Birth*</label>
                            <input type="date" id="edit_date_of_birth" name="date_of_birth" class="form-input" required>
                            <small id="err_edit_date_of_birth" class="form-error"></small>
                        </div>
                        <div>
                            <label class="form-label" for="edit_inpatient_age_display">Age</label>
                            <input type="text" id="edit_inpatient_age_display" class="form-input" readonly placeholder="Auto-calculated">
                        </div>
                        <div>
                            <label class="form-label" for="edit_inpatient_gender">Sex*</label>
                            <select id="edit_inpatient_gender" name="gender" class="form-select" required>
                                <option value="">Select...</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                            <small id="err_edit_inpatient_gender" class="form-error"></small>
                        </div>
                        <div>
                            <label class="form-label" for="edit_inpatient_civil_status">Civil Status*</label>
                            <select id="edit_inpatient_civil_status" name="civil_status" class="form-select" required>
                                <option value="">Select...</option>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Widowed">Widowed</option>
                            </select>
                            <small id="err_edit_inpatient_civil_status" class="form-error"></small>
                        </div>
                        <div>
                            <label class="form-label" for="edit_inpatient_phone">Contact Number*</label>
                            <input type="text" id="edit_inpatient_phone" name="phone" class="form-input" required>
                            <small id="err_edit_inpatient_phone" class="form-error"></small>
                        </div>
                        <div>
                            <label class="form-label" for="edit_inpatient_email">Email Address</label>
                            <input type="email" id="edit_inpatient_email" name="email" class="form-input">
                            <small id="err_edit_inpatient_email" class="form-error"></small>
                        </div>
                    </div>
                </div>

                <!-- Address -->
                <div class="form-section" style="margin-bottom: 1.5rem;">
                    <h4 style="margin-bottom: 1rem; color: #1e293b; font-size: 1.1rem;">Address</h4>
                    <div class="form-grid">
                        <div>
                            <label class="form-label" for="edit_inpatient_province">Province*</label>
                            <select id="edit_inpatient_province" name="province" class="form-select" required>
                                <option value="">Select province...</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="edit_inpatient_city">City / Municipality*</label>
                            <select id="edit_inpatient_city" name="city" class="form-select" required>
                                <option value="">Select city...</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="edit_inpatient_barangay">Barangay*</label>
                            <select id="edit_inpatient_barangay" name="barangay" class="form-select" required>
                                <option value="">Select barangay...</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label" for="edit_inpatient_house_number">House / Lot / Block / Unit No.*</label>
                            <input type="text" id="edit_inpatient_house_number" name="house_number" class="form-input" required>
                        </div>
                        <div>
                            <label class="form-label" for="edit_inpatient_subdivision">Subdivision / Village</label>
                            <input type="text" id="edit_inpatient_subdivision" name="subdivision" class="form-input">
                        </div>
                        <div>
                            <label class="form-label" for="edit_inpatient_zip_code">ZIP Code</label>
                            <input type="text" id="edit_inpatient_zip_code" name="zip_code" class="form-input">
                        </div>
                    </div>
                </div>

                <!-- Emergency Contact -->
                <div class="form-section" style="margin-bottom: 1.5rem;">
                    <h4 style="margin-bottom: 1rem; color: #1e293b; font-size: 1.1rem;">Emergency Contact</h4>
                    <div class="form-grid">
                        <div>
                            <label class="form-label" for="edit_guardian_name">Name*</label>
                            <input type="text" id="edit_guardian_name" name="guardian_name" class="form-input" required>
                            <small id="err_edit_guardian_name" class="form-error"></small>
                        </div>
                        <div>
                            <label class="form-label" for="edit_guardian_relationship">Relationship*</label>
                            <select id="edit_guardian_relationship" name="guardian_relationship" class="form-select" required>
                                <option value="">Select...</option>
                                <option value="Parent">Parent</option>
                                <option value="Child">Child</option>
                                <option value="Sibling">Sibling</option>
                                <option value="Spouse">Spouse</option>
                                <option value="Guardian">Guardian</option>
                                <option value="Relative">Relative</option>
                                <option value="Other">Other</option>
                            </select>
                            <small id="err_edit_guardian_relationship" class="form-error"></small>
                        </div>
                        <div>
                            <label class="form-label" for="edit_guardian_contact">Contact Number*</label>
                            <input type="text" id="edit_guardian_contact" name="guardian_contact" class="form-input" required>
                            <small id="err_edit_guardian_contact" class="form-error"></small>
                        </div>
                        <div>
                            <label class="form-label" for="edit_secondary_contact">Secondary Contact</label>
                            <input type="text" id="edit_secondary_contact" name="secondary_contact" class="form-input">
                        </div>
                        <div>
                            <label class="form-label" for="edit_guardian_relationship_other">If Other, specify</label>
                            <input type="text" id="edit_guardian_relationship_other" name="guardian_relationship_other" class="form-input" hidden>
                        </div>
                    </div>
                </div>

                <!-- Admission Details -->
                <div class="form-section" style="margin-bottom: 1.5rem;">
                    <h4 style="margin-bottom: 1rem; color: #1e293b; font-size: 1.1rem;">Admission Details</h4>
                    <div class="form-grid">
                        <div>
                            <label class="form-label" for="edit_admission_number">Admission Number</label>
                            <input type="text" id="edit_admission_number" class="form-input" readonly>
                        </div>
                        <div>
                            <label class="form-label" for="edit_admission_datetime">Date & Time*</label>
                            <input type="datetime-local" id="edit_admission_datetime" name="admission_datetime" class="form-input" required>
                            <small id="err_edit_admission_datetime" class="form-error"></small>
                        </div>
                        <div>
                            <label class="form-label" for="edit_admission_type">Admission Type*</label>
                            <select id="edit_admission_type" name="admission_type" class="form-select" required>
                                <option value="">Select...</option>
                                <option value="ER">ER Admission</option>
                                <option value="Scheduled">Scheduled</option>
                                <option value="Transfer">Transfer</option>
                            </select>
                            <small id="err_edit_admission_type" class="form-error"></small>
                        </div>
                        <div>
                            <label class="form-label" for="edit_admitting_doctor">Admitting Doctor*</label>
                            <select id="edit_admitting_doctor" name="admitting_doctor" class="form-select" required>
                                <option value="">Select doctor...</option>
                                <?php if (!empty($availableDoctors)): ?>
                                    <?php foreach ($availableDoctors as $d): ?>
                                        <option value="<?= esc($d['staff_id'] ?? $d['id']) ?>" 
                                                data-specialization="<?= esc($d['specialization'] ?? '') ?>"
                                                data-doctor-name="<?= esc(trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? ''))) ?>">
                                            <?= esc(trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? ''))) ?>
                                            <?php if (!empty($d['specialization'])): ?>
                                                - <?= esc($d['specialization']) ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <small id="err_edit_admitting_doctor" class="form-error"></small>
                        </div>
                        <div>
                            <label class="form-label" for="edit_consent_uploaded">Consent Form</label>
                            <select id="edit_consent_uploaded" name="consent_uploaded" class="form-select">
                                <option value="">Select...</option>
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <div class="full">
                            <label class="form-label" for="edit_admitting_diagnosis">Admitting Diagnosis*</label>
                            <textarea id="edit_admitting_diagnosis" name="admitting_diagnosis" class="form-input" rows="2" required></textarea>
                            <small id="err_edit_admitting_diagnosis" class="form-error"></small>
                        </div>
                    </div>
                </div>

                <!-- Collapsible Sections -->
                <details class="form-section" style="margin-bottom: 1rem;">
                    <summary style="cursor: pointer; padding: 0.75rem; background: #f8f9fa; border-radius: 6px; font-weight: 500; color: #1e293b;">
                        <i class="fas fa-chevron-down" style="margin-right: 0.5rem;"></i> Medical History & Assessment
                    </summary>
                    <div style="padding: 1rem 0;">
                        <!-- Medical History -->
                        <div style="margin-bottom: 1.5rem;">
                            <h5 style="margin-bottom: 0.75rem; color: #475569; font-size: 0.95rem;">Medical History</h5>
                            <div class="form-grid">
                                <div class="full">
                                    <label class="form-label" for="edit_history_allergies">Allergies</label>
                                    <textarea id="edit_history_allergies" name="history_allergies" class="form-input" rows="2"></textarea>
                                </div>
                                <div class="full">
                                    <label class="form-label" for="edit_past_medical_history">Past Medical History</label>
                                    <textarea id="edit_past_medical_history" name="past_medical_history" class="form-input" rows="2"></textarea>
                                </div>
                                <div class="full">
                                    <label class="form-label" for="edit_past_surgical_history">Past Surgical History</label>
                                    <textarea id="edit_past_surgical_history" name="past_surgical_history" class="form-input" rows="2"></textarea>
                                </div>
                                <div class="full">
                                    <label class="form-label" for="edit_family_history">Family History</label>
                                    <textarea id="edit_family_history" name="family_history" class="form-input" rows="2"></textarea>
                                </div>
                                <div class="full">
                                    <label class="form-label" for="edit_history_current_medications">Current Medications</label>
                                    <textarea id="edit_history_current_medications" name="history_current_medications" class="form-input" rows="2"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Initial Assessment -->
                        <div>
                            <h5 style="margin-bottom: 0.75rem; color: #475569; font-size: 0.95rem;">Initial Assessment</h5>
                            <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.75rem; margin-bottom: 1rem;">
                                <div>
                                    <label class="form-label" for="edit_assessment_bp">Blood Pressure</label>
                                    <input type="text" id="edit_assessment_bp" name="assessment_bp" class="form-input">
                                </div>
                                <div>
                                    <label class="form-label" for="edit_assessment_hr">Heart Rate</label>
                                    <input type="text" id="edit_assessment_hr" name="assessment_hr" class="form-input">
                                </div>
                                <div>
                                    <label class="form-label" for="edit_assessment_rr">Respiratory Rate</label>
                                    <input type="text" id="edit_assessment_rr" name="assessment_rr" class="form-input">
                                </div>
                                <div>
                                    <label class="form-label" for="edit_assessment_temp">Temperature</label>
                                    <input type="text" id="edit_assessment_temp" name="assessment_temp" class="form-input">
                                </div>
                                <div>
                                    <label class="form-label" for="edit_assessment_spo2">SpO2</label>
                                    <input type="text" id="edit_assessment_spo2" name="assessment_spo2" class="form-input">
                                </div>
                                <div>
                                    <label class="form-label" for="edit_assessment_height_cm">Height (cm)</label>
                                    <input type="number" step="0.1" id="edit_assessment_height_cm" name="assessment_height_cm" class="form-input">
                                </div>
                                <div>
                                    <label class="form-label" for="edit_assessment_weight_kg">Weight (kg)</label>
                                    <input type="number" step="0.1" id="edit_assessment_weight_kg" name="assessment_weight_kg" class="form-input">
                                </div>
                            </div>
                            <div class="form-grid">
                                <div>
                                    <label class="form-label" for="edit_loc">Level of Consciousness*</label>
                                    <select id="edit_loc" name="level_of_consciousness" class="form-select" required>
                                        <option value="">Select...</option>
                                        <option value="Alert">Alert</option>
                                        <option value="Semi-conscious">Semi-conscious</option>
                                        <option value="Unconscious">Unconscious</option>
                                    </select>
                                    <small id="err_edit_level_of_consciousness" class="form-error"></small>
                                </div>
                                <div>
                                    <label class="form-label" for="edit_pain_level">Pain Level (0-10)</label>
                                    <input type="number" min="0" max="10" id="edit_pain_level" name="pain_level" class="form-input">
                                </div>
                                <div>
                                    <label class="form-label" for="edit_mode_of_arrival">Mode of Arrival</label>
                                    <select id="edit_mode_of_arrival" name="mode_of_arrival" class="form-select">
                                        <option value="">Select...</option>
                                        <option value="Walk-in">Walk-in</option>
                                        <option value="Wheelchair">Wheelchair</option>
                                        <option value="Stretcher">Stretcher</option>
                                        <option value="Ambulance">Ambulance</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label" for="edit_skin_condition">Skin Condition</label>
                                    <input type="text" id="edit_skin_condition" name="skin_condition" class="form-input">
                                </div>
                                <div class="full">
                                    <label class="form-label" for="edit_initial_findings">Initial Findings</label>
                                    <textarea id="edit_initial_findings" name="initial_findings" class="form-input" rows="2"></textarea>
                                </div>
                                <div class="full">
                                    <label class="form-label" for="edit_assessment_remarks">Remarks</label>
                                    <textarea id="edit_assessment_remarks" name="assessment_remarks" class="form-input" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </details>

                <details class="form-section" style="margin-bottom: 1rem;">
                    <summary style="cursor: pointer; padding: 0.75rem; background: #f8f9fa; border-radius: 6px; font-weight: 500; color: #1e293b;">
                        <i class="fas fa-chevron-down" style="margin-right: 0.5rem;"></i> HMO / Insurance (Optional)
                    </summary>
                    <div style="padding: 1rem 0;">
                        <div class="form-grid">
                            <div>
                                <label class="form-label" for="edit_inpatient_insurance_provider">Provider</label>
                                <select id="edit_inpatient_insurance_provider" name="insurance_provider" class="form-select">
                                    <option value="">Select provider...</option>
                                    <option value="Maxicare">Maxicare</option>
                                    <option value="Intellicare">Intellicare</option>
                                    <option value="Medicard">Medicard</option>
                                    <option value="PhilCare">PhilCare</option>
                                    <option value="Avega">Avega</option>
                                    <option value="Generali Philippines">Generali Philippines</option>
                                    <option value="Insular Health Care">Insular Health Care</option>
                                    <option value="EastWest Healthcare">EastWest Healthcare</option>
                                    <option value="ValuCare (ValueCare)">ValuCare (ValueCare)</option>
                                    <option value="Caritas Health Shield">Caritas Health Shield</option>
                                    <option value="FortuneCare">FortuneCare</option>
                                    <option value="Kaiser">Kaiser</option>
                                    <option value="Pacific Cross">Pacific Cross</option>
                                    <option value="Asalus Health Care (Healthway / FamilyDOC)">Asalus Health Care (Healthway / FamilyDOC)</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label" for="edit_inpatient_membership_number">Membership Number</label>
                                <input type="text" id="edit_inpatient_membership_number" name="membership_number" class="form-input">
                            </div>
                            <div>
                                <label class="form-label" for="edit_inpatient_cardholder_name">Card Holder Name</label>
                                <input type="text" id="edit_inpatient_cardholder_name" name="hmo_cardholder_name" class="form-input">
                            </div>
                            <div>
                                <label class="form-label" for="edit_inpatient_member_type">Member Type</label>
                                <select id="edit_inpatient_member_type" name="member_type" class="form-select">
                                    <option value="">Select...</option>
                                    <option value="Principal">Principal</option>
                                    <option value="Dependent">Dependent</option>
                                    <option value="Spouse">Spouse</option>
                                    <option value="Child">Child</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label" for="edit_inpatient_relationship">Relationship</label>
                                <select id="edit_inpatient_relationship" name="relationship" class="form-select">
                                    <option value="">Select...</option>
                                    <option value="Self">Self</option>
                                    <option value="Spouse">Spouse</option>
                                    <option value="Child">Child</option>
                                    <option value="Parent">Parent</option>
                                    <option value="Sibling">Sibling</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label" for="edit_inpatient_plan_name">Plan Name</label>
                                <input type="text" id="edit_inpatient_plan_name" name="plan_name" class="form-input">
                            </div>
                            <div class="full">
                                <label class="form-label" id="edit_inpatient_coverage_type_label">Coverage Type</label>
                                <div class="coverage-checkbox-group" role="group" aria-labelledby="edit_inpatient_coverage_type_label">
                                    <?php
                                        $coverageOptions = ['Outpatient', 'Inpatient', 'ER', 'Dental', 'Optical', 'Maternity'];
                                        foreach ($coverageOptions as $option): ?>
                                        <label class="checkbox-chip">
                                            <input type="checkbox" name="plan_coverage_types[]" value="<?= esc($option) ?>">
                                            <span><?= esc($option) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div>
                                <label class="form-label" for="edit_inpatient_mbl">MBL</label>
                                <input type="number" min="0" step="0.01" id="edit_inpatient_mbl" name="mbl" class="form-input" placeholder="Maximum Benefit Limit">
                            </div>
                            <div class="full">
                                <label class="form-label" for="edit_inpatient_preexisting">Pre-existing Coverage</label>
                                <textarea id="edit_inpatient_preexisting" name="pre_existing_coverage" class="form-input" rows="2"></textarea>
                            </div>
                            <div>
                                <label class="form-label" for="edit_inpatient_validity_start">Start Date</label>
                                <input type="date" id="edit_inpatient_validity_start" name="coverage_start_date" class="form-input">
                            </div>
                            <div>
                                <label class="form-label" for="edit_inpatient_validity_end">End Date</label>
                                <input type="date" id="edit_inpatient_validity_end" name="coverage_end_date" class="form-input">
                            </div>
                            <div>
                                <label class="form-label" for="edit_inpatient_card_status">Card Status</label>
                                <select id="edit_inpatient_card_status" name="card_status" class="form-select">
                                    <option value="">Select...</option>
                                    <option value="Active">Active</option>
                                    <option value="Expired">Expired</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Suspended">Suspended</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </details>
            </form>
        </div>
        <div class="hms-modal-actions" style="display: flex; justify-content: flex-end; gap: 0.75rem; padding: 1rem 1.25rem;">
            <button type="button" class="btn btn-secondary" onclick="closeEditPatientModal()">Cancel</button>
            <button type="submit" id="updatePatientBtn" class="btn btn-success" data-active-form="editPatientForm">
                <i class="fas fa-save"></i> Update Patient
            </button>
        </div>
    </div>
</div>

<style>
details summary {
    list-style: none;
}
details summary::-webkit-details-marker {
    display: none;
}
details[open] summary i.fa-chevron-down {
    transform: rotate(180deg);
}
details summary i.fa-chevron-down {
    transition: transform 0.2s;
    display: inline-block;
}
</style>
