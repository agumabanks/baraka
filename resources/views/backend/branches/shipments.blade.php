@extends('backend.partials.master')
@section('title', 'Shipments by Branch')
@section('maincontent')
<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-1">Shipments by Branch</h2>
                    <p class="text-muted mb-0">Track and manage shipments across all branches</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.branches.shipments') }}" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Search Shipments</label>
                            <input type="text" name="search" class="form-control" placeholder="Tracking number, AWB..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" class="form-select">
                                <option value="">All Branches</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="in_transit" {{ request('status') == 'in_transit' ? 'selected' : '' }}>In Transit</option>
                                <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-fill">
                                    <i class="fas fa-search me-2"></i>Filter
                                </button>
                                <a href="{{ route('admin.branches.shipments') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-redo"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Tracking Number</th>
                                    <th>Client</th>
                                    <th>Origin Branch</th>
                                    <th>Destination Branch</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($shipments as $shipment)
                                    <tr>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-semibold">{{ $shipment->tracking_number }}</span>
                                                @if($shipment->awb_number)
                                                    <small class="text-muted">AWB: {{ $shipment->awb_number }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if($shipment->client)
                                                {{ $shipment->client->name }}
                                            @else
                                                <span class="text-muted fst-italic">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($shipment->originBranch)
                                                <a href="{{ route('admin.branches.show', $shipment->originBranch) }}" class="text-decoration-none">
                                                    {{ $shipment->originBranch->name }}
                                                </a>
                                            @else
                                                <span class="text-muted fst-italic">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($shipment->destBranch)
                                                <a href="{{ route('admin.branches.show', $shipment->destBranch) }}" class="text-decoration-none">
                                                    {{ $shipment->destBranch->name }}
                                                </a>
                                            @else
                                                <span class="text-muted fst-italic">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $shipment->current_status === 'delivered' ? 'success' : ($shipment->current_status === 'pending' ? 'warning' : 'info') }}">
                                                {{ ucfirst(str_replace('_', ' ', $shipment->current_status)) }}
                                            </span>
                                        </td>
                                        <td>{{ $shipment->created_at->format('M d, Y') }}</td>
                                        <td class="text-end">
                                            <a href="#" class="btn btn-sm btn-outline-primary" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                            No shipments found for the selected filters.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($shipments->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $shipments->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
