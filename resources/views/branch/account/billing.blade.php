@extends('branch.layout')

@section('title', 'Account – Billing')

@section('content')
    <div class="max-w-4xl space-y-6">
        {{-- Page Header --}}
        <div>
            <h1 class="text-2xl font-semibold mb-1">Billing</h1>
            <p class="muted text-sm">View your subscription plan, usage metrics, and invoice history.</p>
        </div>

        {{-- Current Plan --}}
        <div class="glass-panel p-6 space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold">Current Plan</div>
                    <div class="muted text-xs">Your active subscription details.</div>
                </div>
                <span class="badge badge-success">Active</span>
            </div>

            <div class="border border-white/10 rounded-lg p-5 bg-white/5">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold mb-1">Professional Plan</h3>
                        <p class="text-xs muted">Branch operations and management</p>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold">$299</div>
                        <div class="text-xs muted">/month</div>
                    </div>
                </div>

                <div class="space-y-2 mb-5">
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Unlimited shipments processing</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Up to 50 staff members</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Advanced reporting and analytics</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Priority support (24/7)</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>API access included</span>
                    </div>
                </div>

                <div class="flex items-center gap-2 text-xs muted">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span>Next billing date: <strong class="text-white">December 22, 2024</strong></span>
                </div>
            </div>
        </div>

        {{-- Usage Metrics --}}
        <div class="glass-panel p-6 space-y-5">
            <div>
                <div class="text-sm font-semibold mb-1">Usage This Month</div>
                <div class="muted text-xs">Track your current usage against plan limits.</div>
            </div>

            <div class="space-y-4">
                {{-- Shipments Processed --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium">Shipments Processed</span>
                        <span class="text-xs muted">3,842 / Unlimited</span>
                    </div>
                    <div class="w-full bg-white/10 rounded-full h-2 overflow-hidden">
                        <div class="bg-gradient-to-r from-emerald-500 to-emerald-400 h-2 rounded-full" style="width: 38.42%"></div>
                    </div>
                </div>

                {{-- Staff Members --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium">Active Staff</span>
                        <span class="text-xs muted">28 / 50</span>
                    </div>
                    <div class="w-full bg-white/10 rounded-full h-2 overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-500 to-blue-400 h-2 rounded-full" style="width: 56%"></div>
                    </div>
                </div>

                {{-- Storage Used --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium">Storage Used</span>
                        <span class="text-xs muted">18.5 GB / 100 GB</span>
                    </div>
                    <div class="w-full bg-white/10 rounded-full h-2 overflow-hidden">
                        <div class="bg-gradient-to-r from-purple-500 to-purple-400 h-2 rounded-full" style="width: 18.5%"></div>
                    </div>
                </div>

                {{-- API Calls --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium">API Calls</span>
                        <span class="text-xs muted">245,892 / 500,000</span>
                    </div>
                    <div class="w-full bg-white/10 rounded-full h-2 overflow-hidden">
                        <div class="bg-gradient-to-r from-yellow-500 to-yellow-400 h-2 rounded-full" style="width: 49.18%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Payment Method --}}
        <div class="glass-panel p-6 space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold">Payment Method</div>
                    <div class="muted text-xs">Managed by your organization administrator.</div>
                </div>
            </div>

            <div class="flex items-center gap-4 p-4 border border-white/10 rounded-lg bg-white/5">
                <div class="p-3 rounded-lg bg-blue-500/20">
                    <svg class="w-6 h-6 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <div class="text-sm font-medium">Company Card •••• 4242</div>
                    <div class="text-xs muted">Expires 12/2025</div>
                </div>
                <div class="text-xs muted">Primary</div>
            </div>

            <p class="text-xs muted">Contact your administrator to update payment information.</p>
        </div>

        {{-- Invoice History --}}
        <div class="glass-panel p-6 space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold">Invoice History</div>
                    <div class="muted text-xs">View and download past invoices.</div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="dhl-table">
                    <thead>
                        <tr>
                            <th class="text-left">Invoice #</th>
                            <th class="text-left">Date</th>
                            <th class="text-left">Description</th>
                            <th class="text-right">Amount</th>
                            <th class="text-left">Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $invoices = [
                                ['id' => 'INV-2024-011', 'date' => '2024-11-01', 'desc' => 'Professional Plan - November 2024', 'amount' => 299.00, 'status' => 'paid'],
                                ['id' => 'INV-2024-010', 'date' => '2024-10-01', 'desc' => 'Professional Plan - October 2024', 'amount' => 299.00, 'status' => 'paid'],
                                ['id' => 'INV-2024-009', 'date' => '2024-09-01', 'desc' => 'Professional Plan - September 2024', 'amount' => 299.00, 'status' => 'paid'],
                                ['id' => 'INV-2024-008', 'date' => '2024-08-01', 'desc' => 'Professional Plan - August 2024', 'amount' => 299.00, 'status' => 'paid'],
                            ];
                        @endphp

                        @foreach($invoices as $invoice)
                            <tr>
                                <td class="text-sm font-mono">{{ $invoice['id'] }}</td>
                                <td class="text-sm">{{ \Carbon\Carbon::parse($invoice['date'])->format('M d, Y') }}</td>
                                <td class="text-sm">{{ $invoice['desc'] }}</td>
                                <td class="text-sm text-right font-semibold">${{ number_format($invoice['amount'], 2) }}</td>
                                <td>
                                    <span class="badge badge-success text-xs">{{ ucfirst($invoice['status']) }}</span>
                                </td>
                                <td class="text-right">
                                    <button class="px-3 py-1.5 rounded-lg text-xs font-medium bg-white/5 hover:bg-white/10 border border-white/10 transition-colors">
                                        Download
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Billing Contact --}}
        <div class="glass-panel p-6 space-y-4">
            <div>
                <div class="text-sm font-semibold mb-1">Billing Questions?</div>
                <div class="muted text-xs">Contact our billing department for assistance.</div>
            </div>

            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    <a href="mailto:billing@baraka.co" class="text-sm text-emerald-400 hover:text-emerald-300 transition-colors">billing@baraka.co</a>
                </div>
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    </svg>
                    <a href="tel:+254987654321" class="text-sm text-emerald-400 hover:text-emerald-300 transition-colors">+254 987 654 321</a>
                </div>
            </div>
        </div>
    </div>
@endsection
