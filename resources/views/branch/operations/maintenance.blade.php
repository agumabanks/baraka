@extends('branch.layout')

@section('title', 'Maintenance Windows')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="text-white mb-1">Maintenance Windows</h2>
            <p class="text-gray-400">Schedule and manage maintenance for vehicles, locations, and branch facilities</p>
        </div>
        <div class="col-md-4 text-end">
            <button type="button" class="btn btn-emerald-600 hover:btn-emerald-500" data-bs-toggle="modal" data-bs-target="#createMaintenanceModal">
                <i class="fas fa-plus me-2"></i>Schedule Maintenance
            </button>
        </div>
    </div>

    <!-- Active Maintenance Alert -->
    @if($activeMaintenanceWindows->isNotEmpty())
    <div class="alert alert-warning mb-4">
        <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
            <div class="flex-grow-1">
                <h5 class="mb-1">Active Maintenance in Progress</h5>
                <p class="mb-0">{{ $activeMaintenanceWindows->count() }} maintenance window(s) currently active. Capacity may be reduced.</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Filters -->
    <div class="card bg-gray-800 border-gray-700 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('branch.operations.maintenance') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label text-gray-300">Entity Type</label>
                    <select name="entity_type" class="form-select bg-gray-900 border-gray-700 text-white">
                        <option value="">All Types</option>
                        <option value="branch" {{ request('entity_type') == 'branch' ? 'selected' : '' }}>Branch</option>
                        <option value="vehicle" {{ request('entity_type') == 'vehicle' ? 'selected' : '' }}>Vehicle</option>
                        <option value="warehouse_location" {{ request('entity_type') == 'warehouse_location' ? 'selected' : '' }}>Warehouse Location</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-gray-300">Status</label>
                    <select name="status" class="form-select bg-gray-900 border-gray-700 text-white">
                        <option value="">All Statuses</option>
                        <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-gray-300">From Date</label>
                    <input type="date" name="from" class="form-control bg-gray-900 border-gray-700 text-white" value="{{ request('from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label text-gray-300">To Date</label>
                    <input type="date" name="to" class="form-control bg-gray-900 border-gray-700 text-white" value="{{ request('to') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Maintenance Windows List -->
    <div class="card bg-gray-800 border-gray-700">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-dark table-hover">
                    <thead>
                        <tr>
                            <th>Entity</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Scheduled Time</th>
                            <th>Capacity Impact</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($maintenanceWindows as $window)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if($window->entity_type == 'vehicle')
                                        <i class="fas fa-truck text-blue-400 me-2"></i>
                                    @elseif($window->entity_type == 'warehouse_location')
                                        <i class="fas fa-warehouse text-yellow-400 me-2"></i>
                                    @else
                                        <i class="fas fa-building text-green-400 me-2"></i>
                                    @endif
                                    <span class="text-white">{{ ucfirst(str_replace('_', ' ', $window->entity_type)) }} #{{ $window->entity_id }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ ucfirst($window->maintenance_type) }}</span>
                            </td>
                            <td>
                                @if($window->status == 'scheduled')
                                    <span class="badge bg-info">Scheduled</span>
                                @elseif($window->status == 'in_progress')
                                    <span class="badge bg-warning">In Progress</span>
                                @elseif($window->status == 'completed')
                                    <span class="badge bg-success">Completed</span>
                                @else
                                    <span class="badge bg-danger">Cancelled</span>
                                @endif
                            </td>
                            <td>
                                <div class="text-white">{{ $window->scheduled_start_at->format('M d, Y H:i') }}</div>
                                <small class="text-gray-400">to {{ $window->scheduled_end_at->format('M d, Y H:i') }}</small>
                            </td>
                            <td>
                                @if($window->capacity_impact_percent > 0)
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-2" style="height: 20px; width: 100px;">
                                            <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $window->capacity_impact_percent }}%">
                                                {{ $window->capacity_impact_percent }}%
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-gray-400">No impact</span>
                                @endif
                            </td>
                            <td>
                                <div class="text-white">{{ Str::limit($window->description, 50) }}</div>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    @if($window->status == 'scheduled')
                                        <form method="POST" action="{{ route('branch.operations.maintenance.start', $window->id) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" title="Start Maintenance">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('branch.operations.maintenance.cancel', $window->id) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-danger" title="Cancel">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    @elseif($window->status == 'in_progress')
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#completeModal{{ $window->id }}">
                                            <i class="fas fa-check"></i> Complete
                                        </button>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewModal{{ $window->id }}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Complete Modal -->
                        <div class="modal fade" id="completeModal{{ $window->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content bg-gray-800">
                                    <form method="POST" action="{{ route('branch.operations.maintenance.complete', $window->id) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title text-white">Complete Maintenance</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label text-white">Completion Notes</label>
                                                <textarea name="notes" class="form-control bg-gray-900 border-gray-700 text-white" rows="4" placeholder="Enter any completion notes or observations...">{{ $window->notes }}</textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-success">Mark Complete</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- View Modal -->
                        <div class="modal fade" id="viewModal{{ $window->id }}" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content bg-gray-800">
                                    <div class="modal-header">
                                        <h5 class="modal-title text-white">Maintenance Window Details</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="text-gray-400">Entity Type</label>
                                                <div class="text-white">{{ ucfirst(str_replace('_', ' ', $window->entity_type)) }}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="text-gray-400">Maintenance Type</label>
                                                <div class="text-white">{{ ucfirst($window->maintenance_type) }}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="text-gray-400">Scheduled Start</label>
                                                <div class="text-white">{{ $window->scheduled_start_at->format('M d, Y H:i') }}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="text-gray-400">Scheduled End</label>
                                                <div class="text-white">{{ $window->scheduled_end_at->format('M d, Y H:i') }}</div>
                                            </div>
                                            @if($window->actual_start_at)
                                            <div class="col-md-6">
                                                <label class="text-gray-400">Actual Start</label>
                                                <div class="text-white">{{ $window->actual_start_at->format('M d, Y H:i') }}</div>
                                            </div>
                                            @endif
                                            @if($window->actual_end_at)
                                            <div class="col-md-6">
                                                <label class="text-gray-400">Actual End</label>
                                                <div class="text-white">{{ $window->actual_end_at->format('M d, Y H:i') }}</div>
                                            </div>
                                            @endif
                                            <div class="col-12">
                                                <label class="text-gray-400">Description</label>
                                                <div class="text-white">{{ $window->description }}</div>
                                            </div>
                                            @if($window->notes)
                                            <div class="col-12">
                                                <label class="text-gray-400">Notes</label>
                                                <div class="text-white">{{ $window->notes }}</div>
                                            </div>
                                            @endif
                                            <div class="col-md-6">
                                                <label class="text-gray-400">Capacity Impact</label>
                                                <div class="text-white">{{ $window->capacity_impact_percent }}%</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="text-gray-400">Status</label>
                                                <div class="text-white">{{ ucfirst($window->status) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-gray-400 py-4">
                                <i class="fas fa-tools fa-3x mb-3"></i>
                                <p>No maintenance windows found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($maintenanceWindows->hasPages())
            <div class="mt-3">
                {{ $maintenanceWindows->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Create Maintenance Modal -->
<div class="modal fade" id="createMaintenanceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-gray-800">
            <form method="POST" action="{{ route('branch.operations.maintenance.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title text-white">Schedule Maintenance Window</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-white">Entity Type *</label>
                            <select name="entity_type" class="form-select bg-gray-900 border-gray-700 text-white" required onchange="loadEntities(this.value)">
                                <option value="">Select Type</option>
                                <option value="branch">Branch</option>
                                <option value="vehicle">Vehicle</option>
                                <option value="warehouse_location">Warehouse Location</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white">Entity *</label>
                            <select name="entity_id" id="entity_id" class="form-select bg-gray-900 border-gray-700 text-white" required>
                                <option value="">Select entity type first</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white">Maintenance Type *</label>
                            <select name="maintenance_type" class="form-select bg-gray-900 border-gray-700 text-white" required>
                                <option value="scheduled">Scheduled</option>
                                <option value="emergency">Emergency</option>
                                <option value="repair">Repair</option>
                                <option value="inspection">Inspection</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white">Capacity Impact % *</label>
                            <input type="number" name="capacity_impact_percent" class="form-control bg-gray-900 border-gray-700 text-white" min="0" max="100" value="50" required>
                            <small class="text-gray-400">How much capacity will be reduced (0-100%)</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white">Start Date & Time *</label>
                            <input type="datetime-local" name="scheduled_start_at" class="form-control bg-gray-900 border-gray-700 text-white" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white">End Date & Time *</label>
                            <input type="datetime-local" name="scheduled_end_at" class="form-control bg-gray-900 border-gray-700 text-white" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white">Description *</label>
                            <textarea name="description" class="form-control bg-gray-900 border-gray-700 text-white" rows="3" required placeholder="Describe the maintenance work to be performed..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white">Notes</label>
                            <textarea name="notes" class="form-control bg-gray-900 border-gray-700 text-white" rows="2" placeholder="Additional notes or instructions..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Schedule Maintenance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function loadEntities(entityType) {
    const entitySelect = document.getElementById('entity_id');
    entitySelect.innerHTML = '<option value="">Loading...</option>';
    
    if (!entityType) {
        entitySelect.innerHTML = '<option value="">Select entity type first</option>';
        return;
    }
    
    fetch(`{{ route('branch.operations.maintenance.entities') }}?type=${entityType}`)
        .then(response => response.json())
        .then(data => {
            entitySelect.innerHTML = '<option value="">Select ' + entityType.replace('_', ' ') + '</option>';
            data.forEach(entity => {
                const option = document.createElement('option');
                option.value = entity.id;
                option.textContent = entity.name;
                entitySelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error loading entities:', error);
            entitySelect.innerHTML = '<option value="">Error loading entities</option>';
        });
}
</script>
@endsection
