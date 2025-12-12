<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>About Us - Hospital Management System</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
        <style>
            body {
                margin: 0;
                min-height: 100vh;
                background: #f3f4f6;
                font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                color: #111827;
            }

            .navbar-fixed {
                position: fixed;
                top: 0;
                width: 100%;
                z-index: 1000;
                box-shadow: 0 2px 8px rgba(15, 23, 42, 0.15);
            }

            .main-content {
                margin-top: 88px;
                padding: 2rem 1rem 3rem;
            }

            .button {
                background-color: #24649c;
                color: #f9fafb;
                border: none;
                border-radius: 9999px;
                padding: 0.5rem 1.25rem;
                cursor: pointer;
                font-size: 0.9rem;
                font-weight: 500;
                transition: background-color 0.2s ease, transform 0.1s ease, box-shadow 0.2s ease;
            }

            .button-active {
                background-color: #3f87c6;
                color: #f9fafb;
                border: none;
                border-radius: 9999px;
                padding: 0.5rem 1.25rem;
                font-size: 0.9rem;
                font-weight: 600;
                box-shadow: 0 2px 8px rgba(59, 130, 246, 0.4);
                transition: background-color 0.2s ease, transform 0.1s ease, box-shadow 0.2s ease;
            }

            .button:hover,
            .button:focus-visible {
                background-color: #1d4ed8;
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
            }

            .button-active:hover,
            .button-active:focus-visible {
                background-color: #2563eb;
                transform: translateY(-1px);
                box-shadow: 0 6px 18px rgba(37, 99, 235, 0.5);
            }

            .section-heading {
                font-size: 0.95rem;
                font-weight: 600;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: #6b7280;
                margin-bottom: 0.25rem;
            }

            .page-title {
                font-size: 1.8rem;
                font-weight: 700;
                color: #111827;
                margin-bottom: 0.75rem;
            }

            .page-subtitle {
                font-size: 0.95rem;
                color: #4b5563;
                margin-bottom: 1.75rem;
                max-width: 40rem;
            }

            .about-layout {
                max-width: 1120px;
                margin: 0 auto;
                display: grid;
                grid-template-columns: minmax(0, 3fr) minmax(0, 2fr);
                gap: 2rem;
                align-items: flex-start;
            }

            .about-card {
                background: #ffffff;
                border-radius: 0.9rem;
                padding: 1.5rem 1.4rem;
                box-shadow: 0 2px 10px rgba(15, 23, 42, 0.08);
                border: 1px solid #e5e7eb;
                margin-bottom: 1.25rem;
            }

            .about-card h3 {
                font-size: 1.1rem;
                font-weight: 600;
                color: #111827;
                margin-bottom: 0.6rem;
            }

            .about-card p {
                font-size: 0.9rem;
                color: #4b5563;
                margin-bottom: 0.4rem;
            }

            .about-list {
                padding-left: 1.1rem;
                margin: 0.25rem 0 0.75rem;
            }

            .about-list li {
                font-size: 0.9rem;
                color: #4b5563;
                margin-bottom: 0.25rem;
            }

            .stat-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.9rem;
                margin-top: 0.5rem;
            }

            .stat-card {
                background: #f9fafb;
                border-radius: 0.8rem;
                padding: 0.9rem 0.9rem;
                border: 1px solid #e5e7eb;
            }

            .stat-value {
                font-size: 1.2rem;
                font-weight: 700;
                color: #1d4ed8;
            }

            .stat-label {
                font-size: 0.8rem;
                color: #6b7280;
            }

            @media (max-width: 992px) {
                .about-layout {
                    grid-template-columns: minmax(0, 1fr);
                }
            }

            @media (max-width: 576px) {
                .main-content {
                    margin-top: 80px;
                    padding-inline: 1rem;
                }
            }
        </style>
    </head>
    <body>
        <header>
            <nav class="btm-navbar navbar-fixed" style="background-color:#24649c;">
                <div class="container-fluid d-flex justify-content-between align-items-center py-2 px-3 px-md-4">
                    <a class="navbar-brand text-white d-flex align-items-center gap-2" href="<?= base_url('/') ?>">
                        <span style="font-size: 1.6rem; line-height: 1;">🏥</span>
                        <span style="font-weight: 700; letter-spacing: 0.03em;">Hospital Management System</span>
                    </a>
                    <ul class="nav d-flex align-items-center gap-2 gap-md-3">
                        <li class="nav-item">
                            <a class="nav-link text-white p-0" href="<?= base_url('/') ?>">
                                <button class="button" type="button">Home</button>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white p-0" href="<?= base_url('login')?>">
                                <button class="button" type="button">Log In</button>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white p-0" href="<?= base_url('about') ?>">
                                <button class="button-active" type="button">About Us</button>
                            </a>
                        </li>
                        <li class="nav-item d-none d-sm-block">
                            <a class="nav-link text-white p-0" href="<?= base_url('contact') ?>">
                                <button class="button" type="button">Contact Us</button>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
        </header>

        <main class="main-content">
            <section class="mb-4">
                <div class="about-layout">
                    <div>
                        <div class="section-heading">About Our Hospital</div>
                        <h1 class="page-title">Committed to safe, coordinated, and patient‑centered care.</h1>
                        <p class="page-subtitle">
                            This Hospital Management System supports our doctors, nurses, and administrative staff
                            with a unified, secure platform designed around real clinical workflows and patient safety.
                        </p>

                        <div class="about-card">
                            <h3>Our Mission</h3>
                            <p>
                                To deliver excellent, timely, and compassionate care by giving our medical teams
                                clear, accurate information whenever they need it.
                            </p>
                            <ul class="about-list">
                                <li>Ensure every patient’s journey is clearly documented and easy to follow.</li>
                                <li>Support clinical decisions with up‑to‑date records and status tracking.</li>
                                <li>Reduce manual work so staff can focus more on patient care.</li>
                            </ul>
                        </div>

                        <div class="about-card">
                            <h3>How the System Helps</h3>
                            <p>
                                Inside the system, each role sees a dedicated dashboard, modern tables, and
                                color‑coded statuses for appointments, patients, prescriptions, labs, and billing.
                            </p>
                            <ul class="about-list">
                                <li>Unified views for Patients, Appointments, Staff, Prescriptions, and Billing.</li>
                                <li>Role‑based access so the right people see the right information.</li>
                                <li>Consistent design across all modules for faster training and fewer errors.</li>
                            </ul>
                        </div>
                    </div>

                    <div>
                        <div class="about-card">
                            <h3>At a Glance</h3>
                            <div class="stat-grid">
                                <div class="stat-card">
                                    <div class="stat-value">24/7</div>
                                    <div class="stat-label">System Availability</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value">Multi‑role</div>
                                    <div class="stat-label">Admin, Doctor, Nurse, Staff</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value">Unified</div>
                                    <div class="stat-label">Patients, Appointments, Billing</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-value">Secure</div>
                                    <div class="stat-label">Role‑based permissions</div>
                                </div>
                            </div>
                        </div>

                        <div class="about-card">
                            <h3>Designed for Your Team</h3>
                            <p>
                                Whether you are at the front desk, in the ward, in the lab, or in administration,
                                the system keeps the interface familiar and the workflows consistent.
                            </p>
                            <p>
                                This public About page is a simple overview. The full system experience is available
                                to authorized staff after logging in.
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
