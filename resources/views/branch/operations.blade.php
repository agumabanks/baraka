@extends('branch.layout')

@section('title', 'Operations Board')

@push('styles')
<style>
    .stat-card {
        background: linear-gradient(135deg, rgba(255,255,255,0.08) 0%, rgba(255,255,255,0.03) 100%);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        padding: 16px 20px;
    }
    .stat-value { font-size: 28px; font-weight: 700; }
    .stat-label { font-size: 11px; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.5px; }
    
    .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
    .status-booked { background: rgba(59,130,246,0.2); color: #60a5fa; }
    .status-picked_up { background: rgba(168,85,247,0.2); color: #a78bfa; }
    .status-in_transit, .status-linehaul_departed { background: rgba(251,191,36,0.2); color: #fbbf24; }
    .status-at_origin_hub, .status-at_destination_hub { background: rgba(20,184,166,0.2); color: #2dd4bf; }
    .status-out_for_delivery { background: rgba(249,115,22,0.2); color: #fb923c; }
    .status-delivered { background: rgba(34,197,94,0.2); color: #22c55e; }
    .status-cancelled, .status-returned { background: rgba(239,68,68,0.2); color: #ef4444; }
    .status-created, .status-processing { background: rgba(100,116,139,0.2); color: #94a3b8; }
    
    .action-dropdown { position: relative; display: inline-block; }
    .action-dropdown-content { 
        display: none; position: absolute; right: 0; top: 100%; min-width: 200px; 
        background: #1f1f23; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; 
        box-shadow: 0 10px 40px rgba(0,0,0,0.5); z-index: 50; overflow: hidden;
    }
    .action-dropdown:hover .action-dropdown-content,
    .action-dropdown:focus-within .action-dropdown-content { display: block; }
    .action-dropdown-content a, .action-dropdown-content button { 
        display: flex; align-items: center; gap: 10px; width: 100%; padding: 10px 14px; 
        font-size: 13px; color: #a1a1aa; text-align: left; border: none; background: none;
        transition: all 0.15s; cursor: pointer;
    }
    .action-dropdown-content a:hover, .action-dropdown-content button:hover { 
        background: rgba(255,255,255,0.08); color: white; 
    }
    .action-dropdown-content .divider { border-top: 1px solid rgba(255,255,255,0.1); margin: 4px 0; }
    .action-dropdown-content .danger:hover { background: rgba(239,68,68,0.2); color: #ef4444; }
    
    .alert-card { border-radius: 8px; padding: 12px; }
    .alert-critical { background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); }
    .alert-warning { background: rgba(251,191,36,0.15); border: 1px solid rgba(251,191,36,0.3); }
    .alert-info { background: rgba(59,130,246,0.15); border: 1px solid rgba(59,130,246,0.3); }
    
    .sla-risk-row { background: rgba(239,68,68,0.08) !important; }
    .sla-risk-badge { background: rgba(239,68,68,0.2); color: #ef4444; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 600; }
    
    .filter-input {
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 6px;
        padding: 8px 12px;
        color: white;
        font-size: 13px;
    }
    .filter-input:focus {
        outline: none;
        border-color: #10b981;
        box-shadow: 0 0 0 2px rgba(16,185,129,0.2);
    }
    
    .panel-section { 
        background: rgba(255,255,255,0.03); 
        border: 1px solid rgba(255,255,255,0.08); 
        border-radius: 10px; 
        padding: 16px; 
    }
    .panel-title { font-size: 14px; font-weight: 600; margin-bottom: 12px; }
</style>
@endpush

@section('content')
    {{-- Maintenance Alert Banner --}}
    @if($activeMaintenance->isNotEmpty())
        <div class="bg-amber-500/10 border border-amber-500/30 rounded-lg p-4 mb-6">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-amber-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="flex-1">
                    <div class="font-semibold text-amber-200">Maintenance in Progress</div>
                    <p class="text-sm text-amber-300/70">Capacity limited to {{ $maintenanceCapacity }}% · {{ $activeMaintenance->count() }} active window(s)</p>
                </div>
                <span class="px-3 py-1 bg-amber-500/20 text-amber-300 rounded-full text-xs font-semibold">{{ $maintenanceCapacity }}% CAPACITY</span>
            </div>
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-6">
        <div class="stat-card">
            <div class="stat-value text-white">{{ $backlog }}</div>
            <div class="stat-label">Unassigned</div>
        </div>
        <div class="stat-card">
            <div class="stat-value text-sky-400">{{ $shipments->total() }}</div>
            <div class="stat-label">In Queue</div>
        </div>
        <div class="stat-card">
            <div class="stat-value text-amber-400">{{ $alerts->count() }}</div>
            <div class="stat-label">Open Alerts</div>
        </div>
        <div class="stat-card">
            <div class="stat-value text-emerald-400">{{ $workers->where('status', 'active')->count() }}</div>
            <div class="stat-label">Active Workers</div>
        </div>
        <div class="stat-card col-span-2">
            <div class="flex items-center justify-between">
                <div>
                    <div class="stat-value text-white">{{ $branch->name }}</div>
                    <div class="stat-label">Current Branch</div>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold {{ $maintenanceCapacity < 100 ? 'text-amber-400' : 'text-emerald-400' }}">{{ $maintenanceCapacity }}%</div>
                    <div class="stat-label">Capacity</div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Main Queue Panel --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Filters --}}
            <div class="glass-panel p-4">
                <form method="GET" action="{{ route('branch.operations') }}" class="grid grid-cols-2 md:grid-cols-6 gap-3">
                    <select name="direction" class="filter-input">
                        @foreach(['outbound' => 'Outbound', 'inbound' => 'Inbound', 'all' => 'All Directions'] as $key => $label)
                            <option value="{{ $key }}" @selected($filters['direction'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="filter-input">
                        <option value="">All Statuses</option>
                        @foreach(\App\Enums\ShipmentStatus::orderedLifecycle() as $status)
                            <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                    <input type="date" name="from" value="{{ $filters['from'] }}" class="filter-input" placeholder="From">
                    <input type="date" name="to" value="{{ $filters['to'] }}" class="filter-input" placeholder="To">
                    <label class="filter-input flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="sla_risk" value="1" @checked($filters['sla_risk']) class="accent-emerald-500">
                        <span class="text-sm">SLA Risk Only</span>
                    </label>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-medium transition">
                        Apply Filters
                    </button>
                </form>
            </div>

            {{-- Shipments Table --}}
            <div class="glass-panel overflow-hidden">
                <div class="p-4 border-b border-white/10 flex items-center justify-between">
                    <div>
                        <div class="font-semibold">Live Operations Queue</div>
                        <div class="text-xs text-zinc-500">{{ $shipments->total() }} shipments · {{ $filters['direction'] }} direction</div>
                    </div>
                    <a href="{{ route('branch.operations.manifest.shipments', ['direction' => $filters['direction'], 'format' => 'pdf']) }}" 
                       class="px-3 py-1.5 bg-white/5 hover:bg-white/10 text-zinc-400 hover:text-white rounded-lg text-sm transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export
                    </a>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-white/5">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-zinc-400">Tracking</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-zinc-400">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-zinc-400">Route</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-zinc-400">Assigned</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-zinc-400">Updated</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-zinc-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @forelse($shipments as $shipment)
                                @php
                                    $isSlaRisk = false;
                                    $hoursToDeadline = null;
                                    if (isset($shipment->expected_delivery_date)) {
                                        $hoursToDeadline = now()->diffInHours($shipment->expected_delivery_date, false);
                                        if ($shipment->delivered_at && $shipment->delivered_at > $shipment->expected_delivery_date) {
                                            $isSlaRisk = true;
                                        } elseif (!$shipment->delivered_at && $shipment->expected_delivery_date <= now()->addHours(24)) {
                                            $isSlaRisk = true;
                                        }
                                    }
                                    $statusValue = is_object($shipment->current_status) ? strtolower($shipment->current_status->value) : strtolower($shipment->current_status ?? 'created');
                                @endphp
                                <tr class="hover:bg-white/5 transition {{ $isSlaRisk ? 'sla-risk-row' : '' }}">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('branch.shipments.show', $shipment) }}" class="font-mono text-sm font-semibold text-emerald-400 hover:text-emerald-300">
                                                {{ $shipment->tracking_number ?? 'TRK-' . str_pad($shipment->id, 6, '0', STR_PAD_LEFT) }}
                                            </a>
                                            @if($isSlaRisk)
                                                <span class="sla-risk-badge">SLA RISK</span>
                                            @endif
                                        </div>
                                        @if($isSlaRisk && $hoursToDeadline !== null)
                                            <div class="text-xs text-rose-400 mt-0.5">{{ $hoursToDeadline > 0 ? $hoursToDeadline . 'h remaining' : abs($hoursToDeadline) . 'h overdue' }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="status-badge status-{{ str_replace(' ', '_', $statusValue) }}">
                                            {{ ucfirst(str_replace('_', ' ', $statusValue)) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        @if($shipment->origin_branch_id === $branch->id)
                                            <span class="text-sky-400">OUT</span>
                                            <span class="text-zinc-500">→</span>
                                            <span class="text-zinc-300">{{ $shipment->destBranch?->code ?? 'N/A' }}</span>
                                        @else
                                            <span class="text-amber-400">IN</span>
                                            <span class="text-zinc-500">←</span>
                                            <span class="text-zinc-300">{{ $shipment->originBranch?->code ?? 'N/A' }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($shipment->assignedWorker)
                                            <div class="text-sm text-white">{{ $shipment->assignedWorker->user?->name }}</div>
                                        @else
                                            <span class="text-xs text-amber-400 bg-amber-400/10 px-2 py-1 rounded">Unassigned</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-zinc-400">
                                        {{ $shipment->updated_at?->diffForHumans() ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="action-dropdown">
                                            <button class="px-3 py-1.5 bg-white/5 hover:bg-white/10 rounded-lg text-sm text-zinc-400 hover:text-white transition flex items-center gap-1">
                                                Actions
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            </button>
                                            <div class="action-dropdown-content">
                                                <a href="{{ route('branch.shipments.show', $shipment) }}">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                    View Details
                                                </a>
                                                <button onclick="showAssignModal({{ $shipment->id }})">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                    Assign Worker
                                                </button>
                                                <button onclick="showStatusModal({{ $shipment->id }})">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                    Update Status
                                                </button>
                                                <div class="divider"></div>
                                                @if($isSlaRisk)
                                                    <form method="POST" action="{{ route('branch.operations.alerts.raise') }}" class="contents">
                                                        @csrf
                                                        <input type="hidden" name="shipment_id" value="{{ $shipment->id }}">
                                                        <input type="hidden" name="severity" value="critical">
                                                        <input type="hidden" name="message" value="SLA risk: approaching deadline">
                                                        <button type="submit" class="danger">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                            Escalate SLA Risk
                                                        </button>
                                                    </form>
                                                @endif
                                                <button onclick="showRerouteModal({{ $shipment->id }})">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                                    Reroute
                                                </button>
                                                <form method="POST" action="{{ route('branch.operations.hold') }}" class="contents">
                                                    @csrf
                                                    <input type="hidden" name="shipment_id" value="{{ $shipment->id }}">
                                                    <button type="submit">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                        Place on Hold
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-12 text-center">
                                        <svg class="w-12 h-12 mx-auto text-zinc-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                        <div class="text-zinc-400">No shipments match the filters</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @include('branch.shipments._pagination', ['shipments' => $shipments, 'perPage' => $perPage ?? 15])
            </div>
        </div>

        {{-- Sidebar Panels --}}
        <div class="space-y-4">
            {{-- Scanner Mode --}}
            <div class="panel-section">
                <div class="panel-title flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                    </svg>
                    Scanner Mode
                </div>
                <form method="POST" action="{{ route('branch.operations.scan') }}" class="space-y-3">
                    @csrf
                    <input type="text" name="tracking_number" placeholder="Scan or type tracking #" 
                           class="w-full filter-input" autofocus>
                    <select name="mode" class="w-full filter-input">
                        <option value="route">Route / OFD</option>
                        <option value="delivery">Delivery Confirm</option>
                        <option value="bag">Bag</option>
                        <option value="load">Load to Vehicle</option>
                        <option value="unload">Unload at Hub</option>
                        <option value="returns">Returns</option>
                    </select>
                    <button type="submit" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-medium transition">
                        Process Scan
                    </button>
                </form>
            </div>

            {{-- Open Alerts --}}
            <div class="panel-section">
                <div class="panel-title flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        Open Alerts
                    </span>
                    <span class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-xs font-semibold">{{ $alerts->count() }}</span>
                </div>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    @forelse($alerts as $alert)
                        @php
                            $alertClass = match(strtolower($alert->severity)) {
                                'critical' => 'alert-critical',
                                'warning', 'high' => 'alert-warning',
                                default => 'alert-info'
                            };
                        @endphp
                        <div class="alert-card {{ $alertClass }}">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-sm truncate">{{ $alert->title }}</div>
                                    <p class="text-xs text-zinc-400 mt-0.5">{{ Str::limit($alert->message, 60) }}</p>
                                    @if(isset($alert->context['tracking_number']))
                                        <p class="text-xs text-amber-300 mt-1">{{ $alert->context['tracking_number'] }}</p>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('branch.operations.alerts.resolve', $alert) }}">
                                    @csrf
                                    <button type="submit" class="text-xs px-2 py-1 bg-white/10 hover:bg-white/20 rounded transition">
                                        Resolve
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500 text-center py-4">No open alerts</p>
                    @endforelse
                </div>
            </div>

            {{-- Pending Handoffs --}}
            <div class="panel-section">
                <div class="panel-title flex items-center justify-between">
                    <span class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                        Handoffs
                    </span>
                    <a href="{{ route('branch.operations.handoff.manifest.batch', ['format' => 'pdf']) }}" 
                       class="text-xs text-sky-400 hover:text-sky-300">Export All</a>
                </div>
                @php
                    $handoffs = \App\Models\BranchHandoff::where(function($q) use ($branch) {
                            $q->where('dest_branch_id', $branch->id)->orWhere('origin_branch_id', $branch->id);
                        })
                        ->whereIn('status', ['PENDING','APPROVED'])
                        ->with(['shipment', 'originBranch', 'destBranch'])
                        ->latest()
                        ->limit(5)
                        ->get();
                @endphp
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    @forelse($handoffs as $handoff)
                        <div class="bg-white/5 rounded-lg p-3">
                            <div class="flex items-center justify-between">
                                <span class="font-mono text-sm text-emerald-400">{{ $handoff->shipment?->tracking_number }}</span>
                                <span class="text-xs px-2 py-0.5 rounded {{ $handoff->status === 'PENDING' ? 'bg-amber-500/20 text-amber-400' : 'bg-emerald-500/20 text-emerald-400' }}">
                                    {{ $handoff->status }}
                                </span>
                            </div>
                            <p class="text-xs text-zinc-400 mt-1">
                                {{ $handoff->originBranch?->code }} → {{ $handoff->destBranch?->code }}
                            </p>
                            <div class="flex gap-2 mt-2">
                                @if($handoff->status === 'PENDING' && $handoff->dest_branch_id === $branch->id)
                                    <form method="POST" action="{{ route('branch.operations.handoff.approve', $handoff) }}">
                                        @csrf
                                        <button class="text-xs px-2 py-1 bg-emerald-600 hover:bg-emerald-500 rounded transition">Approve</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('branch.operations.handoff.complete', $handoff) }}">
                                    @csrf
                                    <button class="text-xs px-2 py-1 bg-white/10 hover:bg-white/20 rounded transition">Complete</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500 text-center py-4">No pending handoffs</p>
                    @endforelse
                </div>
                <button onclick="showHandoffModal()" class="w-full mt-3 py-2 bg-white/5 hover:bg-white/10 text-zinc-400 hover:text-white rounded-lg text-sm transition">
                    + Request Handoff
                </button>
            </div>

            {{-- Active Workers --}}
            <div class="panel-section">
                <div class="panel-title flex items-center gap-2">
                    <svg class="w-5 h-5 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Active Workers
                </div>
                <div class="space-y-2">
                    @forelse($workers->take(6) as $worker)
                        <div class="flex items-center justify-between py-2 border-b border-white/5 last:border-0">
                            <div>
                                <div class="text-sm font-medium">{{ $worker->user?->name ?? 'Worker #' . $worker->id }}</div>
                                <div class="text-xs text-zinc-500">{{ $worker->role?->label() ?? 'Staff' }}</div>
                            </div>
                            <span class="w-2 h-2 rounded-full {{ $worker->status === 'active' ? 'bg-emerald-400' : 'bg-zinc-500' }}"></span>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500 text-center py-4">No workers assigned</p>
                    @endforelse
                </div>
            </div>

            {{-- Quick Manifest Export --}}
            <div class="panel-section">
                <div class="panel-title">Quick Export</div>
                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('branch.operations.manifest.shipments', ['direction' => 'outbound', 'format' => 'pdf']) }}" 
                       class="py-2 text-center bg-white/5 hover:bg-white/10 rounded-lg text-sm transition">
                        Outbound PDF
                    </a>
                    <a href="{{ route('branch.operations.manifest.shipments', ['direction' => 'inbound', 'format' => 'pdf']) }}" 
                       class="py-2 text-center bg-white/5 hover:bg-white/10 rounded-lg text-sm transition">
                        Inbound PDF
                    </a>
                    <a href="{{ route('branch.operations.manifest.route', ['format' => 'pdf']) }}" 
                       class="py-2 text-center bg-white/5 hover:bg-white/10 rounded-lg text-sm transition">
                        Route Manifest
                    </a>
                    <a href="{{ route('branch.operations.maintenance') }}" 
                       class="py-2 text-center bg-white/5 hover:bg-white/10 rounded-lg text-sm transition">
                        Maintenance
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Assign Worker Modal --}}
    <div id="assignModal" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50" onclick="if(event.target === this) hideAssignModal()">
        <div class="bg-zinc-900 rounded-xl p-6 w-full max-w-md border border-white/10">
            <h3 class="text-lg font-semibold text-white mb-4">Assign Worker</h3>
            <form method="POST" action="{{ route('branch.operations.assign') }}" id="assignForm">
                @csrf
                <input type="hidden" name="shipment_id" id="assignShipmentId">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm text-zinc-400 mb-2">Select Worker</label>
                        <select name="worker_id" class="w-full filter-input">
                            @foreach($workers as $worker)
                                <option value="{{ $worker->id }}">{{ $worker->user?->name ?? 'Worker #' . $worker->id }} ({{ $worker->role?->label() ?? 'Staff' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="auto" value="1" class="accent-emerald-500">
                        <span class="text-sm text-zinc-300">Auto-assign based on workload</span>
                    </label>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="button" onclick="hideAssignModal()" class="flex-1 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-lg transition">Cancel</button>
                    <button type="submit" class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg transition">Assign</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Update Status Modal --}}
    <div id="statusModal" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50" onclick="if(event.target === this) hideStatusModal()">
        <div class="bg-zinc-900 rounded-xl p-6 w-full max-w-md border border-white/10">
            <h3 class="text-lg font-semibold text-white mb-4">Update Status</h3>
            <form method="POST" action="{{ route('branch.operations.status') }}" id="statusForm">
                @csrf
                <input type="hidden" name="shipment_id" id="statusShipmentId">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm text-zinc-400 mb-2">New Status</label>
                        <select name="status" class="w-full filter-input">
                            @foreach(\App\Enums\ShipmentStatus::orderedLifecycle() as $status)
                                <option value="{{ $status->value }}">{{ $status->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="button" onclick="hideStatusModal()" class="flex-1 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-lg transition">Cancel</button>
                    <button type="submit" class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg transition">Update</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Reroute Modal --}}
    <div id="rerouteModal" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50" onclick="if(event.target === this) hideRerouteModal()">
        <div class="bg-zinc-900 rounded-xl p-6 w-full max-w-md border border-white/10">
            <h3 class="text-lg font-semibold text-white mb-4">Reroute Shipment</h3>
            <form method="POST" action="{{ route('branch.operations.reroute') }}" id="rerouteForm">
                @csrf
                <input type="hidden" name="shipment_id" id="rerouteShipmentId">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm text-zinc-400 mb-2">New Destination</label>
                        <select name="dest_branch_id" class="w-full filter-input">
                            @foreach($branchOptions as $opt)
                                <option value="{{ $opt->id }}">{{ $opt->code ?? $opt->name }} - {{ $opt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-zinc-400 mb-2">Reason (Optional)</label>
                        <input type="text" name="reason" class="w-full filter-input" placeholder="Enter reason for reroute">
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="button" onclick="hideRerouteModal()" class="flex-1 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-lg transition">Cancel</button>
                    <button type="submit" class="flex-1 py-2.5 bg-amber-600 hover:bg-amber-500 text-white rounded-lg transition">Reroute</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Handoff Request Modal --}}
    <div id="handoffModal" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50" onclick="if(event.target === this) hideHandoffModal()">
        <div class="bg-zinc-900 rounded-xl p-6 w-full max-w-md border border-white/10">
            <h3 class="text-lg font-semibold text-white mb-4">Request Handoff</h3>
            <form method="POST" action="{{ route('branch.operations.handoff.request') }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm text-zinc-400 mb-2">Shipment ID</label>
                        <input type="number" name="shipment_id" class="w-full filter-input" placeholder="Enter shipment ID" required>
                    </div>
                    <div>
                        <label class="block text-sm text-zinc-400 mb-2">Destination Branch</label>
                        <select name="dest_branch_id" class="w-full filter-input" required>
                            @foreach($branchOptions as $opt)
                                <option value="{{ $opt->id }}">{{ $opt->code ?? $opt->name }} - {{ $opt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-zinc-400 mb-2">Expected Handoff Time</label>
                        <input type="datetime-local" name="expected_hand_off_at" class="w-full filter-input">
                    </div>
                    <div>
                        <label class="block text-sm text-zinc-400 mb-2">Notes</label>
                        <input type="text" name="notes" class="w-full filter-input" placeholder="Optional notes">
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="button" onclick="hideHandoffModal()" class="flex-1 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-lg transition">Cancel</button>
                    <button type="submit" class="flex-1 py-2.5 bg-sky-600 hover:bg-sky-500 text-white rounded-lg transition">Request</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function showAssignModal(shipmentId) {
    document.getElementById('assignShipmentId').value = shipmentId;
    document.getElementById('assignModal').classList.remove('hidden');
    document.getElementById('assignModal').classList.add('flex');
}
function hideAssignModal() {
    document.getElementById('assignModal').classList.add('hidden');
    document.getElementById('assignModal').classList.remove('flex');
}

function showStatusModal(shipmentId) {
    document.getElementById('statusShipmentId').value = shipmentId;
    document.getElementById('statusModal').classList.remove('hidden');
    document.getElementById('statusModal').classList.add('flex');
}
function hideStatusModal() {
    document.getElementById('statusModal').classList.add('hidden');
    document.getElementById('statusModal').classList.remove('flex');
}

function showRerouteModal(shipmentId) {
    document.getElementById('rerouteShipmentId').value = shipmentId;
    document.getElementById('rerouteModal').classList.remove('hidden');
    document.getElementById('rerouteModal').classList.add('flex');
}
function hideRerouteModal() {
    document.getElementById('rerouteModal').classList.add('hidden');
    document.getElementById('rerouteModal').classList.remove('flex');
}

function showHandoffModal() {
    document.getElementById('handoffModal').classList.remove('hidden');
    document.getElementById('handoffModal').classList.add('flex');
}
function hideHandoffModal() {
    document.getElementById('handoffModal').classList.add('hidden');
    document.getElementById('handoffModal').classList.remove('flex');
}

function changePerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.delete('page');
    window.location.href = url.toString();
}
</script>
@endpush
