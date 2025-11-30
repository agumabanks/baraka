@extends('branch.layout')

@section('content')
<div class="p-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-100">Create Shipment</h1>
            <p class="text-sm text-slate-400">All shipments are scoped to your active branch.</p>
        </div>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-lg shadow-sm p-6">
        <form method="POST" action="{{ route('branch.shipments.store') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="origin_branch_id" value="{{ $branchId }}">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-slate-300 mb-1">Customer</label>
                    <select name="customer_id" class="w-full bg-slate-800 border border-slate-700 rounded px-3 py-2 text-slate-100">
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                    @error('customer_id') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-slate-300 mb-1">Destination branch</label>
                    <select name="dest_branch_id" class="w-full bg-slate-800 border border-slate-700 rounded px-3 py-2 text-slate-100">
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->code ?? $branch->name }}</option>
                        @endforeach
                    </select>
                    @error('dest_branch_id') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm text-slate-300 mb-1">Service level</label>
                    <input type="text" name="service_level" class="w-full bg-slate-800 border border-slate-700 rounded px-3 py-2 text-slate-100" value="{{ old('service_level', 'express') }}">
                    @error('service_level') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-slate-300 mb-1">Incoterms</label>
                    <input type="text" name="incoterms" class="w-full bg-slate-800 border border-slate-700 rounded px-3 py-2 text-slate-100" value="{{ old('incoterms', 'DDP') }}">
                    @error('incoterms') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-slate-300 mb-1">Payer type</label>
                    <select name="payer_type" class="w-full bg-slate-800 border border-slate-700 rounded px-3 py-2 text-slate-100">
                        <option value="sender">Sender</option>
                        <option value="receiver">Receiver</option>
                        <option value="third_party">Third party</option>
                    </select>
                    @error('payer_type') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm text-slate-300 mb-1">Special instructions</label>
                <textarea name="special_instructions" class="w-full bg-slate-800 border border-slate-700 rounded px-3 py-2 text-slate-100" rows="3">{{ old('special_instructions') }}</textarea>
            </div>

            <div class="border border-slate-800 rounded p-4 bg-slate-800/40">
                <h3 class="text-sm font-semibold text-slate-200 mb-3">Parcel</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm text-slate-300 mb-1">Weight (kg)</label>
                        <input type="number" step="0.01" name="parcels[0][weight_kg]" class="w-full bg-slate-800 border border-slate-700 rounded px-3 py-2 text-slate-100" value="{{ old('parcels.0.weight_kg', 1.0) }}">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-300 mb-1">Length (cm)</label>
                        <input type="number" step="1" name="parcels[0][length_cm]" class="w-full bg-slate-800 border border-slate-700 rounded px-3 py-2 text-slate-100" value="{{ old('parcels.0.length_cm', 10) }}">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-300 mb-1">Width (cm)</label>
                        <input type="number" step="1" name="parcels[0][width_cm]" class="w-full bg-slate-800 border border-slate-700 rounded px-3 py-2 text-slate-100" value="{{ old('parcels.0.width_cm', 10) }}">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-300 mb-1">Height (cm)</label>
                        <input type="number" step="1" name="parcels[0][height_cm]" class="w-full bg-slate-800 border border-slate-700 rounded px-3 py-2 text-slate-100" value="{{ old('parcels.0.height_cm', 10) }}">
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-500">Create shipment</button>
            </div>
        </form>
    </div>
</div>
@endsection
