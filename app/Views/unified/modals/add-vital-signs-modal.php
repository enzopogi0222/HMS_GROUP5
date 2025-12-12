<!-- Add Vital Signs Modal -->
<div id="addVitalSignsModal" class="hms-modal-overlay" aria-hidden="true" hidden>
    <div class="hms-modal" role="dialog" aria-modal="true" aria-labelledby="addVitalSignsTitle">
        <div class="hms-modal-header">
            <div class="hms-modal-title" id="addVitalSignsTitle">
                <i class="fas fa-heartbeat" style="color:#4f46e5"></i> Record Vital Signs
            </div>
            <button type="button" class="btn btn-secondary btn-small" onclick="closeAddVitalSignsModal()" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="addVitalSignsForm">
            <input type="hidden" id="vitalSignsPatientId" name="patient_id" value="">
            <div class="hms-modal-body">
                <div class="form-grid">
                    <div>
                        <label class="form-label" for="vital_temperature">Temperature (°C)</label>
                        <input type="number" id="vital_temperature" name="temperature" class="form-input" step="0.1" min="30" max="45" placeholder="e.g., 37.0">
                        <small class="form-hint">Normal: 36.1-37.2°C</small>
                    </div>
                    <div>
                        <label class="form-label" for="vital_oxygen_saturation">Oxygen Saturation (SpO2 %)</label>
                        <input type="number" id="vital_oxygen_saturation" name="oxygen_saturation" class="form-input" step="0.1" min="0" max="100" placeholder="e.g., 98.0">
                        <small class="form-hint">Normal: 95-100%</small>
                    </div>
                    <div>
                        <label class="form-label" for="vital_blood_pressure_systolic">Blood Pressure - Systolic (mmHg)</label>
                        <input type="number" id="vital_blood_pressure_systolic" name="blood_pressure_systolic" class="form-input" min="50" max="250" placeholder="e.g., 120">
                        <small class="form-hint">Top number</small>
                    </div>
                    <div>
                        <label class="form-label" for="vital_blood_pressure_diastolic">Blood Pressure - Diastolic (mmHg)</label>
                        <input type="number" id="vital_blood_pressure_diastolic" name="blood_pressure_diastolic" class="form-input" min="30" max="150" placeholder="e.g., 80">
                        <small class="form-hint">Bottom number</small>
                    </div>
                    <div>
                        <label class="form-label" for="vital_pulse_rate">Pulse Rate (bpm)</label>
                        <input type="number" id="vital_pulse_rate" name="pulse_rate" class="form-input" min="30" max="220" placeholder="e.g., 72">
                        <small class="form-hint">Normal: 60-100 bpm</small>
                    </div>
                    <div>
                        <label class="form-label" for="vital_respiratory_rate">Respiratory Rate (/min)</label>
                        <input type="number" id="vital_respiratory_rate" name="respiratory_rate" class="form-input" min="8" max="40" placeholder="e.g., 16">
                        <small class="form-hint">Normal: 12-20 /min</small>
                    </div>
                    <div>
                        <label class="form-label" for="vital_weight">Weight (kg)</label>
                        <input type="number" id="vital_weight" name="weight" class="form-input" step="0.1" min="0" max="500" placeholder="e.g., 70.5">
                    </div>
                    <div>
                        <label class="form-label" for="vital_height">Height (cm)</label>
                        <input type="number" id="vital_height" name="height" class="form-input" step="0.1" min="0" max="300" placeholder="e.g., 175.0">
                        <small class="form-hint">BMI will be calculated automatically</small>
                    </div>
                    <div>
                        <label class="form-label" for="vital_recorded_at">Recorded At</label>
                        <input type="datetime-local" id="vital_recorded_at" name="recorded_at" class="form-input" value="<?= date('Y-m-d\TH:i') ?>">
                    </div>
                    <div class="full">
                        <label class="form-label" for="vital_notes">Notes</label>
                        <textarea id="vital_notes" name="notes" class="form-input" rows="3" placeholder="Additional observations or notes..."></textarea>
                    </div>
                </div>
            </div>
            <div class="hms-modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeAddVitalSignsModal()">Cancel</button>
                <button type="submit" id="saveVitalSignsBtn" class="btn btn-success">
                    <i class="fas fa-save"></i> Record Vital Signs
                </button>
            </div>
        </form>
    </div>
</div>

