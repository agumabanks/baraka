@extends('settings.layouts.app')

@section('title', 'System Settings')

@section('page_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">
                <i class="fas fa-cog me-2 text-primary"></i>
                System Settings
            </h1>
            <p class="text-muted mb-0">Configure your application's settings and preferences</p>
        </div>
        <div class="page-actions">
            <button type="submit" form="settingsForm" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>Save Changes
            </button>
        </div>
    </div>
@endsection

@section('content')
<form id="settingsForm" method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="ajax-form settings-form-enhanced">
    @csrf
    @method('PUT')
    
    <!-- Settings Tabs Navigation -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="settings-tabs-container">
                <ul class="nav nav-pills nav-justified" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="general-tab" data-bs-toggle="pill" data-bs-target="#general" type="button" role="tab">
                            <i class="fas fa-sliders-h me-2"></i>
                            General
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="branding-tab" data-bs-toggle="pill" data-bs-target="#branding" type="button" role="tab">
                            <i class="fas fa-palette me-2"></i>
                            Branding
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="operations-tab" data-bs-toggle="pill" data-bs-target="#operations" type="button" role="tab">
                            <i class="fas fa-cogs me-2"></i>
                            Operations
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="finance-tab" data-bs-toggle="pill" data-bs-target="#finance" type="button" role="tab">
                            <i class="fas fa-dollar-sign me-2"></i>
                            Finance
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="notifications-tab" data-bs-toggle="pill" data-bs-target="#notifications" type="button" role="tab">
                            <i class="fas fa-bell me-2"></i>
                            Notifications
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="integrations-tab" data-bs-toggle="pill" data-bs-target="#integrations" type="button" role="tab">
                            <i class="fas fa-plug me-2"></i>
                            Integrations
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="system-tab" data-bs-toggle="pill" data-bs-target="#system" type="button" role="tab">
                            <i class="fas fa-server me-2"></i>
                            System
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="website-tab" data-bs-toggle="pill" data-bs-target="#website" type="button" role="tab">
                            <i class="fas fa-globe me-2"></i>
                            Website
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Settings Tabs Content -->
    <div class="tab-content" id="settingsTabsContent">
        <!-- General Settings Tab -->
        <div class="tab-pane fade show active" id="general" role="tabpanel">
            <x-settings.card title="Company Information" subtitle="Basic information about your organization">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-4">
                            <label for="name" class="form-label fw-semibold">Company Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $settings->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-4">
                            <label for="phone" class="form-label fw-semibold">Phone Number</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $settings->phone) }}">
                            @error('phone')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-4">
                            <label for="email" class="form-label fw-semibold">Email Address</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $settings->email) }}">
                            @error('email')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-4">
                            <label for="currency" class="form-label fw-semibold">Currency <span class="text-danger">*</span></label>
                            <select class="form-select @error('currency') is-invalid @enderror" id="currency" name="currency" required>
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency->code }}" {{ old('currency', $settings->currency) == $currency->code ? 'selected' : '' }}>
                                        {{ $currency->name }} ({{ $currency->symbol }})
                                    </option>
                                @endforeach
                            </select>
                            @error('currency')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-4">
                            <label for="par_track_prefix" class="form-label fw-semibold">Parcel Tracking Prefix</label>
                            <input type="text" class="form-control @error('par_track_prefix') is-invalid @enderror" id="par_track_prefix" name="par_track_prefix" value="{{ old('par_track_prefix', $settings->par_track_prefix) }}" placeholder="BRK">
                            @error('par_track_prefix')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-4">
                            <label for="invoice_prefix" class="form-label fw-semibold">Invoice Prefix</label>
                            <input type="text" class="form-control @error('invoice_prefix') is-invalid @enderror" id="invoice_prefix" name="invoice_prefix" value="{{ old('invoice_prefix', $settings->invoice_prefix) }}" placeholder="INV">
                            @error('invoice_prefix')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="address" class="form-label fw-semibold">Address</label>
                    <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3">{{ old('address', $settings->address) }}</textarea>
                    @error('address')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-4">
                    <label for="copyright" class="form-label fw-semibold">Copyright Notice</label>
                    <input type="text" class="form-control @error('copyright') is-invalid @enderror" id="copyright" name="copyright" value="{{ old('copyright', $settings->copyright) }}" placeholder="© 2024 Your Company">
                    @error('copyright')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </x-settings.card>
            
            <x-settings.card title="General Preferences" subtitle="General application preferences">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-4">
                            <label for="preferences.general.tagline" class="form-label fw-semibold">Company Tagline</label>
                            <input type="text" class="form-control" id="preferences.general.tagline" name="preferences[general][tagline]" value="{{ old('preferences.general.tagline', $settings->details['general']['tagline'] ?? '') }}" placeholder="Your company slogan">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-4">
                            <label for="preferences.general.support_email" class="form-label fw-semibold">Support Email</label>
                            <input type="email" class="form-control" id="preferences.general.support_email" name="preferences[general][support_email]" value="{{ old('preferences.general.support_email', $settings->details['general']['support_email'] ?? '') }}" placeholder="support@company.com">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-4">
                            <label for="preferences.general.timezone" class="form-label fw-semibold">Timezone</label>
                            <select class="form-select" id="preferences.general.timezone" name="preferences[general][timezone]">
                                @php
                                    $timezones = [
                                        'Africa/Nairobi' => 'East Africa Time (EAT)',
                                        'UTC' => 'Coordinated Universal Time (UTC)',
                                        'America/New_York' => 'Eastern Time (ET)',
                                        'Europe/London' => 'Greenwich Mean Time (GMT)',
                                        'Asia/Tokyo' => 'Japan Standard Time (JST)'
                                    ];
                                @endphp
                                @foreach($timezones as $tz => $label)
                                    <option value="{{ $tz }}" {{ old('preferences.general.timezone', $settings->details['general']['timezone'] ?? 'Africa/Nairobi') == $tz ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-4">
                            <label for="preferences.general.country" class="form-label fw-semibold">Country</label>
                            <input type="text" class="form-control" id="preferences.general.country" name="preferences[general][country]" value="{{ old('preferences.general.country', $settings->details['general']['country'] ?? '') }}" placeholder="Uganda">
                        </div>
                    </div>
                </div>
            </x-settings.card>
        </div>
        
        <!-- Branding Settings Tab -->
        <div class="tab-pane fade" id="branding" role="tabpanel">
            <x-settings.card title="Logo & Visual Identity" subtitle="Upload logos and customize visual appearance">
                <div class="row">
                    <div class="col-md-4">
                        <x-settings.upload 
                            name="logo" 
                            label="Main Logo" 
                            :existing="$settings->logo ? asset($settings->logo) : null"
                            icon="fas fa-image"
                            accept="image/*"
                            description="Recommended: 200x60px, PNG with transparent background"
                        />
                    </div>
                    <div class="col-md-4">
                        <x-settings.upload 
                            name="light_logo" 
                            label="Light Logo" 
                            :existing="$settings->light_logo ? asset($settings->light_logo) : null"
                            icon="fas fa-image"
                            accept="image/*"
                            description="For dark backgrounds, Recommended: 200x60px"
                        />
                    </div>
                    <div class="col-md-4">
                        <x-settings.upload 
                            name="favicon" 
                            label="Favicon" 
                            :existing="$settings->favicon ? asset($settings->favicon) : null"
                            icon="fas fa-star"
                            accept="image/x-icon,image/png,image/svg+xml"
                            description="Recommended: 32x32px, ICO or PNG format"
                        />
                    </div>
                </div>
            </x-settings.card>
            
            <x-settings.card title="Colors & Theme" subtitle="Customize colors and visual theme">
                <div class="row">
                    <div class="col-md-6">
                        <x-settings.color-picker 
                            name="primary_color" 
                            label="Primary Color" 
                            :value="old('primary_color', $settings->primary_color)"
                            help="Used for buttons, links, and accent elements"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-settings.color-picker 
                            name="text_color" 
                            label="Text Color" 
                            :value="old('text_color', $settings->text_color)"
                            help="Used for text on primary colored backgrounds"
                        />
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <x-settings.toggle 
                            name="preferences[branding][enable_animations]"
                            label="Enable Animations"
                            :checked="old('preferences.branding.enable_animations', $settings->details['branding']['enable_animations'] ?? true)"
                            help="Enable smooth transitions and animations"
                            icon="fas fa-magic"
                        />
                    </div>
                    <div class="col-md-6">
                        <div class="mb-4">
                            <label for="preferences.branding.theme" class="form-label fw-semibold">Theme</label>
                            <select class="form-select" id="preferences.branding.theme" name="preferences[branding][theme]">
                                <option value="light" {{ old('preferences.branding.theme', $settings->details['branding']['theme'] ?? 'light') == 'light' ? 'selected' : '' }}>Light</option>
                                <option value="dark" {{ old('preferences.branding.theme', $settings->details['branding']['theme'] ?? 'light') == 'dark' ? 'selected' : '' }}>Dark</option>
                                <option value="auto" {{ old('preferences.branding.theme', $settings->details['branding']['theme'] ?? 'light') == 'auto' ? 'selected' : '' }}>Auto (System)</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-4">
                            <label for="preferences.branding.sidebar_density" class="form-label fw-semibold">Sidebar Density</label>
                            <select class="form-select" id="preferences.branding.sidebar_density" name="preferences[branding][sidebar_density]">
                                <option value="compact" {{ old('preferences.branding.sidebar_density', $settings->details['branding']['sidebar_density'] ?? 'comfortable') == 'compact' ? 'selected' : '' }}>Compact</option>
                                <option value="comfortable" {{ old('preferences.branding.sidebar_density', $settings->details['branding']['sidebar_density'] ?? 'comfortable') == 'comfortable' ? 'selected' : '' }}>Comfortable</option>
                                <option value="spacious" {{ old('preferences.branding.sidebar_density', $settings->details['branding']['sidebar_density'] ?? 'comfortable') == 'spacious' ? 'selected' : '' }}>Spacious</option>
                            </select>
                        </div>
                    </div>
                </div>
            </x-settings.card>
        </div>
        
        <!-- Operations Settings Tab -->
        <div class="tab-pane fade" id="operations" role="tabpanel">
            <x-settings.card title="Operations Configuration" subtitle="Configure operational workflows and automation">
                <div class="row">
                    <div class="col-md-6">
                        <x-settings.toggle 
                            name="preferences[operations][auto_assign_drivers]"
                            label="Auto-Assign Drivers"
                            :checked="old('preferences.operations.auto_assign_drivers', $settings->details['operations']['auto_assign_drivers'] ?? false)"
                            help="Automatically assign available drivers to new deliveries"
                            icon="fas fa-user-plus"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-settings.toggle 
                            name="preferences[operations][enable_capacity_management]"
                            label="Enable Capacity Management"
                            :checked="old('preferences.operations.enable_capacity_management', $settings->details['operations']['enable_capacity_management'] ?? false)"
                            help="Track and manage vehicle/driver capacity"
                            icon="fas fa-truck"
                        />
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <x-settings.toggle 
                            name="preferences[operations][require_dispatch_approval]"
                            label="Require Dispatch Approval"
                            :checked="old('preferences.operations.require_dispatch_approval', $settings->details['operations']['require_dispatch_approval'] ?? true)"
                            help="Require approval before dispatching deliveries"
                            icon="fas fa-check-circle"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-settings.toggle 
                            name="preferences[operations][auto_generate_tracking_ids]"
                            label="Auto-Generate Tracking IDs"
                            :checked="old('preferences.operations.auto_generate_tracking_ids', $settings->details['operations']['auto_generate_tracking_ids'] ?? true)"
                            help="Automatically generate tracking IDs for new orders"
                            icon="fas fa-barcode"
                        />
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <x-settings.toggle 
                            name="preferences[operations][enforce_pod_otp]"
                            label="Enforce POD OTP"
                            :checked="old('preferences.operations.enforce_pod_otp', $settings->details['operations']['enforce_pod_otp'] ?? true)"
                            help="Require OTP verification for proof of delivery"
                            icon="fas fa-shield-alt"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-settings.toggle 
                            name="preferences[operations][allow_public_tracking]"
                            label="Allow Public Tracking"
                            :checked="old('preferences.operations.allow_public_tracking', $settings->details['operations']['allow_public_tracking'] ?? true)"
                            help="Allow customers to track deliveries without login"
                            icon="fas fa-search"
                        />
                    </div>
                </div>
            </x-settings.card>
        </div>
        
        <!-- Finance Settings Tab -->
        <div class="tab-pane fade" id="finance" role="tabpanel">
            <x-settings.card title="Financial Configuration" subtitle="Configure financial workflows and settings">
                <div class="row">
                    <div class="col-md-6">
                        <x-settings.toggle 
                            name="preferences[finance][auto_reconcile]"
                            label="Auto Reconcile"
                            :checked="old('preferences.finance.auto_reconcile', $settings->details['finance']['auto_reconcile'] ?? false)"
                            help="Automatically reconcile payments and transactions"
                            icon="fas fa-sync"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-settings.toggle 
                            name="preferences[finance][enforce_cod_settlement_workflow]"
                            label="Enforce COD Settlement Workflow"
                            :checked="old('preferences.finance.enforce_cod_settlement_workflow', $settings->details['finance']['enforce_cod_settlement_workflow'] ?? true)"
                            help="Require approval for COD settlements"
                            icon="fas fa-hand-holding-usd"
                        />
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <x-settings.toggle 
                            name="preferences[finance][enable_invoice_emails]"
                            label="Enable Invoice Emails"
                            :checked="old('preferences.finance.enable_invoice_emails', $settings->details['finance']['enable_invoice_emails'] ?? true)"
                            help="Automatically send invoice emails to customers"
                            icon="fas fa-envelope"
                        />
                    </div>
                    <div class="col-md-6">
                        <div class="mb-4">
                            <label for="preferences.finance.default_tax_rate" class="form-label fw-semibold">Default Tax Rate (%)</label>
                            <input type="number" class="form-control" id="preferences.finance.default_tax_rate" name="preferences[finance][default_tax_rate]" value="{{ old('preferences.finance.default_tax_rate', $settings->details['finance']['default_tax_rate'] ?? 0) }}" min="0" max="100" step="0.01">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-4">
                            <label for="preferences.finance.rounding_mode" class="form-label fw-semibold">Rounding Mode</label>
                            <select class="form-select" id="preferences.finance.rounding_mode" name="preferences[finance][rounding_mode]">
                                <option value="nearest" {{ old('preferences.finance.rounding_mode', $settings->details['finance']['rounding_mode'] ?? 'nearest') == 'nearest' ? 'selected' : '' }}>Nearest</option>
                                <option value="up" {{ old('preferences.finance.rounding_mode', $settings->details['finance']['rounding_mode'] ?? 'nearest') == 'up' ? 'selected' : '' }}>Round Up</option>
                                <option value="down" {{ old('preferences.finance.rounding_mode', $settings->details['finance']['rounding_mode'] ?? 'nearest') == 'down' ? 'selected' : '' }}>Round Down</option>
                            </select>
                        </div>
                    </div>
                </div>
            </x-settings.card>
        </div>
        
        <!-- Notifications Settings Tab -->
        <div class="tab-pane fade" id="notifications" role="tabpanel">
            <x-settings.card title="Notification Preferences" subtitle="Configure how notifications are sent">
                <div class="row">
                    <div class="col-md-4">
                        <x-settings.toggle 
                            name="preferences[notifications][email]"
                            label="Email Notifications"
                            :checked="old('preferences.notifications.email', $settings->details['notifications']['email'] ?? true)"
                            help="Send notifications via email"
                            icon="fas fa-envelope"
                        />
                    </div>
                    <div class="col-md-4">
                        <x-settings.toggle 
                            name="preferences[notifications][sms]"
                            label="SMS Notifications"
                            :checked="old('preferences.notifications.sms', $settings->details['notifications']['sms'] ?? false)"
                            help="Send notifications via SMS"
                            icon="fas fa-sms"
                        />
                    </div>
                    <div class="col-md-4">
                        <x-settings.toggle 
                            name="preferences[notifications][push]"
                            label="Push Notifications"
                            :checked="old('preferences.notifications.push', $settings->details['notifications']['push'] ?? true)"
                            help="Send push notifications"
                            icon="fas fa-bell"
                        />
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <x-settings.toggle 
                            name="preferences[notifications][daily_digest]"
                            label="Daily Digest"
                            :checked="old('preferences.notifications.daily_digest', $settings->details['notifications']['daily_digest'] ?? true)"
                            help="Send daily summary of activities"
                            icon="fas fa-calendar-day"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-settings.toggle 
                            name="preferences[notifications][escalate_incidents]"
                            label="Escalate Incidents"
                            :checked="old('preferences.notifications.escalate_incidents', $settings->details['notifications']['escalate_incidents'] ?? false)"
                            help="Automatically escalate critical incidents"
                            icon="fas fa-exclamation-triangle"
                        />
                    </div>
                </div>
            </x-settings.card>
        </div>
        
        <!-- Integrations Settings Tab -->
        <div class="tab-pane fade" id="integrations" role="tabpanel">
            <x-settings.card title="Webhook Configuration" subtitle="Configure webhook endpoints and integrations">
                <div class="row">
                    <div class="col-md-6">
                        <x-settings.toggle 
                            name="preferences[integrations][webhooks_enabled]"
                            label="Enable Webhooks"
                            :checked="old('preferences.integrations.webhooks_enabled', $settings->details['integrations']['webhooks_enabled'] ?? true)"
                            help="Enable webhook notifications to external systems"
                            icon="fas fa-globe"
                        />
                    </div>
                    <div class="col-md-6">
                        <div class="mb-4">
                            <label for="preferences.integrations.webhooks_url" class="form-label fw-semibold">Webhook URL</label>
                            <input type="url" class="form-control" id="preferences.integrations.webhooks_url" name="preferences[integrations][webhooks_url]" value="{{ old('preferences.integrations.webhooks_url', $settings->details['integrations']['webhooks_url'] ?? '') }}" placeholder="https://your-app.com/webhooks">
                        </div>
                    </div>
                </div>
            </x-settings.card>
            
            <x-settings.card title="Third-Party Integrations" subtitle="Configure external service integrations">
                <div class="row">
                    <div class="col-md-6">
                        <x-settings.toggle 
                            name="preferences[integrations][slack_enabled]"
                            label="Slack Integration"
                            :checked="old('preferences.integrations.slack_enabled', $settings->details['integrations']['slack_enabled'] ?? false)"
                            help="Send notifications to Slack"
                            icon="fab fa-slack"
                        />
                    </div>
                    <div class="col-md-6">
                        <div class="mb-4">
                            <label for="preferences.integrations.slack_channel" class="form-label fw-semibold">Slack Channel</label>
                            <input type="text" class="form-control" id="preferences.integrations.slack_channel" name="preferences[integrations][slack_channel]" value="{{ old('preferences.integrations.slack_channel', $settings->details['integrations']['slack_channel'] ?? '') }}" placeholder="#notifications">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <x-settings.toggle 
                            name="preferences[integrations][power_bi_enabled]"
                            label="Power BI Integration"
                            :checked="old('preferences.integrations.power_bi_enabled', $settings->details['integrations']['power_bi_enabled'] ?? false)"
                            help="Enable Power BI reporting integration"
                            icon="fas fa-chart-bar"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-settings.toggle 
                            name="preferences[integrations][zapier_enabled]"
                            label="Zapier Integration"
                            :checked="old('preferences.integrations.zapier_enabled', $settings->details['integrations']['zapier_enabled'] ?? false)"
                            help="Enable Zapier automation"
                            icon="fas fa-bolt"
                        />
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="preferences.integrations.analytics_tracking_id" class="form-label fw-semibold">Analytics Tracking ID</label>
                    <input type="text" class="form-control" id="preferences.integrations.analytics_tracking_id" name="preferences[integrations][analytics_tracking_id]" value="{{ old('preferences.integrations.analytics_tracking_id', $settings->details['integrations']['analytics_tracking_id'] ?? '') }}" placeholder="G-XXXXXXXXXX">
                    <small class="form-text text-muted">Google Analytics tracking ID</small>
                </div>
            </x-settings.card>
        </div>
        
        <!-- System Settings Tab -->
        <div class="tab-pane fade" id="system" role="tabpanel">
            <x-settings.card title="Security Settings" subtitle="Configure security and access controls">
                <div class="row">
                    <div class="col-md-6">
                        <x-settings.toggle 
                            name="preferences[system][maintenance_mode]"
                            label="Maintenance Mode"
                            :checked="old('preferences.system.maintenance_mode', $settings->details['system']['maintenance_mode'] ?? false)"
                            help="Enable maintenance mode to restrict access"
                            icon="fas fa-tools"
                        />
                    </div>
                    <div class="col-md-6">
                        <x-settings.toggle 
                            name="preferences[system][two_factor_required]"
                            label="Require Two-Factor Authentication"
                            :checked="old('preferences.system.two_factor_required', $settings->details['system']['two_factor_required'] ?? false)"
                            help="Require 2FA for all admin users"
                            icon="fas fa-mobile-alt"
                        />
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <x-settings.toggle 
                            name="preferences[system][allow_self_service]"
                            label="Allow Self-Service"
                            :checked="old('preferences.system.allow_self_service', $settings->details['system']['allow_self_service'] ?? true)"
                            help="Allow users to manage their own accounts"
                            icon="fas fa-user-cog"
                        />
                    </div>
                    <div class="col-md-6">
                        <div class="mb-4">
                            <label for="preferences.system.auto_logout_minutes" class="form-label fw-semibold">Auto Logout (minutes)</label>
                            <input type="number" class="form-control" id="preferences.system.auto_logout_minutes" name="preferences[system][auto_logout_minutes]" value="{{ old('preferences.system.auto_logout_minutes', $settings->details['system']['auto_logout_minutes'] ?? 60) }}" min="5" max="480">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-4">
                            <label for="preferences.system.data_retention_days" class="form-label fw-semibold">Data Retention (days)</label>
                            <input type="number" class="form-control" id="preferences.system.data_retention_days" name="preferences[system][data_retention_days]" value="{{ old('preferences.system.data_retention_days', $settings->details['system']['data_retention_days'] ?? 365) }}" min="30" max="2555">
                            <small class="form-text text-muted">How long to retain logs and historical data</small>
                        </div>
                    </div>
                </div>
            </x-settings.card>
        </div>
        
        <!-- Website Settings Tab -->
        <div class="tab-pane fade" id="website" role="tabpanel">
            <x-settings.card title="Website Content" subtitle="Configure public website content and messaging">
                <div class="mb-4">
                    <label for="preferences.website.hero_title" class="form-label fw-semibold">Hero Title</label>
                    <input type="text" class="form-control" id="preferences.website.hero_title" name="preferences[website][hero_title]" value="{{ old('preferences.website.hero_title', $settings->details['website']['hero_title'] ?? '') }}" placeholder="Deliver with confidence">
                </div>
                
                <div class="mb-4">
                    <label for="preferences.website.hero_subtitle" class="form-label fw-semibold">Hero Subtitle</label>
                    <textarea class="form-control" id="preferences.website.hero_subtitle" name="preferences[website][hero_subtitle]" rows="3" placeholder="Baraka routes, tracks, and reconciles every parcel in real time.">{{ old('preferences.website.hero_subtitle', $settings->details['website']['hero_subtitle'] ?? '') }}</textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-4">
                            <label for="preferences.website.hero_cta_label" class="form-label fw-semibold">Hero CTA Button Label</label>
                            <input type="text" class="form-control" id="preferences.website.hero_cta_label" name="preferences[website][hero_cta_label]" value="{{ old('preferences.website.hero_cta_label', $settings->details['website']['hero_cta_label'] ?? '') }}" placeholder="Book a pickup">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-4">
                            <label for="preferences.website.footer_note" class="form-label fw-semibold">Footer Note</label>
                            <input type="text" class="form-control" id="preferences.website.footer_note" name="preferences[website][footer_note]" value="{{ old('preferences.website.footer_note', $settings->details['website']['footer_note'] ?? '') }}" placeholder="Baraka ERP v1.0 • Crafted in Kampala">
                        </div>
                    </div>
                </div>
            </x-settings.card>
        </div>
    </div>
</form>
@endsection

@push('styles')
<style>
.settings-tabs-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    padding: 0.5rem;
}

.nav-pills .nav-link {
    border-radius: 8px;
    padding: 0.75rem 1rem;
    color: #6c757d;
    font-weight: 500;
    transition: all 0.3s ease;
    margin: 0 0.25rem;
    position: relative;
}

.nav-pills .nav-link:hover {
    background-color: #f8f9fa;
    color: var(--primary-color);
    transform: translateY(-2px);
}

.nav-pills .nav-link.active {
    background: linear-gradient(135deg, var(--primary-color), #0056b3);
    color: white;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
}

.nav-pills .nav-link i {
    opacity: 0.8;
}

.nav-pills .nav-link.active i {
    opacity: 1;
}

.tab-content {
    margin-top: 2rem;
}

@media (max-width: 768px) {
    .nav-pills {
        flex-direction: column;
    }
    
    .nav-pills .nav-link {
        margin: 0.25rem 0;
    }
}
</style>
@endpush
