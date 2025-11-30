@php
    // $settings is passed from controller as the 'finance' section of preferences
    $s = $settings ?? [];
@endphp

@extends('settings.layouts.tailwind')

@section('title', 'Finance & Billing')
@section('header', 'Finance & Billing')

@section('content')
<div class="max-w-5xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-green-500 to-emerald-500 flex items-center justify-center shadow-lg">
                <i class="bi bi-currency-exchange text-2xl text-white"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Finance & Billing</h1>
                <p class="text-slate-500 dark:text-slate-400">Pricing, invoicing, taxes, and payment configuration</p>
            </div>
        </div>
        <button type="submit" form="financeForm" class="btn-primary shadow-lg shadow-green-500/25">
            <i class="bi bi-check-lg mr-2"></i>
            Save Changes
        </button>
    </div>

    <form id="financeForm" method="POST" action="{{ route('settings.finance.update') }}" class="ajax-form space-y-6">
        @csrf

        <!-- Currency & Locale -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-cash-stack text-green-500"></i>
                <div>
                    <h3 class="pref-card-title">Currency & Locale</h3>
                    <p class="pref-card-desc">Primary currency and regional formats</p>
                </div>
            </div>
            <div class="pref-card-body space-y-5">
                <div class="pref-row">
                    <div class="pref-label">
                        <span>Primary Currency</span>
                        <span class="pref-hint">Default currency for all transactions</span>
                    </div>
                    <div class="pref-control w-48">
                        <select name="primary_currency" class="input-field w-full">
                            @foreach(['UGX' => 'UGX - Uganda Shilling', 'USD' => 'USD - US Dollar', 'EUR' => 'EUR - Euro', 'GBP' => 'GBP - British Pound', 'KES' => 'KES - Kenya Shilling', 'TZS' => 'TZS - Tanzania Shilling', 'RWF' => 'RWF - Rwanda Franc'] as $code => $label)
                                <option value="{{ $code }}" {{ ($s['primary_currency'] ?? 'UGX') == $code ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Currency Symbol Position</span>
                        <span class="pref-hint">Display symbol before or after amount</span>
                    </div>
                    <div class="pref-control w-40">
                        <select name="currency_position" class="input-field w-full">
                            <option value="before" {{ ($s['currency_position'] ?? 'before') == 'before' ? 'selected' : '' }}>Before (UGX 1,000)</option>
                            <option value="after" {{ ($s['currency_position'] ?? 'before') == 'after' ? 'selected' : '' }}>After (1,000 UGX)</option>
                        </select>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Decimal Places</span>
                        <span class="pref-hint">Number of decimal places for amounts</span>
                    </div>
                    <div class="pref-control w-24">
                        <select name="decimal_places" class="input-field w-full">
                            <option value="0" {{ ($s['decimal_places'] ?? 0) == 0 ? 'selected' : '' }}>0</option>
                            <option value="2" {{ ($s['decimal_places'] ?? 0) == 2 ? 'selected' : '' }}>2</option>
                        </select>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Thousand Separator</span>
                        <span class="pref-hint">Separator for large numbers</span>
                    </div>
                    <div class="pref-control w-32">
                        <select name="thousand_separator" class="input-field w-full">
                            <option value="," {{ ($s['thousand_separator'] ?? ',') == ',' ? 'selected' : '' }}>Comma (1,000)</option>
                            <option value="." {{ ($s['thousand_separator'] ?? ',') == '.' ? 'selected' : '' }}>Period (1.000)</option>
                            <option value=" " {{ ($s['thousand_separator'] ?? ',') == ' ' ? 'selected' : '' }}>Space (1 000)</option>
                        </select>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Enable Multi-Currency</span>
                        <span class="pref-hint">Support transactions in multiple currencies</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="multi_currency" value="1" {{ !empty($s['multi_currency']) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tax Configuration -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-percent text-blue-500"></i>
                <div>
                    <h3 class="pref-card-title">Tax Configuration</h3>
                    <p class="pref-card-desc">VAT, withholding tax, and tax calculations</p>
                </div>
            </div>
            <div class="pref-card-body space-y-5">
                <div class="pref-row">
                    <div class="pref-label">
                        <span>Enable Tax</span>
                        <span class="pref-hint">Apply tax to invoices and charges</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="tax_enabled" value="1" {{ ($s['tax_enabled'] ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Default VAT Rate</span>
                        <span class="pref-hint">Standard Value Added Tax rate</span>
                    </div>
                    <div class="pref-control w-28">
                        <div class="relative">
                            <input type="number" name="vat_rate" value="{{ $s['vat_rate'] ?? 18 }}" class="input-field w-full text-center pr-8" min="0" max="50" step="0.5">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">%</span>
                        </div>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Tax Calculation Method</span>
                        <span class="pref-hint">How tax is applied to line items</span>
                    </div>
                    <div class="pref-control w-48">
                        <select name="tax_calculation" class="input-field w-full">
                            <option value="exclusive" {{ ($s['tax_calculation'] ?? 'exclusive') == 'exclusive' ? 'selected' : '' }}>Exclusive (add to subtotal)</option>
                            <option value="inclusive" {{ ($s['tax_calculation'] ?? 'exclusive') == 'inclusive' ? 'selected' : '' }}>Inclusive (included in price)</option>
                        </select>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Withholding Tax Rate</span>
                        <span class="pref-hint">Tax withheld on payments (WHT)</span>
                    </div>
                    <div class="pref-control w-28">
                        <div class="relative">
                            <input type="number" name="wht_rate" value="{{ $s['wht_rate'] ?? 6 }}" class="input-field w-full text-center pr-8" min="0" max="30" step="0.5">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">%</span>
                        </div>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Tax Registration Number</span>
                        <span class="pref-hint">Your company's TIN for invoices</span>
                    </div>
                    <div class="pref-control w-56">
                        <input type="text" name="tax_number" value="{{ $s['tax_number'] ?? '' }}" class="input-field w-full" placeholder="1000123456">
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoicing -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-receipt text-purple-500"></i>
                <div>
                    <h3 class="pref-card-title">Invoicing</h3>
                    <p class="pref-card-desc">Invoice generation and numbering</p>
                </div>
            </div>
            <div class="pref-card-body space-y-5">
                <div class="pref-row">
                    <div class="pref-label">
                        <span>Invoice Prefix</span>
                        <span class="pref-hint">Prefix for invoice numbers</span>
                    </div>
                    <div class="pref-control w-32">
                        <input type="text" name="invoice_prefix" value="{{ $s['invoice_prefix'] ?? 'INV-' }}" class="input-field w-full" placeholder="INV-">
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Invoice Number Format</span>
                        <span class="pref-hint">Numbering scheme for invoices</span>
                    </div>
                    <div class="pref-control w-48">
                        <select name="invoice_format" class="input-field w-full">
                            <option value="sequential" {{ ($s['invoice_format'] ?? 'sequential') == 'sequential' ? 'selected' : '' }}>Sequential (001, 002...)</option>
                            <option value="year_seq" {{ ($s['invoice_format'] ?? 'sequential') == 'year_seq' ? 'selected' : '' }}>Year-Seq (2024-001)</option>
                            <option value="month_seq" {{ ($s['invoice_format'] ?? 'sequential') == 'month_seq' ? 'selected' : '' }}>Month-Seq (2024-11-001)</option>
                        </select>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Default Payment Terms (days)</span>
                        <span class="pref-hint">Days until invoice is due</span>
                    </div>
                    <div class="pref-control w-24">
                        <input type="number" name="payment_terms" value="{{ $s['payment_terms'] ?? 30 }}" class="input-field w-full text-center" min="0" max="180">
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Auto-Generate on Delivery</span>
                        <span class="pref-hint">Create invoice when shipment delivered</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="auto_invoice" value="1" {{ ($s['auto_invoice'] ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Auto-Send Invoice Email</span>
                        <span class="pref-hint">Email invoice to customer automatically</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="auto_email_invoice" value="1" {{ !empty($s['auto_email_invoice']) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Invoice Footer Text</span>
                        <span class="pref-hint">Terms and notes on invoices</span>
                    </div>
                    <div class="pref-control w-full max-w-md">
                        <textarea name="invoice_footer" rows="2" class="input-field w-full resize-none" placeholder="Thank you for your business...">{{ $s['invoice_footer'] ?? '' }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pricing & Rate Cards -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-tags text-amber-500"></i>
                <div>
                    <h3 class="pref-card-title">Pricing & Rate Cards</h3>
                    <p class="pref-card-desc">Shipping rates and pricing rules</p>
                </div>
            </div>
            <div class="pref-card-body space-y-5">
                <div class="pref-row">
                    <div class="pref-label">
                        <span>Pricing Mode</span>
                        <span class="pref-hint">How shipping rates are calculated</span>
                    </div>
                    <div class="pref-control w-48">
                        <select name="pricing_mode" class="input-field w-full">
                            <option value="zone_weight" {{ ($s['pricing_mode'] ?? 'zone_weight') == 'zone_weight' ? 'selected' : '' }}>Zone + Weight</option>
                            <option value="distance" {{ ($s['pricing_mode'] ?? 'zone_weight') == 'distance' ? 'selected' : '' }}>Distance-Based</option>
                            <option value="flat" {{ ($s['pricing_mode'] ?? 'zone_weight') == 'flat' ? 'selected' : '' }}>Flat Rate</option>
                            <option value="tiered" {{ ($s['pricing_mode'] ?? 'zone_weight') == 'tiered' ? 'selected' : '' }}>Tiered/Volume</option>
                        </select>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Fuel Surcharge (%)</span>
                        <span class="pref-hint">Automatic fuel surcharge on rates</span>
                    </div>
                    <div class="pref-control w-28">
                        <div class="relative">
                            <input type="number" name="fuel_surcharge" value="{{ $s['fuel_surcharge'] ?? 8 }}" class="input-field w-full text-center pr-8" min="0" max="50" step="0.5">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">%</span>
                        </div>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Insurance Rate (%)</span>
                        <span class="pref-hint">Default insurance rate on declared value</span>
                    </div>
                    <div class="pref-control w-28">
                        <div class="relative">
                            <input type="number" name="insurance_rate" value="{{ $s['insurance_rate'] ?? 1.5 }}" class="input-field w-full text-center pr-8" min="0" max="10" step="0.1">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">%</span>
                        </div>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Minimum Shipment Charge</span>
                        <span class="pref-hint">Minimum amount for any shipment</span>
                    </div>
                    <div class="pref-control w-44">
                        <div class="relative">
                            <input type="number" name="min_charge" value="{{ $s['min_charge'] ?? 5000 }}" class="input-field w-full pl-12" min="0">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">UGX</span>
                        </div>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Enable Dynamic Pricing</span>
                        <span class="pref-hint">Adjust prices based on demand/capacity</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="dynamic_pricing" value="1" {{ !empty($s['dynamic_pricing']) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-credit-card-2-front text-indigo-500"></i>
                <div>
                    <h3 class="pref-card-title">Payment Methods</h3>
                    <p class="pref-card-desc">Accepted payment options</p>
                </div>
            </div>
            <div class="pref-card-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach([
                        ['key' => 'cash', 'icon' => 'cash-coin', 'label' => 'Cash', 'desc' => 'Cash payments on delivery', 'default' => true],
                        ['key' => 'mobile_money', 'icon' => 'phone', 'label' => 'Mobile Money', 'desc' => 'MTN, Airtel Money', 'default' => true],
                        ['key' => 'bank_transfer', 'icon' => 'bank', 'label' => 'Bank Transfer', 'desc' => 'Direct bank deposits', 'default' => true],
                        ['key' => 'credit', 'icon' => 'credit-card', 'label' => 'Credit Account', 'desc' => 'Invoice-based credit terms', 'default' => true],
                        ['key' => 'card', 'icon' => 'credit-card-2-front', 'label' => 'Card Payment', 'desc' => 'Visa, Mastercard', 'default' => false],
                        ['key' => 'cheque', 'icon' => 'file-text', 'label' => 'Cheque', 'desc' => 'Bank cheques', 'default' => false],
                    ] as $method)
                        <label class="flex items-center justify-between p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                                    <i class="bi bi-{{ $method['icon'] }} text-indigo-600 dark:text-indigo-400"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-slate-900 dark:text-white">{{ $method['label'] }}</div>
                                    <div class="text-xs text-slate-500">{{ $method['desc'] }}</div>
                                </div>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="payment_{{ $method['key'] }}" value="1" {{ ($s['payment_'.$method['key']] ?? $method['default']) ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Credit Management -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-wallet2 text-rose-500"></i>
                <div>
                    <h3 class="pref-card-title">Credit Management</h3>
                    <p class="pref-card-desc">Customer credit limits and controls</p>
                </div>
            </div>
            <div class="pref-card-body space-y-5">
                <div class="pref-row">
                    <div class="pref-label">
                        <span>Enable Credit Accounts</span>
                        <span class="pref-hint">Allow customers to have credit terms</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="credit_enabled" value="1" {{ ($s['credit_enabled'] ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Default Credit Limit</span>
                        <span class="pref-hint">Initial credit limit for new accounts</span>
                    </div>
                    <div class="pref-control w-44">
                        <div class="relative">
                            <input type="number" name="default_credit_limit" value="{{ $s['default_credit_limit'] ?? 1000000 }}" class="input-field w-full pl-12" min="0">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">UGX</span>
                        </div>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Credit Check on Booking</span>
                        <span class="pref-hint">Verify credit before accepting shipment</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="credit_check_booking" value="1" {{ ($s['credit_check_booking'] ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Block on Credit Exceeded</span>
                        <span class="pref-hint">Prevent bookings when over limit</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="block_over_credit" value="1" {{ !empty($s['block_over_credit']) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Late Payment Fee (%)</span>
                        <span class="pref-hint">Penalty for overdue invoices</span>
                    </div>
                    <div class="pref-control w-28">
                        <div class="relative">
                            <input type="number" name="late_fee" value="{{ $s['late_fee'] ?? 2 }}" class="input-field w-full text-center pr-8" min="0" max="20" step="0.5">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settlements -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-arrow-left-right text-teal-500"></i>
                <div>
                    <h3 class="pref-card-title">Settlements</h3>
                    <p class="pref-card-desc">Merchant and driver settlement configuration</p>
                </div>
            </div>
            <div class="pref-card-body space-y-5">
                <div class="pref-row">
                    <div class="pref-label">
                        <span>Merchant Settlement Cycle</span>
                        <span class="pref-hint">How often to settle with merchants</span>
                    </div>
                    <div class="pref-control w-40">
                        <select name="merchant_settlement_cycle" class="input-field w-full">
                            <option value="daily" {{ ($s['merchant_settlement_cycle'] ?? 'weekly') == 'daily' ? 'selected' : '' }}>Daily</option>
                            <option value="weekly" {{ ($s['merchant_settlement_cycle'] ?? 'weekly') == 'weekly' ? 'selected' : '' }}>Weekly</option>
                            <option value="biweekly" {{ ($s['merchant_settlement_cycle'] ?? 'weekly') == 'biweekly' ? 'selected' : '' }}>Bi-Weekly</option>
                            <option value="monthly" {{ ($s['merchant_settlement_cycle'] ?? 'weekly') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                        </select>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Driver Settlement Cycle</span>
                        <span class="pref-hint">How often to settle with drivers</span>
                    </div>
                    <div class="pref-control w-40">
                        <select name="driver_settlement_cycle" class="input-field w-full">
                            <option value="daily" {{ ($s['driver_settlement_cycle'] ?? 'daily') == 'daily' ? 'selected' : '' }}>Daily</option>
                            <option value="weekly" {{ ($s['driver_settlement_cycle'] ?? 'daily') == 'weekly' ? 'selected' : '' }}>Weekly</option>
                            <option value="biweekly" {{ ($s['driver_settlement_cycle'] ?? 'daily') == 'biweekly' ? 'selected' : '' }}>Bi-Weekly</option>
                        </select>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Auto-Generate Settlements</span>
                        <span class="pref-hint">Automatically create settlement reports</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="auto_settlements" value="1" {{ ($s['auto_settlements'] ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Settlement Approval Required</span>
                        <span class="pref-hint">Require manager approval before payout</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="settlement_approval" value="1" {{ ($s['settlement_approval'] ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
