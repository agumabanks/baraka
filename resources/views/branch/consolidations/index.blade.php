@extends('branch.layout')

@section('title', 'Consolidations')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Groupage Consolidations</h2>
            <p class="text-muted">Manage BBX and LBX consolidations for efficient shipping</p>
        </div>
        <div>
            <button class="btn btn-outline-primary me-2" onclick="autoConsolidate()">
                <i class="fas fa-magic"></i> Auto-Consolidate
            </button>
            <a href="{{ route('branch.consolidations.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Consolidation
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Total This Month</h6>
                            <h3 class="mb-0">{{ $statistics['total_consolidations'] }}</h3>
                        </div>
                        <div class="text-primary fs-2">
                            <i class="fas fa-boxes"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Open</h6>
                            <h3 class="mb-0">{{ $statistics['open_consolidations'] }}</h3>
                        </div>
                        <div class="text-success fs-2">
                            <i class="fas fa-folder-open"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">In Transit</h6>
                            <h3 class="mb-0">{{ $statistics['in_transit_consolidations'] }}</h3>
                        </div>
                        <div class="text-warning fs-2">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Avg Utilization</h6>
                            <h3 class="mb-0">{{ number_format($statistics['avg_utilization'] ?? 0, 1) }}%</h3>
                        </div>
                        <div class="text-info fs-2">
                            <i class="fas fa-percentage"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="OPEN" {{ request('status') == 'OPEN' ? 'selected' : '' }}>Open</option>
                        <option value="LOCKED" {{ request('status') == 'LOCKED' ? 'selected' : '' }}>Locked</option>
                        <option value="IN_TRANSIT" {{ request('status') == 'IN_TRANSIT' ? 'selected' : '' }}>In Transit</option>
                        <option value="ARRIVED" {{ request('status') == 'ARRIVED' ? 'selected' : '' }}>Arrived</option>
                        <option value="DECONSOLIDATING" {{ request('status') == 'DECONSOLIDATING' ? 'selected' : '' }}>Deconsolidating</option>
                        <option value="COMPLETED" {{ request('status') == 'COMPLETED' ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        <option value="BBX" {{ request('type') == 'BBX' ? 'selected' : '' }}>BBX (Physical)</option>
                        <option value="LBX" {{ request('type') == 'LBX' ? 'selected' : '' }}>LBX (Virtual)</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <a href="{{ route('branch.consolidations.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-redo"></i> Reset Filters
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Consolidations Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Consolidation #</th>
                            <th>Type</th>
                            <th>Destination</th>
                            <th>Status</th>
                            <th>Shipments</th>
                            <th>Weight</th>
                            <th>Utilization</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($consolidations as $consolidation)
                        <tr>
                            <td>
                                <a href="{{ route('branch.consolidations.show', $consolidation) }}" class="fw-bold text-decoration-none">
                                    {{ $consolidation->consolidation_number }}
                                </a>
                            </td>
                            <td>
                                <span class="badge {{ $consolidation->type == 'BBX' ? 'bg-primary' : 'bg-info' }}">
                                    {{ $consolidation->type }}
                                </span>
                            </td>
                            <td>{{ $consolidation->destination }}</td>
                            <td>
                                @php
                                    $statusColors = [
                                        'OPEN' => 'success',
                                        'LOCKED' => 'warning',
                                        'IN_TRANSIT' => 'primary',
                                        'ARRIVED' => 'info',
                                        'DECONSOLIDATING' => 'warning',
                                        'COMPLETED' => 'secondary',
                                        'CANCELLED' => 'danger'
                                    ];
                                @endphp
                                <span class="badge bg-{{ $statusColors[$consolidation->status] ?? 'secondary' }}">
                                    {{ str_replace('_', ' ', $consolidation->status) }}
                                </span>
                            </td>
                            <td>
                                {{ $consolidation->current_pieces }}
                                @if($consolidation->max_pieces)
                                    / {{ $consolidation->max_pieces }}
                                @endif
                            </td>
                            <td>{{ number_format($consolidation->current_weight_kg, 2) }} kg</td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar {{ $consolidation->utilization_percentage > 90 ? 'bg-success' : 'bg-primary' }}" 
                                         role="progressbar" 
                                         style="width: {{ min($consolidation->utilization_percentage, 100) }}%">
                                        {{ number_format($consolidation->utilization_percentage, 0) }}%
                                    </div>
                                </div>
                            </td>
                            <td>
                                {{ $consolidation->created_at->format('M d, Y') }}
                                <br>
                                <small class="text-muted">{{ $consolidation->created_at->diffForHumans() }}</small>
                            </td>
                            <td>
                                <a href="{{ route('branch.consolidations.show', $consolidation) }}" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                <p class="text-muted">No consolidations found</p>
                                <a href="{{ route('branch.consolidations.create') }}" class="btn btn-primary">
                                    Create First Consolidation
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($consolidations->hasPages())
            <div class="mt-3">
                {{ $consolidations->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function autoConsolidate() {
    if (!confirm('Auto-consolidate all eligible shipments?')) return;
    
    fetch('{{ route("branch.consolidations.auto-consolidate") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => {
        alert('Auto-consolidation failed');
        console.error(error);
    });
}
</script>
@endpush
@endsection
