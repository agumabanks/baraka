@extends('settings.layouts.app')

@section('title', 'Finance Settings')

@section('breadcrumb_current')
    <li class="breadcrumb-item active">Finance</li>
@endsection

@section('page_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">
                <i class="fas fa-dollar-sign me-2 text-primary"></i>
                Finance
            </h1>
            <p class="text-muted mb-0">Currencies, tax rates, and invoice configuration</p>
        </div>
        <div class="page-actions">
            <button type="submit" form="financeSettingsForm" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>Save Changes
            </button>
        </div>
    </div>
@endsection

@section('content')
    <form id="financeSettingsForm" method="POST" action="{{ route('settings.finance.update') }}" class="ajax-form settings-form-enhanced">
        @csrf

        <x-settings.card title="Currency & Tax" subtitle="Default currency and taxation behavior">
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-4">
                        <label for="default_currency" class="form-label fw-semibold">Default Currency</label>
                        <input
                            type="text"
                            id="default_currency"
                            name="default_currency"
                            class="form-control @error('default_currency') is-invalid @enderror"
                            value="{{ old('default_currency', $settings['default_currency'] ?? 'USD') }}"
                            maxlength="3"
                        >
                        @error('default_currency')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-4">
                        <label for="currency_symbol" class="form-label fw-semibold">Currency Symbol</label>
                        <input
                            type="text"
                            id="currency_symbol"
                            name="currency_symbol"
                            class="form-control @error('currency_symbol') is-invalid @enderror"
                            value="{{ old('currency_symbol', $settings['currency_symbol'] ?? '$') }}"
                            maxlength="1"
                        >
                        @error('currency_symbol')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-4">
                        <label for="tax_rate" class="form-label fw-semibold">Default Tax Rate (%)</label>
                        <input
                            type="number"
                            id="tax_rate"
                            name="tax_rate"
                            class="form-control @error('tax_rate') is-invalid @enderror"
                            value="{{ old('tax_rate', $settings['tax_rate'] ?? 0) }}"
                            min="0"
                            max="100"
                            step="0.01"
                        >
                        @error('tax_rate')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Enabled Payment Methods</label>
                        <input
                            type="text"
                            class="form-control"
                            value="{{ implode(',', $settings['payment_methods'] ?? ['stripe','paypal']) }}"
                            readonly
                        >
                        <small class="form-text text-muted">Payment methods are managed centrally for consistency.</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-4">
                        <label for="invoice_prefix" class="form-label fw-semibold">Invoice Prefix</label>
                        <input
                            type="text"
                            id="invoice_prefix"
                            name="invoice_prefix"
                            class="form-control @error('invoice_prefix') is-invalid @enderror"
                            value="{{ old('invoice_prefix', $settings['invoice_prefix'] ?? 'INV-') }}"
                            maxlength="10"
                        >
                        @error('invoice_prefix')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </x-settings.card>
    </form>
@endsection
