@extends('settings.layouts.app')

@section('title', 'Branding Settings')

@section('breadcrumb_current')
    <li class="breadcrumb-item active">Branding</li>
@endsection

@section('page_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">
                <i class="fas fa-palette me-2 text-primary"></i>
                Branding
            </h1>
            <p class="text-muted mb-0">Logos, colors, and visual identity</p>
        </div>
        <div class="page-actions">
            <button type="submit" form="brandingSettingsForm" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>Save Changes
            </button>
        </div>
    </div>
@endsection

@section('content')
    <form id="brandingSettingsForm" method="POST" action="{{ route('settings.branding.update') }}" enctype="multipart/form-data" class="ajax-form settings-form-enhanced">
        @csrf

        <x-settings.card title="Visual Identity" subtitle="Primary and secondary color palette">
            <div class="row">
                <div class="col-md-6">
                    <x-settings.color-picker
                        name="primary_color"
                        label="Primary Color"
                        :value="old('primary_color', $settings['primary_color'] ?? '#0d6efd')"
                        help="Used for primary buttons, highlights, and key accents."
                    />
                </div>
                <div class="col-md-6">
                    <x-settings.color-picker
                        name="secondary_color"
                        label="Secondary Color"
                        :value="old('secondary_color', $settings['secondary_color'] ?? '#6c757d')"
                        help="Used for secondary buttons and subtle accents."
                    />
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-4">
                        <label for="company_name" class="form-label fw-semibold">Company Name</label>
                        <input
                            type="text"
                            id="company_name"
                            name="company_name"
                            class="form-control @error('company_name') is-invalid @enderror"
                            value="{{ old('company_name', $settings['company_name'] ?? '') }}"
                        >
                        @error('company_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-4">
                        <label for="tagline" class="form-label fw-semibold">Tagline</label>
                        <input
                            type="text"
                            id="tagline"
                            name="tagline"
                            class="form-control @error('tagline') is-invalid @enderror"
                            value="{{ old('tagline', $settings['tagline'] ?? '') }}"
                        >
                        @error('tagline')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </x-settings.card>

        <x-settings.card title="Assets" subtitle="Logos and favicon">
            <div class="row">
                <div class="col-md-4">
                    <x-settings.upload
                        name="logo"
                        label="Primary Logo"
                        :current-path="$settings['logo_url'] ?? null"
                        help="PNG/SVG, at least 200x100px."
                    />
                </div>
                <div class="col-md-4">
                    <x-settings.upload
                        name="favicon"
                        label="Favicon"
                        :current-path="$settings['favicon_url'] ?? null"
                        help="Square image or ICO, 32x32px."
                    />
                </div>
            </div>
        </x-settings.card>
    </form>
@endsection
