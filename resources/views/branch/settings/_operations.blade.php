@php
    $ops = $settings['operations'] ?? $settings;
@endphp

<form id="operationsForm" onsubmit="event.preventDefault(); saveSettings('operationsForm', '{{ route('branch.settings.operations') }}');">
    @csrf
    <div class="grid lg:grid-cols-2 gap-6">
        <!-- Operating Hours -->
        <div class="glass-panel p-6 space-y-5">
            <div class="border-b border-white/10 pb-4">
                <h3 class="text-lg font-semibold text-white">Operating Hours</h3>
                <p class="text-xs text-slate-400 mt-1">Define when this branch accepts shipments</p>
            </div>

            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Opens At</label>
                        <input type="time" name="operating_hours_start" value="{{ $ops['operating_hours_start'] ?? '08:00' }}"
                               class="w-full bg-obsidian-700 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Closes At</label>
                        <input type="time" name="operating_hours_end" value="{{ $ops['operating_hours_end'] ?? '18:00' }}"
                               class="w-full bg-obsidian-700 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Cutoff Time</label>
                    <input type="time" name="cutoff_time" value="{{ $ops['cutoff_time'] ?? '16:00' }}"
                           class="w-full bg-obsidian-700 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                    <p class="text-2xs text-slate-500 mt-1">Last time to accept same-day dispatch shipments</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-3">Working Days</label>
                    <div class="flex flex-wrap gap-2">
                        @php
                            $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                            $workingDays = $ops['working_days'] ?? [1, 2, 3, 4, 5];
                        @endphp
                        @foreach($days as $index => $day)
                            <label class="flex items-center gap-2 px-3 py-2 rounded-lg cursor-pointer transition-all
                                {{ in_array($index, $workingDays) ? 'bg-emerald-500/20 border border-emerald-500/30' : 'bg-obsidian-700 border border-white/10' }}">
                                <input type="checkbox" name="working_days[]" value="{{ $index }}" 
                                       {{ in_array($index, $workingDays) ? 'checked' : '' }}
                                       class="rounded border-white/20 bg-obsidian-700 text-emerald-500 focus:ring-emerald-500/50">
                                <span class="text-sm text-white">{{ $day }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Capacity & Limits -->
        <div class="glass-panel p-6 space-y-5">
            <div class="border-b border-white/10 pb-4">
                <h3 class="text-lg font-semibold text-white">Capacity & Limits</h3>
                <p class="text-xs text-slate-400 mt-1">Set operational boundaries and thresholds</p>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Max Daily Shipments</label>
                    <input type="number" name="max_daily_shipments" value="{{ $ops['max_daily_shipments'] ?? '' }}" 
                           placeholder="No limit" min="0"
                           class="w-full bg-obsidian-700 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Max Parcel Weight (kg)</label>
                    <input type="number" name="max_parcel_weight" value="{{ $ops['max_parcel_weight'] ?? '' }}" 
                           placeholder="70" min="0" step="0.1"
                           class="w-full bg-obsidian-700 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">SLA Threshold (%)</label>
                    <input type="number" name="sla_threshold" value="{{ $ops['sla_threshold'] ?? 95 }}" 
                           min="0" max="100"
                           class="w-full bg-obsidian-700 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                    <p class="text-2xs text-slate-500 mt-1">Alert when on-time delivery falls below this percentage</p>
                </div>
            </div>
        </div>

        <!-- COD & Payment -->
        <div class="glass-panel p-6 space-y-5">
            <div class="border-b border-white/10 pb-4">
                <h3 class="text-lg font-semibold text-white">COD & Payments</h3>
                <p class="text-xs text-slate-400 mt-1">Cash on delivery and payment settings</p>
            </div>

            <div class="space-y-4">
                <label class="flex items-center justify-between p-3 bg-obsidian-700 rounded-lg cursor-pointer">
                    <div>
                        <span class="text-sm font-medium text-white">Enable COD</span>
                        <p class="text-2xs text-slate-500">Allow cash on delivery shipments</p>
                    </div>
                    <input type="checkbox" name="enable_cod" value="1" {{ ($ops['enable_cod'] ?? true) ? 'checked' : '' }}
                           class="rounded border-white/20 bg-obsidian-600 text-emerald-500 focus:ring-emerald-500/50 w-5 h-5">
                </label>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">COD Limit</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm">UGX</span>
                        <input type="number" name="cod_limit" value="{{ $ops['cod_limit'] ?? '' }}" 
                               placeholder="5,000,000" min="0"
                               class="w-full bg-obsidian-700 border border-white/10 rounded-lg pl-14 pr-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                    </div>
                    <p class="text-2xs text-slate-500 mt-1">Maximum COD amount per shipment</p>
                </div>
            </div>
        </div>

        <!-- Delivery Requirements -->
        <div class="glass-panel p-6 space-y-5">
            <div class="border-b border-white/10 pb-4">
                <h3 class="text-lg font-semibold text-white">Delivery Requirements</h3>
                <p class="text-xs text-slate-400 mt-1">Proof of delivery and verification settings</p>
            </div>

            <div class="space-y-3">
                <label class="flex items-center justify-between p-3 bg-obsidian-700 rounded-lg cursor-pointer">
                    <div>
                        <span class="text-sm font-medium text-white">Require POD Photo</span>
                        <p class="text-2xs text-slate-500">Drivers must capture proof of delivery photo</p>
                    </div>
                    <input type="checkbox" name="require_pod" value="1" {{ ($ops['require_pod'] ?? true) ? 'checked' : '' }}
                           class="rounded border-white/20 bg-obsidian-600 text-emerald-500 focus:ring-emerald-500/50 w-5 h-5">
                </label>

                <label class="flex items-center justify-between p-3 bg-obsidian-700 rounded-lg cursor-pointer">
                    <div>
                        <span class="text-sm font-medium text-white">Require Signature</span>
                        <p class="text-2xs text-slate-500">Recipient must sign upon delivery</p>
                    </div>
                    <input type="checkbox" name="require_signature" value="1" {{ ($ops['require_signature'] ?? false) ? 'checked' : '' }}
                           class="rounded border-white/20 bg-obsidian-600 text-emerald-500 focus:ring-emerald-500/50 w-5 h-5">
                </label>

                <label class="flex items-center justify-between p-3 bg-obsidian-700 rounded-lg cursor-pointer">
                    <div>
                        <span class="text-sm font-medium text-white">Auto-assign Drivers</span>
                        <p class="text-2xs text-slate-500">Automatically assign available drivers to shipments</p>
                    </div>
                    <input type="checkbox" name="auto_assign_drivers" value="1" {{ ($ops['auto_assign_drivers'] ?? false) ? 'checked' : '' }}
                           class="rounded border-white/20 bg-obsidian-600 text-emerald-500 focus:ring-emerald-500/50 w-5 h-5">
                </label>
            </div>
        </div>
    </div>

    <!-- Operating Notes -->
    <div class="glass-panel p-6 mt-6">
        <div class="border-b border-white/10 pb-4 mb-4">
            <h3 class="text-lg font-semibold text-white">Operating Notes</h3>
            <p class="text-xs text-slate-400 mt-1">Internal notes about this branch's operations</p>
        </div>
        <textarea name="operating_notes" rows="3" placeholder="E.g., After-hours contact: +256 700 123 456, On-call manager: John Smith"
                  class="w-full bg-obsidian-700 border border-white/10 rounded-lg px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 resize-none">{{ $ops['operating_notes'] ?? '' }}</textarea>
    </div>

    <div class="mt-6 flex justify-end">
        <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium rounded-lg transition-colors">
            <i class="bi bi-check-lg mr-2"></i>Save Operations Settings
        </button>
    </div>
</form>
