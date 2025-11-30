@extends('branch.layout')

@section('title', $consolidation->consolidation_number)

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">{{ $consolidation->consolidation_number }}</h2>
            <p class="text-muted">
                <span class="badge {{ $consolidation->type == 'BBX' ? 'bg-primary' : 'bg-info' }}">{{ $consolidation->type }}</span>
                to {{ $consolidation->destination }}
            </p>
        </div>
        <div>
            <a href="{{ route('branch.consolidations.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Details -->
        <div class="col-md-8">
            <!-- Status and Actions Card -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Consolidation Status</h5>
                    @php
                        $statusColors = [
                            'OPEN' => 'success',
                            'LOCKED' => 'warning',
                            'IN_TRANSIT' => 'primary',
                            'ARRIVED' => 'info',
                            'DECONSOLIDATING' => 'warning',
                            'COMPLETED' => 'secondary',
                        ];
                    @endphp
                    <span class="badge bg-{{ $statusColors[$consolidation->status] ?? 'secondary' }}">
                        {{ str_replace('_', ' ', $consolidation->status) }}
                    </span>
                </div>
                <div class="card-body">
                    <!-- Actions based on status -->
                    @if($consolidation->status == 'OPEN')
                    <div class="d-flex gap-2 mb-3">
                        <button class="btn btn-warning" onclick="lockConsolidation()">
                            <i class="fas fa-lock"></i> Lock Consolidation
                        </button>
                        <button class="btn btn-outline-primary" onclick="addShipmentModal()">
                            <i class="fas fa-plus"></i> Add Shipment
                        </button>
                    </div>
                    @elseif($consolidation->status == 'LOCKED')
                    <form method="POST" action="{{ route('branch.consolidations.dispatch', $consolidation) }}">
                        @csrf
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">AWB/Transport Number</label>
                                <input type="text" name="awb_number" class="form-control" placeholder="AWB-123456">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Vehicle Number</label>
                                <input type="text" name="vehicle_number" class="form-control" placeholder="VH-001">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-shipping-fast"></i> Dispatch Consolidation
                        </button>
                    </form>
                    @elseif($consolidation->status == 'IN_TRANSIT')
                    <form method="POST" action="{{ route('branch.consolidations.arrived', $consolidation) }}">
                        @csrf
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-check-circle"></i> Mark as Arrived
                        </button>
                    </form>
                    @elseif($consolidation->status == 'ARRIVED')
                    <form method="POST" action="{{ route('branch.consolidations.deconsolidate.start', $consolidation) }}">
                        @csrf
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-box-open"></i> Start Deconsolidation
                        </button>
                    </form>
                    @elseif($consolidation->status == 'DECONSOLIDATING')
                    <a href="{{ route('branch.consolidations.deconsolidate', $consolidation) }}" class="btn btn-primary">
                        <i class="fas fa-barcode"></i> Continue Deconsolidation
                    </a>
                    @endif

                    <!-- Timeline -->
                    <div class="mt-4">
                        <h6 class="text-muted mb-3">Timeline</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success"></i>
                                <strong>Created:</strong> {{ $consolidation->created_at->format('M d, Y H:i') }}
                                @if($consolidation->createdBy)
                                    by {{ $consolidation->createdBy->name }}
                                @endif
                            </li>
                            @if($consolidation->locked_at)
                            <li class="mb-2">
                                <i class="fas fa-lock text-warning"></i>
                                <strong>Locked:</strong> {{ $consolidation->locked_at->format('M d, Y H:i') }}
                                @if($consolidation->lockedBy)
                                    by {{ $consolidation->lockedBy->name }}
                                @endif
                            </li>
                            @endif
                            @if($consolidation->dispatched_at)
                            <li class="mb-2">
                                <i class="fas fa-shipping-fast text-primary"></i>
                                <strong>Dispatched:</strong> {{ $consolidation->dispatched_at->format('M d, Y H:i') }}
                            </li>
                            @endif
                            @if($consolidation->arrived_at)
                            <li class="mb-2">
                                <i class="fas fa-map-marker-alt text-info"></i>
                                <strong>Arrived:</strong> {{ $consolidation->arrived_at->format('M d, Y H:i') }}
                            </li>
                            @endif
                            @if($consolidation->completed_at)
                            <li class="mb-2">
                                <i class="fas fa-check-double text-success"></i>
                                <strong>Completed:</strong> {{ $consolidation->completed_at->format('M d, Y H:i') }}
                            </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Baby Shipments Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Baby Shipments ({{ $consolidation->current_pieces }})</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Tracking Number</th>
                                    <th>Client</th>
                                    <th>Weight</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($consolidation->babyShipments as $shipment)
                                <tr>
                                    <td>{{ $shipment->pivot->sequence_number }}</td>
                                    <td>
                                        <a href="{{ route('shipments.show', $shipment) }}" target="_blank">
                                            {{ $shipment->tracking_number }}
                                        </a>
                                    </td>
                                    <td>{{ $shipment->client->name ?? 'N/A' }}</td>
                                    <td>{{ number_format($shipment->pivot->weight_kg, 2) }} kg</td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ $shipment->pivot->status }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($consolidation->status == 'OPEN')
                                        <button class="btn btn-sm btn-outline-danger" onclick="removeShipment({{ $shipment->id }})">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No shipments added yet</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Summary -->
        <div class="col-md-4">
            <!-- Capacity Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Capacity</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Pieces</label>
                        <h4>{{ $consolidation->current_pieces }}
                            @if($consolidation->max_pieces)
                                / {{ $consolidation->max_pieces }}
                            @endif
                        </h4>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Weight</label>
                        <h4>{{ number_format($consolidation->current_weight_kg, 2) }} kg
                            @if($consolidation->max_weight_kg)
                                / {{ number_format($consolidation->max_weight_kg, 2) }} kg
                            @endif
                        </h4>
                    </div>
                    @if($consolidation->current_volume_cbm)
                    <div class="mb-3">
                        <label class="text-muted small">Volume</label>
                        <h4>{{ number_format($consolidation->current_volume_cbm, 3) }} CBM</h4>
                    </div>
                    @endif
                    <div>
                        <label class="text-muted small">Utilization</label>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar {{ $consolidation->utilization_percentage > 90 ? 'bg-success' : 'bg-primary' }}" 
                                 role="progressbar" 
                                 style="width: {{ min($consolidation->utilization_percentage, 100) }}%">
                                {{ number_format($consolidation->utilization_percentage, 0) }}%
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transport Details Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Transport Details</h5>
                </div>
                <div class="card-body">
                    @if($consolidation->transport_mode)
                    <div class="mb-3">
                        <label class="text-muted small">Transport Mode</label>
                        <p class="mb-0">{{ $consolidation->transport_mode }}</p>
                    </div>
                    @endif
                    @if($consolidation->awb_number)
                    <div class="mb-3">
                        <label class="text-muted small">AWB Number</label>
                        <p class="mb-0">{{ $consolidation->awb_number }}</p>
                    </div>
                    @endif
                    @if($consolidation->container_number)
                    <div class="mb-3">
                        <label class="text-muted small">Container Number</label>
                        <p class="mb-0">{{ $consolidation->container_number }}</p>
                    </div>
                    @endif
                    @if($consolidation->vehicle_number)
                    <div class="mb-3">
                        <label class="text-muted small">Vehicle Number</label>
                        <p class="mb-0">{{ $consolidation->vehicle_number }}</p>
                    </div>
                    @endif
                    @if($consolidation->cutoff_time)
                    <div class="mb-3">
                        <label class="text-muted small">Cutoff Time</label>
                        <p class="mb-0">{{ $consolidation->cutoff_time->format('M d, Y H:i') }}</p>
                    </div>
                    @endif
                    @if($consolidation->destinationBranch)
                    <div>
                        <label class="text-muted small">Destination Branch</label>
                        <p class="mb-0">{{ $consolidation->destinationBranch->name }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function lockConsolidation() {
    if (!confirm('Lock this consolidation? No more shipments can be added after locking.')) return;
    
    fetch('{{ route("branch.consolidations.lock", $consolidation) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(() => location.reload());
}

function removeShipment(shipmentId) {
    if (!confirm('Remove this shipment from consolidation?')) return;
    
    fetch('{{ route("branch.consolidations.shipments.remove", $consolidation) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ shipment_id: shipmentId })
    })
    .then(() => location.reload());
}
</script>
@endpush
@endsection
