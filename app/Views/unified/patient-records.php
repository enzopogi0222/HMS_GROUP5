<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= base_url() ?>">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <meta name="user-role" content="<?= esc($userRole ?? 'admin') ?>">
    <title><?= esc($title ?? 'Patient Records') ?> - HMS</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/common.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/unified/patient-management.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .patient-records-container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        .patients-list {
            flex: 0 0 350px;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }
        .records-detail {
            flex: 1;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .patient-item {
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .patient-item:hover {
            background: #f5f5f5;
            border-color: #007bff;
        }
        .patient-item.active {
            background: #e3f2fd;
            border-color: #007bff;
        }
        .patient-item-header {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        .patient-item-meta {
            font-size: 0.85em;
            color: #666;
        }
        .records-tabs {
            display: flex;
            border-bottom: 2px solid #e0e0e0;
            margin-bottom: 20px;
        }
        .tab-button {
            padding: 12px 24px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            color: #666;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all 0.3s;
        }
        .tab-button:hover {
            color: #007bff;
        }
        .tab-button.active {
            color: #007bff;
            border-bottom-color: #007bff;
            font-weight: 600;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .record-section {
            margin-bottom: 30px;
        }
        .record-section h3 {
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        .record-card {
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .record-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .record-card-title {
            font-weight: 600;
            color: #333;
        }
        .record-card-date {
            font-size: 0.85em;
            color: #666;
        }
        .record-card-body {
            color: #555;
            line-height: 1.6;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        .search-box {
            margin-bottom: 20px;
        }
        .search-box input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75em;
            font-weight: 600;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        .info-item {
            padding: 10px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .info-label {
            font-size: 0.85em;
            color: #666;
            margin-bottom: 5px;
        }
        .info-value {
            font-weight: 600;
            color: #333;
        }
    </style>
</head>
<?php include APPPATH . 'Views/template/header.php'; ?>
<div class="main-container">
    <?php include APPPATH . 'Views/unified/components/sidebar.php'; ?>
    <main class="content" role="main">
        <header class="page-header">
            <div>
                <h1 class="page-title">
                    <i class="fas fa-file-medical-alt"></i>
                    <?= esc($title ?? 'Patient Records') ?>
                </h1>
            </div>
        </header>

        <?php if (in_array($userRole ?? '', ['admin', 'doctor'])): ?>
        <section class="dashboard-overview" role="region" aria-label="dashboard overview cards">
            <article class="overview-card" tabindex="0">
                <div class="card-header-modern">
                    <div class="card-icon-modern blue"><i class="fas fa-user-injured"></i></div>
                    <div class="card-info">
                        <h3 class="card-title-modern">Total Files</h3>
                        <p class="card-subtitle">All patient dossiers</p>
                    </div>
                </div>
                <div class="card-metrics">
                    <div class="metric">
                        <div class="metric-value blue"><?= $patientStats['total_patients'] ?? count($patients ?? []) ?></div>
                    </div>
                </div>
            </article>
            <article class="overview-card" tabindex="0">
                <div class="card-header-modern">
                    <div class="card-icon-modern purple"><i class="fas fa-heartbeat"></i></div>
                    <div class="card-info">
                        <h3 class="card-title-modern">Active Cases</h3>
                        <p class="card-subtitle">Currently admitted or under treatment</p>
                    </div>
                </div>
                <div class="card-metrics">
                    <div class="metric">
                        <div class="metric-value purple"><?= $patientStats['active_patients'] ?? '---' ?></div>
                    </div>
                </div>
            </article>
            <article class="overview-card" tabindex="0">
                <div class="card-header-modern">
                    <div class="card-icon-modern green"><i class="fas fa-hand-holding-medical"></i></div>
                    <div class="card-info">
                        <h3 class="card-title-modern">Insurance-Covered</h3>
                        <p class="card-subtitle">With valid providers listed</p>
                    </div>
                </div>
                <div class="card-metrics">
                    <div class="metric">
                        <div class="metric-value green"><?= $patientStats['patients_with_insurance'] ?? '---' ?></div>
                    </div>
                </div>
            </article>
        </section>
        <?php endif; ?>

        <div class="patient-records-container">
            <!-- Patients List -->
            <div class="patients-list">
                <div class="search-box">
                    <input type="text" id="patientSearch" placeholder="Search patients..." aria-label="Search patients">
                </div>
                <div id="patientsList">
                    <?php if (!empty($patients)): ?>
                        <?php foreach ($patients as $patient): ?>
                            <div class="patient-item" data-patient-id="<?= esc($patient['patient_id']) ?>">
                                <div class="patient-item-header">
                                    <?= esc($patient['full_name'] ?? $patient['first_name'] . ' ' . $patient['last_name']) ?>
                                </div>
                                <div class="patient-item-meta">
                                    <div>Case #<?= esc($patient['patient_id']) ?></div>
                                    <div><?= esc($patient['patient_type'] ?? 'N/A') ?> | <?= esc($patient['gender'] ?? $patient['sex'] ?? 'N/A') ?>, Age: <?= esc($patient['age'] ?? 'N/A') ?></div>
                                    <div><span class="badge badge-<?= strtolower($patient['status'] ?? 'Active') === 'active' ? 'success' : 'warning' ?>"><?= esc($patient['status'] ?? 'Active') ?></span></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-user-injured"></i>
                            <p>No patients found</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Records Detail -->
            <div class="records-detail">
                <div id="noPatientSelected" class="empty-state">
                    <i class="fas fa-file-medical"></i>
                    <p>Select a patient from the list to view their records</p>
                </div>

                <div id="patientRecordsDetail" style="display: none;">
                    <!-- Patient Info Header -->
                    <div class="record-section">
                        <div class="info-grid" id="patientInfoHeader">
                            <!-- Patient info will be loaded here -->
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="records-tabs">
                        <button class="tab-button active" data-tab="overview">Overview</button>
                        <button class="tab-button" data-tab="appointments">Appointments</button>
                        <button class="tab-button" data-tab="prescriptions">Prescriptions</button>
                        <button class="tab-button" data-tab="lab-tests">Lab Tests</button>
                        <button class="tab-button" data-tab="visits">Visits</button>
                        <button class="tab-button" data-tab="admissions">Admissions</button>
                        <button class="tab-button" data-tab="financial">Financial</button>
                        <button class="tab-button" data-tab="vitals">Vital Signs</button>
                    </div>

                    <!-- Tab Contents -->
                    <div id="tabOverview" class="tab-content active">
                        <div class="record-section">
                            <h3>Patient Overview</h3>
                            <div id="overviewContent">
                                <!-- Overview content will be loaded here -->
                            </div>
                        </div>
                    </div>

                    <div id="tabAppointments" class="tab-content">
                        <div class="record-section">
                            <h3>Appointments</h3>
                            <div id="appointmentsContent">
                                <!-- Appointments will be loaded here -->
                            </div>
                        </div>
                    </div>

                    <div id="tabPrescriptions" class="tab-content">
                        <div class="record-section">
                            <h3>Prescriptions</h3>
                            <div id="prescriptionsContent">
                                <!-- Prescriptions will be loaded here -->
                            </div>
                        </div>
                    </div>

                    <div id="tabLabTests" class="tab-content">
                        <div class="record-section">
                            <h3>Lab Tests</h3>
                            <div id="labTestsContent">
                                <!-- Lab tests will be loaded here -->
                            </div>
                        </div>
                    </div>

                    <div id="tabVisits" class="tab-content">
                        <div class="record-section">
                            <h3>Outpatient Visits</h3>
                            <div id="visitsContent">
                                <!-- Visits will be loaded here -->
                            </div>
                        </div>
                    </div>

                    <div id="tabAdmissions" class="tab-content">
                        <div class="record-section">
                            <h3>Inpatient Admissions</h3>
                            <div id="admissionsContent">
                                <!-- Admissions will be loaded here -->
                            </div>
                        </div>
                    </div>

                    <div id="tabFinancial" class="tab-content">
                        <div class="record-section">
                            <h3>Financial Records</h3>
                            <div id="financialContent">
                                <!-- Financial records will be loaded here -->
                            </div>
                        </div>
                    </div>

                    <div id="tabVitals" class="tab-content">
                        <div class="record-section">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                <h3 style="margin: 0;">Vital Signs</h3>
                                <?php if (in_array($userRole ?? '', ['admin', 'doctor', 'nurse'], true)): ?>
                                <button type="button" class="btn btn-primary" id="addVitalSignsBtn">
                                    <i class="fas fa-plus"></i> Record Vital Signs
                                </button>
                                <?php endif; ?>
                            </div>
                            <div id="vitalsContent">
                                <!-- Vital signs will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?= $this->include('unified/modals/add-vital-signs-modal') ?>

<script src="<?= base_url('assets/js/unified/patient-records.js') ?>"></script>
</body>
</html>
