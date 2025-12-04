<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in • {{ config('app.name') }}</title>
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
                radial-gradient(ellipse 100% 100% at 100% 0%, rgba(59,130,246,0.12) 0%, transparent 50%),
                radial-gradient(ellipse 80% 80% at 0% 100%, rgba(168,85,247,0.08) 0%, transparent 50%);
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
            margin-bottom: 48px;
        }

        .hero-stats {
            display: flex;
            gap: 48px;
        }

        .hero-stat {
            display: flex;
            flex-direction: column;
        }

        .hero-stat-value {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 4px;
        }

        .hero-stat-label {
            font-size: 13px;
            color: var(--text-tertiary);
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
            max-width: 360px;
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

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 8px;
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

        .form-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 24px 0;
        }

        .form-checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-checkbox {
            appearance: none;
            width: 16px;
            height: 16px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .form-checkbox:hover {
            border-color: var(--border-hover);
        }

        .form-checkbox:checked {
            background: var(--text);
            border-color: var(--text);
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 16 16' fill='%23050505' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M12.207 4.793a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L6.5 9.086l4.293-4.293a1 1 0 011.414 0z'/%3E%3C/svg%3E");
            background-size: 11px;
            background-position: center;
            background-repeat: no-repeat;
        }

        .form-checkbox-label {
            font-size: 13px;
            color: var(--text-secondary);
            cursor: pointer;
        }

        .form-link {
            font-size: 13px;
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .form-link:hover {
            color: var(--text);
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

        /* Messages */
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

        .form-success {
            padding: 14px 16px;
            background: rgba(34,197,94,0.08);
            border: 1px solid rgba(34,197,94,0.15);
            border-radius: 10px;
            margin-bottom: 24px;
        }

        .form-success p {
            font-size: 13px;
            color: #4ade80;
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
                    <img src="{{ asset('images/default/light-logo1.png') }}" alt="{{ config('app.name') }}" style="height: 28px;">
                </div>

                <div class="hero-main">
                    <h1 class="hero-title">
                        <span class="hero-title-gradient">Your shipments are waiting</span>
                    </h1>
                    <p class="hero-desc">
                        Pick up right where you left off. Track active deliveries, manage your account, and ship with confidence.
                    </p>
                    <div class="hero-stats">
                        <div class="hero-stat">
                            <span class="hero-stat-value">99.9%</span>
                            <span class="hero-stat-label">Uptime SLA</span>
                        </div>
                        <div class="hero-stat">
                            <span class="hero-stat-value">24/7</span>
                            <span class="hero-stat-label">Support</span>
                        </div>
                        <div class="hero-stat">
                            <span class="hero-stat-value">50K+</span>
                            <span class="hero-stat-label">Deliveries</span>
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
                <img src="{{ asset('images/default/light-logo1.png') }}" alt="{{ config('app.name') }}" class="logo">

                <div class="form-header animate">
                    <h2 class="form-title">Welcome back</h2>
                    <p class="form-subtitle">Sign in to your account to continue</p>
                </div>

                @if(session('error'))
                    <div class="form-error animate">
                        <p>{{ session('error') }}</p>
                    </div>
                @endif

                @if(session('success'))
                    <div class="form-success animate">
                        <p>{{ session('success') }}</p>
                    </div>
                @endif

                <form action="{{ route('client.login.submit') }}" method="POST">
                    @csrf

                    <div class="form-group animate animate-delay-1">
                        <label class="form-label">Email address</label>
                        <input type="email" name="email" required autofocus class="form-input" placeholder="john@company.com" value="{{ old('email') }}">
                    </div>

                    <div class="form-group animate animate-delay-2">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" required class="form-input" placeholder="••••••••">
                    </div>

                    <div class="form-row animate animate-delay-2">
                        <div class="form-checkbox-group">
                            <input type="checkbox" name="remember" id="remember" class="form-checkbox">
                            <label for="remember" class="form-checkbox-label">Remember me</label>
                        </div>
                        <a href="#" class="form-link">Forgot password?</a>
                    </div>

                    <button type="submit" class="form-button animate animate-delay-3">
                        Sign in
                    </button>
                </form>

                <div class="form-divider"></div>

                <p class="form-footer animate animate-delay-3">
                    Don't have an account? <a href="{{ route('client.register') }}">Create one</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
