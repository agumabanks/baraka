@php
    // $settings is passed from controller as the 'operations' section of preferences
    $s = $settings ?? [];
@endphp

@extends('settings.layouts.tailwind')

@section('title', 'Operations')
@section('header', 'Operations')

@section('content')
<div class="max-w-5xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-500 flex items-center justify-center shadow-lg">
                <i class="bi bi-boxes text-2xl text-white"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Operations</h1>
                <p class="text-slate-500 dark:text-slate-400">Shipment handling, SLAs, and logistics automation</p>
            </div>
        </div>
        <button type="submit" form="operationsForm" class="btn-primary shadow-lg shadow-amber-500/25">
            <i class="bi bi-check-lg mr-2"></i>
            Save Changes
        </button>
    </div>

    <form id="operationsForm" method="POST" action="{{ route('settings.operations.update') }}" class="ajax-form space-y-6">
        @csrf

        <!-- Shipment Configuration -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-box-seam text-blue-500"></i>
                <div>
                    <h3 class="pref-card-title">Shipment Configuration</h3>
                    <p class="pref-card-desc">Core shipment handling settings</p>
                </div>
            </div>
            <div class="pref-card-body space-y-5">
                <div class="pref-row">
                    <div class="pref-label">
                        <span>Auto-Generate Tracking IDs</span>
                        <span class="pref-hint">Automatically create tracking numbers for new shipments</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="auto_tracking_ids" value="1" {{ ($s['auto_tracking_ids'] ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Tracking ID Prefix</span>
                        <span class="pref-hint">Prefix for generated tracking numbers (e.g., BRK)</span>
                    </div>
                    <div class="pref-control w-32">
                        <input type="text" name="tracking_prefix" value="{{ $s['tracking_prefix'] ?? 'BRK' }}" 
                               class="input-field w-full uppercase" maxlength="5" placeholder="BRK">
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>AWB Number Format</span>
                        <span class="pref-hint">Format for Air Waybill numbers</span>
                    </div>
                    <div class="pref-control w-48">
                        <select name="awb_format" class="input-field w-full">
                            <option value="sequential" {{ ($s['awb_format'] ?? 'date_prefix') == 'sequential' ? 'selected' : '' }}>Sequential (001, 002...)</option>
                            <option value="date_prefix" {{ ($s['awb_format'] ?? 'date_prefix') == 'date_prefix' ? 'selected' : '' }}>Date Prefix (241128-001)</option>
                            <option value="random" {{ ($s['awb_format'] ?? 'date_prefix') == 'random' ? 'selected' : '' }}>Random Alphanumeric</option>
                        </select>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Require Proof of Delivery</span>
                        <span class="pref-hint">Mandate POD capture for delivery confirmation</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="require_pod" value="1" {{ ($s['require_pod'] ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- SLA Configuration -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-clock-history text-purple-500"></i>
                <div>
                    <h3 class="pref-card-title">Service Level Agreements</h3>
                    <p class="pref-card-desc">Delivery time targets by service level</p>
                </div>
            </div>
            <div class="pref-card-body space-y-5">
                <div class="pref-row">
                    <div class="pref-label">
                        <span>Express SLA (hours)</span>
                        <span class="pref-hint">Target delivery time for express shipments</span>
                    </div>
                    <div class="pref-control w-24">
                        <input type="number" name="sla_express_hours" value="{{ $s['sla_express_hours'] ?? 4 }}" class="input-field w-full text-center" min="1" max="24">
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Standard SLA (hours)</span>
                        <span class="pref-hint">Target delivery time for standard shipments</span>
                    </div>
                    <div class="pref-control w-24">
                        <input type="number" name="sla_standard_hours" value="{{ $s['sla_standard_hours'] ?? 24 }}" class="input-field w-full text-center" min="1" max="168">
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Economy SLA (hours)</span>
                        <span class="pref-hint">Target delivery time for economy shipments</span>
                    </div>
                    <div class="pref-control w-24">
                        <input type="number" name="sla_economy_hours" value="{{ $s['sla_economy_hours'] ?? 72 }}" class="input-field w-full text-center" min="1" max="336">
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Auto-Escalate Overdue</span>
                        <span class="pref-hint">Alert managers when SLA is at risk</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="auto_escalate_overdue" value="1" {{ ($s['auto_escalate_overdue'] ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Weight & Dimensions -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-rulers text-green-500"></i>
                <div>
                    <h3 class="pref-card-title">Weight & Dimensions</h3>
                    <p class="pref-card-desc">Measurement units and volumetric calculations</p>
                </div>
            </div>
            <div class="pref-card-body space-y-5">
                <div class="pref-row">
                    <div class="pref-label">
                        <span>Weight Unit</span>
                        <span class="pref-hint">Default unit for parcel weight</span>
                    </div>
                    <div class="pref-control w-32">
                        <select name="weight_unit" class="input-field w-full">
                            <option value="kg" {{ ($s['weight_unit'] ?? 'kg') == 'kg' ? 'selected' : '' }}>Kilograms (kg)</option>
                            <option value="lb" {{ ($s['weight_unit'] ?? 'kg') == 'lb' ? 'selected' : '' }}>Pounds (lb)</option>
                        </select>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Dimension Unit</span>
                        <span class="pref-hint">Default unit for parcel dimensions</span>
                    </div>
                    <div class="pref-control w-32">
                        <select name="dimension_unit" class="input-field w-full">
                            <option value="cm" {{ ($s['dimension_unit'] ?? 'cm') == 'cm' ? 'selected' : '' }}>Centimeters (cm)</option>
                            <option value="in" {{ ($s['dimension_unit'] ?? 'cm') == 'in' ? 'selected' : '' }}>Inches (in)</option>
                        </select>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Volumetric Divisor</span>
                        <span class="pref-hint">Divisor for calculating volumetric weight (standard: 5000)</span>
                    </div>
                    <div class="pref-control w-28">
                        <input type="number" name="volumetric_divisor" value="{{ $s['volumetric_divisor'] ?? 5000 }}" class="input-field w-full text-center" min="1000" max="10000">
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Max Parcel Weight (kg)</span>
                        <span class="pref-hint">Maximum allowed weight per parcel</span>
                    </div>
                    <div class="pref-control w-24">
                        <input type="number" name="max_parcel_weight" value="{{ $s['max_parcel_weight'] ?? 70 }}" class="input-field w-full text-center" min="1" max="500">
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Use Chargeable Weight</span>
                        <span class="pref-hint">Charge higher of actual vs volumetric weight</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="use_chargeable_weight" value="1" {{ ($s['use_chargeable_weight'] ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- COD Management -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-cash-stack text-emerald-500"></i>
                <div>
                    <h3 class="pref-card-title">Cash on Delivery (COD)</h3>
                    <p class="pref-card-desc">COD collection and remittance settings</p>
                </div>
            </div>
            <div class="pref-card-body space-y-5">
                <div class="pref-row">
                    <div class="pref-label">
                        <span>Enable COD</span>
                        <span class="pref-hint">Allow cash on delivery payments</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="cod_enabled" value="1" {{ ($s['cod_enabled'] ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Maximum COD Amount</span>
                        <span class="pref-hint">Maximum allowed COD value per shipment</span>
                    </div>
                    <div class="pref-control w-44">
                        <div class="relative">
                            <input type="number" name="cod_max_amount" value="{{ $s['cod_max_amount'] ?? 5000000 }}" class="input-field w-full pl-12" min="0">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">UGX</span>
                        </div>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>COD Fee (%)</span>
                        <span class="pref-hint">Percentage fee charged on COD collections</span>
                    </div>
                    <div class="pref-control w-28">
                        <div class="relative">
                            <input type="number" name="cod_fee_percent" value="{{ $s['cod_fee_percent'] ?? 2.5 }}" class="input-field w-full text-center pr-8" min="0" max="20" step="0.5">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">%</span>
                        </div>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>COD Remittance Cycle</span>
                        <span class="pref-hint">How often to remit collected COD</span>
                    </div>
                    <div class="pref-control w-40">
                        <select name="cod_remittance_cycle" class="input-field w-full">
                            <option value="daily" {{ ($s['cod_remittance_cycle'] ?? 'daily') == 'daily' ? 'selected' : '' }}>Daily</option>
                            <option value="weekly" {{ ($s['cod_remittance_cycle'] ?? 'daily') == 'weekly' ? 'selected' : '' }}>Weekly</option>
                            <option value="biweekly" {{ ($s['cod_remittance_cycle'] ?? 'daily') == 'biweekly' ? 'selected' : '' }}>Bi-Weekly</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Returns Management -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-arrow-return-left text-red-500"></i>
                <div>
                    <h3 class="pref-card-title">Returns Management</h3>
                    <p class="pref-card-desc">Failed delivery and returns handling</p>
                </div>
            </div>
            <div class="pref-card-body space-y-5">
                <div class="pref-row">
                    <div class="pref-label">
                        <span>Max Delivery Attempts</span>
                        <span class="pref-hint">Attempts before marking as failed</span>
                    </div>
                    <div class="pref-control w-24">
                        <input type="number" name="max_delivery_attempts" value="{{ $s['max_delivery_attempts'] ?? 3 }}" class="input-field w-full text-center" min="1" max="10">
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Auto-Return Days</span>
                        <span class="pref-hint">Days before auto-initiating return</span>
                    </div>
                    <div class="pref-control w-24">
                        <input type="number" name="auto_return_days" value="{{ $s['auto_return_days'] ?? 7 }}" class="input-field w-full text-center" min="1" max="30">
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Require Return Reason</span>
                        <span class="pref-hint">Mandate reason capture for returns</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="require_return_reason" value="1" {{ ($s['require_return_reason'] ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Automation -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-gear-wide-connected text-cyan-500"></i>
                <div>
                    <h3 class="pref-card-title">Automation</h3>
                    <p class="pref-card-desc">Automated processes and scheduling</p>
                </div>
            </div>
            <div class="pref-card-body space-y-5">
                <div class="pref-row">
                    <div class="pref-label">
                        <span>Auto-Assign Drivers</span>
                        <span class="pref-hint">Automatically assign available drivers</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="auto_assign_drivers" value="1" {{ !empty($s['auto_assign_drivers']) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Auto-Generate Invoices</span>
                        <span class="pref-hint">Create invoices on delivery completion</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="auto_generate_invoices" value="1" {{ ($s['auto_generate_invoices'] ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Auto Routing</span>
                        <span class="pref-hint">Optimize routes automatically</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="auto_routing" value="1" {{ ($s['auto_routing'] ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Enable Consolidation</span>
                        <span class="pref-hint">Group shipments for efficient routing</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="enable_consolidation" value="1" {{ ($s['enable_consolidation'] ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
