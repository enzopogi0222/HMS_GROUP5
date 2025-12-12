<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Hospital Management System</title>
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

            .hero-section {
                max-width: 1120px;
                margin: 0 auto 2.5rem;
                display: grid;
                grid-template-columns: minmax(0, 2fr) minmax(0, 1.5fr);
                gap: 2.5rem;
                align-items: center;
            }

            .hero-title {
                font-size: clamp(2rem, 3vw, 2.5rem);
                font-weight: 700;
                color: #111827;
                margin-bottom: 0.75rem;
            }

            .hero-subtitle {
                font-size: 1rem;
                color: #4b5563;
                margin-bottom: 1.5rem;
                max-width: 34rem;
            }

            .hero-badges {
                display: flex;
                flex-wrap: wrap;
                gap: 0.75rem;
                margin-bottom: 1.75rem;
            }

            .badge-pill {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                padding: 0.3rem 0.8rem;
                border-radius: 9999px;
                font-size: 0.75rem;
                font-weight: 500;
            }

            .badge-pill.primary {
                background: #dbeafe;
                color: #1d4ed8;
            }

            .badge-pill.success {
                background: #dcfce7;
                color: #15803d;
            }

            .hero-cta {
                display: flex;
                flex-wrap: wrap;
                gap: 0.75rem;
                align-items: center;
            }

            .hero-meta {
                font-size: 0.8rem;
                color: #6b7280;
            }

            .hero-illustration {
                background: radial-gradient(circle at top, #eff6ff, #e5e7eb);
                border-radius: 1.5rem;
                padding: 1.75rem 1.5rem;
                box-shadow: 0 18px 45px rgba(15, 23, 42, 0.15);
                border: 1px solid #e5e7eb;
            }

            .hero-illustration-title {
                font-size: 0.95rem;
                font-weight: 600;
                color: #111827;
                margin-bottom: 0.5rem;
            }

            .hero-stats {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 1rem;
                margin-top: 1.25rem;
            }

            .hero-stat {
                text-align: center;
            }

            .hero-stat-value {
                font-size: 1.4rem;
                font-weight: 700;
                color: #1d4ed8;
            }

            .hero-stat-label {
                font-size: 0.8rem;
                color: #4b5563;
            }

            .features-section {
                max-width: 1120px;
                margin: 0 auto;
            }

            .features-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                gap: 1.5rem;
            }

            .feature-card {
                background: #ffffff;
                border-radius: 0.9rem;
                padding: 1.35rem 1.25rem;
                box-shadow: 0 2px 10px rgba(15, 23, 42, 0.08);
                border: 1px solid #e5e7eb;
                transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
            }

            .feature-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(15, 23, 42, 0.16);
                border-color: #cbd5f5;
            }

            .feature-icon {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 40px;
                height: 40px;
                border-radius: 9999px;
                background: linear-gradient(135deg, #3b82f6, #6366f1);
                color: #ffffff;
                font-size: 1.1rem;
                margin-bottom: 0.75rem;
            }

            .feature-title {
                font-size: 1rem;
                font-weight: 600;
                color: #111827;
                margin-bottom: 0.4rem;
            }

            .feature-text {
                font-size: 0.87rem;
                color: #4b5563;
                margin-bottom: 0.6rem;
            }

            .feature-meta {
                font-size: 0.75rem;
                color: #6b7280;
            }

            .section-heading {
                font-size: 0.95rem;
                font-weight: 600;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: #6b7280;
                margin-bottom: 0.25rem;
            }

            .section-title {
                font-size: 1.4rem;
                font-weight: 700;
                color: #111827;
                margin-bottom: 0.75rem;
            }

            .section-subtitle {
                font-size: 0.95rem;
                color: #4b5563;
                margin-bottom: 1.5rem;
                max-width: 40rem;
            }

            @media (max-width: 992px) {
                .hero-section {
                    grid-template-columns: minmax(0, 1fr);
                    text-align: left;
                }

                .hero-illustration {
                    order: -1;
                }
            }

            @media (max-width: 576px) {
                .main-content {
                    margin-top: 80px;
                    padding-inline: 1rem;
                }

                .hero-section {
                    gap: 1.75rem;
                }

                .hero-cta {
                    flex-direction: column;
                    align-items: flex-start;
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
                                <button class="button-active" type="button">Home</button>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white p-0" href="<?= base_url('login') ?>">
                                <button class="button" type="button">Log In</button>
                            </a>
                        </li>
                        <li class="nav-item d-none d-sm-block">
                            <a class="nav-link text-white p-0" href="<?= base_url('about') ?>">
                                <button class="button" type="button">About Us</button>
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
            <section class="hero-section">
                <div>
                    <div class="section-heading">Unified Hospital Platform</div>
                    <h1 class="hero-title">Streamline patient care, appointments, and staff in one modern system.</h1>
                    <p class="hero-subtitle">
                        Our Hospital Management System gives your team a centralized, secure, and intuitive workspace
                        for managing patients, appointments, billing, prescriptions, and more.
                    </p>

                    <div class="hero-badges">
                        <span class="badge-pill primary">Role-based dashboards for doctors, nurses, and admin</span>
                        <span class="badge-pill success">Secure, unified workflows across all departments</span>
                    </div>

                    <div class="hero-cta">
                        <a href="<?= base_url('login') ?>" class="text-decoration-none">
                            <button type="button" class="button-active">Log In to Dashboard</button>
                        </a>
                        <span class="hero-meta">Access restricted to authorized hospital staff.</span>
                    </div>
                </div>

                <div class="hero-illustration">
                    <div class="hero-illustration-title">Today at a glance</div>
                    <p style="font-size: 0.85rem; color: #4b5563; margin-bottom: 0.75rem;">
                        A snapshot of how HMS keeps your operations running smoothly.
                    </p>
                    <div class="hero-stats">
                        <div class="hero-stat">
                            <div class="hero-stat-value">+120</div>
                            <div class="hero-stat-label">Active Patients</div>
                        </div>
                        <div class="hero-stat">
                            <div class="hero-stat-value">36</div>
                            <div class="hero-stat-label">Todays Appointments</div>
                        </div>
                        <div class="hero-stat">
                            <div class="hero-stat-value">8</div>
                            <div class="hero-stat-label">Clinical Departments</div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="features-section mt-4">
                <div class="section-title">Built for real hospital workflows</div>
                <p class="section-subtitle">
                    Inside the system, each role gets a tailored dashboard and tools. The internal design is modern,
                    consistent, and focused on clarity and patient safety.
                </p>

                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">👨‍⚕️</div>
                        <div class="feature-title">Patient &amp; Appointment Management</div>
                        <p class="feature-text">
                            Unified views for doctors, nurses, and receptionists to manage patient records, schedules,
                            and follow-ups with clear status indicators.
                        </p>
                        <div class="feature-meta">Role-based access  b7 Real-time stats  b7 Modern table layouts</div>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">📋</div>
                        <div class="feature-title">Prescriptions, Labs &amp; Billing</div>
                        <p class="feature-text">
                            Integrated prescription, laboratory, and financial modules that share a consistent design
                            with color-coded badges and clear actions.
                        </p>
                        <div class="feature-meta">Consistent UI  b7 Status badges  b7 Unified workflows</div>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">🧑‍💼</div>
                        <div class="feature-title">Staff &amp; Shift Management</div>
                        <p class="feature-text">
                            Modern dashboards for administrators and IT staff to manage teams, schedules, resources,
                            and analytics from a single place.
                        </p>
                        <div class="feature-meta">Unified dashboards  b7 Department views  b7 Analytics-ready</div>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
