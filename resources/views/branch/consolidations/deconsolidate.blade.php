@extends('branch.layout')

@section('title', 'Deconsolidate ' . $consolidation->consolidation_number)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Deconsolidate: {{ $consolidation->consolidation_number }}</h1>
            <p class="text-muted mb-0">Scan baby shipments to release them into your branch</p>
        </div>
        <a href="{{ route('branch.consolidations.show', $consolidation) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Details
        </a>
    </div>

    <div class="row">
        <!-- Scanner Section -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow border-left-primary h-100">
                <div class="card-body">
                    <h5 class="card-title text-primary"><i class="fas fa-barcode"></i> Scan Shipment</h5>
                    <div class="form-group">
                        <label for="tracking_number">Tracking Number</label>
                        <input type="text" class="form-control form-control-lg" id="tracking_number" placeholder="Scan or type..." autofocus>
                    </div>
                    <div id="scan-status" class="alert d-none"></div>
                    
                    <div class="mt-4">
                        <h6>Deconsolidation Progress</h6>
                        @php
                            $total = $consolidation->babyShipments->count();
                            $deconsolidated = $consolidation->babyShipments->where('pivot.status', 'DECONSOLIDATED')->count();
                            $percent = $total > 0 ? ($deconsolidated / $total) * 100 : 0;
                        @endphp
                        <div class="progress mb-2">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $percent }}%" 
                                 id="progress-bar"
                                 aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="d-flex justify-content-between text-sm">
                            <span id="progress-text">{{ $deconsolidated }} / {{ $total }} Scanned</span>
                            <span>{{ number_format($percent, 0) }}%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shipment List -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Baby Shipments</h6>
                    <span class="badge badge-info">{{ $consolidation->babyShipments->count() }} Items</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="shipments-table">
                            <thead>
                                <tr>
                                    <th>Tracking #</th>
                                    <th>Status</th>
                                    <th>Weight</th>
                                    <th>Scanned At</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($consolidation->babyShipments as $shipment)
                                <tr id="row-{{ $shipment->id }}" class="{{ $shipment->pivot->status === 'DECONSOLIDATED' ? 'table-success' : '' }}">
                                    <td>
                                        <strong>{{ $shipment->tracking_number }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $shipment->client->name ?? 'N/A' }}</small>
                                    </td>
                                    <td class="status-cell">
                                        @if($shipment->pivot->status === 'DECONSOLIDATED')
                                            <span class="badge badge-success">Received</span>
                                        @else
                                            <span class="badge badge-secondary">Pending</span>
                                        @endif
                                    </td>
                                    <td>{{ $shipment->weight_kg }} kg</td>
                                    <td class="time-cell">
                                        {{ $shipment->pivot->updated_at->format('H:i:s') }}
                                    </td>
                                    <td>
                                        @if($shipment->pivot->status !== 'DECONSOLIDATED')
                                        <form action="{{ route('branch.consolidations.release', $consolidation) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="shipment_id" value="{{ $shipment->id }}">
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="fas fa-check"></i> Manual Release
                                            </button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('tracking_number');
        const statusDiv = document.getElementById('scan-status');
        
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                scanShipment(this.value);
            }
        });

        function scanShipment(trackingNumber) {
            if (!trackingNumber) return;

            fetch('{{ route("branch.consolidations.scan", $consolidation) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ tracking_number: trackingNumber })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showStatus('success', data.message);
                    updateRow(data.shipment.id);
                    input.value = '';
                    // Reload page to update progress fully or implement dynamic update
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showStatus('danger', data.message);
                    input.select();
                }
            })
            .catch(error => {
                showStatus('danger', 'Error scanning shipment');
                console.error(error);
            });
        }

        function showStatus(type, message) {
            statusDiv.className = `alert alert-${type}`;
            statusDiv.textContent = message;
            statusDiv.classList.remove('d-none');
        }

        function updateRow(shipmentId) {
            const row = document.getElementById(`row-${shipmentId}`);
            if (row) {
                row.classList.add('table-success');
                row.querySelector('.status-cell').innerHTML = '<span class="badge badge-success">Received</span>';
            }
        }
    });
</script>
@endpush
@endsection
