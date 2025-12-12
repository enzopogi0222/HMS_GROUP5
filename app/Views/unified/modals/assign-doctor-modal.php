<!-- Assign Doctor Modal -->
<div id="assignDoctorModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.4); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; padding:2rem; border-radius:8px; max-width:500px; width:98%; margin:auto; position:relative; max-height:90vh; overflow:auto; box-sizing:border-box; -webkit-overflow-scrolling:touch;">
        <div class="hms-modal-header">
            <div class="hms-modal-title">
                <i class="fas fa-user-md" style="color:#4f46e5"></i>
                <h2 style="margin:0; font-size:1.25rem;">Assign Doctor</h2>
            </div>
            <button type="button" onclick="closeAssignDoctorModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:#666;">&times;</button>
        </div>
        <form id="assignDoctorForm">
            <input type="hidden" id="assign_patient_id" name="patient_id">
            <div style="padding-bottom:5rem;">
                <div style="margin-bottom:1rem;">
                    <label for="patient_name_display">Patient</label>
                    <input type="text" id="patient_name_display" readonly style="width:100%; padding:0.5rem; border:1px solid #ddd; border-radius:4px; background:#f9fafb;">
                </div>
                
                <div style="margin-bottom:1rem;">
                    <label for="doctor_id">Select Doctor *</label>
                    <select id="doctor_id" name="doctor_id" required style="width:100%; padding:0.5rem; border:1px solid #ddd; border-radius:4px;">
                        <option value="">Loading doctors...</option>
                    </select>
                    <small id="err_doctor_id" style="color:#dc2626"></small>
                </div>
                
                <div style="background:#e0f2fe; border:1px solid #0288d1; border-left:4px solid #0288d1; border-radius:8px; padding:1rem; margin-bottom:1rem;">
                    <div style="display:flex; align-items:center; gap:0.5rem; font-weight:600; color:#01579b; margin-bottom:0.5rem;">
                        <i class="fas fa-info-circle"></i>
                        <span>Note:</span>
                    </div>
                    <div style="color:#0277bd; font-size:0.9rem;">
                        Assigning a new doctor will update the patient's primary care physician.
                    </div>
                </div>
            </div>
            <div style="position:absolute; bottom:1rem; right:2rem; left:2rem; display:flex; gap:1rem; justify-content:flex-end; background:#fff; padding-top:1rem; border-top:1px solid #eee;">
                <button type="button" onclick="closeAssignDoctorModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" id="assignDoctorBtn" class="btn btn-primary">
                    <i class="fas fa-user-md"></i> Assign Doctor
                </button>
            </div>
        </form>
    </div>
</div>
