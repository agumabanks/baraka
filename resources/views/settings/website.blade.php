@extends('settings.layouts.tailwind')

@section('title', 'Website & Landing Page Settings')

@section('content')
<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                Website & Landing Page
            </h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1">
                Control your entire public website content, SEO, and appearance
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('settings.index') }}" class="btn-secondary">
                <i class="bi bi-arrow-left mr-2"></i>Back
            </a>
            <a href="/" target="_blank" class="btn-secondary">
                <i class="bi bi-eye mr-2"></i>Preview Site
            </a>
            <button type="submit" form="websiteSettingsForm" class="btn-primary shadow-lg shadow-emerald-500/20">
                <i class="bi bi-check-lg mr-2"></i>Save Changes
            </button>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="mb-6 border-b border-slate-200 dark:border-slate-700">
        <nav class="flex gap-4 -mb-px" id="settingsTabs">
            <button type="button" class="tab-btn active" data-tab="seo">
                <i class="bi bi-search mr-2"></i>SEO & Meta
            </button>
            <button type="button" class="tab-btn" data-tab="hero">
                <i class="bi bi-image mr-2"></i>Hero Section
            </button>
            <button type="button" class="tab-btn" data-tab="sections">
                <i class="bi bi-layout-text-window mr-2"></i>Page Sections
            </button>
            <button type="button" class="tab-btn" data-tab="contact">
                <i class="bi bi-telephone mr-2"></i>Contact & Social
            </button>
            <button type="button" class="tab-btn" data-tab="footer">
                <i class="bi bi-card-text mr-2"></i>Footer
            </button>
            <button type="button" class="tab-btn" data-tab="analytics">
                <i class="bi bi-graph-up mr-2"></i>Analytics
            </button>
            <button type="button" class="tab-btn" data-tab="advanced">
                <i class="bi bi-code-slash mr-2"></i>Advanced
            </button>
        </nav>
    </div>

    <form id="websiteSettingsForm" method="POST" action="{{ route('settings.website.update') }}" class="ajax-form">
        @csrf

        <!-- SEO & Meta Tab -->
        <div class="tab-content active" id="tab-seo">
            <div class="grid gap-6">
                <section class="settings-card">
                    <div class="settings-card-header">
                        <div class="settings-card-icon bg-blue-500/10 text-blue-600 dark:text-blue-400">
                            <i class="bi bi-search"></i>
                        </div>
                        <h2 class="settings-card-title">SEO & Meta Tags</h2>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label for="site_title" class="form-label">Site Title</label>
                                <input type="text" id="site_title" name="site_title" 
                                       value="{{ old('site_title', $settings['site_title']) }}"
                                       class="input-field w-full" placeholder="Baraka Logistics">
                                <p class="text-xs text-slate-500">Appears in browser tab and search results</p>
                            </div>
                            <div class="space-y-2">
                                <label for="site_tagline" class="form-label">Tagline</label>
                                <input type="text" id="site_tagline" name="site_tagline" 
                                       value="{{ old('site_tagline', $settings['site_tagline']) }}"
                                       class="input-field w-full" placeholder="Your Trusted Logistics Partner">
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label for="site_description" class="form-label">Meta Description</label>
                            <textarea id="site_description" name="site_description" rows="3"
                                      class="input-field w-full resize-none" maxlength="160"
                                      placeholder="Brief description for search engines (max 160 chars)">{{ old('site_description', $settings['site_description']) }}</textarea>
                            <p class="text-xs text-slate-500"><span id="descCount">0</span>/160 characters</p>
                        </div>
                        <div class="space-y-2">
                            <label for="site_keywords" class="form-label">Keywords</label>
                            <input type="text" id="site_keywords" name="site_keywords" 
                                   value="{{ old('site_keywords', $settings['site_keywords']) }}"
                                   class="input-field w-full" placeholder="logistics, courier, shipping, delivery">
                            <p class="text-xs text-slate-500">Comma-separated keywords for SEO</p>
                        </div>
                        <div class="space-y-2">
                            <label for="og_image" class="form-label">Social Share Image (OG Image)</label>
                            <div class="flex gap-3">
                                <input type="text" id="og_image" name="og_image" 
                                       value="{{ old('og_image', $settings['og_image']) }}"
                                       class="input-field flex-1" placeholder="https://...">
                                <button type="button" class="btn-secondary" onclick="document.getElementById('og_image_file').click()">
                                    <i class="bi bi-upload"></i>
                                </button>
                            </div>
                            <p class="text-xs text-slate-500">Recommended: 1200x630px</p>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <!-- Hero Section Tab -->
        <div class="tab-content hidden" id="tab-hero">
            <div class="grid gap-6">
                <section class="settings-card">
                    <div class="settings-card-header">
                        <div class="settings-card-icon bg-purple-500/10 text-purple-600 dark:text-purple-400">
                            <i class="bi bi-image"></i>
                        </div>
                        <h2 class="settings-card-title">Hero Section</h2>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="space-y-2">
                            <label for="hero_title" class="form-label">Hero Title</label>
                            <input type="text" id="hero_title" name="hero_title" 
                                   value="{{ old('hero_title', $settings['hero_title']) }}"
                                   class="input-field w-full text-lg" placeholder="Fast & Reliable Logistics">
                        </div>
                        <div class="space-y-2">
                            <label for="hero_subtitle" class="form-label">Hero Subtitle</label>
                            <textarea id="hero_subtitle" name="hero_subtitle" rows="2"
                                      class="input-field w-full resize-none"
                                      placeholder="Connecting businesses across Africa...">{{ old('hero_subtitle', $settings['hero_subtitle']) }}</textarea>
                        </div>
                        <div class="space-y-2">
                            <label for="hero_background" class="form-label">Background Image URL</label>
                            <input type="text" id="hero_background" name="hero_background" 
                                   value="{{ old('hero_background', $settings['hero_background']) }}"
                                   class="input-field w-full" placeholder="https://...">
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <h4 class="font-medium text-slate-700 dark:text-slate-300">Primary CTA Button</h4>
                                <div class="space-y-2">
                                    <label for="hero_cta_primary_text" class="form-label text-sm">Button Text</label>
                                    <input type="text" id="hero_cta_primary_text" name="hero_cta_primary_text" 
                                           value="{{ old('hero_cta_primary_text', $settings['hero_cta_primary_text']) }}"
                                           class="input-field w-full" placeholder="Get a Quote">
                                </div>
                                <div class="space-y-2">
                                    <label for="hero_cta_primary_url" class="form-label text-sm">Button URL</label>
                                    <input type="text" id="hero_cta_primary_url" name="hero_cta_primary_url" 
                                           value="{{ old('hero_cta_primary_url', $settings['hero_cta_primary_url']) }}"
                                           class="input-field w-full" placeholder="/quote">
                                </div>
                            </div>
                            <div class="space-y-4">
                                <h4 class="font-medium text-slate-700 dark:text-slate-300">Secondary CTA Button</h4>
                                <div class="space-y-2">
                                    <label for="hero_cta_secondary_text" class="form-label text-sm">Button Text</label>
                                    <input type="text" id="hero_cta_secondary_text" name="hero_cta_secondary_text" 
                                           value="{{ old('hero_cta_secondary_text', $settings['hero_cta_secondary_text']) }}"
                                           class="input-field w-full" placeholder="Track Shipment">
                                </div>
                                <div class="space-y-2">
                                    <label for="hero_cta_secondary_url" class="form-label text-sm">Button URL</label>
                                    <input type="text" id="hero_cta_secondary_url" name="hero_cta_secondary_url" 
                                           value="{{ old('hero_cta_secondary_url', $settings['hero_cta_secondary_url']) }}"
                                           class="input-field w-full" placeholder="/tracking">
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="checkbox" id="hero_show_tracking_widget" name="hero_show_tracking_widget" value="1"
                                   {{ ($settings['hero_show_tracking_widget'] ?? false) ? 'checked' : '' }}
                                   class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <label for="hero_show_tracking_widget" class="text-sm text-slate-700 dark:text-slate-300">
                                Show tracking widget in hero section
                            </label>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <!-- Page Sections Tab -->
        <div class="tab-content hidden" id="tab-sections">
            <div class="grid gap-6">
                <!-- Features Section -->
                <section class="settings-card">
                    <div class="settings-card-header">
                        <div class="settings-card-icon bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                            <i class="bi bi-stars"></i>
                        </div>
                        <h2 class="settings-card-title">Features Section</h2>
                        <div class="ml-auto">
                            <input type="checkbox" id="features_enabled" name="features_enabled" value="1"
                                   {{ ($settings['features_enabled'] ?? true) ? 'checked' : '' }}
                                   class="toggle-switch">
                        </div>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label for="features_title" class="form-label">Section Title</label>
                                <input type="text" id="features_title" name="features_title" 
                                       value="{{ old('features_title', $settings['features_title']) }}"
                                       class="input-field w-full" placeholder="Why Choose Us">
                            </div>
                            <div class="space-y-2">
                                <label for="features_subtitle" class="form-label">Section Subtitle</label>
                                <input type="text" id="features_subtitle" name="features_subtitle" 
                                       value="{{ old('features_subtitle', $settings['features_subtitle']) }}"
                                       class="input-field w-full" placeholder="Experience the difference...">
                            </div>
                        </div>
                        <div id="featuresContainer" class="space-y-3">
                            @foreach($settings['features'] ?? [] as $index => $feature)
                            <div class="feature-item flex gap-3 p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
                                <input type="text" name="features[{{ $index }}][icon]" value="{{ $feature['icon'] }}" 
                                       class="input-field w-24" placeholder="Icon">
                                <input type="text" name="features[{{ $index }}][title]" value="{{ $feature['title'] }}" 
                                       class="input-field flex-1" placeholder="Title">
                                <input type="text" name="features[{{ $index }}][description]" value="{{ $feature['description'] }}" 
                                       class="input-field flex-[2]" placeholder="Description">
                                <button type="button" class="text-rose-500 hover:text-rose-600" onclick="this.parentElement.remove()">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                            @endforeach
                        </div>
                        <button type="button" onclick="addFeature()" class="btn-secondary text-sm">
                            <i class="bi bi-plus mr-1"></i>Add Feature
                        </button>
                    </div>
                </section>

                <!-- Services Section -->
                <section class="settings-card">
                    <div class="settings-card-header">
                        <div class="settings-card-icon bg-sky-500/10 text-sky-600 dark:text-sky-400">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <h2 class="settings-card-title">Services Section</h2>
                        <div class="ml-auto">
                            <input type="checkbox" id="services_enabled" name="services_enabled" value="1"
                                   {{ ($settings['services_enabled'] ?? true) ? 'checked' : '' }}
                                   class="toggle-switch">
                        </div>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label for="services_title" class="form-label">Section Title</label>
                                <input type="text" id="services_title" name="services_title" 
                                       value="{{ old('services_title', $settings['services_title']) }}"
                                       class="input-field w-full" placeholder="Our Services">
                            </div>
                            <div class="space-y-2">
                                <label for="services_subtitle" class="form-label">Section Subtitle</label>
                                <input type="text" id="services_subtitle" name="services_subtitle" 
                                       value="{{ old('services_subtitle', $settings['services_subtitle']) }}"
                                       class="input-field w-full" placeholder="Comprehensive logistics solutions...">
                            </div>
                        </div>
                        <div id="servicesContainer" class="space-y-3">
                            @foreach($settings['services'] ?? [] as $index => $service)
                            <div class="service-item flex gap-3 p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
                                <input type="text" name="services[{{ $index }}][icon]" value="{{ $service['icon'] }}" 
                                       class="input-field w-24" placeholder="Icon">
                                <input type="text" name="services[{{ $index }}][title]" value="{{ $service['title'] }}" 
                                       class="input-field flex-1" placeholder="Title">
                                <input type="text" name="services[{{ $index }}][description]" value="{{ $service['description'] }}" 
                                       class="input-field flex-1" placeholder="Description">
                                <input type="text" name="services[{{ $index }}][price]" value="{{ $service['price'] ?? '' }}" 
                                       class="input-field w-32" placeholder="Price">
                                <button type="button" class="text-rose-500 hover:text-rose-600" onclick="this.parentElement.remove()">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                            @endforeach
                        </div>
                        <button type="button" onclick="addService()" class="btn-secondary text-sm">
                            <i class="bi bi-plus mr-1"></i>Add Service
                        </button>
                    </div>
                </section>

                <!-- Statistics Section -->
                <section class="settings-card">
                    <div class="settings-card-header">
                        <div class="settings-card-icon bg-amber-500/10 text-amber-600 dark:text-amber-400">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                        <h2 class="settings-card-title">Statistics Section</h2>
                        <div class="ml-auto">
                            <input type="checkbox" id="stats_enabled" name="stats_enabled" value="1"
                                   {{ ($settings['stats_enabled'] ?? true) ? 'checked' : '' }}
                                   class="toggle-switch">
                        </div>
                    </div>
                    <div class="p-6 space-y-4">
                        <div id="statsContainer" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            @foreach($settings['stats'] ?? [] as $index => $stat)
                            <div class="stat-item p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg space-y-2">
                                <input type="text" name="stats[{{ $index }}][value]" value="{{ $stat['value'] }}" 
                                       class="input-field w-full text-center font-bold" placeholder="50K+">
                                <input type="text" name="stats[{{ $index }}][label]" value="{{ $stat['label'] }}" 
                                       class="input-field w-full text-center text-sm" placeholder="Deliveries">
                            </div>
                            @endforeach
                        </div>
                    </div>
                </section>

                <!-- About Section -->
                <section class="settings-card">
                    <div class="settings-card-header">
                        <div class="settings-card-icon bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                            <i class="bi bi-info-circle"></i>
                        </div>
                        <h2 class="settings-card-title">About Section</h2>
                        <div class="ml-auto">
                            <input type="checkbox" id="about_enabled" name="about_enabled" value="1"
                                   {{ ($settings['about_enabled'] ?? true) ? 'checked' : '' }}
                                   class="toggle-switch">
                        </div>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="space-y-2">
                            <label for="about_title" class="form-label">Section Title</label>
                            <input type="text" id="about_title" name="about_title" 
                                   value="{{ old('about_title', $settings['about_title']) }}"
                                   class="input-field w-full" placeholder="About Baraka Logistics">
                        </div>
                        <div class="space-y-2">
                            <label for="about_content" class="form-label">Content</label>
                            <textarea id="about_content" name="about_content" rows="4"
                                      class="input-field w-full resize-none"
                                      placeholder="Tell your story...">{{ old('about_content', $settings['about_content']) }}</textarea>
                        </div>
                        <div class="space-y-2">
                            <label for="about_image" class="form-label">Image URL</label>
                            <input type="text" id="about_image" name="about_image" 
                                   value="{{ old('about_image', $settings['about_image']) }}"
                                   class="input-field w-full" placeholder="https://...">
                        </div>
                    </div>
                </section>

                <!-- Testimonials Section -->
                <section class="settings-card">
                    <div class="settings-card-header">
                        <div class="settings-card-icon bg-pink-500/10 text-pink-600 dark:text-pink-400">
                            <i class="bi bi-chat-quote"></i>
                        </div>
                        <h2 class="settings-card-title">Testimonials</h2>
                        <div class="ml-auto">
                            <input type="checkbox" id="testimonials_enabled" name="testimonials_enabled" value="1"
                                   {{ ($settings['testimonials_enabled'] ?? true) ? 'checked' : '' }}
                                   class="toggle-switch">
                        </div>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="space-y-2">
                            <label for="testimonials_title" class="form-label">Section Title</label>
                            <input type="text" id="testimonials_title" name="testimonials_title" 
                                   value="{{ old('testimonials_title', $settings['testimonials_title']) }}"
                                   class="input-field w-full" placeholder="What Our Clients Say">
                        </div>
                        <div id="testimonialsContainer" class="space-y-4">
                            @foreach($settings['testimonials'] ?? [] as $index => $testimonial)
                            <div class="testimonial-item p-4 bg-slate-50 dark:bg-slate-800/50 rounded-lg space-y-3">
                                <div class="flex gap-3">
                                    <input type="text" name="testimonials[{{ $index }}][name]" value="{{ $testimonial['name'] }}" 
                                           class="input-field flex-1" placeholder="Name">
                                    <input type="text" name="testimonials[{{ $index }}][company]" value="{{ $testimonial['company'] }}" 
                                           class="input-field flex-1" placeholder="Company">
                                    <select name="testimonials[{{ $index }}][rating]" class="input-field w-20">
                                        @for($r = 5; $r >= 1; $r--)
                                        <option value="{{ $r }}" {{ ($testimonial['rating'] ?? 5) == $r ? 'selected' : '' }}>{{ $r }}★</option>
                                        @endfor
                                    </select>
                                    <button type="button" class="text-rose-500 hover:text-rose-600" onclick="this.closest('.testimonial-item').remove()">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <textarea name="testimonials[{{ $index }}][content]" rows="2" 
                                          class="input-field w-full resize-none" placeholder="Testimonial content...">{{ $testimonial['content'] }}</textarea>
                            </div>
                            @endforeach
                        </div>
                        <button type="button" onclick="addTestimonial()" class="btn-secondary text-sm">
                            <i class="bi bi-plus mr-1"></i>Add Testimonial
                        </button>
                    </div>
                </section>
            </div>
        </div>

        <!-- Contact & Social Tab -->
        <div class="tab-content hidden" id="tab-contact">
            <div class="grid gap-6">
                <!-- Contact Info -->
                <section class="settings-card">
                    <div class="settings-card-header">
                        <div class="settings-card-icon bg-green-500/10 text-green-600 dark:text-green-400">
                            <i class="bi bi-telephone"></i>
                        </div>
                        <h2 class="settings-card-title">Contact Information</h2>
                        <div class="ml-auto">
                            <input type="checkbox" id="contact_enabled" name="contact_enabled" value="1"
                                   {{ ($settings['contact_enabled'] ?? true) ? 'checked' : '' }}
                                   class="toggle-switch">
                        </div>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label for="contact_title" class="form-label">Section Title</label>
                                <input type="text" id="contact_title" name="contact_title" 
                                       value="{{ old('contact_title', $settings['contact_title']) }}"
                                       class="input-field w-full" placeholder="Get In Touch">
                            </div>
                            <div class="space-y-2">
                                <label for="contact_subtitle" class="form-label">Section Subtitle</label>
                                <input type="text" id="contact_subtitle" name="contact_subtitle" 
                                       value="{{ old('contact_subtitle', $settings['contact_subtitle']) }}"
                                       class="input-field w-full" placeholder="Have questions? We're here to help!">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label for="contact_email" class="form-label">Email</label>
                                <input type="email" id="contact_email" name="contact_email" 
                                       value="{{ old('contact_email', $settings['contact_email']) }}"
                                       class="input-field w-full" placeholder="info@baraka.co">
                            </div>
                            <div class="space-y-2">
                                <label for="contact_phone" class="form-label">Phone</label>
                                <input type="text" id="contact_phone" name="contact_phone" 
                                       value="{{ old('contact_phone', $settings['contact_phone']) }}"
                                       class="input-field w-full" placeholder="+256 700 000 000">
                            </div>
                            <div class="space-y-2">
                                <label for="contact_whatsapp" class="form-label">WhatsApp</label>
                                <input type="text" id="contact_whatsapp" name="contact_whatsapp" 
                                       value="{{ old('contact_whatsapp', $settings['contact_whatsapp']) }}"
                                       class="input-field w-full" placeholder="+256 700 000 000">
                            </div>
                            <div class="space-y-2">
                                <label for="contact_hours" class="form-label">Business Hours</label>
                                <input type="text" id="contact_hours" name="contact_hours" 
                                       value="{{ old('contact_hours', $settings['contact_hours']) }}"
                                       class="input-field w-full" placeholder="Mon - Fri: 8AM - 6PM">
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label for="contact_address" class="form-label">Address</label>
                            <textarea id="contact_address" name="contact_address" rows="2"
                                      class="input-field w-full resize-none"
                                      placeholder="Full business address">{{ old('contact_address', $settings['contact_address']) }}</textarea>
                        </div>
                        <div class="space-y-2">
                            <label for="contact_map_embed" class="form-label">Google Maps Embed URL</label>
                            <input type="text" id="contact_map_embed" name="contact_map_embed" 
                                   value="{{ old('contact_map_embed', $settings['contact_map_embed']) }}"
                                   class="input-field w-full" placeholder="https://www.google.com/maps/embed?...">
                            <p class="text-xs text-slate-500">Paste the embed URL from Google Maps</p>
                        </div>
                    </div>
                </section>

                <!-- Social Links -->
                <section class="settings-card">
                    <div class="settings-card-header">
                        <div class="settings-card-icon bg-blue-500/10 text-blue-600 dark:text-blue-400">
                            <i class="bi bi-share"></i>
                        </div>
                        <h2 class="settings-card-title">Social Media Links</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach(['facebook' => 'Facebook', 'twitter' => 'Twitter/X', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'youtube' => 'YouTube', 'tiktok' => 'TikTok'] as $key => $label)
                            <div class="space-y-2">
                                <label for="social_{{ $key }}" class="form-label flex items-center gap-2">
                                    <i class="bi bi-{{ $key }}"></i> {{ $label }}
                                </label>
                                <input type="url" id="social_{{ $key }}" name="social_{{ $key }}" 
                                       value="{{ old('social_'.$key, $settings['social_'.$key]) }}"
                                       class="input-field w-full" placeholder="https://{{ $key }}.com/...">
                            </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <!-- Footer Tab -->
        <div class="tab-content hidden" id="tab-footer">
            <div class="grid gap-6">
                <section class="settings-card">
                    <div class="settings-card-header">
                        <div class="settings-card-icon bg-slate-500/10 text-slate-600 dark:text-slate-400">
                            <i class="bi bi-card-text"></i>
                        </div>
                        <h2 class="settings-card-title">Footer Content</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="space-y-2">
                            <label for="footer_about" class="form-label">Footer About Text</label>
                            <textarea id="footer_about" name="footer_about" rows="3"
                                      class="input-field w-full resize-none"
                                      placeholder="Brief company description for footer">{{ old('footer_about', $settings['footer_about']) }}</textarea>
                        </div>
                        <div class="space-y-2">
                            <label for="footer_copyright" class="form-label">Copyright Text</label>
                            <input type="text" id="footer_copyright" name="footer_copyright" 
                                   value="{{ old('footer_copyright', $settings['footer_copyright']) }}"
                                   class="input-field w-full" placeholder="© 2024 Baraka Logistics. All rights reserved.">
                        </div>
                        <div class="space-y-2">
                            <label class="form-label">Footer Links</label>
                            <div id="footerLinksContainer" class="space-y-2">
                                @foreach($settings['footer_links'] ?? [] as $index => $link)
                                <div class="footer-link-item flex gap-3">
                                    <input type="text" name="footer_links[{{ $index }}][title]" value="{{ $link['title'] }}" 
                                           class="input-field flex-1" placeholder="Link Title">
                                    <input type="text" name="footer_links[{{ $index }}][url]" value="{{ $link['url'] }}" 
                                           class="input-field flex-1" placeholder="/page-url">
                                    <button type="button" class="text-rose-500 hover:text-rose-600" onclick="this.parentElement.remove()">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                @endforeach
                            </div>
                            <button type="button" onclick="addFooterLink()" class="btn-secondary text-sm">
                                <i class="bi bi-plus mr-1"></i>Add Link
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <!-- Analytics Tab -->
        <div class="tab-content hidden" id="tab-analytics">
            <div class="grid gap-6">
                <section class="settings-card">
                    <div class="settings-card-header">
                        <div class="settings-card-icon bg-orange-500/10 text-orange-600 dark:text-orange-400">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <h2 class="settings-card-title">Analytics & Tracking</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label for="google_analytics_id" class="form-label flex items-center gap-2">
                                    <i class="bi bi-google text-red-500"></i> Google Analytics ID
                                </label>
                                <input type="text" id="google_analytics_id" name="google_analytics_id" 
                                       value="{{ old('google_analytics_id', $settings['google_analytics_id']) }}"
                                       class="input-field w-full" placeholder="G-XXXXXXXXXX or UA-XXXXXXXX-X">
                            </div>
                            <div class="space-y-2">
                                <label for="google_tag_manager_id" class="form-label flex items-center gap-2">
                                    <i class="bi bi-google text-blue-500"></i> Google Tag Manager ID
                                </label>
                                <input type="text" id="google_tag_manager_id" name="google_tag_manager_id" 
                                       value="{{ old('google_tag_manager_id', $settings['google_tag_manager_id']) }}"
                                       class="input-field w-full" placeholder="GTM-XXXXXXX">
                            </div>
                            <div class="space-y-2">
                                <label for="facebook_pixel_id" class="form-label flex items-center gap-2">
                                    <i class="bi bi-facebook text-blue-600"></i> Facebook Pixel ID
                                </label>
                                <input type="text" id="facebook_pixel_id" name="facebook_pixel_id" 
                                       value="{{ old('facebook_pixel_id', $settings['facebook_pixel_id']) }}"
                                       class="input-field w-full" placeholder="XXXXXXXXXXXXXXXXX">
                            </div>
                            <div class="space-y-2">
                                <label for="hotjar_id" class="form-label flex items-center gap-2">
                                    <i class="bi bi-fire text-orange-500"></i> Hotjar Site ID
                                </label>
                                <input type="text" id="hotjar_id" name="hotjar_id" 
                                       value="{{ old('hotjar_id', $settings['hotjar_id']) }}"
                                       class="input-field w-full" placeholder="XXXXXXX">
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <!-- Advanced Tab -->
        <div class="tab-content hidden" id="tab-advanced">
            <div class="grid gap-6">
                <!-- Maintenance Mode -->
                <section class="settings-card">
                    <div class="settings-card-header">
                        <div class="settings-card-icon bg-rose-500/10 text-rose-600 dark:text-rose-400">
                            <i class="bi bi-tools"></i>
                        </div>
                        <h2 class="settings-card-title">Maintenance Mode</h2>
                        <div class="ml-auto">
                            <input type="checkbox" id="maintenance_mode" name="maintenance_mode" value="1"
                                   {{ ($settings['maintenance_mode'] ?? false) ? 'checked' : '' }}
                                   class="toggle-switch">
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="space-y-2">
                            <label for="maintenance_message" class="form-label">Maintenance Message</label>
                            <textarea id="maintenance_message" name="maintenance_message" rows="3"
                                      class="input-field w-full resize-none"
                                      placeholder="We're currently performing maintenance...">{{ old('maintenance_message', $settings['maintenance_message']) }}</textarea>
                        </div>
                    </div>
                </section>

                <!-- Custom Code -->
                <section class="settings-card">
                    <div class="settings-card-header">
                        <div class="settings-card-icon bg-violet-500/10 text-violet-600 dark:text-violet-400">
                            <i class="bi bi-code-slash"></i>
                        </div>
                        <h2 class="settings-card-title">Custom Code</h2>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="space-y-2">
                            <label for="custom_css" class="form-label">Custom CSS</label>
                            <textarea id="custom_css" name="custom_css" rows="6"
                                      class="input-field w-full font-mono text-sm resize-none"
                                      placeholder="/* Your custom CSS */">{{ old('custom_css', $settings['custom_css']) }}</textarea>
                        </div>
                        <div class="space-y-2">
                            <label for="custom_js_head" class="form-label">Custom JavaScript (Head)</label>
                            <textarea id="custom_js_head" name="custom_js_head" rows="4"
                                      class="input-field w-full font-mono text-sm resize-none"
                                      placeholder="// Scripts to load in <head>">{{ old('custom_js_head', $settings['custom_js_head']) }}</textarea>
                        </div>
                        <div class="space-y-2">
                            <label for="custom_js_body" class="form-label">Custom JavaScript (Body)</label>
                            <textarea id="custom_js_body" name="custom_js_body" rows="4"
                                      class="input-field w-full font-mono text-sm resize-none"
                                      placeholder="// Scripts to load before </body>">{{ old('custom_js_body', $settings['custom_js_body']) }}</textarea>
                        </div>
                    </div>
                </section>

                <!-- Robots.txt -->
                <section class="settings-card">
                    <div class="settings-card-header">
                        <div class="settings-card-icon bg-gray-500/10 text-gray-600 dark:text-gray-400">
                            <i class="bi bi-robot"></i>
                        </div>
                        <h2 class="settings-card-title">robots.txt</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-2">
                            <textarea id="robots_txt" name="robots_txt" rows="6"
                                      class="input-field w-full font-mono text-sm resize-none"
                                      placeholder="User-agent: *&#10;Allow: /">{{ old('robots_txt', $settings['robots_txt']) }}</textarea>
                            <p class="text-xs text-slate-500">Control how search engines crawl your site</p>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </form>
</div>

<style>
.tab-btn {
    @apply px-4 py-3 text-sm font-medium text-slate-500 dark:text-slate-400 border-b-2 border-transparent hover:text-slate-700 dark:hover:text-slate-200 hover:border-slate-300 transition-colors;
}
.tab-btn.active {
    @apply text-emerald-600 dark:text-emerald-400 border-emerald-500;
}
.tab-content {
    @apply transition-opacity duration-200;
}
.tab-content.hidden {
    @apply opacity-0 absolute pointer-events-none;
}
.settings-card {
    @apply bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden;
}
.settings-card-header {
    @apply px-6 py-4 border-b border-slate-100 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-800/50 flex items-center gap-3;
}
.settings-card-icon {
    @apply w-8 h-8 rounded-lg flex items-center justify-center;
}
.settings-card-title {
    @apply text-base font-semibold text-slate-900 dark:text-white;
}
.form-label {
    @apply text-sm font-medium text-slate-700 dark:text-slate-300;
}
.toggle-switch {
    @apply w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 dark:peer-focus:ring-emerald-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-emerald-500;
}
</style>

@push('scripts')
<script>
// Tab functionality
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const tabId = this.dataset.tab;
        
        // Update buttons
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        // Update content
        document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
        document.getElementById('tab-' + tabId).classList.remove('hidden');
    });
});

// Character count for meta description
const descInput = document.getElementById('site_description');
const descCount = document.getElementById('descCount');
if (descInput && descCount) {
    descCount.textContent = descInput.value.length;
    descInput.addEventListener('input', () => {
        descCount.textContent = descInput.value.length;
    });
}

// Dynamic item counters
let featureIndex = {{ count($settings['features'] ?? []) }};
let serviceIndex = {{ count($settings['services'] ?? []) }};
let testimonialIndex = {{ count($settings['testimonials'] ?? []) }};
let footerLinkIndex = {{ count($settings['footer_links'] ?? []) }};

function addFeature() {
    const container = document.getElementById('featuresContainer');
    const html = `
        <div class="feature-item flex gap-3 p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
            <input type="text" name="features[${featureIndex}][icon]" class="input-field w-24" placeholder="Icon">
            <input type="text" name="features[${featureIndex}][title]" class="input-field flex-1" placeholder="Title">
            <input type="text" name="features[${featureIndex}][description]" class="input-field flex-[2]" placeholder="Description">
            <button type="button" class="text-rose-500 hover:text-rose-600" onclick="this.parentElement.remove()">
                <i class="bi bi-trash"></i>
            </button>
        </div>`;
    container.insertAdjacentHTML('beforeend', html);
    featureIndex++;
}

function addService() {
    const container = document.getElementById('servicesContainer');
    const html = `
        <div class="service-item flex gap-3 p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg">
            <input type="text" name="services[${serviceIndex}][icon]" class="input-field w-24" placeholder="Icon">
            <input type="text" name="services[${serviceIndex}][title]" class="input-field flex-1" placeholder="Title">
            <input type="text" name="services[${serviceIndex}][description]" class="input-field flex-1" placeholder="Description">
            <input type="text" name="services[${serviceIndex}][price]" class="input-field w-32" placeholder="Price">
            <button type="button" class="text-rose-500 hover:text-rose-600" onclick="this.parentElement.remove()">
                <i class="bi bi-trash"></i>
            </button>
        </div>`;
    container.insertAdjacentHTML('beforeend', html);
    serviceIndex++;
}

function addTestimonial() {
    const container = document.getElementById('testimonialsContainer');
    const html = `
        <div class="testimonial-item p-4 bg-slate-50 dark:bg-slate-800/50 rounded-lg space-y-3">
            <div class="flex gap-3">
                <input type="text" name="testimonials[${testimonialIndex}][name]" class="input-field flex-1" placeholder="Name">
                <input type="text" name="testimonials[${testimonialIndex}][company]" class="input-field flex-1" placeholder="Company">
                <select name="testimonials[${testimonialIndex}][rating]" class="input-field w-20">
                    <option value="5">5★</option>
                    <option value="4">4★</option>
                    <option value="3">3★</option>
                    <option value="2">2★</option>
                    <option value="1">1★</option>
                </select>
                <button type="button" class="text-rose-500 hover:text-rose-600" onclick="this.closest('.testimonial-item').remove()">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
            <textarea name="testimonials[${testimonialIndex}][content]" rows="2" class="input-field w-full resize-none" placeholder="Testimonial content..."></textarea>
        </div>`;
    container.insertAdjacentHTML('beforeend', html);
    testimonialIndex++;
}

function addFooterLink() {
    const container = document.getElementById('footerLinksContainer');
    const html = `
        <div class="footer-link-item flex gap-3">
            <input type="text" name="footer_links[${footerLinkIndex}][title]" class="input-field flex-1" placeholder="Link Title">
            <input type="text" name="footer_links[${footerLinkIndex}][url]" class="input-field flex-1" placeholder="/page-url">
            <button type="button" class="text-rose-500 hover:text-rose-600" onclick="this.parentElement.remove()">
                <i class="bi bi-trash"></i>
            </button>
        </div>`;
    container.insertAdjacentHTML('beforeend', html);
    footerLinkIndex++;
}
</script>
@endpush
@endsection
