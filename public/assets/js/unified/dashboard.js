// Unified Dashboard JavaScript
// Handles role-based dashboard functionality for HMS

document.addEventListener('DOMContentLoaded', function() {
    // Get configuration from meta tags
    const baseUrl = document.querySelector('meta[name="base-url"]')?.content || '';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const userRole = document.querySelector('meta[name="user-role"]')?.content || '';

    // Initialize dashboard
    console.log('Dashboard initialized for role:', userRole);

    // Add any role-specific initialization here
    if (userRole === 'admin') {
        initAdminDashboard();
    } else if (userRole === 'doctor') {
        initDoctorDashboard();
    } else if (userRole === 'nurse') {
        initNurseDashboard();
    } else if (userRole === 'receptionist') {
        initReceptionistDashboard();
    }

    // Update current time display
    updateCurrentTime();
    setInterval(updateCurrentTime, 60000); // Update every minute
});

// Admin Dashboard Initialization
function initAdminDashboard() {
    console.log('Admin dashboard features loaded');
    // Add admin-specific features here
}

// Doctor Dashboard Initialization
function initDoctorDashboard() {
    console.log('Doctor dashboard features loaded');
    // Add doctor-specific features here
}

// Nurse Dashboard Initialization
function initNurseDashboard() {
    console.log('Nurse dashboard features loaded');
    // Add nurse-specific features here
}

// Receptionist Dashboard Initialization
function initReceptionistDashboard() {
    console.log('Receptionist dashboard features loaded');
    // Add receptionist-specific features here
}

// Update current time
function updateCurrentTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit',
        hour12: true 
    });
    
    // Update any time displays on the page
    const timeElements = document.querySelectorAll('.current-time');
    timeElements.forEach(element => {
        element.textContent = timeString;
    });
}

// Utility: Format date
function formatDate(date) {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Utility: Format time
function formatTime(time) {
    return new Date(time).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
}

// Show notification
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 10000;
        animation: slideIn 0.3s ease-out;
    `;
    
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
