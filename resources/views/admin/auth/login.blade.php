<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login • {{ config('app.name') }}</title>
    @vite(['resources/css/branch.css', 'resources/js/branch.js'])
    <style>
        .login-bg {
            background-image: url('{{ asset("images/2.jpg") }}');
            background-size: cover;
            background-position: center;
        }
        .glass-card {
            background: rgba(17, 24, 39, 0.75);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-slide-up {
            animation: slideUp 0.6s ease-out forwards;
        }
        .input-group:focus-within label {
            color: #3b82f6; /* blue-500 */
        }
        .input-group:focus-within input {
            border-color: #3b82f6;
            box-shadow: 0 0 0 1px #3b82f6;
        }
    </style>
</head>
<body class="h-screen w-screen overflow-hidden font-sans text-white login-bg">
    <!-- Dark Overlay -->
    <div class="absolute inset-0 bg-gray-900/80"></div>

    <div class="relative z-10 flex h-full w-full items-center justify-center p-4">
        <div class="w-full max-w-[420px] animate-slide-up">
            <!-- Logo Section -->
            <div class="mb-8 text-center">
                <img src="{{ \App\Support\SystemSettings::adminLogo() }}" alt="{{ \App\Support\SystemSettings::companyName() }}" class="mx-auto h-12 w-auto opacity-90 hover:opacity-100 transition-opacity">
            </div>

            <!-- Login Card -->
            <div class="glass-card rounded-2xl p-8 shadow-2xl shadow-black/50">
                <div class="mb-6 text-center">
                    <h1 class="text-2xl font-bold tracking-tight text-white">Admin Portal</h1>
                    <p class="mt-2 text-sm text-gray-400">Sign in with your admin or support account.</p>
                    <div class="mt-3 flex justify-center gap-2 text-xs">
                        <span class="px-3 py-1 rounded-full bg-blue-500/10 text-blue-300 border border-blue-500/20">Admin</span>
                        <a href="{{ route('branch.login') }}" class="px-3 py-1 rounded-full bg-white/5 text-gray-300 border border-white/10 hover:bg-white/10 transition-colors">Branch Login</a>
                    </div>
                </div>

                @if(session('error'))
                    <div class="mb-6 rounded-lg bg-rose-500/10 border border-rose-500/20 p-4 text-sm text-rose-400 flex items-center gap-3">
                        <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ session('error') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-5">
                    @csrf

                    <div class="space-y-1.5 input-group transition-colors duration-200">
                        <label for="login" class="block text-xs font-medium uppercase tracking-wider text-gray-500 transition-colors">Email or Phone</label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </div>
                            <input type="text" name="login" id="login" required autofocus
                                class="block w-full rounded-lg border border-white/10 bg-gray-900/50 py-2.5 pl-10 pr-3 text-white placeholder-gray-600 transition-all focus:bg-gray-900 focus:outline-none"
                                placeholder="admin@company.com or +2567..."
                                value="{{ old('login') }}">
                        </div>
                        @error('login')
                            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1.5 input-group transition-colors duration-200">
                        <label for="password" class="block text-xs font-medium uppercase tracking-wider text-gray-500 transition-colors">Password</label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input type="password" name="password" id="password" required
                                class="block w-full rounded-lg border border-white/10 bg-gray-900/50 py-2.5 pl-10 pr-3 text-white placeholder-gray-600 transition-all focus:bg-gray-900 focus:outline-none"
                                placeholder="••••••••">
                        </div>
                        @error('password')
                            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input type="checkbox" name="remember" class="rounded border-white/10 bg-gray-900/50 text-blue-500 focus:ring-blue-500/50 transition-colors group-hover:border-blue-500/50">
                            <span class="text-sm text-gray-400 group-hover:text-gray-300 transition-colors">Remember me</span>
                        </label>
                        <a href="#" class="text-sm font-medium text-blue-500 hover:text-blue-400 transition-colors">Forgot password?</a>
                    </div>

                    <button type="submit" class="group relative flex w-full justify-center rounded-lg bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-lg transition-all hover:bg-blue-500 hover:shadow-blue-500/25 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-900">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-blue-300 group-hover:text-blue-100 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                        </span>
                        Sign In
                    </button>
                </form>
            </div>

            <!-- Footer -->
            <div class="mt-8 text-center">
                <p class="text-xs text-gray-500">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
