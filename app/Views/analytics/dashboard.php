<?= $this->extend('templates/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                    <h6>Analytics & Reports</h6>
                    <div class="d-flex">
                        <div class="input-group input-group-sm me-2" style="width: 250px;">
                            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                            <input type="date" id="startDate" class="form-control form-control-sm" value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
                            <span class="input-group-text">to</span>
                            <input type="date" id="endDate" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                            <button class="btn btn-primary btn-sm" type="button" id="applyDateRange">
                                <i class="fas fa-filter"></i>
                            </button>
                        </div>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="exportPdf">
                                <i class="fas fa-file-pdf"></i> PDF
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="exportCsv">
                                <i class="fas fa-file-csv"></i> CSV
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Loading Indicator -->
                    <div id="loadingIndicator" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading analytics data...</p>
                    </div>

                    <!-- Analytics Content -->
                    <div id="analyticsContent" style="display: none;">
                        <!-- Overview Cards -->
                        <div class="row mb-4" id="overviewCards">
                            <!-- Dynamic content will be loaded here -->
                        </div>

                        <!-- Charts Section -->
                        <div class="row">
                            <div class="col-12 col-lg-8 mb-4">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h6 class="mb-0">Appointments Overview</h6>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="appointmentsChart" height="300"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-4 mb-4">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h6 class="mb-0">Patient Distribution</h6>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="patientsChart" height="300"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Tables -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Recent Activity</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table align-items-center mb-0" id="recentActivityTable">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Activity</th>
                                                        <th>Details</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Dynamic content will be loaded here -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize date pickers
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const applyDateRangeBtn = document.getElementById('applyDateRange');
    const exportPdfBtn = document.getElementById('exportPdf');
    const exportCsvBtn = document.getElementById('exportCsv');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const analyticsContent = document.getElementById('analyticsContent');

    // Load data on page load
    loadAnalyticsData();

    // Event Listeners
    applyDateRangeBtn.addEventListener('click', loadAnalyticsData);
    exportPdfBtn.addEventListener('click', exportReport.bind(null, 'pdf'));
    exportCsvBtn.addEventListener('click', exportReport.bind(null, 'csv'));

    // Load analytics data
    function loadAnalyticsData() {
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;

        // Show loading indicator
        loadingIndicator.style.display = 'block';
        analyticsContent.style.display = 'none';

        // Make AJAX request to get analytics data
        fetch('/analytics/getData', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `startDate=${encodeURIComponent(startDate)}&endDate=${encodeURIComponent(endDate)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateDashboard(data.data);
            } else {
                showError('Failed to load analytics data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('An error occurred while loading data');
        })
        .finally(() => {
            loadingIndicator.style.display = 'none';
            analyticsContent.style.display = 'block';
        });
    }

    // Update dashboard with data
    function updateDashboard(data) {
        updateOverviewCards(data);
        renderCharts(data);
        updateRecentActivity(data);
    }

    // Update overview cards
    function updateOverviewCards(data) {
        const cardsContainer = document.getElementById('overviewCards');
        let cardsHtml = '';

        if (data.overview) {
            // Admin/Staff View
            const { total_patients, total_appointments, total_revenue, active_staff } = data.overview;
            
            cardsHtml = `
                <div class="col-xl-3 col-sm-6 mb-4">
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-8">
                                    <div class="numbers">
                                        <p class="text-sm mb-0 text-uppercase font-weight-bold">Total Patients</p>
                                        <h5 class="font-weight-bolder">${total_patients || 0}</h5>
                                        <p class="mb-0">
                                            <span class="text-success text-sm font-weight-bolder">+5%</span>
                                            since last month
                                        </p>
                                    </div>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="icon icon-shape bg-gradient-primary shadow-primary text-center rounded-circle">
                                        <i class="fas fa-users text-lg opacity-10" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6 mb-4">
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-8">
                                    <div class="numbers">
                                        <p class="text-sm mb-0 text-uppercase font-weight-bold">Appointments</p>
                                        <h5 class="font-weight-bolder">${total_appointments || 0}</h5>
                                        <p class="mb-0">
                                            <span class="text-success text-sm font-weight-bolder">+12%</span>
                                            since last month
                                        </p>
                                    </div>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="icon icon-shape bg-gradient-success shadow-success text-center rounded-circle">
                                        <i class="fas fa-calendar-check text-lg opacity-10" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6 mb-4">
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-8">
                                    <div class="numbers">
                                        <p class="text-sm mb-0 text-uppercase font-weight-bold">Total Revenue</p>
                                        <h5 class="font-weight-bolder">₱${(total_revenue || 0).toLocaleString()}</h5>
                                        <p class="mb-0">
                                            <span class="text-success text-sm font-weight-bolder">+8%</span>
                                            since last month
                                        </p>
                                    </div>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="icon icon-shape bg-gradient-warning shadow-warning text-center rounded-circle">
                                        <i class="fas fa-dollar-sign text-lg opacity-10" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-sm-6 mb-4">
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-8">
                                    <div class="numbers">
                                        <p class="text-sm mb-0 text-uppercase font-weight-bold">Active Staff</p>
                                        <h5 class="font-weight-bolder">${active_staff || 0}</h5>
                                        <p class="mb-0">
                                            <span class="text-success text-sm font-weight-bolder">+2</span>
                                            new this month
                                        </p>
                                    </div>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="icon icon-shape bg-gradient-info shadow-info text-center rounded-circle">
                                        <i class="fas fa-user-md text-lg opacity-10" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else if (data.doctor) {
            // Doctor View
            const { my_patients, appointments, revenue } = data.doctor;
            
            cardsHtml = `
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-8">
                                    <div class="numbers">
                                        <p class="text-sm mb-0 text-uppercase font-weight-bold">My Patients</p>
                                        <h5 class="font-weight-bolder">${my_patients?.total || 0}</h5>
                                        <p class="mb-0">
                                            <span class="text-success text-sm font-weight-bolder">+${my_patients?.new || 0}</span>
                                            new this period
                                        </p>
                                    </div>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="icon icon-shape bg-gradient-primary shadow-primary text-center rounded-circle">
                                        <i class="fas fa-user-injured text-lg opacity-10" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-8">
                                    <div class="numbers">
                                        <p class="text-sm mb-0 text-uppercase font-weight-bold">Appointments</p>
                                        <h5 class="font-weight-bolder">${appointments?.total || 0}</h5>
                                        <p class="mb-0">
                                            <span class="text-success text-sm font-weight-bolder">${appointments?.completed || 0}</span>
                                            completed
                                        </p>
                                    </div>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="icon icon-shape bg-gradient-success shadow-success text-center rounded-circle">
                                        <i class="fas fa-calendar-check text-lg opacity-10" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-8">
                                    <div class="numbers">
                                        <p class="text-sm mb-0 text-uppercase font-weight-bold">Revenue</p>
                                        <h5 class="font-weight-bolder">₱${(revenue?.total || 0).toLocaleString()}</h5>
                                        <p class="mb-0">
                                            <span class="text-success text-sm font-weight-bolder">+5%</span>
                                            from last period
                                        </p>
                                    </div>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="icon icon-shape bg-gradient-warning shadow-warning text-center rounded-circle">
                                        <i class="fas fa-dollar-sign text-lg opacity-10" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else if (data.nurse) {
            // Nurse View
            const { department_patients, medication_stats } = data.nurse;
            
            cardsHtml = `
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-8">
                                    <div class="numbers">
                                        <p class="text-sm mb-0 text-uppercase font-weight-bold">Department Patients</p>
                                        <h5 class="font-weight-bolder">${department_patients?.total || 0}</h5>
                                        <p class="mb-0">
                                            <span class="text-success text-sm font-weight-bolder">${department_patients?.active || 0}</span>
                                            active patients
                                        </p>
                                    </div>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="icon icon-shape bg-gradient-primary shadow-primary text-center rounded-circle">
                                        <i class="fas fa-hospital text-lg opacity-10" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-8">
                                    <div class="numbers">
                                        <p class="text-sm mb-0 text-uppercase font-weight-bold">Medications</p>
                                        <h5 class="font-weight-bolder">${medication_stats?.administered || 0} / ${medication_stats?.total || 0}</h5>
                                        <p class="mb-0">
                                            <span class="text-success text-sm font-weight-bolder">${medication_stats?.pending || 0}</span>
                                            pending administration
                                        </p>
                                    </div>
                                </div>
                                <div class="col-4 text-end">
                                    <div class="icon icon-shape bg-gradient-success shadow-success text-center rounded-circle">
                                        <i class="fas fa-pills text-lg opacity-10" aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        cardsContainer.innerHTML = cardsHtml;
    }

    // Render charts
    function renderCharts(data) {
        // Sample chart data - replace with actual data from the server
        const appointmentsCtx = document.getElementById('appointmentsChart').getContext('2d');
        const patientsCtx = document.getElementById('patientsChart').getContext('2d');

        // Destroy existing charts if they exist
        if (window.appointmentsChart) {
            window.appointmentsChart.destroy();
        }
        if (window.patientsChart) {
            window.patientsChart.destroy();
        }

        // Appointments Chart
        window.appointmentsChart = new Chart(appointmentsCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                datasets: [{
                    label: 'Appointments',
                    data: [65, 59, 80, 81, 56, 55, 40],
                    fill: false,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Patients Chart
        window.patientsChart = new Chart(patientsCtx, {
            type: 'doughnut',
            data: {
                labels: ['New', 'Returning', 'Inactive'],
                datasets: [{
                    data: [30, 50, 20],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(255, 99, 132, 0.7)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    }

    // Update recent activity
    function updateRecentActivity(data) {
        const tbody = document.querySelector('#recentActivityTable tbody');
        let rows = '';

        // Sample data - replace with actual data from the server
        const activities = [
            { date: '2023-06-15 09:30', activity: 'New Appointment', details: 'John Doe - General Checkup', status: 'Completed' },
            { date: '2023-06-14 14:15', activity: 'Lab Results', details: 'Blood Test - Jane Smith', status: 'Pending Review' },
            { date: '2023-06-14 11:20', activity: 'Prescription', details: 'Amoxicillin - Robert Johnson', status: 'Filled' },
            { date: '2023-06-13 16:45', activity: 'Patient Admission', details: 'Maria Garcia - Room 205', status: 'Active' },
            { date: '2023-06-12 10:10', activity: 'Payment Received', details: 'Invoice #10045 - $120.00', status: 'Paid' }
        ];

        activities.forEach(activity => {
            rows += `
                <tr>
                    <td>${activity.date}</td>
                    <td>${activity.activity}</td>
                    <td>${activity.details}</td>
                    <td><span class="badge bg-success">${activity.status}</span></td>
                </tr>
            `;
        });

        tbody.innerHTML = rows;
    }

    // Export report
    function exportReport(type) {
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        
        // Open export URL in a new tab/window
        window.open(`/analytics/export/${type}?startDate=${encodeURIComponent(startDate)}&endDate=${encodeURIComponent(endDate)}`, '_blank');
    }

    // Show error message
    function showError(message) {
        // You can implement a toast or alert here
        console.error(message);
        alert('Error: ' + message);
    }
});
</script>

<style>
/* Add custom styles here if needed */
.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

.icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.bg-gradient-primary {
    background: linear-gradient(45deg, #5e72e4, #825ee4);
}

.bg-gradient-success {
    background: linear-gradient(45deg, #2dce89, #2dcecc);
}

.bg-gradient-warning {
    background: linear-gradient(45deg, #fb6340, #fbb140);
}

.bg-gradient-info {
    background: linear-gradient(45deg, #11cdef, #1171ef);
}

.badge {
    padding: 0.4em 0.8em;
    font-weight: 500;
    border-radius: 50px;
}

.table th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.7rem;
    letter-spacing: 0.5px;
    color: #6c757d;
}
</style>

<?= $this->endSection() ?>
