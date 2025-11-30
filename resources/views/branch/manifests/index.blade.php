@extends('branch.layout')

@section('title', 'Manifests')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manifests</h1>
        <a href="{{ route('branch.manifests.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create Manifest
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">All Manifests</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Number</th>
                            <th>Type</th>
                            <th>Origin</th>
                            <th>Destination</th>
                            <th>Driver / Vehicle</th>
                            <th>Status</th>
                            <th>Departure</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($manifests as $manifest)
                        <tr>
                            <td>
                                <a href="{{ route('branch.manifests.show', $manifest) }}">
                                    {{ $manifest->number }}
                                </a>
                            </td>
                            <td>{{ $manifest->type }} ({{ $manifest->mode }})</td>
                            <td>{{ $manifest->originBranch->name }}</td>
                            <td>{{ $manifest->destinationBranch->name ?? 'N/A' }}</td>
                            <td>
                                <div><i class="fas fa-user"></i> {{ $manifest->driver->name ?? 'Unassigned' }}</div>
                                <div><i class="fas fa-truck"></i> {{ $manifest->vehicle->plate_no ?? 'Unassigned' }}</div>
                            </td>
                            <td>
                                <span class="badge badge-{{ $manifest->status === 'open' ? 'warning' : ($manifest->status === 'departed' ? 'primary' : 'success') }}">
                                    {{ ucfirst($manifest->status) }}
                                </span>
                            </td>
                            <td>{{ $manifest->departure_at ? $manifest->departure_at->format('Y-m-d H:i') : 'Scheduled' }}</td>
                            <td>
                                <a href="{{ route('branch.manifests.show', $manifest) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">No manifests found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $manifests->links() }}
        </div>
    </div>
</div>
@endsection
