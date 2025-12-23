@php
    // $settings is passed from controller with branding settings
    $s = $settings ?? [];
@endphp

@extends('settings.layouts.tailwind')

@section('title', 'Appearance')
@section('header', 'Appearance')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center shadow-lg">
                <i class="bi bi-palette-fill text-2xl text-white"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Appearance</h1>
                <p class="text-slate-500 dark:text-slate-400">Customize the look and feel of your application</p>
            </div>
        </div>
        <button type="submit" form="brandingForm" class="btn-primary shadow-lg shadow-purple-500/25">
            <i class="bi bi-check-lg mr-2"></i>
            Save Changes
        </button>
    </div>

    <form id="brandingForm" method="POST" action="{{ route('settings.branding.update') }}" enctype="multipart/form-data" class="ajax-form space-y-6">
        @csrf

        <!-- Theme -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-moon-stars text-indigo-500"></i>
                <div>
                    <h3 class="pref-card-title">Theme</h3>
                    <p class="pref-card-desc">Choose how the interface looks</p>
                </div>
            </div>
            <div class="pref-card-body">
                <div class="grid grid-cols-3 gap-4">
                    @foreach([
                        ['value' => 'light', 'label' => 'Light', 'icon' => 'sun-fill', 'preview' => 'bg-white border-slate-200'],
                        ['value' => 'dark', 'label' => 'Dark', 'icon' => 'moon-fill', 'preview' => 'bg-slate-800 border-slate-700'],
                        ['value' => 'auto', 'label' => 'Auto', 'icon' => 'circle-half', 'preview' => 'bg-gradient-to-r from-white to-slate-800 border-slate-300'],
                    ] as $theme)
                        <label class="relative cursor-pointer group">
                            <input type="radio" name="theme" value="{{ $theme['value'] }}" class="peer sr-only" {{ ($s['theme'] ?? 'auto') === $theme['value'] ? 'checked' : '' }}>
                            <div class="p-4 rounded-2xl border-2 border-slate-200 dark:border-slate-700 peer-checked:border-purple-500 peer-checked:bg-purple-50 dark:peer-checked:bg-purple-900/20 transition-all">
                                <div class="w-full h-16 rounded-xl {{ $theme['preview'] }} border mb-3"></div>
                                <div class="flex items-center justify-center gap-2">
                                    <i class="bi bi-{{ $theme['icon'] }} text-slate-500 peer-checked:text-purple-500"></i>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ $theme['label'] }}</span>
                                </div>
                            </div>
                            <div class="absolute top-3 right-3 w-5 h-5 rounded-full bg-purple-500 text-white flex items-center justify-center opacity-0 peer-checked:opacity-100 transition-opacity">
                                <i class="bi bi-check text-xs"></i>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Brand Identity -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-building text-blue-500"></i>
                <div>
                    <h3 class="pref-card-title">Brand Identity</h3>
                    <p class="pref-card-desc">Your company's visual identity</p>
                </div>
            </div>
            <div class="pref-card-body space-y-5">
                <div class="pref-row">
                    <div class="pref-label">
                        <span>Company Name</span>
                        <span class="pref-hint">Displayed in headers and documents</span>
                    </div>
                    <div class="pref-control w-64">
                        <input type="text" name="company_name" value="{{ $s['company_name'] ?? config('app.name') }}" 
                               class="input-field w-full">
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Tagline</span>
                        <span class="pref-hint">A short slogan or motto</span>
                    </div>
                    <div class="pref-control w-80">
                        <input type="text" name="tagline" value="{{ $s['tagline'] ?? '' }}" 
                               class="input-field w-full" placeholder="Your trusted delivery partner">
                    </div>
                </div>
            </div>
        </div>

        <!-- Colors -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-palette2 text-pink-500"></i>
                <div>
                    <h3 class="pref-card-title">Brand Colors</h3>
                    <p class="pref-card-desc">Define your color palette</p>
                </div>
            </div>
            <div class="pref-card-body space-y-5">
                <div class="pref-row">
                    <div class="pref-label">
                        <span>Primary Color</span>
                        <span class="pref-hint">Main brand color for buttons and links</span>
                    </div>
                    <div class="pref-control flex items-center gap-3">
                        <input type="color" name="primary_color" value="{{ $s['primary_color'] ?? '#3b82f6' }}" 
                               class="w-12 h-10 rounded-lg cursor-pointer border border-slate-300 dark:border-slate-600 p-1">
                        <input type="text" value="{{ $s['primary_color'] ?? '#3b82f6' }}" 
                               class="input-field w-28 font-mono text-sm uppercase" id="primary_color_text" readonly>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Secondary Color</span>
                        <span class="pref-hint">Accent color for secondary elements</span>
                    </div>
                    <div class="pref-control flex items-center gap-3">
                        <input type="color" name="secondary_color" value="{{ $s['secondary_color'] ?? '#64748b' }}" 
                               class="w-12 h-10 rounded-lg cursor-pointer border border-slate-300 dark:border-slate-600 p-1">
                        <input type="text" value="{{ $s['secondary_color'] ?? '#64748b' }}" 
                               class="input-field w-28 font-mono text-sm uppercase" id="secondary_color_text" readonly>
                    </div>
                </div>

                <!-- Color Preview -->
                <div class="mt-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Preview</p>
                    <div class="flex items-center gap-3">
                        <button type="button" id="preview-primary" class="px-4 py-2 rounded-lg text-white text-sm font-medium" style="background-color: {{ $s['primary_color'] ?? '#3b82f6' }}">
                            Primary Button
                        </button>
                        <button type="button" id="preview-secondary" class="px-4 py-2 rounded-lg text-white text-sm font-medium" style="background-color: {{ $s['secondary_color'] ?? '#64748b' }}">
                            Secondary Button
                        </button>
                        <a href="#" id="preview-link" class="text-sm font-medium" style="color: {{ $s['primary_color'] ?? '#3b82f6' }}">
                            Link Text
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logos -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-image text-amber-500"></i>
                <div>
                    <h3 class="pref-card-title">Logos & Images</h3>
                    <p class="pref-card-desc">Upload your brand assets</p>
                </div>
            </div>
            <div class="pref-card-body">
                @php
                    $logoFields = [
                        [
                            'name' => 'logo',
                            'label' => 'Main Logo (Default)',
                            'current' => $s['logo_url'] ?? null,
                            'help' => 'Used as the default logo across the system. Recommended: transparent PNG.',
                            'accept' => 'image/png,image/jpeg,image/webp',
                        ],
                        [
                            'name' => 'favicon',
                            'label' => 'Favicon',
                            'current' => $s['favicon_url'] ?? null,
                            'help' => 'ICO or PNG. Recommended: 32x32px or 64x64px.',
                            'accept' => 'image/png,image/x-icon',
                        ],
                        [
                            'name' => 'logo_admin',
                            'label' => 'Admin Portal Logo',
                            'current' => data_get($s, 'logos.admin'),
                            'help' => 'Used on admin login and admin sidebar. Leave empty to use Main Logo.',
                            'accept' => 'image/png,image/jpeg,image/webp',
                            'reset' => 'reset_logo_admin',
                        ],
                        [
                            'name' => 'logo_branch',
                            'label' => 'Branch Portal Logo',
                            'current' => data_get($s, 'logos.branch'),
                            'help' => 'Used on branch login and branch portal. Leave empty to use Main Logo.',
                            'accept' => 'image/png,image/jpeg,image/webp',
                            'reset' => 'reset_logo_branch',
                        ],
                        [
                            'name' => 'logo_client',
                            'label' => 'Customer Portal Logo',
                            'current' => data_get($s, 'logos.client'),
                            'help' => 'Used on customer login/register and customer portal. Leave empty to use Main Logo.',
                            'accept' => 'image/png,image/jpeg,image/webp',
                            'reset' => 'reset_logo_client',
                        ],
                        [
                            'name' => 'logo_landing',
                            'label' => 'Landing Page Logo',
                            'current' => data_get($s, 'logos.landing'),
                            'help' => 'Used on the marketing/landing page header. Leave empty to use Main Logo.',
                            'accept' => 'image/png,image/jpeg,image/webp',
                            'reset' => 'reset_logo_landing',
                        ],
                        [
                            'name' => 'logo_print',
                            'label' => 'Print & PDF Logo',
                            'current' => data_get($s, 'logos.print'),
                            'help' => 'Used on labels, receipts and PDF printouts. Leave empty to use Main Logo.',
                            'accept' => 'image/png,image/jpeg,image/webp',
                            'reset' => 'reset_logo_print',
                        ],
                    ];
                @endphp

                <div class="mb-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
                    <div class="flex items-start gap-3">
                        <i class="bi bi-lightbulb text-amber-500"></i>
                        <div class="text-xs text-slate-600 dark:text-slate-300">
                            <div class="font-semibold text-slate-700 dark:text-slate-200">Pro tip</div>
                            <div>Admin/Branch/Customer portals use dark backgrounds — upload a light/white logo for best visibility. Any logo left unset will fall back to the Main Logo.</div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($logoFields as $field)
                        <div class="space-y-3">
                            <div class="flex items-center justify-between gap-4">
                                <label class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ $field['label'] }}</label>
                                @if(!empty($field['reset']))
                                    <label class="inline-flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                                        <input type="checkbox" name="{{ $field['reset'] }}" value="1" class="rounded border-slate-300 dark:border-slate-600">
                                        Use Main Logo
                                    </label>
                                @endif
                            </div>
                            <div class="relative group">
                                <div class="w-full h-32 rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-600 flex items-center justify-center bg-slate-50 dark:bg-slate-800/50 group-hover:border-purple-400 transition-colors">
                                    @if(!empty($field['current']))
                                        <img src="{{ $field['current'] }}" alt="{{ $field['label'] }}" class="max-h-24 max-w-full object-contain">
                                    @else
                                        <div class="text-center">
                                            <i class="bi bi-cloud-arrow-up text-3xl text-slate-400"></i>
                                            <p class="text-xs text-slate-500 mt-1">Drop image here</p>
                                        </div>
                                    @endif
                                </div>
                                <input type="file" name="{{ $field['name'] }}" accept="{{ $field['accept'] }}"
                                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                            </div>
                            <p class="text-xs text-slate-500">{{ $field['help'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Typography -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-fonts text-cyan-500"></i>
                <div>
                    <h3 class="pref-card-title">Typography</h3>
                    <p class="pref-card-desc">Font preferences for the interface</p>
                </div>
            </div>
            <div class="pref-card-body space-y-5">
                <div class="pref-row">
                    <div class="pref-label">
                        <span>Font Family</span>
                        <span class="pref-hint">Primary font for the interface</span>
                    </div>
                    <div class="pref-control w-56">
                        <select name="font_family" class="input-field w-full">
                            @foreach(['system' => 'System Default', 'inter' => 'Inter', 'roboto' => 'Roboto', 'poppins' => 'Poppins', 'open-sans' => 'Open Sans'] as $val => $label)
                                <option value="{{ $val }}" {{ ($s['font_family'] ?? 'system') == $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Base Font Size</span>
                        <span class="pref-hint">Default text size</span>
                    </div>
                    <div class="pref-control w-32">
                        <select name="font_size" class="input-field w-full">
                            @foreach(['14' => '14px (Small)', '16' => '16px (Normal)', '18' => '18px (Large)'] as $val => $label)
                                <option value="{{ $val }}" {{ ($s['font_size'] ?? '16') == $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    // Sync color picker with text input and preview
    document.querySelectorAll('input[type="color"]').forEach(picker => {
        const textInput = picker.nextElementSibling;
        picker.addEventListener('input', (e) => {
            textInput.value = e.target.value.toUpperCase();
            // Update preview
            if (picker.name === 'primary_color') {
                document.getElementById('preview-primary').style.backgroundColor = e.target.value;
                document.getElementById('preview-link').style.color = e.target.value;
            } else if (picker.name === 'secondary_color') {
                document.getElementById('preview-secondary').style.backgroundColor = e.target.value;
            }
        });
    });
</script>
@endsection
