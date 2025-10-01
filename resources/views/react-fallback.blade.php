<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard - Fallback')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Custom Styles -->
    <style>
        .fallback-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .fallback-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            padding: 2rem;
            max-width: 500px;
            text-align: center;
        }
        .fallback-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background: #e53e3e;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
        }
        .fallback-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 1rem;
        }
        .fallback-message {
            color: #4a5568;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        .fallback-button {
            background: #4299e1;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s ease;
        }
        .fallback-button:hover {
            background: #3182ce;
        }
        .fallback-links {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
        }
        .fallback-links a {
            color: #718096;
            text-decoration: none;
            margin: 0 0.5rem;
        }
        .fallback-links a:hover {
            color: #4a5568;
        }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="fallback-container">
        <div class="fallback-card">
            <div class="fallback-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h1 class="fallback-title">Dashboard Temporarily Unavailable</h1>
            <p class="fallback-message">
                We're experiencing technical difficulties loading the dashboard.
                Please try refreshing the page or contact support if the problem persists.
            </p>
            <a href="javascript:window.location.reload()" class="fallback-button">
                <i class="fas fa-sync-alt mr-2"></i>Try Again
            </a>
            <div class="fallback-links">
                <a href="{{ route('dashboard.index') }}">← Back to Dashboard</a>
                <span class="text-gray-400">|</span>
                <a href="{{ route('logout') }}">Logout</a>
            </div>
        </div>
    </div>
</body>
</html>