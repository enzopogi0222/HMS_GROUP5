// Helper to expose closePrescriptionBillingModal globally without re-instantiating PrescriptionManager
(function() {
    window.closePrescriptionBillingModal = function() {
        if (window.prescriptionManager && typeof window.prescriptionManager.closeBillingModal === 'function') {
            window.prescriptionManager.closeBillingModal();
        }
    };
})();
