@php
    $labels = $settings['labels'] ?? [];
@endphp

<form id="labelsForm" onsubmit="event.preventDefault(); saveSettings('labelsForm', '{{ route('branch.settings.labels') }}');">
    @csrf
    <div class="grid lg:grid-cols-2 gap-6">
        <!-- Label Format -->
        <div class="glass-panel p-6 space-y-5">
            <div class="border-b border-white/10 pb-4">
                <h3 class="text-lg font-semibold text-white">Label Format</h3>
                <p class="text-xs text-slate-400 mt-1">Configure label size and orientation</p>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Label Size</label>
                    <select name="label_format" class="w-full bg-obsidian-700 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                        @foreach($labelFormats as $value => $name)
                            <option value="{{ $value }}" @selected(($labels['label_format'] ?? '4x6') === $value)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Orientation</label>
                    <select name="label_orientation" class="w-full bg-obsidian-700 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                        <option value="portrait" @selected(($labels['label_orientation'] ?? 'portrait') === 'portrait')>Portrait</option>
                        <option value="landscape" @selected(($labels['label_orientation'] ?? '') === 'landscape')>Landscape</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Copies Per Shipment</label>
                    <select name="copies_per_shipment" class="w-full bg-obsidian-700 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                        @for($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}" @selected(($labels['copies_per_shipment'] ?? 1) == $i)>{{ $i }} {{ $i === 1 ? 'copy' : 'copies' }}</option>
                        @endfor
                    </select>
                </div>
            </div>
        </div>

        <!-- Label Content -->
        <div class="glass-panel p-6 space-y-5">
            <div class="border-b border-white/10 pb-4">
                <h3 class="text-lg font-semibold text-white">Label Content</h3>
                <p class="text-xs text-slate-400 mt-1">Choose what information appears on labels</p>
            </div>

            <div class="space-y-3">
                <label class="flex items-center justify-between p-3 bg-obsidian-700 rounded-lg cursor-pointer">
                    <span class="text-sm text-white">Show Barcode</span>
                    <input type="checkbox" name="show_barcode" value="1" {{ ($labels['show_barcode'] ?? true) ? 'checked' : '' }}
                           class="rounded border-white/20 bg-obsidian-600 text-emerald-500 focus:ring-emerald-500/50 w-5 h-5">
                </label>

                <label class="flex items-center justify-between p-3 bg-obsidian-700 rounded-lg cursor-pointer">
                    <span class="text-sm text-white">Show QR Code</span>
                    <input type="checkbox" name="show_qr_code" value="1" {{ ($labels['show_qr_code'] ?? true) ? 'checked' : '' }}
                           class="rounded border-white/20 bg-obsidian-600 text-emerald-500 focus:ring-emerald-500/50 w-5 h-5">
                </label>

                <label class="flex items-center justify-between p-3 bg-obsidian-700 rounded-lg cursor-pointer">
                    <span class="text-sm text-white">Show Company Logo</span>
                    <input type="checkbox" name="show_logo" value="1" {{ ($labels['show_logo'] ?? true) ? 'checked' : '' }}
                           class="rounded border-white/20 bg-obsidian-600 text-emerald-500 focus:ring-emerald-500/50 w-5 h-5">
                </label>

                <label class="flex items-center justify-between p-3 bg-obsidian-700 rounded-lg cursor-pointer">
                    <span class="text-sm text-white">Show Sender Address</span>
                    <input type="checkbox" name="show_sender_address" value="1" {{ ($labels['show_sender_address'] ?? true) ? 'checked' : '' }}
                           class="rounded border-white/20 bg-obsidian-600 text-emerald-500 focus:ring-emerald-500/50 w-5 h-5">
                </label>

                <label class="flex items-center justify-between p-3 bg-obsidian-700 rounded-lg cursor-pointer">
                    <span class="text-sm text-white">Show Weight</span>
                    <input type="checkbox" name="show_weight" value="1" {{ ($labels['show_weight'] ?? true) ? 'checked' : '' }}
                           class="rounded border-white/20 bg-obsidian-600 text-emerald-500 focus:ring-emerald-500/50 w-5 h-5">
                </label>

                <label class="flex items-center justify-between p-3 bg-obsidian-700 rounded-lg cursor-pointer">
                    <span class="text-sm text-white">Show Dimensions</span>
                    <input type="checkbox" name="show_dimensions" value="1" {{ ($labels['show_dimensions'] ?? false) ? 'checked' : '' }}
                           class="rounded border-white/20 bg-obsidian-600 text-emerald-500 focus:ring-emerald-500/50 w-5 h-5">
                </label>

                <label class="flex items-center justify-between p-3 bg-obsidian-700 rounded-lg cursor-pointer">
                    <span class="text-sm text-white">Show COD Amount</span>
                    <input type="checkbox" name="show_cod_amount" value="1" {{ ($labels['show_cod_amount'] ?? true) ? 'checked' : '' }}
                           class="rounded border-white/20 bg-obsidian-600 text-emerald-500 focus:ring-emerald-500/50 w-5 h-5">
                </label>

                <label class="flex items-center justify-between p-3 bg-obsidian-700 rounded-lg cursor-pointer">
                    <span class="text-sm text-white">Show Special Instructions</span>
                    <input type="checkbox" name="show_special_instructions" value="1" {{ ($labels['show_special_instructions'] ?? true) ? 'checked' : '' }}
                           class="rounded border-white/20 bg-obsidian-600 text-emerald-500 focus:ring-emerald-500/50 w-5 h-5">
                </label>
            </div>
        </div>

        <!-- Printing Settings -->
        <div class="glass-panel p-6 space-y-5 lg:col-span-2">
            <div class="border-b border-white/10 pb-4">
                <h3 class="text-lg font-semibold text-white">Printing Settings</h3>
                <p class="text-xs text-slate-400 mt-1">Configure automatic printing behavior</p>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <label class="flex items-center justify-between p-4 bg-obsidian-700 rounded-lg cursor-pointer">
                        <div>
                            <span class="text-sm font-medium text-white">Auto-print Labels</span>
                            <p class="text-2xs text-slate-500">Automatically print when shipment is created</p>
                        </div>
                        <input type="checkbox" name="auto_print" value="1" {{ ($labels['auto_print'] ?? false) ? 'checked' : '' }}
                               class="rounded border-white/20 bg-obsidian-600 text-emerald-500 focus:ring-emerald-500/50 w-5 h-5">
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Default Printer Name</label>
                    <input type="text" name="printer_name" value="{{ $labels['printer_name'] ?? '' }}" 
                           placeholder="e.g., Zebra ZD420"
                           class="w-full bg-obsidian-700 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                    <p class="text-2xs text-slate-500 mt-1">Used for auto-print and direct printing</p>
                </div>
            </div>

            <!-- Label Preview -->
            <div class="bg-obsidian-700/50 rounded-lg p-6 border border-white/5">
                <div class="flex items-center gap-3 mb-4">
                    <i class="bi bi-eye text-slate-400"></i>
                    <span class="text-sm font-medium text-slate-300">Label Preview</span>
                </div>
                <div class="bg-white rounded-lg p-4 max-w-xs mx-auto">
                    <div class="border-2 border-dashed border-slate-300 rounded p-4 text-center">
                        <div class="text-xs text-slate-500 mb-2">Sample Label Preview</div>
                        <div class="w-24 h-24 mx-auto bg-slate-100 rounded flex items-center justify-center mb-2">
                            <i class="bi bi-qr-code text-3xl text-slate-400"></i>
                        </div>
                        <div class="text-xs font-mono text-slate-700">BRK-2024-000001</div>
                        <div class="text-xs text-slate-500 mt-1">{{ $branch->name }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 flex justify-end">
        <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium rounded-lg transition-colors">
            <i class="bi bi-check-lg mr-2"></i>Save Label Settings
        </button>
    </div>
</form>
