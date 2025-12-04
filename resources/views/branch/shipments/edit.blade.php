@extends('branch.layout')

@section('title', 'Edit Shipment - ' . $shipment->tracking_number)

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('branch.shipments.show', $shipment) }}" class="text-sky-400 hover:text-sky-300">
            &larr; Back to Shipment
        </a>
    </div>

    <div class="glass-panel p-6">
        <h2 class="text-xl font-semibold mb-6">Edit Shipment: {{ $shipment->tracking_number }}</h2>

        @if($errors->any())
        <div class="bg-rose-500/20 border border-rose-500 text-rose-400 px-4 py-3 rounded mb-6">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('branch.shipments.update', $shipment) }}" method="POST" id="editShipmentForm">
            @csrf
            @method('PUT')

            {{-- Shipment Details --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium mb-2">Origin Branch</label>
                    <input type="text" value="{{ $shipment->originBranch->name ?? 'N/A' }}" disabled 
                           class="form-input bg-white/5 opacity-60">
                    <p class="text-xs text-gray-400 mt-1">Origin cannot be changed</p>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Destination Branch *</label>
                    <select name="dest_branch_id" class="form-select" required>
                        @foreach($branches as $branch)
                            @if($branch->id != $shipment->origin_branch_id)
                                <option value="{{ $branch->id }}" {{ $shipment->dest_branch_id == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Service Level *</label>
                    <select name="service_level" class="form-select" required>
                        <option value="economy" {{ $shipment->service_level == 'economy' ? 'selected' : '' }}>Economy</option>
                        <option value="standard" {{ $shipment->service_level == 'standard' ? 'selected' : '' }}>Standard</option>
                        <option value="express" {{ $shipment->service_level == 'express' ? 'selected' : '' }}>Express</option>
                        <option value="priority" {{ $shipment->service_level == 'priority' ? 'selected' : '' }}>Priority</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Payer Type *</label>
                    <select name="payer_type" class="form-select" required>
                        <option value="sender" {{ $shipment->payer_type == 'sender' ? 'selected' : '' }}>Sender</option>
                        <option value="receiver" {{ $shipment->payer_type == 'receiver' ? 'selected' : '' }}>Receiver</option>
                        <option value="third_party" {{ $shipment->payer_type == 'third_party' ? 'selected' : '' }}>Third Party</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Incoterms</label>
                    <select name="incoterms" class="form-select">
                        <option value="">Select Incoterm</option>
                        <option value="DDP" {{ $shipment->incoterms == 'DDP' ? 'selected' : '' }}>DDP - Delivered Duty Paid</option>
                        <option value="DAP" {{ $shipment->incoterms == 'DAP' ? 'selected' : '' }}>DAP - Delivered at Place</option>
                        <option value="EXW" {{ $shipment->incoterms == 'EXW' ? 'selected' : '' }}>EXW - Ex Works</option>
                        <option value="FCA" {{ $shipment->incoterms == 'FCA' ? 'selected' : '' }}>FCA - Free Carrier</option>
                        <option value="CPT" {{ $shipment->incoterms == 'CPT' ? 'selected' : '' }}>CPT - Carriage Paid To</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Expected Delivery Date</label>
                    <input type="date" name="expected_delivery_date" class="form-input"
                           value="{{ $shipment->expected_delivery_date?->format('Y-m-d') }}">
                </div>
            </div>

            {{-- Insurance & Value --}}
            <div class="border-t border-white/10 pt-6 mb-6">
                <h3 class="text-lg font-medium mb-4">Value & Insurance</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium mb-2">Declared Value ({{ $shipment->currency ?? 'USD' }})</label>
                        <input type="number" name="declared_value" step="0.01" min="0" class="form-input"
                               value="{{ old('declared_value', $shipment->declared_value ?? 0) }}">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Insurance Amount ({{ $shipment->currency ?? 'USD' }})</label>
                        <input type="number" name="insurance_amount" step="0.01" min="0" class="form-input"
                               value="{{ old('insurance_amount', $shipment->insurance_amount ?? 0) }}">
                        <p class="text-xs text-gray-400 mt-1">Optional coverage for declared value</p>
                    </div>
                </div>
            </div>

            {{-- Package Weight --}}
            <div class="border-t border-white/10 pt-6 mb-6">
                <h3 class="text-lg font-medium mb-4">Package Details</h3>
                <div class="bg-white/5 rounded-lg p-4">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs mb-1">Chargeable Weight (kg)</label>
                            <input type="number" name="chargeable_weight_kg" step="0.01" min="0.01"
                                   class="form-input text-sm" value="{{ $shipment->chargeable_weight_kg ?? 1 }}">
                        </div>
                        <div>
                            <label class="block text-xs mb-1">Pieces</label>
                            <input type="number" name="piece_count" min="1"
                                   class="form-input text-sm" value="{{ $shipment->piece_count ?? 1 }}">
                        </div>
                        <div>
                            <label class="block text-xs mb-1">Package Type</label>
                            <select name="package_type" class="form-select text-sm">
                                <option value="parcel" {{ ($shipment->package_type ?? 'parcel') == 'parcel' ? 'selected' : '' }}>Parcel</option>
                                <option value="document" {{ ($shipment->package_type ?? '') == 'document' ? 'selected' : '' }}>Document</option>
                                <option value="pallet" {{ ($shipment->package_type ?? '') == 'pallet' ? 'selected' : '' }}>Pallet</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs mb-1">Contents Description</label>
                            <input type="text" name="contents_description" maxlength="500"
                                   class="form-input text-sm" value="{{ $shipment->contents_description ?? '' }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Special Instructions --}}
            <div class="border-t border-white/10 pt-6 mb-6">
                <label class="block text-sm font-medium mb-2">Special Instructions</label>
                <textarea name="special_instructions" rows="3" class="form-textarea"
                          maxlength="2000">{{ old('special_instructions', $shipment->special_instructions) }}</textarea>
            </div>

            {{-- Submit --}}
            <div class="flex items-center justify-end gap-4 pt-6 border-t border-white/10">
                <a href="{{ route('branch.shipments.show', $shipment) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Shipment</button>
            </div>
        </form>
    </div>
</div>


@endsection
