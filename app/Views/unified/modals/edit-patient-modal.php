<!-- Edit Patient Modal -->
<div id="editPatientModal" class="hms-modal-overlay" hidden>
    <div class="hms-modal" role="dialog" aria-modal="true" aria-labelledby="editPatientTitle">
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
            <form id="editPatientForm" class="patient-form" novalidate>
                <input type="hidden" id="edit_patient_id" name="patient_id">

                            <div class="form-section">
                                <div class="section-header">
                                    <div>
                                        <h4>1. Patient Information</h4>
                            <p class="section-subtitle">Update the patient's basic demographic details.</p>
                                    </div>
                                </div>
                                <div class="form-grid">
                                    <div>
                            <label class="form-label" for="edit_first_name">First Name*</label>
                            <input type="text" id="edit_first_name" name="first_name" class="form-input" required>
                            <small id="err_edit_first_name" class="form-error"></small>
                                    </div>
                                    <div>
                            <label class="form-label" for="edit_middle_name">Middle Name</label>
                            <input type="text" id="edit_middle_name" name="middle_name" class="form-input">
                                    </div>
                                    <div>
                            <label class="form-label" for="edit_last_name">Last Name*</label>
                            <input type="text" id="edit_last_name" name="last_name" class="form-input" required>
                            <small id="err_edit_last_name" class="form-error"></small>
                                    </div>
                                    <div>
                            <label class="form-label" for="edit_date_of_birth">Date of Birth*</label>
                            <input type="date" id="edit_date_of_birth" name="date_of_birth" class="form-input" required>
                            <small id="err_edit_date_of_birth" class="form-error"></small>
                                    </div>
                                    <div>
                            <label class="form-label" for="edit_age_display">Age</label>
                            <input type="text" id="edit_age_display" class="form-input" readonly placeholder="Auto-calculated">
                                    </div>
                                    <div>
                            <label class="form-label" for="edit_gender">Sex*</label>
                            <select id="edit_gender" name="gender" class="form-select" required>
                                            <option value="">Select...</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                            <option value="other">Other</option>
                                        </select>
                            <small id="err_edit_gender" class="form-error"></small>
                                    </div>
                                    <div>
                            <label class="form-label" for="edit_civil_status">Civil Status*</label>
                            <select id="edit_civil_status" name="civil_status" class="form-select" required>
                                            <option value="">Select...</option>
                                            <option value="Single">Single</option>
                                            <option value="Married">Married</option>
                                <option value="Divorced">Divorced</option>
                                            <option value="Widowed">Widowed</option>
                                        </select>
                            <small id="err_edit_civil_status" class="form-error"></small>
                                    </div>
                                    <div>
                            <label class="form-label" for="edit_phone">Contact Number*</label>
                            <input type="text" id="edit_phone" name="phone" class="form-input" required inputmode="numeric" maxlength="11" pattern="^09\d{9}$">
                            <small id="err_edit_phone" class="form-error"></small>
                                    </div>
                                    <div>
                            <label class="form-label" for="edit_email">Email Address</label>
                            <input type="email" id="edit_email" name="email" class="form-input">
                            <small id="err_edit_email" class="form-error"></small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <div class="section-header">
                                    <div>
                            <h4>2. Address Information</h4>
                            <p class="section-subtitle">Update the patient's complete address details.</p>
                                    </div>
                                </div>
                                <div class="form-grid">
                                    <div>
                            <label class="form-label" for="edit_province">Province*</label>
                            <select id="edit_province" name="province" class="form-select" required>
                                            <option value="">Select a province...</option>
                                        </select>
                            <small id="err_edit_province" class="form-error"></small>
                                    </div>
                                    <div>
                            <label class="form-label" for="edit_city">City / Municipality*</label>
                            <select id="edit_city" name="city" class="form-select" required>
                                            <option value="">Select a city or municipality...</option>
                                        </select>
                            <small id="err_edit_city" class="form-error"></small>
                                    </div>
                                    <div>
                            <label class="form-label" for="edit_barangay">Barangay*</label>
                            <select id="edit_barangay" name="barangay" class="form-select" required>
                                            <option value="">Select a barangay...</option>
                                        </select>
                            <small id="err_edit_barangay" class="form-error"></small>
                                    </div>
                                    <div>
                            <label class="form-label" for="edit_subdivision">Subdivision / Village</label>
                            <input type="text" id="edit_subdivision" name="subdivision" class="form-input" placeholder="Optional">
                                    </div>
                                    <div>
                            <label class="form-label" for="edit_house_number">House / Lot / Block / Unit No.*</label>
                            <input type="text" id="edit_house_number" name="house_number" class="form-input" required>
                            <small id="err_edit_house_number" class="form-error"></small>
                                    </div>
                                    <div>
                            <label class="form-label" for="edit_zip_code">ZIP Code</label>
                            <input type="text" id="edit_zip_code" name="zip_code" class="form-input" placeholder="Optional">
                        </div>
                        <div class="full">
                            <label class="form-label" for="edit_address">Complete Address*</label>
                            <textarea id="edit_address" name="address" class="form-input" rows="2" required></textarea>
                            <small id="err_edit_address" class="form-error"></small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <div class="section-header">
                                    <div>
                            <h4>3. Emergency Contact</h4>
                            <p class="section-subtitle">Who should we reach out to during emergencies?</p>
                                    </div>
                                </div>
                                <div class="form-grid">
                                    <div>
                            <label class="form-label" for="edit_emergency_contact_name">Contact Person Name*</label>
                            <input type="text" id="edit_emergency_contact_name" name="emergency_contact_name" class="form-input" required>
                            <small id="err_edit_emergency_contact_name" class="form-error"></small>
                                    </div>
                                    <div>
                            <label class="form-label" for="edit_emergency_contact_relationship">Relationship*</label>
                            <select id="edit_emergency_contact_relationship" name="emergency_contact_relationship" class="form-select" required>
                                            <option value="">Select relationship...</option>
                                            <option value="Parent">Parent</option>
                                            <option value="Child">Child</option>
                                            <option value="Sibling">Sibling</option>
                                            <option value="Spouse">Spouse</option>
                                            <option value="Grandparent">Grandparent</option>
                                            <option value="Guardian">Guardian</option>
                                            <option value="Relative">Relative</option>
                                            <option value="Friend">Friend</option>
                                            <option value="Partner">Partner</option>
                                            <option value="Employer">Employer</option>
                                            <option value="Other">Other</option>
                                        </select>
                            <small id="err_edit_emergency_contact_relationship" class="form-error"></small>
                                    </div>
                                    <div>
                            <label class="form-label" for="edit_emergency_contact_relationship_other">If Other, please specify</label>
                            <input type="text" id="edit_emergency_contact_relationship_other" name="emergency_contact_relationship_other" class="form-input" placeholder="Specify relationship" hidden>
                                    </div>
                                    <div>
                            <label class="form-label" for="edit_emergency_contact_phone">Contact Number*</label>
                            <input type="text" id="edit_emergency_contact_phone" name="emergency_contact_phone" class="form-input" required inputmode="numeric" maxlength="11" pattern="^09\d{9}$">
                            <small id="err_edit_emergency_contact_phone" class="form-error"></small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <div class="section-header">
                                    <div>
                            <h4>4. Medical & Administrative Information</h4>
                            <p class="section-subtitle">Patient classification and medical details.</p>
                                    </div>
                                </div>
                                <div class="form-grid">
                                    <div>
                            <label class="form-label" for="edit_patient_type">Patient Type</label>
                            <select id="edit_patient_type" name="patient_type" class="form-select">
                                <option value="Outpatient">Outpatient</option>
                                <option value="Inpatient">Inpatient</option>
                                <option value="Emergency">Emergency</option>
                            </select>
                                    </div>
                                    <div>
                            <label class="form-label" for="edit_blood_group">Blood Group</label>
                            <select id="edit_blood_group" name="blood_group" class="form-select">
                                            <option value="">Select...</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                                        </select>
                                    </div>
                                    <div>
                            <label class="form-label" for="edit_status">Status</label>
                            <select id="edit_status" name="status" class="form-select">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <div class="section-header">
                                    <div>
                            <h4>5. Insurance Information (Optional)</h4>
                            <p class="section-subtitle">Complete these only if the patient has insurance coverage.</p>
                                    </div>
                                </div>
                                <div class="form-grid">
                                    <div>
                            <label class="form-label" for="edit_insurance_provider">Insurance Provider</label>
                            <select id="edit_insurance_provider" name="insurance_provider" class="form-select">
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
                            <label class="form-label" for="edit_insurance_number">Insurance Number</label>
                            <input type="text" id="edit_insurance_number" name="insurance_number" class="form-input">
                                    </div>
                                    </div>
                                </div>

                <div class="form-section">
                    <div class="section-header">
                                    <div>
                            <h4>6. Medical Notes</h4>
                            <p class="section-subtitle">Additional medical information and notes.</p>
                                        </div>
                                    </div>
                    <div class="form-grid">
                                    <div class="full">
                            <label class="form-label" for="edit_medical_notes">Medical Notes</label>
                            <textarea id="edit_medical_notes" name="medical_notes" class="form-input" rows="4" placeholder="Enter any additional medical notes or information..."></textarea>
                                    </div>
                                </div>
                        </div>
                        </form>
        </div>
        <div class="hms-modal-actions" style="display: flex; justify-content: flex-end; gap: 0.75rem;">
            <button type="button" class="btn btn-secondary" onclick="closeEditPatientModal()">Cancel</button>
            <button type="submit" id="updatePatientBtn" class="btn btn-success" form="editPatientForm">
                <i class="fas fa-save"></i> Update Patient
            </button>
        </div>
    </div>
</div>
