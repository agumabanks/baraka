@extends('branch.layout')

@section('title', 'Shipment POS Terminal')

@push('styles')
<style>
    :root {
        --pos-primary: #dc2626;
        --pos-secondary: #fbbf24;
        --pos-success: #22c55e;
        --pos-dark: #18181b;
        --pos-card: #27272a;
    }
    
    /* ============================================
       SANAA - Steve Jobs Simplicity
       "Design is not just what it looks like. 
        Design is how it works."
       ============================================ */
    .sanaa-loading-overlay {
        position: fixed;
        inset: 0;
        background: #000;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 99999;
        opacity: 1;
        visibility: visible;
        transition: opacity 1s ease;
    }
    .sanaa-loading-overlay.hidden {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }
    
    .sanaa-loading-content {
        text-align: center;
        opacity: 0;
        transform: scale(0.98);
        animation: sanaaReveal 1.2s cubic-bezier(0.25, 0.1, 0.25, 1) 0.2s forwards;
    }
    @keyframes sanaaReveal {
        to { opacity: 1; transform: scale(1); }
    }
    
    .sanaa-logo {
        font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', system-ui, sans-serif;
        font-size: 64px;
        font-weight: 500;
        letter-spacing: -1px;
        color: #fff;
        margin-bottom: 48px;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }
    
    .sanaa-pulse {
        width: 8px;
        height: 8px;
        background: rgba(255,255,255,0.9);
        border-radius: 50%;
        margin: 0 auto;
        animation: sanaaPulse 2s ease-in-out infinite;
    }
    @keyframes sanaaPulse {
        0%, 100% { opacity: 0.3; transform: scale(1); }
        50% { opacity: 1; transform: scale(1.1); }
    }
    
    .shimmer {
        background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.04) 50%, transparent 100%);
        background-size: 200% 100%;
        animation: shimmer 2s ease-in-out infinite;
    }
    @keyframes shimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    
    .processing-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.8);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        display: none;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        z-index: 100;
    }
    .processing-overlay.show { display: flex; }
    .processing-spinner {
        width: 24px;
        height: 24px;
        border: 2px solid rgba(255,255,255,0.15);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    
    .pos-terminal { background: transparent; position: relative; }
    .pos-card { background: var(--pos-card); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; }
    .pos-input { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); color: white; border-radius: 8px; padding: 12px 16px; width: 100%; transition: all 0.2s; }
    .pos-input:focus { outline: none; border-color: var(--pos-primary); box-shadow: 0 0 0 3px rgba(220,38,38,0.2); }
    .pos-input::placeholder { color: rgba(255,255,255,0.4); }
    .pos-select { background: rgba(255,255,255,0.05) url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") right 12px center/16px no-repeat; }
    
    .pos-btn { padding: 12px 24px; border-radius: 8px; font-weight: 600; transition: all 0.2s; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
    .pos-btn-primary { background: var(--pos-primary); color: white; border: none; }
    .pos-btn-primary:hover { background: #b91c1c; transform: translateY(-1px); }
    .pos-btn-secondary { background: transparent; color: white; border: 1px solid rgba(255,255,255,0.2); }
    .pos-btn-secondary:hover { background: rgba(255,255,255,0.1); }
    .pos-btn-success { background: var(--pos-success); color: white; border: none; }
    .pos-btn-success:hover { background: #16a34a; }
    
    .stat-card { background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%); border-radius: 12px; padding: 16px 20px; border: 1px solid rgba(255,255,255,0.1); }
    .stat-value { font-size: 28px; font-weight: 700; line-height: 1.2; }
    .stat-label { font-size: 12px; color: rgba(255,255,255,0.6); text-transform: uppercase; letter-spacing: 0.5px; }
    
    .service-card { padding: 16px; border-radius: 10px; border: 2px solid transparent; cursor: pointer; transition: all 0.2s; background: rgba(255,255,255,0.03); }
    .service-card:hover { background: rgba(255,255,255,0.08); }
    .service-card.selected { border-color: var(--pos-primary); background: rgba(220,38,38,0.1); }
    .service-card .service-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
    
    .payment-option { padding: 16px; border-radius: 10px; border: 2px solid rgba(255,255,255,0.1); cursor: pointer; transition: all 0.2s; text-align: center; }
    .payment-option:hover { border-color: rgba(255,255,255,0.3); }
    .payment-option.selected { border-color: var(--pos-success); background: rgba(34,197,94,0.1); }
    
    .customer-result { padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.1); cursor: pointer; transition: background 0.15s; }
    .customer-result:hover { background: rgba(255,255,255,0.1); }
    .customer-result:last-child { border-bottom: none; }
    
    .pricing-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; }
    .pricing-total { font-size: 24px; font-weight: 700; color: var(--pos-success); }
    
    .kbd { background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px; font-size: 11px; font-family: monospace; border: 1px solid rgba(255,255,255,0.2); }
    
    .quick-action { padding: 8px 16px; border-radius: 6px; font-size: 13px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: rgba(255,255,255,0.8); cursor: pointer; transition: all 0.15s; white-space: nowrap; }
    .quick-action:hover { background: rgba(255,255,255,0.1); color: white; }
    
    .scanner-indicator { animation: pulse 2s infinite; }
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    
    .weight-display { font-family: 'Courier New', monospace; font-size: 32px; font-weight: bold; background: #000; padding: 16px 24px; border-radius: 8px; color: #22c55e; text-align: center; letter-spacing: 2px; border: 2px solid #333; }
    
    .section-header { font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,0.5); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
    .section-header svg { width: 18px; height: 18px; }
    
    .tab-btn { padding: 10px 20px; border: none; background: transparent; color: rgba(255,255,255,0.6); cursor: pointer; border-bottom: 2px solid transparent; transition: all 0.2s; font-weight: 500; }
    .tab-btn:hover { color: white; }
    .tab-btn.active { color: white; border-bottom-color: var(--pos-primary); }
    
    .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .badge-priority { background: #dc2626; color: white; }
    .badge-express { background: #f59e0b; color: black; }
    .badge-standard { background: #3b82f6; color: white; }
    .badge-economy { background: #6b7280; color: white; }
    
    .shipment-row { padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.05); transition: background 0.15s; }
    .shipment-row:hover { background: rgba(255,255,255,0.02); }
    
    .pos-modal { position: fixed; inset: 0; background: rgba(0,0,0,0.8); display: none; align-items: center; justify-content: center; z-index: 9999; backdrop-filter: blur(4px); }
    .pos-modal.show { display: flex; }
    .pos-modal-content { background: var(--pos-card); border-radius: 16px; padding: 32px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto; border: 1px solid rgba(255,255,255,0.1); }
    
    @media print {
        .no-print { display: none !important; }
        body { background: white; color: black; }
    }
    
    @media (max-width: 1280px) {
        .hide-lg { display: none; }
    }
</style>
@endpush

@section('content')
{{-- Sanaa Loading --}}
<div id="sanaaLoadingOverlay" class="sanaa-loading-overlay">
    <div class="sanaa-loading-content">
        <div class="sanaa-logo">sanaa</div>
        <div class="sanaa-pulse"></div>
    </div>
</div>

<div class="pos-terminal">
    {{-- Top Bar --}}
    <div class="flex items-center justify-between mb-6 no-print">
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-600 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-white">POS Terminal</h1>
                    <p class="text-xs text-zinc-500">{{ $currentBranch->name ?? 'Branch' }} &bull; {{ now()->format('D, M d Y') }}</p>
                </div>
            </div>
            
            <div class="hidden lg:flex items-center gap-2 px-3 py-2 bg-zinc-800 rounded-lg">
                <div class="w-2 h-2 rounded-full bg-green-500 scanner-indicator"></div>
                <span class="text-xs text-zinc-400">Scanner Ready</span>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2">
                <div class="relative">
                    <input type="text" id="quickTrackInput" placeholder="Scan or enter tracking #" 
                        class="pos-input w-64 pr-10 text-sm" onkeypress="if(event.key==='Enter')quickTrack()">
                    <button onclick="quickTrack()" class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="hidden lg:block text-right">
                <div class="text-sm text-white font-medium">{{ auth()->user()->name }}</div>
                <div class="text-xs text-zinc-500">Shift: {{ now()->format('H:i') }} - Active</div>
            </div>
            
            <div class="flex items-center gap-2">
                <button onclick="resetForm()" class="quick-action bg-red-600/20 hover:bg-red-600/40 border-red-500/30" title="Clear Form (Esc)">
                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <span class="hidden lg:inline text-red-400">Clear</span>
                </button>
                <button onclick="showHoldQueue()" class="quick-action" title="Hold Queue (F9)">
                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span class="hidden lg:inline">Hold</span>
                    <span class="bg-yellow-500 text-black text-xs px-1.5 rounded-full" id="holdCount">0</span>
                </button>
                <button onclick="showRepeatLast()" class="quick-action" title="Repeat Last (F8)">
                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span class="hidden lg:inline">Repeat</span>
                </button>
            </div>
        </div>
    </div>
    
    {{-- Stats Row --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6 no-print">
        <div class="stat-card">
            <div class="stat-value text-white">{{ $todayStats['shipments_count'] ?? 0 }}</div>
            <div class="stat-label">Today's Shipments</div>
        </div>
        <div class="stat-card">
            <div class="stat-value text-green-400">{{ $systemConfig['currency_symbol'] ?? '$' }}{{ number_format($todayStats['total_revenue'] ?? 0, 0) }}</div>
            <div class="stat-label">Revenue</div>
        </div>
        <div class="stat-card hidden lg:block">
            <div class="stat-value text-blue-400">{{ number_format($todayStats['total_weight'] ?? 0, 1) }}<span class="text-lg">kg</span></div>
            <div class="stat-label">Total Weight</div>
        </div>
        <div class="stat-card hidden lg:block">
            <div class="stat-value text-yellow-400">{{ $todayStats['pending_pickup'] ?? 0 }}</div>
            <div class="stat-label">Pending Pickup</div>
        </div>
        <div class="stat-card hidden lg:block">
            <div class="stat-value text-zinc-400">{{ now()->format('H:i') }}</div>
            <div class="stat-label">Current Time</div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
        {{-- Left Panel: Main Form --}}
        <div class="xl:col-span-8 space-y-5">
            {{-- Customer Section --}}
            <div class="pos-card p-5">
                <div class="section-header">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Customer <span class="kbd ml-auto">F2</span>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="lg:col-span-2 relative">
                        <input type="text" id="customerSearch" placeholder="Search by name, phone, company, or scan ID..." 
                            class="pos-input text-lg" autocomplete="off">
                        <div id="customerResults" class="absolute top-full left-0 right-0 bg-zinc-800 border border-zinc-700 rounded-lg mt-1 max-h-64 overflow-y-auto hidden z-50 shadow-xl"></div>
                        <input type="hidden" id="customerId" name="customer_id">
                    </div>
                    
                    <div id="selectedCustomerCard" class="lg:col-span-2 hidden">
                        <div class="bg-green-500/10 border border-green-500/30 rounded-lg p-4 flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-green-500/20 rounded-full flex items-center justify-center">
                                    <span class="text-lg font-bold text-green-400" id="customerInitials">JD</span>
                                </div>
                                <div>
                                    <div class="font-semibold text-white text-lg" id="customerDisplayName">John Doe</div>
                                    <div class="text-sm text-zinc-400" id="customerDisplayInfo">+256 700 123456 &bull; john@company.com</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button onclick="viewCustomerHistory()" class="text-blue-400 hover:text-blue-300 text-sm">History</button>
                                <button onclick="clearCustomer()" class="text-red-400 hover:text-red-300 text-sm ml-4">Change</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="lg:col-span-2 flex items-center gap-4 pt-2">
                        <button onclick="showNewCustomerModal()" class="text-red-400 hover:text-red-300 text-sm flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                            New Customer
                        </button>
                        <button onclick="showAddressBook()" class="text-zinc-400 hover:text-zinc-300 text-sm flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            Address Book
                        </button>
                    </div>
                </div>
            </div>

            {{-- Route Section --}}
            <div class="pos-card p-5">
                <div class="section-header">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                    Route & Service
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-xs text-zinc-500 mb-2 uppercase">Origin Branch</label>
                        <select id="originBranch" class="pos-input pos-select">
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ ($currentBranch && $currentBranch->id == $branch->id) ? 'selected' : '' }}>
                                    {{ $branch->name }} ({{ $branch->code ?? 'N/A' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-zinc-500 mb-2 uppercase">Destination Branch <span class="text-red-400">*</span></label>
                        <select id="destBranch" class="pos-input pos-select" onchange="onRouteChange()">
                            <option value="">Select destination...</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }} ({{ $branch->code ?? 'N/A' }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                {{-- Service Level Cards --}}
                <label class="block text-xs text-zinc-500 mb-3 uppercase">Service Level</label>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3" id="serviceLevelGrid">
                    <div class="service-card" onclick="selectService('economy')" data-service="economy">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="service-icon bg-zinc-700">
                                <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <span class="badge badge-economy">ECONOMY</span>
                        </div>
                        <div class="text-white font-semibold">5-7 Days</div>
                        <div class="text-xs text-zinc-500">Best value</div>
                        <div class="text-sm text-zinc-400 mt-1" id="economyPrice">--</div>
                    </div>
                    
                    <div class="service-card selected" onclick="selectService('standard')" data-service="standard">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="service-icon bg-blue-500/20">
                                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                            </div>
                            <span class="badge badge-standard">STANDARD</span>
                        </div>
                        <div class="text-white font-semibold">3-5 Days</div>
                        <div class="text-xs text-zinc-500">Most popular</div>
                        <div class="text-sm text-zinc-400 mt-1" id="standardPrice">--</div>
                    </div>
                    
                    <div class="service-card" onclick="selectService('express')" data-service="express">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="service-icon bg-yellow-500/20">
                                <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <span class="badge badge-express">EXPRESS</span>
                        </div>
                        <div class="text-white font-semibold">1-2 Days</div>
                        <div class="text-xs text-zinc-500">Fast delivery</div>
                        <div class="text-sm text-zinc-400 mt-1" id="expressPrice">--</div>
                    </div>
                    
                    <div class="service-card" onclick="selectService('priority')" data-service="priority">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="service-icon bg-red-500/20">
                                <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/></svg>
                            </div>
                            <span class="badge badge-priority">PRIORITY</span>
                        </div>
                        <div class="text-white font-semibold">Same Day</div>
                        <div class="text-xs text-zinc-500">Urgent delivery</div>
                        <div class="text-sm text-zinc-400 mt-1" id="priorityPrice">--</div>
                    </div>
                </div>
                <input type="hidden" id="serviceLevel" value="standard">
            </div>

            {{-- Package Section --}}
            <div class="pos-card p-5">
                <div class="section-header">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    Package Details <span class="kbd ml-auto">F3</span>
                </div>
                
                <div class="mb-4">
                    <label class="block text-xs text-zinc-500 mb-2 uppercase">Quick Presets</label>
                    <div id="packagePresetsContainer" class="flex flex-wrap gap-2"></div>
                </div>
                
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 lg:col-span-4">
                        <label class="block text-xs text-zinc-500 mb-2 uppercase">Weight (kg) <span class="text-red-400">*</span></label>
                        <div class="weight-display" id="weightDisplay">0.00</div>
                        <input type="number" id="weight" step="0.01" min="0.01" 
                            class="pos-input mt-2 text-center text-lg" placeholder="Enter weight"
                            oninput="updateWeightDisplay(); calculateRate()">
                        <div class="text-xs text-zinc-500 text-center mt-1">
                            <button onclick="syncScale()" class="text-blue-400 hover:text-blue-300">Sync from scale</button>
                        </div>
                    </div>
                    
                    <div class="col-span-12 lg:col-span-8">
                        <label class="block text-xs text-zinc-500 mb-2 uppercase">Dimensions (cm) - Optional</label>
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <input type="number" id="length" step="1" min="0" placeholder="Length" 
                                    class="pos-input text-center" onchange="calculateRate()">
                            </div>
                            <div>
                                <input type="number" id="width" step="1" min="0" placeholder="Width" 
                                    class="pos-input text-center" onchange="calculateRate()">
                            </div>
                            <div>
                                <input type="number" id="height" step="1" min="0" placeholder="Height" 
                                    class="pos-input text-center" onchange="calculateRate()">
                            </div>
                        </div>
                        <div class="text-xs text-zinc-500 mt-2">
                            Volumetric weight: <span id="volWeight" class="text-white">0.00 kg</span>
                            &bull; Chargeable: <span id="chargeWeight" class="text-green-400 font-medium">0.00 kg</span>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="block text-xs text-zinc-500 mb-2 uppercase">Pieces</label>
                                <input type="number" id="pieces" value="1" min="1" max="999" class="pos-input text-center">
                            </div>
                            <div>
                                <label class="block text-xs text-zinc-500 mb-2 uppercase">Declared Value ($)</label>
                                <input type="number" id="declaredValue" step="0.01" min="0" placeholder="0.00" 
                                    class="pos-input text-center" onchange="calculateRate()">
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-span-12">
                        <label class="block text-xs text-zinc-500 mb-2 uppercase">Contents Description</label>
                        <input type="text" id="description" placeholder="e.g., Electronics, Documents, Clothing..." 
                            class="pos-input">
                    </div>
                    
                    <div class="col-span-12 flex flex-wrap items-center gap-4 pt-2 border-t border-zinc-700">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="isFragile" class="w-5 h-5 rounded bg-zinc-700 border-zinc-600 text-red-500 focus:ring-red-500">
                            <span class="text-sm text-zinc-300">Fragile</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="requiresSignature" class="w-5 h-5 rounded bg-zinc-700 border-zinc-600 text-red-500 focus:ring-red-500">
                            <span class="text-sm text-zinc-300">Signature Required</span>
                        </label>
                        
                        <div class="flex items-center gap-2 ml-auto">
                            <label class="text-sm text-zinc-400">Insurance:</label>
                            <select id="insuranceType" class="pos-input pos-select w-40 py-2 text-sm" onchange="calculateRate()">
                                <option value="none">None</option>
                                <option value="basic">Basic (1%)</option>
                                <option value="full">Full (2%)</option>
                                <option value="premium">Premium (3%)</option>
                            </select>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-zinc-400">COD:</label>
                            <input type="number" id="codAmount" step="0.01" min="0" placeholder="0.00" 
                                class="pos-input w-28 py-2 text-sm text-center" onchange="calculateRate()">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Receiver Section --}}
            <div class="pos-card p-5">
                <div class="section-header">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Receiver Details <span class="kbd ml-auto">F4</span>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-zinc-500 mb-2 uppercase">Receiver Name</label>
                        <input type="text" id="receiverName" placeholder="Full name" class="pos-input">
                    </div>
                    <div>
                        <label class="block text-xs text-zinc-500 mb-2 uppercase">Receiver Phone</label>
                        <input type="text" id="receiverPhone" placeholder="+256 7XX XXX XXX" class="pos-input">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="block text-xs text-zinc-500 mb-2 uppercase">Delivery Address</label>
                        <textarea id="deliveryAddress" rows="2" placeholder="Street address, building, landmarks..." class="pos-input resize-none"></textarea>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="block text-xs text-zinc-500 mb-2 uppercase">Special Instructions</label>
                        <textarea id="specialInstructions" rows="2" placeholder="Delivery notes, handling instructions..." class="pos-input resize-none"></textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Panel: Pricing & Actions --}}
        <div class="xl:col-span-4 space-y-5">
            <div class="pos-card p-5 sticky top-4">
                <div class="section-header">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Pricing Summary
                </div>
                
                <div id="pricingBreakdown" class="space-y-2 text-sm mb-4">
                    <div class="pricing-row text-zinc-400">
                        <span>Base Rate</span>
                        <span id="baseRate">$0.00</span>
                    </div>
                    <div class="pricing-row text-zinc-400">
                        <span>Weight Charge</span>
                        <span id="weightCharge">$0.00</span>
                    </div>
                    <div class="pricing-row text-zinc-400" id="surchargeRow" style="display: none;">
                        <span>Surcharges</span>
                        <span id="surcharges">$0.00</span>
                    </div>
                    <div class="pricing-row text-zinc-400" id="insuranceRow" style="display: none;">
                        <span>Insurance</span>
                        <span id="insuranceAmount">$0.00</span>
                    </div>
                    <div class="pricing-row text-zinc-400" id="codFeeRow" style="display: none;">
                        <span>COD Fee</span>
                        <span id="codFee">$0.00</span>
                    </div>
                    <div class="pricing-row text-zinc-400">
                        <span>Tax (18%)</span>
                        <span id="taxAmount">$0.00</span>
                    </div>
                    <div class="border-t border-zinc-700 pt-3 mt-3 flex justify-between items-center">
                        <span class="text-white font-semibold text-lg">TOTAL</span>
                        <span class="pricing-total" id="totalAmount">$0.00</span>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-xs text-zinc-500 mb-2 uppercase">Who Pays?</label>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" onclick="selectPayer('sender')" class="payment-option selected text-sm" data-payer="sender">
                            <div class="font-semibold text-white">Sender</div>
                            <div class="text-xs text-zinc-500">Prepaid</div>
                        </button>
                        <button type="button" onclick="selectPayer('receiver')" class="payment-option text-sm" data-payer="receiver">
                            <div class="font-semibold text-white">Receiver</div>
                            <div class="text-xs text-zinc-500">Collect</div>
                        </button>
                        <button type="button" onclick="selectPayer('third_party')" class="payment-option text-sm" data-payer="third_party">
                            <div class="font-semibold text-white">3rd Party</div>
                            <div class="text-xs text-zinc-500">Account</div>
                        </button>
                    </div>
                    <input type="hidden" id="payerType" value="sender">
                </div>
                
                <div class="mb-4" id="paymentSection">
                    <label class="block text-xs text-zinc-500 mb-2 uppercase">Payment Method</label>
                    <div id="paymentMethodsGrid" class="grid grid-cols-2 gap-2">
                        <button type="button" onclick="selectPayment('cash')" class="payment-option selected" data-method="cash">
                            <svg class="w-6 h-6 mx-auto mb-1 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <span class="text-sm">Cash</span>
                        </button>
                    </div>
                    <input type="hidden" id="paymentMethod" value="cash">
                </div>
                
                <div id="amountReceivedSection" class="mb-4">
                    <label class="block text-xs text-zinc-500 mb-2 uppercase">Amount Received ($)</label>
                    <input type="number" id="amountReceived" step="0.01" min="0" placeholder="0.00" 
                        class="pos-input text-center text-lg font-mono" oninput="calculateChange()">
                    <div id="changeDisplay" class="text-center mt-2 hidden">
                        <span class="text-zinc-400">Change:</span>
                        <span class="text-green-400 font-bold text-lg ml-2" id="changeAmount">$0.00</span>
                    </div>
                </div>
                
                <div class="space-y-3 pt-4 border-t border-zinc-700">
                    <button onclick="createShipment()" id="createBtn" class="pos-btn pos-btn-primary w-full py-4 text-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        Create Shipment <span class="kbd ml-2 bg-red-700">F12</span>
                    </button>
                    
                    <div class="grid grid-cols-2 gap-2">
                        <button onclick="holdTransaction()" class="pos-btn pos-btn-secondary w-full">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Hold <span class="kbd">F9</span>
                        </button>
                        <button onclick="resetForm()" class="pos-btn pos-btn-secondary w-full">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Clear <span class="kbd">Esc</span>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="pos-card p-5">
                <div class="section-header">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Recent Transactions
                </div>
                
                <div class="space-y-1 max-h-64 overflow-y-auto">
                    @forelse($recentShipments->take(5) as $shipment)
                    <div class="shipment-row flex items-center justify-between">
                        <div>
                            <div class="font-mono text-sm text-white">{{ $shipment->tracking_number }}</div>
                            <div class="text-xs text-zinc-500">{{ $shipment->customerProfile?->contact_person ?? $shipment->customer?->name ?? 'N/A' }} &bull; {{ $shipment->created_at->diffForHumans() }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-semibold text-green-400">{{ $systemConfig['currency_symbol'] ?? '$' }}{{ number_format($shipment->price_amount ?? 0, 2) }}</div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('branch.pos.label', $shipment) }}" target="_blank" class="text-xs text-blue-400 hover:text-blue-300">Label</a>
                                <a href="{{ route('branch.pos.receipt', $shipment) }}" target="_blank" class="text-xs text-blue-400 hover:text-blue-300">Receipt</a>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-zinc-500 py-4">No transactions yet today</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

{{-- New Customer Modal --}}
<div id="newCustomerModal" class="pos-modal">
    <div class="pos-modal-content">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-white">Add New Customer</h3>
            <button onclick="hideNewCustomerModal()" class="text-zinc-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        
        <div class="space-y-4">
            <div>
                <label class="block text-xs text-zinc-500 mb-2 uppercase">Full Name <span class="text-red-400">*</span></label>
                <input type="text" id="newCustomerName" class="pos-input" placeholder="John Doe">
            </div>
            <div>
                <label class="block text-xs text-zinc-500 mb-2 uppercase">Phone <span class="text-red-400">*</span></label>
                <input type="text" id="newCustomerPhone" class="pos-input" placeholder="+256 7XX XXX XXX">
            </div>
            <div>
                <label class="block text-xs text-zinc-500 mb-2 uppercase">Email</label>
                <input type="email" id="newCustomerEmail" class="pos-input" placeholder="john@example.com">
            </div>
            <div>
                <label class="block text-xs text-zinc-500 mb-2 uppercase">Company</label>
                <input type="text" id="newCustomerCompany" class="pos-input" placeholder="Company name">
            </div>
            <div>
                <label class="block text-xs text-zinc-500 mb-2 uppercase">Address</label>
                <textarea id="newCustomerAddress" class="pos-input resize-none" rows="2" placeholder="Street address..."></textarea>
            </div>
        </div>
        
        <div class="flex gap-3 mt-6">
            <button onclick="hideNewCustomerModal()" class="pos-btn pos-btn-secondary flex-1">Cancel</button>
            <button onclick="createCustomer()" class="pos-btn pos-btn-primary flex-1">Create Customer</button>
        </div>
    </div>
</div>

{{-- Success Modal --}}
<div id="successModal" class="pos-modal">
    <div class="pos-modal-content text-center">
        <div class="w-20 h-20 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        
        <h3 class="text-2xl font-bold text-white mb-2">Shipment Created!</h3>
        <p class="text-zinc-400 mb-2">Tracking Number:</p>
        <p class="text-3xl font-mono text-green-400 mb-6" id="newTrackingNumber">TRK-XXXXXXXXXX</p>
        
        <div class="bg-zinc-800 rounded-lg p-4 mb-6">
            <div class="flex justify-between text-sm mb-2">
                <span class="text-zinc-400">Total Charged:</span>
                <span class="text-white font-semibold" id="successTotal">$0.00</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-zinc-400">Payment:</span>
                <span class="text-green-400" id="successPayment">Cash</span>
            </div>
        </div>
        
        <div class="grid grid-cols-2 gap-3 mb-4">
            <a href="#" id="printLabelBtn" target="_blank" class="pos-btn pos-btn-secondary justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print Label
            </a>
            <a href="#" id="printReceiptBtn" target="_blank" class="pos-btn pos-btn-secondary justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Print Receipt
            </a>
        </div>
        
        <button onclick="closeSuccessAndReset()" class="pos-btn pos-btn-success w-full py-4 text-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            New Shipment
        </button>
    </div>
</div>

{{-- Quick Track Modal --}}
<div id="trackModal" class="pos-modal">
    <div class="pos-modal-content">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-white">Shipment Status</h3>
            <button onclick="hideTrackModal()" class="text-zinc-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div id="trackResult"></div>
    </div>
</div>

@push('scripts')
<script>
const systemConfig = {
    currency: '{{ $systemConfig['currency'] ?? 'USD' }}',
    currencySymbol: '{{ $systemConfig['currency_symbol'] ?? '$' }}',
    decimalPlaces: {{ $systemConfig['decimal_places'] ?? 0 }},
    thousandSeparator: '{{ $systemConfig['thousand_separator'] ?? ',' }}',
    currencyPosition: '{{ $systemConfig['currency_position'] ?? 'before' }}',
    companyName: '{{ $systemConfig['company_name'] ?? 'Baraka Logistics' }}',
    trackingPrefix: '{{ $systemConfig['tracking_prefix'] ?? 'BRK' }}',
    vatRate: {{ $systemConfig['vat_rate'] ?? 18 }},
    fuelSurcharge: {{ $systemConfig['fuel_surcharge'] ?? 8 }},
    insuranceRate: {{ $systemConfig['insurance_rate'] ?? 1.5 }},
    minCharge: {{ $systemConfig['min_charge'] ?? 5000 }},
    baseRatePerKg: {{ $systemConfig['base_rate_per_kg'] ?? 5000 }},
    serviceLevels: @json($systemConfig['service_levels'] ?? []),
    packagePresets: @json($systemConfig['package_presets'] ?? []),
    paymentMethods: @json($systemConfig['payment_methods'] ?? []),
};

let selectedCustomer = null;
let currentPricing = null;
let holdQueue = JSON.parse(localStorage.getItem('branchPosHoldQueue') || '[]');
let searchResults = [];
let debounceTimer = null;
let lastTransaction = JSON.parse(localStorage.getItem('branchPosLastTransaction') || 'null');
let isCalculating = false;

function formatCurrency(amount) {
    const num = parseFloat(amount) || 0;
    const formatted = num.toLocaleString('en-US', {
        minimumFractionDigits: systemConfig.decimalPlaces,
        maximumFractionDigits: systemConfig.decimalPlaces
    });
    return systemConfig.currencyPosition === 'before' 
        ? systemConfig.currencySymbol + formatted 
        : formatted + ' ' + systemConfig.currency;
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showToast(message, type = 'info') {
    const colors = { success: 'bg-green-500', error: 'bg-red-500', warning: 'bg-yellow-500', info: 'bg-blue-500' };
    const toast = document.createElement('div');
    toast.className = `fixed bottom-4 right-4 ${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg z-[9999] transform transition-all duration-300`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 3000);
}

function playSound(type) { console.log('Sound:', type); }
function showLoading() { document.getElementById('sanaaLoadingOverlay').classList.remove('hidden'); }
function hideLoading() { document.getElementById('sanaaLoadingOverlay').classList.add('hidden'); }

document.addEventListener('DOMContentLoaded', function() {
    initKeyboardShortcuts();
    initCustomerSearch();
    initFormListeners();
    initPackagePresets();
    initPaymentMethods();
    selectPayment('cash');
    selectService('standard');
    selectPayer('sender');
    updateHoldCount();
    updateValidationState();
    
    @if(isset($preSelectedCustomer) && $preSelectedCustomer)
    const preCustomer = {
        id: {{ $preSelectedCustomer->id }},
        name: @json($preSelectedCustomer->contact_person ?: $preSelectedCustomer->company_name),
        display: @json($preSelectedCustomer->company_name ? $preSelectedCustomer->contact_person . ' (' . $preSelectedCustomer->company_name . ')' : $preSelectedCustomer->contact_person),
        phone: @json($preSelectedCustomer->phone ?? ''),
        email: @json($preSelectedCustomer->email ?? ''),
        company: @json($preSelectedCustomer->company_name ?? ''),
        code: @json($preSelectedCustomer->customer_code ?? ''),
    };
    selectCustomer(preCustomer);
    @endif
    
    setTimeout(() => { hideLoading(); playSound('ready'); }, 800);
});

window.addEventListener('load', function() { setTimeout(hideLoading, 1000); });

function initFormListeners() {
    const weightInput = document.getElementById('weight');
    if (weightInput) {
        weightInput.addEventListener('input', () => { updateWeightDisplay(); scheduleRateCalculation(); });
        weightInput.addEventListener('change', () => { updateWeightDisplay(); scheduleRateCalculation(); });
    }
    ['length', 'width', 'height'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', () => { updateVolumetricWeight(); scheduleRateCalculation(); });
            el.addEventListener('change', () => { updateVolumetricWeight(); scheduleRateCalculation(); });
        }
    });
    ['declaredValue', 'codAmount', 'pieces'].forEach(id => {
        const el = document.getElementById(id);
        if (el) { el.addEventListener('input', scheduleRateCalculation); el.addEventListener('change', scheduleRateCalculation); }
    });
    const insuranceType = document.getElementById('insuranceType');
    if (insuranceType) insuranceType.addEventListener('change', scheduleRateCalculation);
    const destBranch = document.getElementById('destBranch');
    if (destBranch) destBranch.addEventListener('change', () => { scheduleRateCalculation(); fetchServicePricing(); updateValidationState(); });
    const originBranch = document.getElementById('originBranch');
    if (originBranch) originBranch.addEventListener('change', scheduleRateCalculation);
}

let rateCalcTimer = null;
function scheduleRateCalculation() { clearTimeout(rateCalcTimer); rateCalcTimer = setTimeout(calculateRate, 300); }

function initPackagePresets() {
    const container = document.getElementById('packagePresetsContainer');
    if (!container) return;
    container.innerHTML = systemConfig.packagePresets.map((p, i) => `<button type="button" onclick="applyPackagePreset(${i})" class="quick-action text-xs">${p.name}</button>`).join('');
}

function applyPackagePreset(index) {
    const preset = systemConfig.packagePresets[index];
    if (!preset) return;
    document.getElementById('weight').value = preset.weight;
    document.getElementById('length').value = preset.l;
    document.getElementById('width').value = preset.w;
    document.getElementById('height').value = preset.h;
    updateWeightDisplay();
    updateVolumetricWeight();
    scheduleRateCalculation();
    showToast(`Applied ${preset.name} preset`, 'success');
}

function initPaymentMethods() {
    const container = document.getElementById('paymentMethodsGrid');
    if (!container) return;
    const methods = [
        { key: 'cash', icon: 'cash', label: 'Cash', color: 'green' },
        { key: 'card', icon: 'card', label: 'Card', color: 'blue' },
        { key: 'mobile_money', icon: 'mobile', label: 'Mobile', color: 'yellow' },
        { key: 'credit', icon: 'account', label: 'Account', color: 'purple' },
        { key: 'bank_transfer', icon: 'bank', label: 'Bank Transfer', color: 'cyan' },
    ];
    container.innerHTML = methods.filter(m => systemConfig.paymentMethods[m.key]).map(m => `
        <button type="button" onclick="selectPayment('${m.key}')" class="payment-option" data-method="${m.key}">
            <svg class="w-6 h-6 mx-auto mb-1 text-${m.color}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">${getPaymentIcon(m.key)}</svg>
            <span class="text-sm">${m.label}</span>
        </button>
    `).join('');
}

function getPaymentIcon(method) {
    const icons = {
        cash: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>',
        card: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>',
        mobile_money: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>',
        credit: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
        bank_transfer: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M4 6l8-4 8 4M6 14v6m4-6v6m4-6v6m4-6v6"/>',
    };
    return icons[method] || icons.cash;
}

function initKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
            if (e.key === 'Escape') { e.target.blur(); return; }
            if (e.key !== 'F12' && !e.key.startsWith('F')) return;
        }
        switch(e.key) {
            case 'F2': e.preventDefault(); document.getElementById('customerSearch').focus(); break;
            case 'F3': e.preventDefault(); document.getElementById('weight').focus(); break;
            case 'F4': e.preventDefault(); document.getElementById('receiverName').focus(); break;
            case 'F8': e.preventDefault(); showRepeatLast(); break;
            case 'F9': e.preventDefault(); holdTransaction(); break;
            case 'F12': e.preventDefault(); createShipment(); break;
            case 'Escape': resetForm(); break;
        }
    });
}

function initCustomerSearch() {
    const searchInput = document.getElementById('customerSearch');
    const resultsDiv = document.getElementById('customerResults');
    searchInput.addEventListener('input', function(e) {
        clearTimeout(debounceTimer);
        const query = e.target.value.trim();
        if (query.length < 2) { resultsDiv.classList.add('hidden'); return; }
        resultsDiv.innerHTML = '<div class="p-4 text-center text-zinc-400"><svg class="animate-spin w-5 h-5 inline mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>Searching...</div>';
        resultsDiv.classList.remove('hidden');
        debounceTimer = setTimeout(() => searchCustomer(query), 300);
    });
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) resultsDiv.classList.add('hidden');
    });
    resultsDiv.addEventListener('click', function(e) {
        const item = e.target.closest('[data-index]');
        if (item && searchResults[item.dataset.index]) selectCustomer(searchResults[item.dataset.index]);
    });
}

async function searchCustomer(query) {
    try {
        const response = await fetch(`{{ route('branch.pos.search-customer') }}?q=${encodeURIComponent(query)}`);
        const data = await response.json();
        searchResults = data.results || [];
        const resultsDiv = document.getElementById('customerResults');
        if (searchResults.length === 0) {
            resultsDiv.innerHTML = `<div class="p-4 text-center text-zinc-400">No customers found. <button onclick="showNewCustomerModal()" class="text-red-400 hover:underline">Create new?</button></div>`;
        } else {
            resultsDiv.innerHTML = searchResults.map((c, i) => `
                <div class="customer-result" data-index="${i}">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-zinc-700 rounded-full flex items-center justify-center text-sm font-bold text-white">${(c.name || 'N').charAt(0).toUpperCase()}</div>
                        <div class="flex-1">
                            <div class="font-medium text-white">${escapeHtml(c.display || c.name)}</div>
                            <div class="text-sm text-zinc-400">${escapeHtml(c.phone || '')} ${c.email ? '&bull; ' + escapeHtml(c.email) : ''}</div>
                        </div>
                    </div>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Search error:', error);
        document.getElementById('customerResults').innerHTML = '<div class="p-4 text-center text-red-400">Search failed</div>';
    }
}

function selectCustomer(customer) {
    selectedCustomer = customer;
    document.getElementById('customerId').value = customer.id;
    const initials = (customer.name || 'NA').split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
    document.getElementById('customerInitials').textContent = initials;
    document.getElementById('customerDisplayName').textContent = customer.display || customer.name;
    document.getElementById('customerDisplayInfo').textContent = [customer.phone, customer.email].filter(Boolean).join(' • ');
    const card = document.getElementById('selectedCustomerCard');
    card.classList.remove('hidden');
    document.getElementById('customerSearch').value = '';
    document.getElementById('customerResults').classList.add('hidden');
    updateValidationState();
    showToast(`Customer selected: ${customer.name}`, 'success');
    setTimeout(() => document.getElementById('destBranch').focus(), 100);
}

function clearCustomer() {
    selectedCustomer = null;
    document.getElementById('customerId').value = '';
    document.getElementById('selectedCustomerCard').classList.add('hidden');
    document.getElementById('customerSearch').focus();
    updateValidationState();
}

function updateValidationState() {
    const createBtn = document.getElementById('createBtn');
    const hasCustomer = !!selectedCustomer;
    const hasDestination = !!document.getElementById('destBranch').value;
    const hasWeight = parseFloat(document.getElementById('weight').value) > 0;
    const pricingOk = currentPricing && currentPricing.success !== false;
    const isValid = hasCustomer && hasDestination && hasWeight && pricingOk;
    createBtn.disabled = !isValid;
    createBtn.classList.toggle('opacity-50', !isValid);
    createBtn.classList.toggle('cursor-not-allowed', !isValid);
    return isValid;
}

function updateVolumetricWeight() {
    const length = parseFloat(document.getElementById('length').value) || 0;
    const width = parseFloat(document.getElementById('width').value) || 0;
    const height = parseFloat(document.getElementById('height').value) || 0;
    const actualWeight = parseFloat(document.getElementById('weight').value) || 0;
    const volWeight = (length * width * height) / 5000;
    const chargeWeight = Math.max(actualWeight, volWeight);
    document.getElementById('volWeight').textContent = volWeight.toFixed(2) + ' kg';
    document.getElementById('chargeWeight').textContent = chargeWeight.toFixed(2) + ' kg';
    const chargeEl = document.getElementById('chargeWeight');
    if (volWeight > actualWeight && volWeight > 0) {
        chargeEl.classList.add('text-yellow-400'); chargeEl.classList.remove('text-green-400');
    } else {
        chargeEl.classList.remove('text-yellow-400'); chargeEl.classList.add('text-green-400');
    }
    return chargeWeight;
}

function selectService(service) {
    document.querySelectorAll('.service-card').forEach(el => el.classList.remove('selected'));
    document.querySelector(`[data-service="${service}"]`)?.classList.add('selected');
    document.getElementById('serviceLevel').value = service;
    scheduleRateCalculation();
}

function selectPayer(payer) {
    document.querySelectorAll('[data-payer]').forEach(el => el.classList.remove('selected'));
    document.querySelector(`[data-payer="${payer}"]`)?.classList.add('selected');
    document.getElementById('payerType').value = payer;
    const paymentSection = document.getElementById('paymentSection');
    const amountSection = document.getElementById('amountReceivedSection');
    if (payer === 'sender') { paymentSection.style.display = 'block'; amountSection.style.display = 'block'; }
    else { paymentSection.style.display = 'none'; amountSection.style.display = 'none'; }
}

function selectPayment(method) {
    document.querySelectorAll('[data-method]').forEach(el => el.classList.remove('selected'));
    document.querySelector(`[data-method="${method}"]`)?.classList.add('selected');
    document.getElementById('paymentMethod').value = method;
}

function updateWeightDisplay() {
    const weight = parseFloat(document.getElementById('weight').value) || 0;
    document.getElementById('weightDisplay').textContent = weight.toFixed(2);
    updateVolumetricWeight();
    updateValidationState();
}

function syncScale() {
    const simulatedWeight = (Math.random() * 10 + 0.5).toFixed(2);
    document.getElementById('weight').value = simulatedWeight;
    updateWeightDisplay();
    calculateRate();
}

async function calculateRate() {
    if (isCalculating) return;
    const originBranch = document.getElementById('originBranch')?.value;
    const destBranch = document.getElementById('destBranch')?.value;
    const serviceLevel = document.getElementById('serviceLevel')?.value || 'standard';
    const weight = parseFloat(document.getElementById('weight')?.value) || 0;
    const length = parseFloat(document.getElementById('length')?.value) || 0;
    const width = parseFloat(document.getElementById('width')?.value) || 0;
    const height = parseFloat(document.getElementById('height')?.value) || 0;
    const volWeight = (length * width * height) / 5000;
    const chargeWeight = Math.max(weight, volWeight);
    
    const volEl = document.getElementById('volWeight');
    const chargeEl = document.getElementById('chargeWeight');
    if (volEl) volEl.textContent = volWeight.toFixed(2) + ' kg';
    if (chargeEl) {
        chargeEl.textContent = chargeWeight.toFixed(2) + ' kg';
        chargeEl.className = volWeight > weight && volWeight > 0 ? 'text-yellow-400 font-medium' : 'text-green-400 font-medium';
    }
    
    if (!destBranch || weight <= 0) return;
    isCalculating = true;
    
    try {
        const response = await fetch('{{ route('branch.pos.calculate-rate') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({
                origin_branch_id: originBranch,
                dest_branch_id: destBranch,
                service_level: serviceLevel,
                weight: weight,
                length: length,
                width: width,
                height: height,
                declared_value: parseFloat(document.getElementById('declaredValue')?.value) || 0,
                insurance_type: document.getElementById('insuranceType')?.value || 'none',
                cod_amount: parseFloat(document.getElementById('codAmount')?.value) || 0,
            })
        });
        
        if (!response.ok) {
            currentPricing = { success: false, error: 'Rate calculation failed' };
            updatePricingDisplay(currentPricing);
            updateValidationState();
            return;
        }
        
        const data = await response.json();
        currentPricing = data;
        updatePricingDisplay(data);
        updateValidationState();
    } catch (error) {
        console.error('Rate calculation error:', error);
        currentPricing = { success: false, error: 'Rate calculation error' };
        updateValidationState();
    } finally {
        isCalculating = false;
    }
}

function updatePricingDisplay(pricing) {
    if (!pricing) {
        document.getElementById('baseRate').textContent = '--';
        document.getElementById('weightCharge').textContent = '--';
        document.getElementById('surcharges').textContent = '--';
        document.getElementById('insuranceAmount').textContent = '--';
        document.getElementById('codFee').textContent = '--';
        document.getElementById('taxAmount').textContent = '--';
        document.getElementById('totalAmount').textContent = '--';
        document.getElementById('surchargeRow').style.display = 'none';
        document.getElementById('insuranceRow').style.display = 'none';
        document.getElementById('codFeeRow').style.display = 'none';
        return;
    }
    if (pricing.success === false) {
        showToast(pricing.error || 'Pricing unavailable for this route', 'error');
        document.getElementById('baseRate').textContent = '--';
        document.getElementById('weightCharge').textContent = '--';
        document.getElementById('totalAmount').textContent = '--';
        document.getElementById('surchargeRow').style.display = 'none';
        document.getElementById('insuranceRow').style.display = 'none';
        document.getElementById('codFeeRow').style.display = 'none';
        return;
    }
    const cs = systemConfig.currencySymbol;
    const baseRate = typeof pricing.base_rate === 'object' ? (pricing.base_rate?.amount || 0) : (pricing.base_rate || 0);
    document.getElementById('baseRate').textContent = cs + baseRate.toFixed(2);
    document.getElementById('weightCharge').textContent = cs + (pricing.weight_charge || 0).toFixed(2);
    let surchargesTotal = 0;
    if (Array.isArray(pricing.surcharges)) surchargesTotal = pricing.surcharges.reduce((sum, s) => sum + (s.amount || 0), 0);
    else if (typeof pricing.surcharges === 'number') surchargesTotal = pricing.surcharges;
    surchargesTotal += (pricing.fuel_surcharge || 0);
    document.getElementById('surchargeRow').style.display = surchargesTotal > 0 ? 'flex' : 'none';
    document.getElementById('surcharges').textContent = cs + surchargesTotal.toFixed(2);
    const insurance = typeof pricing.insurance === 'object' ? (pricing.insurance?.amount || 0) : (pricing.insurance || 0);
    document.getElementById('insuranceRow').style.display = insurance > 0 ? 'flex' : 'none';
    document.getElementById('insuranceAmount').textContent = cs + insurance.toFixed(2);
    const codFee = pricing.cod_fee || 0;
    document.getElementById('codFeeRow').style.display = codFee > 0 ? 'flex' : 'none';
    document.getElementById('codFee').textContent = cs + codFee.toFixed(2);
    document.getElementById('taxAmount').textContent = cs + (pricing.tax || 0).toFixed(2);
    document.getElementById('totalAmount').textContent = cs + (pricing.total || 0).toFixed(2);
}

function onRouteChange() { calculateRate(); fetchServicePricing(); updateValidationState(); }

async function fetchServicePricing() {
    const originBranch = document.getElementById('originBranch').value;
    const destBranch = document.getElementById('destBranch').value;
    const weight = parseFloat(document.getElementById('weight').value) || 1;
    if (!destBranch) return;
    try {
        const response = await fetch(`{{ route('branch.pos.service-levels') }}?origin_branch_id=${originBranch}&dest_branch_id=${destBranch}&weight=${weight}`);
        const data = await response.json();
        if (data.success && data.service_levels) {
            data.service_levels.forEach(level => {
                const priceEl = document.getElementById(level.service + 'Price');
                if (priceEl) priceEl.textContent = '$' + (level.total || 0).toFixed(2);
            });
        }
    } catch (error) { console.error('Service pricing error:', error); }
}

function calculateChange() {
    const total = currentPricing?.total || 0;
    const received = parseFloat(document.getElementById('amountReceived').value) || 0;
    const changeDisplay = document.getElementById('changeDisplay');
    if (received >= total && received > 0) {
        document.getElementById('changeAmount').textContent = systemConfig.currencySymbol + (received - total).toFixed(2);
        changeDisplay.classList.remove('hidden');
    } else { changeDisplay.classList.add('hidden'); }
}

async function createShipment() {
    if (!selectedCustomer) { showToast('Please select a customer', 'error'); document.getElementById('customerSearch').focus(); return; }
    if (!currentPricing || currentPricing.success === false) { showToast('Please calculate a valid rate before creating a shipment', 'error'); calculateRate(); return; }
    const weight = parseFloat(document.getElementById('weight').value);
    if (!weight || weight <= 0) { showToast('Please enter a valid weight', 'error'); document.getElementById('weight').focus(); return; }
    const destBranch = document.getElementById('destBranch').value;
    if (!destBranch) { showToast('Please select a destination', 'error'); document.getElementById('destBranch').focus(); return; }
    
    showLoading();
    const btn = document.getElementById('createBtn');
    btn.disabled = true;
    btn.innerHTML = '<svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Processing...';
    
    try {
        const response = await fetch('{{ route('branch.pos.create-shipment') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({
                customer_id: selectedCustomer.id,
                origin_branch_id: document.getElementById('originBranch').value,
                dest_branch_id: destBranch,
                service_level: document.getElementById('serviceLevel').value,
                payer_type: document.getElementById('payerType').value,
                weight: weight,
                length: parseFloat(document.getElementById('length').value) || null,
                width: parseFloat(document.getElementById('width').value) || null,
                height: parseFloat(document.getElementById('height').value) || null,
                description: document.getElementById('description').value,
                pieces: parseInt(document.getElementById('pieces').value) || 1,
                declared_value: parseFloat(document.getElementById('declaredValue').value) || 0,
                insurance_type: document.getElementById('insuranceType').value,
                cod_amount: parseFloat(document.getElementById('codAmount').value) || 0,
                payment_method: document.getElementById('paymentMethod').value,
                amount_received: parseFloat(document.getElementById('amountReceived').value) || 0,
                receiver_name: document.getElementById('receiverName').value,
                receiver_phone: document.getElementById('receiverPhone').value,
                delivery_address: document.getElementById('deliveryAddress').value,
                special_instructions: document.getElementById('specialInstructions').value,
                is_fragile: document.getElementById('isFragile').checked,
                requires_signature: document.getElementById('requiresSignature').checked,
            })
        });
        const data = await response.json();
        if (data.success) { showSuccessModal(data.shipment, data.urls); }
        else { showToast(data.message || 'Failed to create shipment', 'error'); }
    } catch (error) { console.error('Create error:', error); showToast('An error occurred', 'error'); }
    finally {
        hideLoading();
        btn.disabled = false;
        btn.innerHTML = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg> Create Shipment <span class="kbd ml-2 bg-red-700">F12</span>';
    }
}

function showSuccessModal(shipment, urls) {
    lastTransaction = {
        originBranch: document.getElementById('originBranch').value,
        destBranch: document.getElementById('destBranch').value,
        serviceLevel: document.getElementById('serviceLevel').value,
        payerType: document.getElementById('payerType').value,
        receiverName: document.getElementById('receiverName').value,
        receiverPhone: document.getElementById('receiverPhone').value,
        deliveryAddress: document.getElementById('deliveryAddress').value,
    };
    localStorage.setItem('branchPosLastTransaction', JSON.stringify(lastTransaction));
    document.getElementById('newTrackingNumber').textContent = shipment.tracking_number;
    document.getElementById('successTotal').textContent = systemConfig.currencySymbol + (shipment.total || 0).toFixed(2);
    document.getElementById('successPayment').textContent = document.getElementById('paymentMethod').value.replace('_', ' ').toUpperCase();
    document.getElementById('printLabelBtn').href = urls.label;
    document.getElementById('printReceiptBtn').href = urls.receipt;
    document.getElementById('successModal').classList.add('show');
}

function closeSuccessAndReset() { document.getElementById('successModal').classList.remove('show'); resetForm(); location.reload(); }

function holdTransaction() {
    if (!selectedCustomer) { showToast('Nothing to hold', 'warning'); return; }
    const transaction = {
        id: Date.now(),
        customer: selectedCustomer,
        originBranch: document.getElementById('originBranch').value,
        destBranch: document.getElementById('destBranch').value,
        serviceLevel: document.getElementById('serviceLevel').value,
        payerType: document.getElementById('payerType').value,
        weight: document.getElementById('weight').value,
        length: document.getElementById('length').value,
        width: document.getElementById('width').value,
        height: document.getElementById('height').value,
        description: document.getElementById('description').value,
        pieces: document.getElementById('pieces').value,
        declaredValue: document.getElementById('declaredValue').value,
        insuranceType: document.getElementById('insuranceType').value,
        codAmount: document.getElementById('codAmount').value,
        receiverName: document.getElementById('receiverName').value,
        receiverPhone: document.getElementById('receiverPhone').value,
        deliveryAddress: document.getElementById('deliveryAddress').value,
        specialInstructions: document.getElementById('specialInstructions').value,
        isFragile: document.getElementById('isFragile').checked,
        requiresSignature: document.getElementById('requiresSignature').checked,
        pricing: currentPricing,
        timestamp: new Date().toISOString()
    };
    holdQueue.push(transaction);
    localStorage.setItem('branchPosHoldQueue', JSON.stringify(holdQueue));
    updateHoldCount();
    showToast('Transaction held (#' + holdQueue.length + ')', 'success');
    resetForm();
}

function updateHoldCount() { document.getElementById('holdCount').textContent = holdQueue.length; }

function showHoldQueue() {
    if (holdQueue.length === 0) { showToast('No held transactions', 'info'); return; }
    let html = '<div class="space-y-3 max-h-96 overflow-y-auto">';
    holdQueue.forEach((t, i) => {
        html += `<div class="bg-zinc-800 rounded-lg p-4">
            <div class="flex justify-between items-start mb-2">
                <div><div class="font-semibold text-white">${escapeHtml(t.customer?.name || 'Unknown')}</div><div class="text-xs text-zinc-500">${new Date(t.timestamp).toLocaleString()}</div></div>
                <div class="text-right"><div class="text-green-400 font-semibold">${systemConfig.currencySymbol}${(t.pricing?.total || 0).toFixed(2)}</div><div class="text-xs text-zinc-500">${t.weight || 0} kg</div></div>
            </div>
            <div class="flex gap-2"><button onclick="restoreHeld(${i})" class="flex-1 py-2 bg-blue-600 hover:bg-blue-500 text-white text-sm rounded-lg">Restore</button><button onclick="removeHeld(${i})" class="px-4 py-2 bg-red-600/20 hover:bg-red-600/40 text-red-400 text-sm rounded-lg">Remove</button></div>
        </div>`;
    });
    html += '</div>';
    document.getElementById('trackResult').innerHTML = html;
    document.getElementById('trackModal').classList.add('show');
}

function restoreHeld(index) {
    const t = holdQueue[index];
    if (!t) return;
    selectCustomer(t.customer);
    document.getElementById('originBranch').value = t.originBranch || '';
    document.getElementById('destBranch').value = t.destBranch || '';
    selectService(t.serviceLevel || 'standard');
    selectPayer(t.payerType || 'sender');
    document.getElementById('weight').value = t.weight || '';
    updateWeightDisplay();
    document.getElementById('length').value = t.length || '';
    document.getElementById('width').value = t.width || '';
    document.getElementById('height').value = t.height || '';
    document.getElementById('description').value = t.description || '';
    document.getElementById('pieces').value = t.pieces || '1';
    document.getElementById('declaredValue').value = t.declaredValue || '';
    document.getElementById('insuranceType').value = t.insuranceType || 'none';
    document.getElementById('codAmount').value = t.codAmount || '';
    document.getElementById('receiverName').value = t.receiverName || '';
    document.getElementById('receiverPhone').value = t.receiverPhone || '';
    document.getElementById('deliveryAddress').value = t.deliveryAddress || '';
    document.getElementById('specialInstructions').value = t.specialInstructions || '';
    document.getElementById('isFragile').checked = t.isFragile || false;
    document.getElementById('requiresSignature').checked = t.requiresSignature || false;
    if (t.pricing) { currentPricing = t.pricing; updatePricingDisplay(t.pricing); }
    holdQueue.splice(index, 1);
    localStorage.setItem('branchPosHoldQueue', JSON.stringify(holdQueue));
    updateHoldCount();
    hideTrackModal();
    showToast('Transaction restored', 'success');
}

function removeHeld(index) {
    holdQueue.splice(index, 1);
    localStorage.setItem('branchPosHoldQueue', JSON.stringify(holdQueue));
    updateHoldCount();
    showHoldQueue();
    showToast('Transaction removed', 'info');
}

function showRepeatLast() {
    if (!lastTransaction) { showToast('No previous transaction to repeat', 'info'); return; }
    document.getElementById('originBranch').value = lastTransaction.originBranch || '';
    document.getElementById('destBranch').value = lastTransaction.destBranch || '';
    selectService(lastTransaction.serviceLevel || 'standard');
    selectPayer(lastTransaction.payerType || 'sender');
    document.getElementById('receiverName').value = lastTransaction.receiverName || '';
    document.getElementById('receiverPhone').value = lastTransaction.receiverPhone || '';
    document.getElementById('deliveryAddress').value = lastTransaction.deliveryAddress || '';
    showToast('Last transaction data loaded (select customer)', 'success');
    document.getElementById('customerSearch').focus();
}

async function quickTrack() {
    const tracking = document.getElementById('quickTrackInput').value.trim();
    if (!tracking) return;
    try {
        const response = await fetch(`{{ route('branch.pos.quick-track') }}?tracking=${encodeURIComponent(tracking)}`);
        const data = await response.json();
        if (data.success) showTrackResult(data.shipment);
        else showToast('Shipment not found', 'error');
    } catch (error) { showToast('Track failed', 'error'); }
}

function showTrackResult(shipment) {
    document.getElementById('trackResult').innerHTML = `
        <div class="text-center mb-4">
            <div class="font-mono text-2xl text-white mb-2">${shipment.tracking_number}</div>
            <div class="inline-block px-3 py-1 rounded-full text-sm font-semibold ${getStatusClass(shipment.status)}">${shipment.status}</div>
        </div>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between"><span class="text-zinc-400">Customer:</span><span class="text-white">${shipment.customer || 'N/A'}</span></div>
            <div class="flex justify-between"><span class="text-zinc-400">Origin:</span><span class="text-white">${shipment.origin || 'N/A'}</span></div>
            <div class="flex justify-between"><span class="text-zinc-400">Destination:</span><span class="text-white">${shipment.destination || 'N/A'}</span></div>
            <div class="flex justify-between"><span class="text-zinc-400">Created:</span><span class="text-white">${shipment.created_at}</span></div>
        </div>
    `;
    document.getElementById('trackModal').classList.add('show');
}

function hideTrackModal() { document.getElementById('trackModal').classList.remove('show'); }

function getStatusClass(status) {
    const classes = { 'BOOKED': 'bg-blue-500/20 text-blue-400', 'PICKED_UP': 'bg-yellow-500/20 text-yellow-400', 'IN_TRANSIT': 'bg-purple-500/20 text-purple-400', 'OUT_FOR_DELIVERY': 'bg-orange-500/20 text-orange-400', 'DELIVERED': 'bg-green-500/20 text-green-400' };
    return classes[status] || 'bg-zinc-500/20 text-zinc-400';
}

function resetForm() {
    clearCustomer();
    document.getElementById('destBranch').value = '';
    document.getElementById('weight').value = '';
    updateWeightDisplay();
    document.getElementById('length').value = '';
    document.getElementById('width').value = '';
    document.getElementById('height').value = '';
    document.getElementById('description').value = '';
    document.getElementById('pieces').value = '1';
    document.getElementById('declaredValue').value = '';
    document.getElementById('insuranceType').value = 'none';
    document.getElementById('codAmount').value = '';
    document.getElementById('receiverName').value = '';
    document.getElementById('receiverPhone').value = '';
    document.getElementById('deliveryAddress').value = '';
    document.getElementById('specialInstructions').value = '';
    document.getElementById('isFragile').checked = false;
    document.getElementById('requiresSignature').checked = false;
    document.getElementById('amountReceived').value = '';
    document.getElementById('changeDisplay').classList.add('hidden');
    document.getElementById('volWeight').textContent = '0.00 kg';
    document.getElementById('chargeWeight').textContent = '0.00 kg';
    selectService('standard');
    selectPayment('cash');
    selectPayer('sender');
    updatePricingDisplay({});
    currentPricing = null;
    updateValidationState();
    showToast('Form cleared', 'info');
    document.getElementById('customerSearch').focus();
}

function showNewCustomerModal() {
    document.getElementById('customerResults').classList.add('hidden');
    document.getElementById('newCustomerModal').classList.add('show');
    document.getElementById('newCustomerName').focus();
}

function hideNewCustomerModal() { document.getElementById('newCustomerModal').classList.remove('show'); }

async function createCustomer() {
    const name = document.getElementById('newCustomerName').value.trim();
    const phone = document.getElementById('newCustomerPhone').value.trim();
    if (!name || !phone) { showToast('Name and phone are required', 'error'); return; }
    try {
        const response = await fetch('{{ route('branch.pos.quick-create-customer') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({
                name: name,
                phone: phone,
                email: document.getElementById('newCustomerEmail').value,
                company: document.getElementById('newCustomerCompany').value,
                address: document.getElementById('newCustomerAddress').value,
            })
        });
        const data = await response.json();
        if (data.success) {
            selectCustomer({ id: data.customer.id, name: data.customer.name, display: data.customer.company ? `${data.customer.name} (${data.customer.company})` : data.customer.name, phone: data.customer.phone, email: data.customer.email });
            hideNewCustomerModal();
            document.getElementById('newCustomerName').value = '';
            document.getElementById('newCustomerPhone').value = '';
            document.getElementById('newCustomerEmail').value = '';
            document.getElementById('newCustomerCompany').value = '';
            document.getElementById('newCustomerAddress').value = '';
            showToast('Customer created', 'success');
        } else { showToast(data.message || 'Failed to create customer', 'error'); }
    } catch (error) { showToast('An error occurred', 'error'); }
}

function viewCustomerHistory() { showToast('Customer history coming soon', 'info'); }
function showAddressBook() { showToast('Address book coming soon', 'info'); }
</script>
@endpush
@endsection
