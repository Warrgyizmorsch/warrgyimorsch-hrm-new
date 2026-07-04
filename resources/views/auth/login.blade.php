<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sign in to Warrgyizmorsch Pvt Ltd internal HRM portal">
    <title>Warrgyizmorsch Pvt Ltd | Employee Portal</title>

    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/images/favicon.ico') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/vendors.min.css') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --brand-nav: #1a2138;
            --brand-primary: #1070e0;
            --brand-primary-hover: #0d5fc0;
            --brand-primary-soft: #e6f1fc;
            --brand-primary-muted: #b3d4f5;
            --brand-secondary: #5c6575;
            --brand-navy: #1a2138;
            --brand-navy-light: #232b45;
            --brand-blue: #1070e0;
            --brand-blue-bright: #3d8fe8;
            --brand-sky: #6baef0;
            --brand-gold: #1070e0;
            --text-primary: #1e2533;
            --text-muted: #5c6575;
            --text-light: #8b95a5;
            --border: #e0e0e0;
            --surface: #ffffff;
            --surface-muted: #f4f7f9;
            --radius-lg: 6px;
            --radius-md: 4px;
            --shadow-form: 0 2px 8px rgba(20, 50, 80, 0.08);
        }

        *, *::before, *::after {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Inter', 'Plus Jakarta Sans', sans-serif;
            background: var(--surface-muted);
            color: var(--text-primary);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        .login-shell {
            display: flex;
            min-height: 100vh;
        }

        /* ── Brand panel (logo blue — light professional) ── */
        .brand-panel {
            flex: 0 0 48%;
            background: linear-gradient(160deg, #1a2138 0%, #1070e0 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 2.75rem 3.5rem;
            position: relative;
            overflow: hidden;
        }

        .brand-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 15% 20%, rgba(255, 255, 255, 0.12) 0%, transparent 45%),
                radial-gradient(circle at 85% 80%, rgba(255, 255, 255, 0.08) 0%, transparent 40%);
            pointer-events: none;
        }

        .brand-grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: linear-gradient(to bottom, rgba(0,0,0,0.5), transparent 80%);
            pointer-events: none;
        }

        .brand-top,
        .brand-main,
        .brand-bottom {
            position: relative;
            z-index: 1;
        }

        .brand-logo-wrap {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .brand-logo-wrap img {
            height: 44px;
            width: auto;
            filter: brightness(0) invert(1);
        }

        .brand-logo-text {
            display: flex;
            flex-direction: column;
            gap: 0.1rem;
        }

        .brand-logo-text strong {
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: -0.01em;
        }

        .brand-logo-text span {
            font-size: 0.72rem;
            font-weight: 500;
            opacity: 0.65;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .internal-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-top: 2.5rem;
            padding: 0.35rem 0.85rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.15);
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            backdrop-filter: blur(6px);
        }

        .internal-badge i {
            font-size: 12px;
            color: var(--brand-gold);
        }

        .brand-main h1 {
            font-size: clamp(1.75rem, 3vw, 2.35rem);
            font-weight: 800;
            line-height: 1.2;
            margin: 1.5rem 0 1rem;
            letter-spacing: -0.03em;
        }

        .brand-main h1 em {
            font-style: normal;
            color: var(--brand-sky);
        }

        .brand-main > p {
            font-size: 0.95rem;
            line-height: 1.7;
            opacity: 0.78;
            max-width: 400px;
            margin: 0 0 2.25rem;
        }

        .module-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.85rem;
            max-width: 420px;
        }

        .module-card {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 1rem;
            border-radius: var(--radius-md);
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(8px);
            transition: background 0.2s, transform 0.2s;
        }

        .module-card:hover {
            background: rgba(255, 255, 255, 0.11);
            transform: translateY(-1px);
        }

        .module-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: rgba(59, 130, 246, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .module-icon i {
            font-size: 16px;
        }

        .module-card strong {
            display: block;
            font-size: 0.8125rem;
            font-weight: 700;
            margin-bottom: 0.15rem;
        }

        .module-card span {
            font-size: 0.72rem;
            opacity: 0.65;
            line-height: 1.4;
        }

        .brand-bottom {
            font-size: 0.78rem;
            opacity: 0.5;
        }

        /* ── Form panel ── */
        .form-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background:
                radial-gradient(circle at 100% 0%, rgba(59, 130, 246, 0.04) 0%, transparent 40%),
                var(--surface-muted);
        }

        .form-card {
            width: 100%;
            max-width: 440px;
            background: var(--surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-form);
            padding: 2.5rem 2.25rem;
        }

        .form-card-header {
            margin-bottom: 2rem;
        }

        .form-card-header h2 {
            font-size: 1.5rem;
            font-weight: 800;
            margin: 0 0 0.4rem;
            letter-spacing: -0.02em;
        }

        .form-card-header p {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin: 0;
            line-height: 1.5;
        }

        .form-alert {
            border-radius: var(--radius-md);
            padding: 0.75rem 1rem;
            font-size: 0.8125rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            line-height: 1.5;
        }

        .form-alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .form-alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .form-field {
            margin-bottom: 1.15rem;
        }

        .form-field label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.4rem;
        }

        .input-group {
            position: relative;
        }

        .input-group .field-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 16px;
            pointer-events: none;
            z-index: 2;
        }

        .input-group .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 16px;
            cursor: pointer;
            z-index: 2;
            transition: color 0.2s;
            line-height: 1;
            background: none;
            border: none;
            padding: 0;
        }

        .input-group .toggle-password:hover {
            color: var(--brand-blue);
        }

        .form-input {
            width: 100%;
            height: 48px;
            padding: 0 44px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-family: inherit;
            color: var(--text-primary);
            background: var(--surface);
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-input::placeholder {
            color: var(--text-light);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--brand-blue-bright);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
        }

        .form-input.is-invalid {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }

        .field-error {
            font-size: 0.75rem;
            color: #ef4444;
            margin-top: 0.35rem;
        }

        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .form-remember {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .form-remember input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--brand-blue);
            cursor: pointer;
        }

        .form-remember span {
            font-size: 0.8125rem;
            color: var(--text-muted);
            user-select: none;
        }

        .form-forgot {
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--brand-blue);
            text-decoration: none;
            transition: color 0.2s;
        }

        .form-forgot:hover {
            color: var(--brand-navy-light);
        }

        .form-submit {
            width: 100%;
            height: 48px;
            border: none;
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, var(--brand-blue) 0%, var(--brand-blue-bright) 100%);
            color: #fff;
            font-size: 0.9375rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.1s, box-shadow 0.2s;
            box-shadow: 0 4px 16px rgba(29, 78, 216, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .form-submit:hover:not(:disabled) {
            box-shadow: 0 6px 22px rgba(29, 78, 216, 0.38);
        }

        .form-submit:active:not(:disabled) {
            transform: scale(0.985);
        }

        .form-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .form-submit .spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.35);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        .form-submit.is-loading .spinner {
            display: block;
        }

        .form-submit.is-loading .btn-text {
            opacity: 0.85;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .form-footer {
            margin-top: 1.75rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
            text-align: center;
        }

        .form-footer p {
            font-size: 0.75rem;
            color: var(--text-light);
            margin: 0 0 0.35rem;
        }

        .form-footer a {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-decoration: none;
        }

        .form-footer a:hover {
            color: var(--brand-blue);
        }

        .secure-note {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            margin-top: 1rem;
            font-size: 0.7rem;
            color: var(--text-light);
        }

        .secure-note i {
            font-size: 12px;
            color: var(--brand-primary);
        }

        /* ── Mobile header ── */
        .mobile-header {
            display: none;
            background: linear-gradient(160deg, #1a2138, #1070e0);
            padding: 1.25rem 1.5rem;
            color: #fff;
        }

        .mobile-header .brand-logo-wrap img {
            height: 32px;
        }

        .mobile-header .brand-logo-text strong {
            font-size: 0.9rem;
        }

        .mobile-header .brand-logo-text span {
            font-size: 0.65rem;
        }

        @media (max-width: 991px) {
            .login-shell {
                flex-direction: column;
            }

            .brand-panel {
                display: none;
            }

            .mobile-header {
                display: block;
            }

            .form-panel {
                padding: 1.5rem 1.25rem 2.5rem;
            }

            .form-card {
                padding: 2rem 1.5rem;
                box-shadow: none;
                border: none;
                background: transparent;
            }
        }

        @media (max-width: 480px) {
            .form-options {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>
    <header class="mobile-header">
        <div class="brand-logo-wrap">
            <img src="{{ asset('assets/images/warr-logo.webp') }}" alt="Warrgyizmorsch">
            <div class="brand-logo-text">
                <strong>Warrgyizmorsch Pvt Ltd</strong>
                <span>Employee Portal</span>
            </div>
        </div>
    </header>

    <div class="login-shell">
        <aside class="brand-panel">
            <div class="brand-grid"></div>

            <div class="brand-top">
                <div class="brand-logo-wrap">
                    <img src="{{ asset('assets/images/warr-logo.webp') }}" alt="Warrgyizmorsch">
                    <div class="brand-logo-text">
                        <strong>Warrgyizmorsch Pvt Ltd</strong>
                        <span>Human Resource Management</span>
                    </div>
                </div>
                <div class="internal-badge">
                    <i class="feather-shield"></i>
                    Internal Employee Portal
                </div>
            </div>

            <div class="brand-main">
                <h1>Manage your work,<br><em>all in one place</em></h1>
                <p>Access your daily tasks, track attendance, view payroll, and complete performance reviews — securely from anywhere within the organisation.</p>

                <div class="module-grid">
                    <div class="module-card">
                        <div class="module-icon"><i class="feather-check-square"></i></div>
                        <div>
                            <strong>Daily Tasks</strong>
                            <span>Track assignments &amp; project work</span>
                        </div>
                    </div>
                    <div class="module-card">
                        <div class="module-icon"><i class="feather-clock"></i></div>
                        <div>
                            <strong>Attendance</strong>
                            <span>Clock-in, leave &amp; holidays</span>
                        </div>
                    </div>
                    <div class="module-card">
                        <div class="module-icon"><i class="feather-credit-card"></i></div>
                        <div>
                            <strong>Payroll</strong>
                            <span>Payslips &amp; compensation</span>
                        </div>
                    </div>
                    <div class="module-card">
                        <div class="module-icon"><i class="feather-star"></i></div>
                        <div>
                            <strong>Reviews</strong>
                            <span>Performance &amp; appraisals</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="brand-bottom">
                &copy; {{ date('Y') }} Warrgyizmorsch Pvt Ltd. Authorised personnel only.
            </div>
        </aside>

        <main class="form-panel">
            <div class="form-card">
                <div class="form-card-header">
                    <h2>Welcome back</h2>
                    <p>Sign in with your company email to access the HRM portal</p>
                </div>

                @if (session('status'))
                    <div class="form-alert form-alert-success">
                        <i class="feather-check-circle"></i>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="form-alert form-alert-danger">
                        <i class="feather-alert-circle"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" novalidate id="loginForm">
                    @csrf

                    <div class="form-field">
                        <label for="email">Company email</label>
                        <div class="input-group">
                            <i class="feather-mail field-icon"></i>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-input @error('email') is-invalid @enderror"
                                placeholder="name@warrgyizmorsch.com"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="email">
                        </div>
                        @error('email')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="loginPassword">Password</label>
                        <div class="input-group">
                            <i class="feather-lock field-icon"></i>
                            <input
                                type="password"
                                id="loginPassword"
                                name="password"
                                class="form-input @error('password') is-invalid @enderror"
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password">
                            <button type="button" class="toggle-password" onclick="togglePassword('loginPassword', this)" aria-label="Toggle password visibility">
                                <i class="feather-eye-off"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-options">
                        <label class="form-remember">
                            <input type="checkbox" id="rememberMe" name="remember" {{ old('remember') ? 'checked' : '' }}>
                            <span>Keep me signed in</span>
                        </label>
                        <a href="{{ route('password.request') }}" class="form-forgot">Forgot password?</a>
                    </div>

                    <button type="submit" class="form-submit" id="loginBtn">
                        <span class="spinner"></span>
                        <span class="btn-text">Sign in to portal</span>
                    </button>
                </form>

                <div class="form-footer">
                    <p>&copy; {{ date('Y') }} Warrgyizmorsch Pvt Ltd. All rights reserved.</p>
                    <a href="https://warrgyizmorsch.com/" target="_blank" rel="noopener">warrgyizmorsch.com</a>
                </div>

                <div class="secure-note">
                    <i class="feather-lock"></i>
                    Secure connection · For authorised employees only
                </div>
            </div>
        </main>
    </div>

    <script src="{{ asset('assets/vendors/js/vendors.min.js') }}"></script>
    <script>
        function togglePassword(inputId, el) {
            const input = document.getElementById(inputId);
            const icon = el.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'feather-eye';
            } else {
                input.type = 'password';
                icon.className = 'feather-eye-off';
            }
        }

        document.getElementById('loginForm').addEventListener('submit', function () {
            const btn = document.getElementById('loginBtn');
            btn.classList.add('is-loading');
            btn.disabled = true;
        });
    </script>
</body>

</html>
