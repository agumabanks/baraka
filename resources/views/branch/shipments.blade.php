@extends('branch.layout')

@section('title', 'Shipment Management')

@section('content')
<div class="container-fluid py-4">
    <!-- Header with Stats -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="text-white mb-1">Shipment Management</h2>
            <p class="text-gray-400">Complete shipment tracking and management for {{ $branch->name }}</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('branch.pos.index') }}" class="btn btn-emerald-600">
                <i class="fas fa-plus me-2"></i>Shipment POS
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card bg-gray-800 border-gray-700">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                                <i class="fas fa-box text-primary fa-2x"></i>
                            </div>
                        </div>
                        <div class="ms-3">
                            <small class="text-gray-400 d-block">Total Shipments</small>
                            <h3 class="text-white mb-0">{{ $stats['total'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-gray-800 border-gray-700">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                                <i class="fas fa-clock text-warning fa-2x"></i>
                            </div>
                        </div>
                        <div class="ms-3">
                            <small class="text-gray-400 d-block">In Transit</small>
                            <h3 class="text-white mb-0">{{ $stats['in_transit'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-gray-800 border-gray-700">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-success bg-opacity-10 p-3">
                                <i class="fas fa-check-circle text-success fa-2x"></i>
                            </div>
                        </div>
                        <div class="ms-3">
                            <small class="text-gray-400 d-block">Delivered Today</small>
                            <h3 class="text-white mb-0">{{ $stats['delivered_today'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-gray-800 border-gray-700">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-danger bg-opacity-10 p-3">
                                <i class="fas fa-exclamation-triangle text-danger fa-2x"></i>
                            </div>
                        </div>
                        <div class="ms-3">
                            <small class="text-gray-400 d-block">At Risk</small>
                            <h3 class="text-white mb-0">{{ $stats['at_risk'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card bg-gray-800 border-gray-700 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('branch.shipments') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label text-white">Direction</label>
                    <select name="direction" class="form-select bg-gray-900 border-gray-700 text-white">
                        <option value="all" {{ request('direction') === 'all' ? 'selected' : '' }}>All</option>
                        <option value="inbound" {{ request('direction') === 'inbound' ? 'selected' : '' }}>Inbound</option>
                        <option value="outbound" {{ request('direction') === 'outbound' ? 'selected' : '' }}>Outbound</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label text-white">Status</label>
                    <select name="status" class="form-select bg-gray-900 border-gray-700 text-white">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="picked_up" {{ request('status') === 'picked_up' ? 'selected' : '' }}>Picked Up</option>
                        <option value="in_transit" {{ request('status') === 'in_transit' ? 'selected' : '' }}>In Transit</option>
                        <option value="at_hub" {{ request('status') === 'at_hub' ? 'selected' : '' }}>At Hub</option>
                        <option value="out_for_delivery" {{ request('status') === 'out_for_delivery' ? 'selected' : '' }}>Out for Delivery</option>
                        <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label text-white">From Date</label>
                    <input type="date" name="from" class="form-control bg-gray-900 border-gray-700 text-white" value="{{ request('from') }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label text-white">To Date</label>
                    <input type="date" name="to" class="form-control bg-gray-900 border-gray-700 text-white" value="{{ request('to') }}">
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#all-shipments">
                All Shipments
                <span class="badge bg-primary ms-2">{{ $shipments->total() }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#inbound">
                Inbound
                <span class="badge bg-info ms-2">{{ $stats['inbound'] }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#outbound">
                Outbound
                <span class="badge bg-warning ms-2">{{ $stats['outbound'] }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#at-risk">
                At Risk
                <span class="badge bg-danger ms-2">{{ $stats['at_risk'] }}</span>
            </a>
        </li>
    </ul>

    <!-- Shipments Table -->
    <div class="card bg-gray-800 border-gray-700">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-dark table-hover">
                    <thead>
                        <tr>
                            <th>Tracking #</th>
                            <th>Customer</th>
                            <th>Origin → Destination</th>
                            <th>Status</th>
                            <th>Assigned To</th>
                            <th>SLA</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shipments as $shipment)
                        <tr>
                            <td>
                                <a href="{{ route('shipments.show', $shipment->id) }}" class="text-primary">
                                    <strong>{{ $shipment->tracking_number }}</strong>
                                </a>
                                @if($shipment->is_fragile)
                                    <span class="badge bg-warning ms-2">Fragile</span>
                                @endif
                            </td>
                            <td>{{ $shipment->customer->name ?? 'N/A' }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-secondary">{{ $shipment->originBranch->code ?? 'N/A' }}</span>
                                    <i class="fas fa-arrow-right mx-2 text-gray-400"></i>
                                    <span class="badge bg-secondary">{{ $shipment->destBranch->code ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td>
                                @php
                                    $statusClass = match($shipment->current_status) {
                                        'PENDING', 'CREATED' => 'bg-secondary',
                                        'PICKED_UP', 'PROCESSING' => 'bg-info',
                                        'IN_TRANSIT', 'AT_ORIGIN_HUB', 'AT_DESTINATION_HUB' => 'bg-primary',
                                        'OUT_FOR_DELIVERY' => 'bg-warning',
                                        'DELIVERED' => 'bg-success',
                                        'CANCELLED', 'FAILED' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                @endphp
                                <span class="badge {{ $statusClass }}">
                                    {{ str_replace('_', ' ', $shipment->current_status) }}
                                </span>
                            </td>
                            <td>
                                @if($shipment->assignedWorker)
                                    <span class="text-white">{{ $shipment->assignedWorker->user->name }}</span>
                                @else
                                    <span class="text-gray-400">Unassigned</span>
                                @endif
                            </td>
                            <td>
                                @if($shipment->expected_delivery_date)
                                    @php
                                        $isOverdue = $shipment->expected_delivery_date->isPast() && !$shipment->delivered_at;
                                        $isAtRisk = $shipment->expected_delivery_date->diffInHours(now()) < 24 && !$shipment->delivered_at;
                                    @endphp
                                    @if($isOverdue)
                                        <span class="badge bg-danger">
                                            <i class="fas fa-exclamation-circle me-1"></i>Overdue
                                        </span>
                                    @elseif($isAtRisk)
                                        <span class="badge bg-warning">
                                            <i class="fas fa-clock me-1"></i>At Risk
                                        </span>
                                    @else
                                        <span class="badge bg-success">On Time</span>
                                    @endif
                                @else
                                    <span class="text-gray-400">N/A</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-gray-400">
                                    {{ $shipment->created_at->format('M d, H:i') }}
                                </small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('shipments.show', $shipment->id) }}" class="btn btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-dark">
                                        <li><a class="dropdown-item" href="{{ route('shipments.show', $shipment->id) }}">
                                            <i class="fas fa-eye me-2"></i>View Details
                                        </a></li>
                                        <li><a class="dropdown-item" href="{{ route('shipments.label', $shipment->id) }}" target="_blank">
                                            <i class="fas fa-print me-2"></i>Print Label
                                        </a></li>
                                        @if(!$shipment->assignedWorker)
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="#" onclick="assignShipment({{ $shipment->id }})">
                                            <i class="fas fa-user-plus me-2"></i>Assign Worker
                                        </a></li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-gray-400 py-4">
                                <i class="fas fa-box-open fa-3x mb-3 opacity-50"></i>
                                <p>No shipments found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($shipments->hasPages())
            <div class="mt-3">
                {{ $shipments->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<script>
function assignShipment(shipmentId) {
    // Implement assign worker modal
    alert('Assign worker functionality - ID: ' + shipmentId);
}
</script>
@endsection
