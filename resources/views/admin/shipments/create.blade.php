@extends('admin.layout')

@section('title', 'Create Shipment')
@section('header', 'Create New Shipment')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="glass-panel">
        <div class="p-4 border-b border-white/10">
            <h2 class="text-lg font-semibold">Shipment Details</h2>
            <p class="text-sm muted">Fill in the shipment information below</p>
        </div>

        <form action="{{ route('admin.shipments.store') }}" method="POST" class="p-6 space-y-6">
            @csrf

            @if($errors->any())
                <div class="bg-red-500/20 border border-red-500/50 rounded-lg p-4">
                    <ul class="text-sm text-red-400 list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Customer Selection --}}
            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium mb-2">Customer <span class="text-red-400">*</span></label>
                    <select name="customer_id" required class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                        <option value="">Select Customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }} ({{ $customer->email }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Service Type <span class="text-red-400">*</span></label>
                    <select name="service_type" required class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                        <option value="">Select Service</option>
                        <option value="express" {{ old('service_type') == 'express' ? 'selected' : '' }}>Express (1-2 Days)</option>
                        <option value="standard" {{ old('service_type') == 'standard' ? 'selected' : '' }}>Standard (3-5 Days)</option>
                        <option value="economy" {{ old('service_type') == 'economy' ? 'selected' : '' }}>Economy (5-7 Days)</option>
                        <option value="same_day" {{ old('service_type') == 'same_day' ? 'selected' : '' }}>Same Day</option>
                    </select>
                </div>
            </div>

            {{-- Route Selection --}}
            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium mb-2">Origin Branch <span class="text-red-400">*</span></label>
                    <select name="origin_branch_id" required class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                        <option value="">Select Origin</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('origin_branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }} ({{ $branch->code ?? 'N/A' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Destination Branch <span class="text-red-400">*</span></label>
                    <select name="dest_branch_id" required class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                        <option value="">Select Destination</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('dest_branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }} ({{ $branch->code ?? 'N/A' }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Package Details --}}
            <div class="border-t border-white/10 pt-6">
                <h3 class="text-md font-semibold mb-4">Package Information</h3>
                
                <div class="grid gap-6 md:grid-cols-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Weight (kg) <span class="text-red-400">*</span></label>
                        <input type="number" name="weight" value="{{ old('weight') }}" step="0.01" min="0.01" required
                               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-sky-500 focus:border-transparent"
                               placeholder="0.00">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Length (cm)</label>
                        <input type="number" name="dimensions[length]" value="{{ old('dimensions.length') }}" step="0.1" min="0"
                               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-sky-500 focus:border-transparent"
                               placeholder="0">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Width (cm)</label>
                        <input type="number" name="dimensions[width]" value="{{ old('dimensions.width') }}" step="0.1" min="0"
                               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-sky-500 focus:border-transparent"
                               placeholder="0">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Height (cm)</label>
                        <input type="number" name="dimensions[height]" value="{{ old('dimensions.height') }}" step="0.1" min="0"
                               class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-sky-500 focus:border-transparent"
                               placeholder="0">
                    </div>
                </div>
            </div>

            {{-- Value & Payment --}}
            <div class="grid gap-6 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium mb-2">Declared Value (UGX)</label>
                    <input type="number" name="value" value="{{ old('value') }}" step="100" min="0"
                           class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-sky-500 focus:border-transparent"
                           placeholder="0">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">COD Amount (UGX)</label>
                    <input type="number" name="cod_amount" value="{{ old('cod_amount') }}" step="100" min="0"
                           class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-sky-500 focus:border-transparent"
                           placeholder="0">
                    <p class="text-xs muted mt-1">Cash to collect on delivery</p>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Priority</label>
                    <select name="priority" class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                        <option value="normal" {{ old('priority', 'normal') == 'normal' ? 'selected' : '' }}>Normal</option>
                        <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low</option>
                        <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                        <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                    </select>
                </div>
            </div>

            {{-- Description --}}
            <div>
                <label class="block text-sm font-medium mb-2">Package Description</label>
                <textarea name="description" rows="3"
                          class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-sky-500 focus:border-transparent"
                          placeholder="Describe the contents of the package...">{{ old('description') }}</textarea>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-white/10">
                <a href="{{ route('admin.shipments.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Create Shipment
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
