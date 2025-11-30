@extends('branch.layout')

@section('title', 'Manifest Details: ' . $manifest->number)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Manifest: {{ $manifest->number }}</h1>
            <p class="text-muted mb-0">
                {{ $manifest->originBranch->name }} <i class="fas fa-arrow-right mx-2"></i> {{ $manifest->destinationBranch->name ?? 'Unknown' }}
            </p>
        </div>
        <div>
            <a href="{{ route('branch.manifests.index') }}" class="btn btn-secondary mr-2">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            @if($manifest->status === 'open')
                <form action="{{ route('branch.manifests.dispatch', $manifest) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to dispatch this manifest?')">
                        <i class="fas fa-paper-plane"></i> Dispatch
                    </button>
                </form>
            @elseif($manifest->status === 'departed' && $manifest->destination_branch_id === auth()->user()->branch_id)
                <form action="{{ route('branch.manifests.arrive', $manifest) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Confirm arrival of this manifest?')">
                        <i class="fas fa-check-circle"></i> Mark Arrived
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Status</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ ucfirst($manifest->status) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-info-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Driver</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $manifest->driver->name ?? 'Unassigned' }}</div>
                            <small>{{ $manifest->driver->phone ?? '' }}</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Vehicle</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $manifest->vehicle->plate_no ?? 'Unassigned' }}</div>
                            <small>{{ $manifest->vehicle->model ?? '' }}</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-truck fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Manifest Items</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>ID / Tracking</th>
                            <th>Loaded At</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($manifest->items as $item)
                        <tr>
                            <td>{{ class_basename($item->manifestable_type) }}</td>
                            <td>
                                @if($item->manifestable_type === 'App\Models\Shipment')
                                    <a href="{{ route('branch.shipments.show', $item->manifestable_id) }}">
                                        {{ $item->manifestable->tracking_number ?? 'N/A' }}
                                    </a>
                                @elseif($item->manifestable_type === 'App\Models\Consolidation')
                                    <a href="{{ route('branch.consolidations.show', $item->manifestable_id) }}">
                                        {{ $item->manifestable->consolidation_number ?? 'N/A' }}
                                    </a>
                                @else
                                    {{ $item->manifestable_id }}
                                @endif
                            </td>
                            <td>{{ $item->loaded_at ? $item->loaded_at->format('Y-m-d H:i') : '-' }}</td>
                            <td>
                                <span class="badge badge-{{ $item->status === 'LOADED' ? 'success' : 'secondary' }}">
                                    {{ $item->status }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center">No items in this manifest.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
