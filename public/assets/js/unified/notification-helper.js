/**
 * Universal Notification Helper
 * Tries to use the appropriate notification system based on available context
 */
window.showUniversalNotification = function(message, type = 'info') {
    if (!message) return;

    // Try different notification systems in order of preference
    
    // 1. Prescription Manager
    if (window.prescriptionManager && typeof window.prescriptionManager.showNotification === 'function') {
        window.prescriptionManager.showNotification(message, type);
        return;
    }

    // 2. Prescription notification function
    if (typeof showPrescriptionsNotification === 'function') {
        showPrescriptionsNotification(message, type);
        return;
    }

    // 3. Appointments notification
    if (typeof showAppointmentsNotification === 'function') {
        showAppointmentsNotification(message, type);
        return;
    }

    // 4. User Utils
    if (window.UserUtils && typeof window.UserUtils.showNotification === 'function') {
        window.UserUtils.showNotification(message, type);
        return;
    }

    // 5. Staff Utils
    if (window.StaffUtils && typeof window.StaffUtils.showNotification === 'function') {
        window.StaffUtils.showNotification(message, type);
        return;
    }

    // 6. Patient Utils
    if (window.PatientUtils && typeof window.PatientUtils.showNotification === 'function') {
        window.PatientUtils.showNotification(message, type);
        return;
    }

    // 7. Shift Manager
    if (window.shiftManager && typeof window.shiftManager.showNotification === 'function') {
        window.shiftManager.showNotification(message, type);
        return;
    }

    // 8. Financial notification
    if (typeof showFinancialNotification === 'function') {
        showFinancialNotification(message, type);
        return;
    }

    // 9. Try common notification containers
    const notificationIds = [
        'financialNotification', 'prescriptionsNotification', 'appointmentsNotification',
        'usersNotification', 'staffNotification', 'patientsNotification',
        'resourcesNotification', 'scheduleNotification', 'labNotification'
    ];

    for (const id of notificationIds) {
        const container = document.getElementById(id);
        const iconEl = document.getElementById(id + 'Icon');
        const textEl = document.getElementById(id + 'Text');

        if (container && iconEl && textEl) {
            const isError = type === 'error';
            const isSuccess = type === 'success';

            container.style.border = isError ? '1px solid #fecaca' : '1px solid #bbf7d0';
            container.style.background = isError ? '#fee2e2' : '#ecfdf5';
            container.style.color = isError ? '#991b1b' : '#166534';

            const iconClass = isError
                ? 'fa-exclamation-triangle'
                : (isSuccess ? 'fa-check-circle' : 'fa-info-circle');
            iconEl.className = 'fas ' + iconClass;

            textEl.textContent = String(message || '');
            container.style.display = 'flex';

            setTimeout(() => {
                if (container.style.display !== 'none') {
                    container.style.display = 'none';
                }
            }, 4000);
            return;
        }
    }

    // 10. Fallback: Create floating toast notification
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    const isError = type === 'error';
    const isSuccess = type === 'success';
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        color: ${isError ? '#991b1b' : '#166534'};
        font-weight: 500;
        z-index: 10050;
        max-width: 400px;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.25);
        border: ${isError ? '1px solid #fecaca' : '1px solid #bbf7d0'};
        background: ${isError ? '#fee2e2' : '#ecfdf5'};
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    `;

    const iconClass = isError
        ? 'fa-exclamation-triangle'
        : (isSuccess ? 'fa-check-circle' : 'fa-info-circle');

    notification.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.5rem; width: 100%;">
            <i class="fas ${iconClass}" style="flex-shrink: 0;"></i>
            <span style="flex: 1;">${escapeHtml(message)}</span>
            <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: inherit; margin-left: auto; cursor: pointer; padding: 0.25rem; flex-shrink: 0;">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);

    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 300);
    }, 5000);
};

// Helper function to escape HTML
function escapeHtml(text) {
    if (typeof text !== 'string') return text;
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
