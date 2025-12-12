// Prescription Management JavaScript

document.addEventListener('DOMContentLoaded', function() {
    console.log('Prescription management loaded');
    
    // Get the add prescription button
    const addPrescriptionBtn = document.getElementById('createPrescriptionBtn');
    const prescriptionModal = document.getElementById('prescriptionModal');
    const closeBtn = document.getElementById('closePrescriptionModal');
    const cancelBtn = document.getElementById('cancelPrescriptionBtn');
    
    console.log('Add prescription button found:', !!addPrescriptionBtn);
    console.log('Prescription modal found:', !!prescriptionModal);
    
    // Handle add prescription button click
    if (addPrescriptionBtn) {
        addPrescriptionBtn.addEventListener('click', function() {
            console.log('Add prescription button clicked');
            showPrescriptionModal();
        });
    }
    
    // Handle close button clicks
    if (closeBtn) {
        closeBtn.addEventListener('click', hidePrescriptionModal);
    }
    
    if (cancelBtn) {
        cancelBtn.addEventListener('click', hidePrescriptionModal);
    }
    
    // Handle click outside modal to close
    if (prescriptionModal) {
        prescriptionModal.addEventListener('click', function(e) {
            if (e.target === prescriptionModal) {
                hidePrescriptionModal();
            }
        });
    }
    
    // Handle escape key to close modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && prescriptionModal && prescriptionModal.classList.contains('active')) {
            hidePrescriptionModal();
        }
    });
});

// Function to show prescription modal
function showPrescriptionModal() {
    const modal = document.getElementById('prescriptionModal');
    if (modal) {
        // Reset form
        const form = document.getElementById('prescriptionForm');
        if (form) {
            form.reset();
            const idField = document.getElementById('prescriptionId');
            if (idField) {
                idField.value = '';
            }
            // Set default date to today
            const dateField = document.getElementById('prescriptionDate');
            if (dateField) {
                dateField.value = new Date().toISOString().split('T')[0];
            }
        }
        
        // Show modal
        modal.classList.add('active');
        modal.style.display = 'flex';
        modal.style.position = 'fixed';
        modal.style.top = '0';
        modal.style.left = '0';
        modal.style.width = '100vw';
        modal.style.height = '100vh';
        modal.style.zIndex = '9999';
        modal.style.alignItems = 'center';
        modal.style.justifyContent = 'center';
        modal.style.background = 'rgba(15, 23, 42, 0.55)';
        
        document.body.style.overflow = 'hidden';
        
        console.log('Prescription modal should be visible now');
    } else {
        console.error('Prescription modal not found!');
    }
}

// Function to hide prescription modal
function hidePrescriptionModal() {
    const modal = document.getElementById('prescriptionModal');
    if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
        document.body.style.overflow = '';
        console.log('Prescription modal closed');
    } else {
        console.error('Prescription modal not found for closing!');
    }
}

// Global functions for onclick handlers
window.showPrescriptionModal = showPrescriptionModal;
window.hidePrescriptionModal = hidePrescriptionModal;
