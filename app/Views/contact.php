<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Contact Us - Hospital Management System</title>
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

            .page-title {
                font-size: 1.8rem;
                font-weight: 700;
                color: #111827;
                margin-bottom: 0.6rem;
            }

            .page-subtitle {
                font-size: 0.95rem;
                color: #4b5563;
                margin-bottom: 1.75rem;
                max-width: 36rem;
            }

            .contact-wrapper {
                max-width: 800px;
                margin: 0 auto;
            }

            .contact-card {
                background: #ffffff;
                border-radius: 1rem;
                padding: 1.8rem 1.6rem;
                box-shadow: 0 2px 10px rgba(15, 23, 42, 0.08);
                border: 1px solid #e5e7eb;
            }

            .contact-item {
                display: flex;
                align-items: flex-start;
                gap: 0.75rem;
                margin-bottom: 1rem;
            }

            .contact-icon {
                width: 32px;
                height: 32px;
                border-radius: 9999px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #eff6ff;
                color: #1d4ed8;
                font-size: 1.1rem;
                flex-shrink: 0;
            }

            .contact-label {
                font-size: 0.85rem;
                font-weight: 600;
                color: #6b7280;
                text-transform: uppercase;
                letter-spacing: 0.06em;
            }

            .contact-value {
                font-size: 0.95rem;
                color: #111827;
            }

            .contact-note {
                font-size: 0.85rem;
                color: #6b7280;
                margin-top: 0.75rem;
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
                        <li class="nav-item d-none d-sm-block">
                            <a class="nav-link text-white p-0" href="<?= base_url('about') ?>">
                                <button class="button" type="button">About Us</button>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white p-0" href="<?= base_url('contact') ?>">
                                <button class="button-active" type="button">Contact Us</button>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
        </header>

        <main class="main-content">
            <div class="contact-wrapper">
                <h1 class="page-title">Contact Information</h1>
                <p class="page-subtitle">
                    Get in touch with the hospital administration for inquiries, appointments, or support related
                    to the Hospital Management System.
                </p>

                <div class="contact-card">
                    <div class="contact-item">
                        <div class="contact-icon">📍</div>
                        <div>
                            <div class="contact-label">Address</div>
                            <div class="contact-value">123 Hospital Road, City, Province, ZIP Code</div>
                        </div>
                    </div>

                    <div class="contact-item">
                        <div class="contact-icon">📞</div>
                        <div>
                            <div class="contact-label">Phone</div>
                            <div class="contact-value">(+63) 900 000 0000 / (XXX) XXX XXXX</div>
                        </div>
                    </div>

                    <div class="contact-item">
                        <div class="contact-icon">✉️</div>
                        <div>
                            <div class="contact-label">Email</div>
                            <div class="contact-value">hms.support@example.com</div>
                        </div>
                    </div>

                    <div class="contact-item mb-0">
                        <div class="contact-icon">⏰</div>
                        <div>
                            <div class="contact-label">Office Hours</div>
                            <div class="contact-value">Monday to Friday, 8:00 AM – 5:00 PM</div>
                        </div>
                    </div>

                    <div class="contact-note">
                        For urgent medical concerns, please contact the hospital emergency hotline directly. This
                        page is for general and system‑related inquiries only.
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>
