/**
 * View Billing Account Modal
 */
(function() {
    const baseUrl = document.querySelector('meta[name="base-url"]')?.content?.replace(/\/+$/, '') || '';
    const utils = new BillingModalUtils(baseUrl);
    const modalId = 'billingAccountModal';

    const modal = document.getElementById(modalId);
    const header = document.getElementById('billingAccountHeader');
    const patientTypeEl = document.getElementById('billingAccountPatientType');
    const admissionInfoEl = document.getElementById('billingAccountAdmissionInfo');
    const insuranceInfoEl = document.getElementById('billingAccountInsuranceInfo');
    const body = document.getElementById('billingItemsBody');
    const totalEl = document.getElementById('billingAccountTotal');

    function init() {
        if (!modal) return;
        utils.setupModalCloseHandlers(modalId);
    }

    function open(billingId, patientName) {
        if (!modal || !header || !body || !totalEl) return;

        utils.open(modalId);
        header.innerHTML = '';
        if (patientTypeEl) patientTypeEl.innerHTML = '';
        if (admissionInfoEl) admissionInfoEl.style.display = 'none';
        if (insuranceInfoEl) insuranceInfoEl.style.display = 'none';
        body.innerHTML = `<tr><td colspan="4" class="loading-row"><i class="fas fa-spinner fa-spin"></i> Loading billing details...</td></tr>`;

        fetch(`${baseUrl}/billing/accounts/${billingId}`)
            .then(resp => resp.json())
            .then(result => {
                if (!result || !result.success) {
                    body.innerHTML = `<tr><td colspan="4" class="loading-row">Failed to load billing account.</td></tr>`;
                    return;
                }

                const acc = result.data;
                const patientNameDisplay = patientName || acc.patient_name || 'Unknown Patient';
                const patientType = acc.patient_type || 'Outpatient';
                const isInpatient = patientType.toLowerCase() === 'inpatient';

                // Header with basic info
                header.innerHTML = `
                    <div><strong>Billing ID:</strong> ${acc.billing_id}</div>
                    <div><strong>Patient:</strong> ${utils.escapeHtml(patientNameDisplay)}</div>
                    ${acc.status ? `<div><strong>Status:</strong> <span class="billing-status-${acc.status}">${acc.status}</span></div>` : ''}
                `;

                // Patient type badge
                if (patientTypeEl) {
                    const typeClass = isInpatient ? 'patient-type-inpatient' : 'patient-type-outpatient';
                    const typeIcon = isInpatient ? 'fa-bed' : 'fa-user-md';
                    patientTypeEl.innerHTML = `
                        <span class="patient-type-badge ${typeClass}">
                            <i class="fas ${typeIcon}"></i> ${patientType}
                        </span>
                    `;
                }

                // Admission info (for inpatients)
                if (isInpatient && acc.admission) {
                    const admission = acc.admission;
                    let admissionHtml = '<div class="billing-info-section"><h4><i class="fas fa-hospital"></i> Admission Information</h4>';
                    admissionHtml += '<div class="billing-info-grid">';
                    
                    if (admission.admission_id) {
                        admissionHtml += `<div><strong>Admission ID:</strong> ${admission.admission_id}</div>`;
                    }
                    if (admission.admission_datetime) {
                        const admDate = new Date(admission.admission_datetime);
                        admissionHtml += `<div><strong>Admission Date:</strong> ${admDate.toLocaleDateString()} ${admDate.toLocaleTimeString()}</div>`;
                    }
                    if (admission.admission_type) {
                        admissionHtml += `<div><strong>Admission Type:</strong> ${utils.escapeHtml(admission.admission_type)}</div>`;
                    }
                    if (admission.admitting_doctor) {
                        admissionHtml += `<div><strong>Admitting Doctor:</strong> ${utils.escapeHtml(admission.admitting_doctor)}</div>`;
                    }
                    if (admission.admitting_diagnosis) {
                        admissionHtml += `<div><strong>Diagnosis:</strong> ${utils.escapeHtml(admission.admitting_diagnosis)}</div>`;
                    }
                    if (admission.discharge_date) {
                        const disDate = new Date(admission.discharge_date);
                        admissionHtml += `<div><strong>Discharge Date:</strong> ${disDate.toLocaleDateString()} ${disDate.toLocaleTimeString()}</div>`;
                    } else {
                        admissionHtml += `<div><strong>Status:</strong> <span class="status-active">Currently Admitted</span></div>`;
                    }
                    
                    admissionHtml += '</div></div>';
                    if (admissionInfoEl) {
                        admissionInfoEl.innerHTML = admissionHtml;
                        admissionInfoEl.style.display = 'block';
                    }
                }

                // Insurance/Insurance info (for inpatients)
                if (isInpatient && (acc.philhealth_number || acc.hmo_provider || acc.payment_method)) {
                    let insuranceHtml = '<div class="billing-info-section"><h4><i class="fas fa-shield-alt"></i> Insurance & Payment Information</h4>';
                    insuranceHtml += '<div class="billing-info-grid">';
                    
                    if (acc.philhealth_number) {
                        insuranceHtml += `<div><strong>PhilHealth Number:</strong> ${utils.escapeHtml(acc.philhealth_number)}</div>`;
                    }
                    if (acc.hmo_provider) {
                        insuranceHtml += `<div><strong>HMO Provider:</strong> ${utils.escapeHtml(acc.hmo_provider)}</div>`;
                    }
                    if (acc.hmo_approval_code) {
                        insuranceHtml += `<div><strong>HMO Approval Code:</strong> ${utils.escapeHtml(acc.hmo_approval_code)}</div>`;
                    }
                    if (acc.payment_method) {
                        insuranceHtml += `<div><strong>Payment Method:</strong> ${utils.escapeHtml(acc.payment_method)}</div>`;
                    }
                    if (acc.company_guarantee_letter) {
                        insuranceHtml += `<div><strong>Company Guarantee:</strong> <span class="status-yes">Yes</span></div>`;
                    }
                    if (acc.responsible_person_name) {
                        insuranceHtml += `<div><strong>Responsible Person:</strong> ${utils.escapeHtml(acc.responsible_person_name)}</div>`;
                    }
                    if (acc.responsible_person_contact) {
                        insuranceHtml += `<div><strong>Contact:</strong> ${utils.escapeHtml(acc.responsible_person_contact)}</div>`;
                    }
                    
                    insuranceHtml += '</div></div>';
                    if (insuranceInfoEl) {
                        insuranceInfoEl.innerHTML = insuranceHtml;
                        insuranceInfoEl.style.display = 'block';
                    }
                }

                const items = Array.isArray(acc.items) ? acc.items : [];

                if (!items.length) {
                    body.innerHTML = `<tr><td colspan="4" class="loading-row"><i class="fas fa-info-circle"></i> No billing items found.</td></tr>`;
                } else {
                    body.innerHTML = '';
                    items.forEach(item => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${utils.escapeHtml(item.description)}</td>
                            <td>${item.quantity}</td>
                            <td>₱${parseFloat(item.unit_price).toFixed(2)}</td>
                            <td>₱${parseFloat(item.line_total).toFixed(2)}</td>
                        `;
                        body.appendChild(tr);
                    });
                }

                totalEl.textContent = "₱" + parseFloat(acc.total_amount).toFixed(2);
            })
            .catch(() => {
                body.innerHTML = `<tr><td colspan="4">Failed to load billing account.</td></tr>`;
            });
    }

    function close() {
        utils.close(modalId);
    }

    // Export to global scope
    window.ViewBillingAccountModal = { init, open, close };

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

