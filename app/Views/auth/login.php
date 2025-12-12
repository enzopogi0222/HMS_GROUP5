<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
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

        .login-wrapper {
            max-width: 420px;
            margin: 0 auto;
        }

        .login-card {
            background: #ffffff;
            border-radius: 1rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.18);
            overflow: hidden;
        }

        .login-card-header {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #f9fafb;
            padding: 1.5rem 1.75rem;
            text-align: left;
        }

        .login-card-header h3 {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 700;
        }

        .login-card-header p {
            margin: 0.25rem 0 0;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .login-card-body {
            padding: 1.75rem 1.75rem 1.5rem;
        }

        .form-label {
            font-size: 0.9rem;
            font-weight: 500;
            color: #374151;
        }

        .form-control {
            border-radius: 0.55rem;
            border: 1px solid #e5e7eb;
            padding: 0.7rem 0.9rem;
            font-size: 0.9rem;
        }

        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.18);
        }

        .btn-primary {
            background-color: #2563eb;
            border: none;
            font-weight: 600;
            padding: 0.7rem 1rem;
            border-radius: 9999px;
            font-size: 0.95rem;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
        }

        .btn-primary:hover {
            background-color: #1d4ed8;
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.5);
        }

        .login-meta {
            font-size: 0.8rem;
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
                        <a class="nav-link text-white p-0" href="<?= base_url('/login') ?>">
                            <button class="button-active" type="button">Log In</button>
                        </a>
                    </li>
                    <li class="nav-item d-none d-sm-block">
                        <a class="nav-link text-white p-0" href="<?= base_url('/about') ?>">
                            <button class="button" type="button">About Us</button>
                        </a>
                    </li>
                    <li class="nav-item d-none d-sm-block">
                        <a class="nav-link text-white p-0" href="<?= base_url('/contact') ?>">
                            <button class="button" type="button">Contact Us</button>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-5">
                    <div class="login-wrapper">
                        <div class="login-card">
                            <div class="login-card-header">
                                <h3 class="mb-1">Staff Login</h3>
                                <p>Sign in to access your HMS dashboard.</p>
                            </div>
                            <div class="login-card-body">
                                <?php if (session()->getFlashdata('error')): ?>
                                    <div class="alert alert-danger" role="alert">
                                        <?= session()->getFlashdata('error') ?>
                                    </div>
                                <?php endif; ?>

                                <form action="<?= base_url('/login') ?>" method="post">
                                    <?= csrf_field() ?>

                                    <div class="mb-3">
                                        <label for="login" class="form-label">Email or Username</label>
                                        <input type="text"
                                               class="form-control <?= isset($validation) && $validation->hasError('login') ? 'is-invalid' : '' ?>"
                                               id="login"
                                               name="login"
                                               value="<?= old('login') ?>"
                                               placeholder="Enter your email or username"
                                               autocomplete="username"
                                               required>
                                        <?php if (isset($validation) && $validation->hasError('login')): ?>
                                            <div class="invalid-feedback">
                                                <?= $validation->getError('login') ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password"
                                               class="form-control <?= isset($validation) && $validation->hasError('password') ? 'is-invalid' : '' ?>"
                                               id="password"
                                               name="password"
                                               placeholder="Enter your password"
                                               autocomplete="current-password"
                                               required>
                                        <?php if (isset($validation) && $validation->hasError('password')): ?>
                                            <div class="invalid-feedback">
                                                <?= $validation->getError('password') ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="d-grid mb-1">
                                        <button type="submit" class="btn btn-primary">Log In</button>
                                    </div>

                                    <div class="login-meta">
                                        For authorized hospital personnel only. Your activity may be monitored.
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
