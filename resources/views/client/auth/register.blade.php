<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Join {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/branch.css', 'resources/js/branch.js'])
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        
        :root {
            --bg: #050505;
            --surface: #0a0a0a;
            --border: rgba(255,255,255,0.06);
            --border-hover: rgba(255,255,255,0.1);
            --text: #fafafa;
            --text-secondary: rgba(255,255,255,0.5);
            --text-tertiary: rgba(255,255,255,0.3);
            --accent: #fff;
            --accent-glow: rgba(255,255,255,0.1);
        }

        html { height: 100%; }
        
        body {
            min-height: 100%;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
            line-height: 1.5;
        }

        /* Layout */
        .page { display: flex; min-height: 100vh; }
        
        .hero {
            display: none;
            position: relative;
            width: 50%;
            background: var(--bg);
            overflow: hidden;
        }

        @media (min-width: 1024px) {
            .hero { display: flex; }
        }

        .hero-bg {
            position: absolute;
            inset: 0;
            background: 
                radial-gradient(ellipse 100% 100% at 0% 0%, rgba(99,102,241,0.15) 0%, transparent 50%),
                radial-gradient(ellipse 80% 80% at 100% 100%, rgba(168,85,247,0.1) 0%, transparent 50%);
        }

        .hero-grid {
            position: absolute;
            inset: 0;
            background-image: 
                linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
            background-size: 64px 64px;
            mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 20%, transparent 70%);
        }

        .hero-content {
            position: relative;
            z-index: 10;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px;
            width: 100%;
        }

        .hero-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            max-width: 480px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px 6px 8px;
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border);
            border-radius: 100px;
            font-size: 12px;
            font-weight: 500;
            color: var(--text-secondary);
            width: fit-content;
            margin-bottom: 32px;
        }

        .hero-badge-dot {
            width: 6px;
            height: 6px;
            background: #22c55e;
            border-radius: 50%;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        .hero-title {
            font-size: clamp(36px, 4vw, 52px);
            font-weight: 700;
            letter-spacing: -0.03em;
            line-height: 1.1;
            margin-bottom: 20px;
        }

        .hero-title-gradient {
            background: linear-gradient(135deg, #fff 0%, rgba(255,255,255,0.4) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-desc {
            font-size: 16px;
            color: var(--text-secondary);
            line-height: 1.7;
            max-width: 400px;
        }

        .hero-features {
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
            margin-top: 48px;
        }

        .hero-feature {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: var(--text-secondary);
        }

        .hero-feature-icon {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border);
            border-radius: 8px;
        }

        .hero-feature-icon svg {
            width: 16px;
            height: 16px;
            color: var(--text-secondary);
        }

        .hero-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
            color: var(--text-tertiary);
        }

        /* Form Section */
        .form-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 24px;
            background: var(--surface);
            border-left: 1px solid var(--border);
        }

        @media (min-width: 1024px) {
            .form-section { padding: 48px; }
        }

        .form-container {
            width: 100%;
            max-width: 380px;
        }

        .logo {
            display: block;
            height: 32px;
            width: auto;
            margin-bottom: 48px;
        }

        @media (min-width: 1024px) {
            .logo { display: none; }
        }

        .form-header {
            margin-bottom: 40px;
        }

        .form-title {
            font-size: 24px;
            font-weight: 600;
            letter-spacing: -0.02em;
            margin-bottom: 8px;
        }

        .form-subtitle {
            font-size: 14px;
            color: var(--text-secondary);
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        .form-label-optional {
            font-weight: 400;
            color: var(--text-tertiary);
        }

        .form-input {
            width: 100%;
            padding: 12px 14px;
            font-size: 14px;
            font-family: inherit;
            color: var(--text);
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            outline: none;
            transition: all 0.15s ease;
        }

        .form-input::placeholder {
            color: var(--text-tertiary);
        }

        .form-input:hover {
            border-color: var(--border-hover);
        }

        .form-input:focus {
            border-color: rgba(255,255,255,0.2);
            box-shadow: 0 0 0 3px rgba(255,255,255,0.03);
        }

        .form-checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin: 24px 0;
        }

        .form-checkbox {
            appearance: none;
            width: 18px;
            height: 18px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 5px;
            cursor: pointer;
            flex-shrink: 0;
            margin-top: 2px;
            transition: all 0.15s ease;
        }

        .form-checkbox:hover {
            border-color: var(--border-hover);
        }

        .form-checkbox:checked {
            background: var(--text);
            border-color: var(--text);
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 16 16' fill='%23050505' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M12.207 4.793a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L6.5 9.086l4.293-4.293a1 1 0 011.414 0z'/%3E%3C/svg%3E");
            background-size: 12px;
            background-position: center;
            background-repeat: no-repeat;
        }

        .form-checkbox:focus {
            box-shadow: 0 0 0 3px rgba(255,255,255,0.1);
        }

        .form-checkbox-label {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.5;
        }

        .form-checkbox-label a {
            color: var(--text);
            text-decoration: none;
        }

        .form-checkbox-label a:hover {
            text-decoration: underline;
        }

        .form-button {
            width: 100%;
            padding: 14px 20px;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            color: var(--bg);
            background: var(--text);
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .form-button:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .form-button:active {
            transform: translateY(0);
        }

        .form-divider {
            height: 1px;
            background: var(--border);
            margin: 32px 0;
        }

        .form-footer {
            text-align: center;
            font-size: 14px;
            color: var(--text-secondary);
        }

        .form-footer a {
            color: var(--text);
            text-decoration: none;
            font-weight: 500;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        /* Error */
        .form-error {
            padding: 14px 16px;
            background: rgba(239,68,68,0.08);
            border: 1px solid rgba(239,68,68,0.15);
            border-radius: 10px;
            margin-bottom: 24px;
        }

        .form-error p {
            font-size: 13px;
            color: #f87171;
            margin: 0;
        }

        /* Autofill */
        input:-webkit-autofill {
            -webkit-box-shadow: 0 0 0 30px var(--bg) inset !important;
            -webkit-text-fill-color: var(--text) !important;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate { animation: fadeIn 0.4s ease forwards; }
        .animate-delay-1 { animation-delay: 0.05s; opacity: 0; }
        .animate-delay-2 { animation-delay: 0.1s; opacity: 0; }
        .animate-delay-3 { animation-delay: 0.15s; opacity: 0; }
    </style>
</head>
<body>
    <div class="page">
        <!-- Hero Section -->
        <div class="hero">
            <div class="hero-bg"></div>
            <div class="hero-grid"></div>
            <div class="hero-content">
                <div>
                    <img src="{{ \App\Support\SystemSettings::clientPortalLogo() }}" alt="{{ \App\Support\SystemSettings::companyName() }}" style="height: 28px;">
                </div>

                <div class="hero-main">
                    <div class="hero-badge">
                        <span class="hero-badge-dot"></span>
                        Now available nationwide
                    </div>
                    <h1 class="hero-title">
                        <span class="hero-title-gradient">Logistics that moves at the speed of your business</span>
                    </h1>
                    <p class="hero-desc">
                        From instant quotes to real-time tracking, experience shipping infrastructure built for modern commerce.
                    </p>
                    <div class="hero-features">
                        <div class="hero-feature">
                            <div class="hero-feature-icon">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                                </svg>
                            </div>
                            <span>Instant pricing</span>
                        </div>
                        <div class="hero-feature">
                            <div class="hero-feature-icon">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                </svg>
                            </div>
                            <span>Live tracking</span>
                        </div>
                        <div class="hero-feature">
                            <div class="hero-feature-icon">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                </svg>
                            </div>
                            <span>Insured delivery</span>
                        </div>
                    </div>
                </div>

                <div class="hero-footer">
                    <span>&copy; {{ date('Y') }} {{ config('app.name') }}</span>
                    <span>Premium logistics infrastructure</span>
                </div>
            </div>
        </div>

        <!-- Form Section -->
        <div class="form-section">
            <div class="form-container">
                <img src="{{ \App\Support\SystemSettings::clientPortalLogo() }}" alt="{{ \App\Support\SystemSettings::companyName() }}" class="logo">

                <div class="form-header animate">
                    <h2 class="form-title">Create your account</h2>
                    <p class="form-subtitle">Start shipping in under 2 minutes</p>
                </div>

                @if($errors->any())
                    <div class="form-error animate">
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <form action="{{ route('client.register.submit') }}" method="POST">
                    @csrf

                    <div class="form-group animate animate-delay-1">
                        <label class="form-label">Full name</label>
                        <input type="text" name="contact_person" required class="form-input" placeholder="John Doe" value="{{ old('contact_person') }}">
                    </div>

                    <div class="form-group animate animate-delay-1">
                        <label class="form-label">Email address</label>
                        <input type="email" name="email" required class="form-input" placeholder="john@company.com" value="{{ old('email') }}">
                    </div>

                    <div class="form-group animate animate-delay-2">
                        <label class="form-label">Phone number</label>
                        <input type="tel" name="phone" required class="form-input" placeholder="+256 700 000 000" value="{{ old('phone') }}">
                    </div>

                    <div class="form-group animate animate-delay-2">
                        <label class="form-label">Company <span class="form-label-optional">(optional)</span></label>
                        <input type="text" name="company_name" class="form-input" placeholder="Company name" value="{{ old('company_name') }}">
                    </div>

                    <div class="form-row animate animate-delay-3">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" required class="form-input" placeholder="••••••••">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label">Confirm</label>
                            <input type="password" name="password_confirmation" required class="form-input" placeholder="••••••••">
                        </div>
                    </div>

                    <div class="form-checkbox-group animate animate-delay-3">
                        <input type="checkbox" name="terms" id="terms" required class="form-checkbox">
                        <label for="terms" class="form-checkbox-label">
                            I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                        </label>
                    </div>

                    <button type="submit" class="form-button animate animate-delay-3">
                        Create account
                    </button>
                </form>

                <div class="form-divider"></div>

                <p class="form-footer animate animate-delay-3">
                    Already have an account? <a href="{{ route('client.login') }}">Sign in</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
