@extends('settings.layouts.app')

@section('title', 'Integrations')

@section('breadcrumb_current')
    <li class="breadcrumb-item active">Integrations</li>
@endsection

@section('page_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">
                <i class="fas fa-plug me-2 text-primary"></i>
                Integrations
            </h1>
            <p class="text-muted mb-0">Connect Baraka to external platforms</p>
        </div>
        <div class="page-actions">
            <button type="submit" form="integrationsSettingsForm" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>Save Changes
            </button>
        </div>
    </div>
@endsection

@section('content')
    <form id="integrationsSettingsForm" method="POST" action="{{ route('settings.integrations.update') }}" class="ajax-form settings-form-enhanced">
        @csrf

        <x-settings.card title="Stripe" subtitle="Card processing and billing integration">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-4">
                        <label for="stripe_public_key" class="form-label fw-semibold">Public Key</label>
                        <input
                            type="text"
                            id="stripe_public_key"
                            name="stripe[public_key]"
                            class="form-control @error('stripe.public_key') is-invalid @enderror"
                            value="{{ old('stripe.public_key', $integrations['stripe']['public_key'] ?? '') }}"
                        >
                        @error('stripe.public_key')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-4">
                        <label for="stripe_secret_key" class="form-label fw-semibold">Secret Key</label>
                        <input
                            type="password"
                            id="stripe_secret_key"
                            name="stripe[secret_key]"
                            class="form-control @error('stripe.secret_key') is-invalid @enderror"
                            value="{{ old('stripe.secret_key') }}"
                        >
                        @error('stripe.secret_key')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </x-settings.card>

        <x-settings.card title="PayPal" subtitle="Alternative payment gateway">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-4">
                        <label for="paypal_client_id" class="form-label fw-semibold">Client ID</label>
                        <input
                            type="text"
                            id="paypal_client_id"
                            name="paypal[client_id]"
                            class="form-control @error('paypal.client_id') is-invalid @enderror"
                            value="{{ old('paypal.client_id', $integrations['paypal']['client_id'] ?? '') }}"
                        >
                        @error('paypal.client_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-4">
                        <label for="paypal_client_secret" class="form-label fw-semibold">Client Secret</label>
                        <input
                            type="password"
                            id="paypal_client_secret"
                            name="paypal[client_secret]"
                            class="form-control @error('paypal.client_secret') is-invalid @enderror"
                            value="{{ old('paypal.client_secret') }}"
                        >
                        @error('paypal.client_secret')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </x-settings.card>

        <x-settings.card title="Google" subtitle="Analytics and Maps">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-4">
                        <label for="google_analytics_id" class="form-label fw-semibold">Analytics ID</label>
                        <input
                            type="text"
                            id="google_analytics_id"
                            name="google[analytics_id]"
                            class="form-control @error('google.analytics_id') is-invalid @enderror"
                            value="{{ old('google.analytics_id', $integrations['google']['analytics_id'] ?? '') }}"
                        >
                        @error('google.analytics_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-4">
                        <label for="google_maps_api_key" class="form-label fw-semibold">Maps API Key</label>
                        <input
                            type="text"
                            id="google_maps_api_key"
                            name="google[maps_api_key]"
                            class="form-control @error('google.maps_api_key') is-invalid @enderror"
                            value="{{ old('google.maps_api_key', $integrations['google']['maps_api_key'] ?? '') }}"
                        >
                        @error('google.maps_api_key')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </x-settings.card>
    </form>
@endsection
