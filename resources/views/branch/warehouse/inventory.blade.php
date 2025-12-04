@extends('branch.layout')

@section('title', 'Inventory: ' . $location->code)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Inventory: {{ $location->code }}</h1>
            <p class="text-muted mb-0">
                {{ $location->full_name }} 
                <span class="badge badge-{{ $location->status === 'ACTIVE' ? 'success' : 'secondary' }}">{{ $location->status }}</span>
            </p>
        </div>
        <a href="{{ route('branch.warehouse.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Warehouse
        </a>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Parcels</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $parcels->count() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-box fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Capacity Usage</div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                        {{ $location->capacity ? round(($parcels->count() / $location->capacity) * 100) . '%' : 'N/A' }}
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-info" role="progressbar" 
                                             style="width: {{ $location->capacity ? ($parcels->count() / $location->capacity) * 100 : 0 }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Parcels in Location</h6>
            <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="recursiveSwitch" {{ request('recursive', true) ? 'checked' : '' }} onchange="toggleRecursive()">
                <label class="custom-control-label" for="recursiveSwitch">Include Sub-locations</label>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Barcode</th>
                            <th>Shipment</th>
                            <th>Current Location</th>
                            <th>Weight</th>
                            <th>Volume</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($parcels as $parcel)
                        <tr>
                            <td>{{ $parcel->barcode ?? 'N/A' }}</td>
                            <td>
                                <a href="{{ route('branch.shipments.show', $parcel->shipment_id) }}">
                                    {{ $parcel->shipment->tracking_number }}
                                </a>
                            </td>
                            <td>{{ $parcel->currentLocation->code ?? 'Unknown' }}</td>
                            <td>{{ $parcel->weight_kg }} kg</td>
                            <td>{{ $parcel->volume_cbm }} m³</td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="moveParcel('{{ $parcel->barcode }}')">
                                    <i class="fas fa-dolly"></i> Move
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">No parcels found in this location.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function toggleRecursive() {
    const isChecked = document.getElementById('recursiveSwitch').checked;
    const url = new URL(window.location.href);
    url.searchParams.set('recursive', isChecked ? '1' : '0');
    window.location.href = url.toString();
}

function moveParcel(barcode) {
    // Implement move modal or redirection
    alert('Move functionality coming soon for ' + barcode);
}
</script>
@endsection
