<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings['site_title'] }} | {{ $settings['site_tagline'] }}</title>
    <meta name="description" content="{{ $settings['site_description'] }}">
    <meta name="keywords" content="{{ $settings['site_keywords'] }}">
    
    <!-- Open Graph -->
    <meta property="og:title" content="{{ $settings['site_title'] }}">
    <meta property="og:description" content="{{ $settings['site_description'] }}">
    <meta property="og:image" content="{{ $settings['og_image'] }}">
    <meta property="og:url" content="{{ url()->current() }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://rsms.me/">
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- Styles -->
    @vite(['resources/css/landing.css'])
    
    <!-- Custom CSS -->
    @if(!empty($settings['custom_css']))
    <style>
        {!! $settings['custom_css'] !!}
    </style>
    @endif

    <!-- Custom JS Head -->
    @if(!empty($settings['custom_js_head']))
    <script>
        {!! $settings['custom_js_head'] !!}
    </script>
    @endif
</head>
<body class="bg-white">
    <!-- Navigation -->
    <header class="fixed w-full bg-white/90 backdrop-blur-md z-50 border-b border-slate-100">
        <div class="container-custom">
            <div class="flex justify-between items-center h-20">
                <!-- Logo -->
                <div class="flex items-center gap-3">
                    @if(\App\Support\SystemSettings::logo() && \App\Support\SystemSettings::logo() !== '/images/logo.png')
                        <img src="{{ \App\Support\SystemSettings::logo() }}" alt="{{ config('app.name') }}" class="h-10 w-auto">
                    @else
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-blue-700 flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-blue-600/20">
                            {{ substr(config('app.name'), 0, 1) }}
                        </div>
                    @endif
                    <span class="font-bold text-xl tracking-tight text-slate-900">{{ config('app.name') }}</span>
                </div>

                <!-- Desktop Nav -->
                <nav class="hidden md:flex items-center gap-8">
                    @if($settings['services_enabled'])
                    <a href="#solutions" class="nav-link">Solutions</a>
                    @endif
                    @if($settings['features_enabled'])
                    <a href="#features" class="nav-link">Platform</a>
                    @endif
                    @if($settings['stats_enabled'])
                    <a href="#network" class="nav-link">Network</a>
                    @endif
                    <a href="{{ route('tracking.index') }}" class="nav-link">Tracking</a>
                </nav>

                <!-- Auth Buttons -->
                <div class="flex items-center gap-4">
                    <a href="{{ route('client.login') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                        Sign In
                    </a>
                    <a href="{{ route('client.register') }}" class="hidden sm:inline-flex items-center justify-center px-5 py-2.5 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition-all shadow-md shadow-blue-600/20 hover:shadow-lg hover:shadow-blue-600/30">
                        Sign Up
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main>
        <!-- Hero Section -->
        <div class="relative pt-32 pb-20 lg:pt-40 lg:pb-28 overflow-hidden">
            <div class="absolute inset-0 -z-10">
                <div class="absolute inset-0 bg-[radial-gradient(#e5e7eb_1px,transparent_1px)] [background-size:16px_16px] [mask-image:radial-gradient(ellipse_50%_50%_at_50%_50%,#000_70%,transparent_100%)] opacity-30"></div>
                <div class="absolute top-0 right-0 -translate-y-12 translate-x-1/4 blur-3xl opacity-20">
                    <div class="aspect-[1155/678] w-[72.1875rem] bg-gradient-to-tr from-blue-600 to-cyan-400" style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)"></div>
                </div>
            </div>

            <div class="container-custom">
                <div class="text-center max-w-4xl mx-auto">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 border border-blue-100 text-blue-700 text-sm font-medium mb-8">
                        <span class="relative flex h-2 w-2">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                        </span>
                        Next-Gen Logistics Platform
                    </div>
                    
                    <h1 class="text-5xl md:text-7xl font-bold tracking-tight text-slate-900 mb-8">
                        {{ $settings['hero_title'] }}
                    </h1>
                    
                    <p class="text-xl text-slate-600 mb-12 max-w-2xl mx-auto leading-relaxed">
                        {{ $settings['hero_subtitle'] }}
                    </p>

                    <!-- Tracking Box -->
                    @if($settings['hero_show_tracking_widget'])
                    <div class="max-w-2xl mx-auto bg-white p-2 rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 mb-16">
                        <form action="{{ route('tracking.track') }}" method="POST" class="flex flex-col sm:flex-row gap-2">
                            @csrf
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="bi bi-search text-slate-400 text-lg"></i>
                                </div>
                                <input type="text" name="tracking_number" 
                                    class="block w-full pl-11 pr-4 py-4 bg-slate-50 border-0 rounded-xl text-slate-900 placeholder-slate-500 focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition-all"
                                    placeholder="Enter tracking number (e.g., TRK-123456789)"
                                    required>
                            </div>
                            <button type="submit" class="inline-flex items-center justify-center px-8 py-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-all shadow-lg shadow-blue-600/20 hover:shadow-blue-600/30">
                                Track
                                <i class="bi bi-arrow-right ml-2"></i>
                            </button>
                        </form>
                    </div>
                    @endif

                    <!-- Stats -->
                    @if($settings['stats_enabled'] && !empty($settings['stats']))
                    <div id="network" class="grid grid-cols-2 md:grid-cols-4 gap-8 border-t border-slate-100 pt-12">
                        @foreach($settings['stats'] as $stat)
                        <div>
                            <div class="text-3xl font-bold text-slate-900 mb-1">{{ $stat['value'] }}</div>
                            <div class="text-sm font-medium text-slate-500 uppercase tracking-wide">{{ $stat['label'] }}</div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Features Grid -->
        @if($settings['features_enabled'])
        <section id="features" class="py-24 bg-slate-50">
            <div class="container-custom">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <h2 class="text-3xl font-bold text-slate-900 mb-4">{{ $settings['features_title'] }}</h2>
                    <p class="text-lg text-slate-600">{{ $settings['features_subtitle'] }}</p>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    @foreach($settings['features'] as $feature)
                    <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
                        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 text-2xl mb-6">
                            <i class="bi bi-{{ $feature['icon'] }}"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">{{ $feature['title'] }}</h3>
                        <p class="text-slate-600 leading-relaxed">
                            {{ $feature['description'] }}
                        </p>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        <!-- Services Grid -->
        @if($settings['services_enabled'])
        <section id="solutions" class="py-24 bg-white">
            <div class="container-custom">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <h2 class="text-3xl font-bold text-slate-900 mb-4">{{ $settings['services_title'] }}</h2>
                    <p class="text-lg text-slate-600">{{ $settings['services_subtitle'] }}</p>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    @foreach($settings['services'] as $service)
                    <div class="group p-8 rounded-2xl border border-slate-100 hover:border-blue-100 hover:bg-blue-50/30 transition-all">
                        <div class="w-12 h-12 bg-slate-100 group-hover:bg-blue-600 group-hover:text-white rounded-xl flex items-center justify-center text-slate-600 text-2xl mb-6 transition-colors">
                            <i class="bi bi-{{ $service['icon'] }}"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">{{ $service['title'] }}</h3>
                        <p class="text-slate-600 leading-relaxed mb-4">
                            {{ $service['description'] }}
                        </p>
                        @if(!empty($service['price']))
                        <div class="text-sm font-semibold text-blue-600">
                            {{ $service['price'] }}
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        <!-- Dashboard Preview -->
        <section class="py-24 overflow-hidden">
            <div class="container-custom">
                <div class="relative bg-slate-900 rounded-3xl p-8 md:p-12 overflow-hidden">
                    <!-- Background Glow -->
                    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full max-w-3xl bg-blue-500/20 blur-[100px] rounded-full"></div>
                    
                    <div class="relative z-10 grid lg:grid-cols-2 gap-12 items-center">
                        <div>
                            <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">
                                Complete visibility into your <br>
                                <span class="text-blue-400">financial performance</span>
                            </h2>
                            <p class="text-slate-300 text-lg mb-8 leading-relaxed">
                                Track COD collections, settlements, and remittances in real-time. 
                                Reconcile payments faster and reduce financial discrepancies with our automated finance guardrails.
                            </p>
                            
                            <div class="space-y-4">
                                <div class="flex items-center gap-4 text-white">
                                    <div class="w-8 h-8 rounded-full bg-blue-500/20 flex items-center justify-center text-blue-400">
                                        <i class="bi bi-check-lg"></i>
                                    </div>
                                    <span>Automated COD Verification</span>
                                </div>
                                <div class="flex items-center gap-4 text-white">
                                    <div class="w-8 h-8 rounded-full bg-blue-500/20 flex items-center justify-center text-blue-400">
                                        <i class="bi bi-check-lg"></i>
                                    </div>
                                    <span>Real-time Settlement Reports</span>
                                </div>
                                <div class="flex items-center gap-4 text-white">
                                    <div class="w-8 h-8 rounded-full bg-blue-500/20 flex items-center justify-center text-blue-400">
                                        <i class="bi bi-check-lg"></i>
                                    </div>
                                    <span>Driver Cash Account Management</span>
                                </div>
                            </div>

                            <div class="mt-10">
                                <a href="{{ route('login') }}" class="inline-flex items-center gap-2 text-white font-medium hover:text-blue-300 transition-colors">
                                    Explore Finance Module <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                        
                        <!-- Abstract UI Representation -->
                        <div class="relative">
                            <div class="bg-slate-800/50 backdrop-blur-xl border border-slate-700/50 rounded-2xl p-6 shadow-2xl">
                                <div class="flex items-center justify-between mb-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                        <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                                    </div>
                                    <div class="text-xs text-slate-500 font-mono">finance_dashboard.blade.php</div>
                                </div>
                                <div class="space-y-4">
                                    <div class="flex gap-4">
                                        <div class="flex-1 bg-slate-700/50 h-24 rounded-xl animate-pulse"></div>
                                        <div class="flex-1 bg-slate-700/50 h-24 rounded-xl animate-pulse" style="animation-delay: 0.1s"></div>
                                    </div>
                                    <div class="bg-slate-700/30 h-40 rounded-xl animate-pulse" style="animation-delay: 0.2s"></div>
                                    <div class="flex gap-4">
                                        <div class="w-1/3 bg-slate-700/50 h-32 rounded-xl animate-pulse" style="animation-delay: 0.3s"></div>
                                        <div class="flex-1 bg-slate-700/50 h-32 rounded-xl animate-pulse" style="animation-delay: 0.4s"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonials -->
        @if($settings['testimonials_enabled'] && !empty($settings['testimonials']))
        <section class="py-24 bg-slate-50">
            <div class="container-custom">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <h2 class="text-3xl font-bold text-slate-900 mb-4">{{ $settings['testimonials_title'] }}</h2>
                </div>
                <div class="grid md:grid-cols-2 lg:grid-cols-2 gap-8">
                    @foreach($settings['testimonials'] as $testimonial)
                    <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100">
                        <div class="flex gap-1 text-amber-400 mb-4">
                            @for($i = 0; $i < ($testimonial['rating'] ?? 5); $i++)
                            <i class="bi bi-star-fill"></i>
                            @endfor
                        </div>
                        <p class="text-slate-600 italic mb-6">"{{ $testimonial['content'] }}"</p>
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center text-slate-500 font-bold">
                                {{ substr($testimonial['name'], 0, 1) }}
                            </div>
                            <div>
                                <div class="font-bold text-slate-900">{{ $testimonial['name'] }}</div>
                                <div class="text-sm text-slate-500">{{ $testimonial['company'] }}</div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        <!-- CTA Section -->
        <section class="py-20 bg-blue-600">
            <div class="container-custom text-center">
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">Ready to streamline your logistics?</h2>
                <p class="text-blue-100 text-lg mb-10 max-w-2xl mx-auto">
                    Join the network of fast-growing businesses using {{ config('app.name') }} to deliver excellence.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center flex-wrap">
                    <a href="{{ route('client.register') }}" class="inline-flex items-center justify-center px-8 py-4 bg-white text-blue-600 font-bold rounded-xl hover:bg-blue-50 transition-colors shadow-lg">
                        Sign Up
                    </a>
                    <a href="{{ route('client.login') }}" class="inline-flex items-center justify-center px-8 py-4 bg-blue-700 text-white font-bold rounded-xl hover:bg-blue-800 transition-colors border border-blue-500">
                        Customer Login
                    </a>
                    <a href="{{ route('admin.login') }}" class="inline-flex items-center justify-center px-6 py-3 bg-blue-800/50 text-white font-medium rounded-xl hover:bg-blue-800 transition-colors border border-blue-600/50 text-sm">
                        Admin Login
                    </a>
                    <a href="{{ route('branch.login') }}" class="inline-flex items-center justify-center px-6 py-3 bg-blue-800/50 text-white font-medium rounded-xl hover:bg-blue-800 transition-colors border border-blue-600/50 text-sm">
                        Branch Portal
                    </a>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer - Steve Jobs Inspired (Dark) -->
    <footer class="bg-black text-white" style="background-color: #000000 !important; color: #ffffff;">
        <!-- Main Footer Content -->
        <div class="max-w-[980px] mx-auto px-6 pt-8 pb-4">
            <!-- Tagline -->
            <p class="text-xs text-white/70 leading-relaxed pb-4 border-b border-white/10">
                {{ $settings['footer_about'] }}
            </p>
            
            <!-- Footer Links Grid -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-x-6 gap-y-6 py-6 text-xs">
                <!-- Ship & Track -->
                <div>
                    <h3 class="font-semibold text-white mb-3">Ship & Track</h3>
                    <ul class="space-y-2 text-white/70">
                        <li><a href="{{ route('tracking.index') }}" class="hover:text-white transition-colors">Track Shipment</a></li>
                        <li><a href="{{ route('client.register') }}" class="hover:text-white transition-colors">Get a Quote</a></li>
                        <li><a href="{{ route('client.register') }}" class="hover:text-white transition-colors">Schedule Pickup</a></li>
                        <li><a href="/#solutions" class="hover:text-white transition-colors">Service Guide</a></li>
                        <li><a href="/shipping-calculator" class="hover:text-white transition-colors">Shipping Calculator</a></li>
                    </ul>
                </div>
                
                <!-- Services -->
                <div>
                    <h3 class="font-semibold text-white mb-3">Services</h3>
                    <ul class="space-y-2 text-white/70">
                        <li><a href="/#solutions" class="hover:text-white transition-colors">Express Courier</a></li>
                        <li><a href="/#solutions" class="hover:text-white transition-colors">Air Freight</a></li>
                        <li><a href="/#solutions" class="hover:text-white transition-colors">Sea Freight</a></li>
                        <li><a href="/#solutions" class="hover:text-white transition-colors">Road Freight</a></li>
                        <li><a href="/#solutions" class="hover:text-white transition-colors">E-Commerce Fulfillment</a></li>
                        <li><a href="/#solutions" class="hover:text-white transition-colors">Contract Logistics</a></li>
                    </ul>
                </div>
                
                <!-- Account -->
                <div>
                    <h3 class="font-semibold text-white mb-3">Account</h3>
                    <ul class="space-y-2 text-white/70">
                        <li><a href="{{ route('client.login') }}" class="hover:text-white transition-colors">Customer Login</a></li>
                        <li><a href="{{ route('client.register') }}" class="hover:text-white transition-colors">Create Account</a></li>
                        <li><a href="{{ route('branch.login') }}" class="hover:text-white transition-colors">Branch Portal</a></li>
                        <li><a href="{{ route('admin.login') }}" class="hover:text-white transition-colors">Admin Access</a></li>
                        <li><a href="/api-docs" class="hover:text-white transition-colors">API for Developers</a></li>
                    </ul>
                </div>
                
                <!-- Company -->
                <div>
                    <h3 class="font-semibold text-white mb-3">Company</h3>
                    <ul class="space-y-2 text-white/70">
                        <li><a href="/#about" class="hover:text-white transition-colors">About Baraka</a></li>
                        <li><a href="/#network" class="hover:text-white transition-colors">Our Network</a></li>
                        <li><a href="/careers" class="hover:text-white transition-colors">Careers</a></li>
                        <li><a href="/newsroom" class="hover:text-white transition-colors">Newsroom</a></li>
                        <li><a href="/sustainability" class="hover:text-white transition-colors">Sustainability</a></li>
                    </ul>
                </div>
                
                <!-- Support -->
                <div>
                    <h3 class="font-semibold text-white mb-3">Support</h3>
                    <ul class="space-y-2 text-white/70">
                        <li><a href="/help" class="hover:text-white transition-colors">Help Center</a></li>
                        <li><a href="/contact" class="hover:text-white transition-colors">Contact Us</a></li>
                        <li><a href="/claims" class="hover:text-white transition-colors">Claims & Insurance</a></li>
                        <li><a href="/prohibited-items" class="hover:text-white transition-colors">Prohibited Items</a></li>
                        <li><a href="/faq" class="hover:text-white transition-colors">FAQs</a></li>
                    </ul>
                </div>
            </div>
            
            <!-- Contact Bar -->
            <div class="py-4 border-t border-white/10 text-xs text-white/70">
                <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                    @if(!empty($settings['contact_phone']))
                    <span>Call us: <a href="tel:{{ preg_replace('/[^0-9+]/', '', $settings['contact_phone']) }}" class="text-white hover:underline">{{ $settings['contact_phone'] }}</a></span>
                    @endif
                    @if(!empty($settings['contact_whatsapp']))
                    <span>WhatsApp: <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $settings['contact_whatsapp']) }}" class="text-white hover:underline">{{ $settings['contact_whatsapp'] }}</a></span>
                    @endif
                    @if(!empty($settings['contact_email']))
                    <span>Email: <a href="mailto:{{ $settings['contact_email'] }}" class="text-white hover:underline">{{ $settings['contact_email'] }}</a></span>
                    @endif
                    @if(!empty($settings['contact_hours']))
                    <span class="text-white/50">{{ $settings['contact_hours'] }}</span>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Bottom Bar -->
        <div class="border-t border-white/10" style="border-color: rgba(255,255,255,0.1);">
            <div class="max-w-[980px] mx-auto px-6 py-4">
                <div class="flex flex-col items-center justify-center gap-3 text-xs text-white/60 text-center">
                    <!-- Copyright -->
                    <div>
                        <span>{{ $settings['footer_copyright'] }}</span>
                    </div>
                    
                    <!-- Legal Links & Region -->
                    <div class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1">
                        <a href="/privacy" class="hover:text-white transition-colors">Privacy Policy</a>
                        <span class="text-white/30">|</span>
                        <a href="/terms" class="hover:text-white transition-colors">Terms of Use</a>
                        <span class="text-white/30">|</span>
                        <a href="/cookies" class="hover:text-white transition-colors">Cookies</a>
                        <span class="text-white/30">|</span>
                        <a href="/sitemap" class="hover:text-white transition-colors">Site Map</a>
                        <span class="text-white/30">|</span>
                        <span class="flex items-center gap-1">
                            <i class="bi bi-geo-alt text-[10px]"></i>
                            Uganda
                        </span>
                    </div>
                    
                    <!-- Social Icons -->
                    <div class="flex items-center gap-4">
                        @if(!empty($settings['social_twitter']))
                        <a href="{{ $settings['social_twitter'] }}" target="_blank" rel="noopener" class="text-white/60 hover:text-white transition-colors" aria-label="Twitter">
                            <i class="bi bi-twitter-x text-sm"></i>
                        </a>
                        @endif
                        @if(!empty($settings['social_linkedin']))
                        <a href="{{ $settings['social_linkedin'] }}" target="_blank" rel="noopener" class="text-white/60 hover:text-white transition-colors" aria-label="LinkedIn">
                            <i class="bi bi-linkedin text-sm"></i>
                        </a>
                        @endif
                        @if(!empty($settings['social_facebook']))
                        <a href="{{ $settings['social_facebook'] }}" target="_blank" rel="noopener" class="text-white/60 hover:text-white transition-colors" aria-label="Facebook">
                            <i class="bi bi-facebook text-sm"></i>
                        </a>
                        @endif
                        @if(!empty($settings['social_instagram']))
                        <a href="{{ $settings['social_instagram'] }}" target="_blank" rel="noopener" class="text-white/60 hover:text-white transition-colors" aria-label="Instagram">
                            <i class="bi bi-instagram text-sm"></i>
                        </a>
                        @endif
                        @if(!empty($settings['social_youtube']))
                        <a href="{{ $settings['social_youtube'] }}" target="_blank" rel="noopener" class="text-white/60 hover:text-white transition-colors" aria-label="YouTube">
                            <i class="bi bi-youtube text-sm"></i>
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Custom JS Body -->
    @if(!empty($settings['custom_js_body']))
    <script>
        {!! $settings['custom_js_body'] !!}
    </script>
    @endif
</body>
</html>
