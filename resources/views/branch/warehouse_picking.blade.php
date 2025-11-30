@extends('branch.layout')

@section('title', 'Warehouse Picking Lists')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="text-white mb-1">Warehouse Picking Lists</h2>
            <p class="text-gray-400">Manage outbound shipment picking and packing operations</p>
        </div>
        <div class="col-md-4 text-end">
            <button type="button" class="btn btn-emerald-600" data-bs-toggle="modal" data-bs-target="#generatePickListModal">
                <i class="fas fa-plus me-2"></i>Generate Pick List
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card bg-gray-800 border-gray-700">
                <div class="card-body">
                    <small class="text-gray-400">Pending Picks</small>
                    <h3 class="text-white">{{ $pendingCount }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-gray-800 border-gray-700">
                <div class="card-body">
                    <small class="text-gray-400">In Progress</small>
                    <h3 class="text-white">{{ $inProgressCount }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-gray-800 border-gray-700">
                <div class="card-body">
                    <small class="text-gray-400">Completed Today</small>
                    <h3 class="text-white">{{ $completedTodayCount }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-gray-800 border-gray-700">
                <div class="card-body">
                    <small class="text-gray-400">Total Items</small>
                    <h3 class="text-white">{{ $totalItemsCount }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Pick Lists -->
    <div class="card bg-gray-800 border-gray-700 mb-4">
        <div class="card-header bg-gray-900 border-gray-700">
            <h5 class="mb-0 text-white">Active Pick Lists</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-dark table-hover">
                    <thead>
                        <tr>
                            <th>Pick List #</th>
                            <th>Created</th>
                            <th>Items</th>
                            <th>Assigned To</th>
                            <th>Status</th>
                            <th>Progress</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pickLists as $pickList)
                        <tr>
                            <td>
                                <strong class="text-white">{{ $pickList->pick_list_number }}</strong>
                            </td>
                            <td>{{ $pickList->created_at->format('M d, H:i') }}</td>
                            <td>{{ $pickList->total_items }} items</td>
                            <td>
                                @if($pickList->assigned_to)
                                    {{ $pickList->assignee->name ?? 'Unknown' }}
                                @else
                                    <span class="text-gray-400">Unassigned</span>
                                @endif
                            </td>
                            <td>
                                @if($pickList->status === 'pending')
                                    <span class="badge bg-secondary">Pending</span>
                                @elseif($pickList->status === 'in_progress')
                                    <span class="badge bg-primary">In Progress</span>
                                @elseif($pickList->status === 'completed')
                                    <span class="badge bg-success">Completed</span>
                                @elseif($pickList->status === 'cancelled')
                                    <span class="badge bg-danger">Cancelled</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="progress flex-grow-1 me-2" style="height: 20px; width: 100px;">
                                        <div class="progress-bar bg-success" style="width: {{ $pickList->picked_items / max($pickList->total_items, 1) * 100 }}%">
                                            {{ round($pickList->picked_items / max($pickList->total_items, 1) * 100) }}%
                                        </div>
                                    </div>
                                    <small class="text-gray-400">{{ $pickList->picked_items }}/{{ $pickList->total_items }}</small>
                                </div>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('branch.warehouse.picking.show', $pickList->id) }}" class="btn btn-outline-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    @if($pickList->status === 'pending')
                                    <form method="POST" action="{{ route('branch.warehouse.picking.start', $pickList->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-success">
                                            <i class="fas fa-play"></i> Start
                                        </button>
                                    </form>
                                    @endif
                                    <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-dark">
                                        <li><a class="dropdown-item" href="{{ route('branch.warehouse.picking.print', $pickList->id) }}" target="_blank">
                                            <i class="fas fa-print me-2"></i>Print
                                        </a></li>
                                        @if($pickList->status !== 'completed')
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" action="{{ route('branch.warehouse.picking.cancel', $pickList->id) }}">
                                                @csrf
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="fas fa-times me-2"></i>Cancel
                                                </button>
                                            </form>
                                        </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-gray-400 py-4">
                                <i class="fas fa-clipboard-list fa-3x mb-3 opacity-50"></i>
                                <p>No active pick lists</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($pickLists->hasPages())
            <div class="mt-3">
                {{ $pickLists->links() }}
            </div>
            @endif
        </div>
    </div>

    <!-- Ready for Picking Shipments -->
    <div class="card bg-gray-800 border-gray-700">
        <div class="card-header bg-gray-900 border-gray-700">
            <h5 class="mb-0 text-white">Shipments Ready for Picking</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-dark table-hover">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th>Tracking #</th>
                            <th>Customer</th>
                            <th>Destination</th>
                            <th>Parcels</th>
                            <th>Priority</th>
                            <th>Scheduled Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($readyShipments as $shipment)
                        <tr>
                            <td>
                                <input type="checkbox" class="form-check-input shipment-select" value="{{ $shipment->id }}">
                            </td>
                            <td><strong>{{ $shipment->tracking_number }}</strong></td>
                            <td>{{ $shipment->customer->name ?? 'N/A' }}</td>
                            <td>{{ $shipment->destBranch->name ?? $shipment->delivery_address }}</td>
                            <td>{{ $shipment->parcels_count ?? 0 }}</td>
                            <td>
                                @if($shipment->priority === 'urgent')
                                    <span class="badge bg-danger">Urgent</span>
                                @elseif($shipment->priority === 'high')
                                    <span class="badge bg-warning">High</span>
                                @else
                                    <span class="badge bg-secondary">Normal</span>
                                @endif
                            </td>
                            <td>{{ $shipment->expected_delivery_date ? \Carbon\Carbon::parse($shipment->expected_delivery_date)->format('M d') : 'N/A' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-gray-400 py-4">No shipments ready for picking</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($readyShipments->isNotEmpty())
            <div class="mt-3">
                <button type="button" class="btn btn-primary" onclick="createPickListFromSelected()">
                    <i class="fas fa-plus me-2"></i>Create Pick List from Selected
                </button>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Generate Pick List Modal -->
<div class="modal fade" id="generatePickListModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-gray-800">
            <form method="POST" action="{{ route('branch.warehouse.picking.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title text-white">Generate Pick List</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-white">Assignment</label>
                        <select name="assigned_to" class="form-select bg-gray-900 border-gray-700 text-white">
                            <option value="">Unassigned</option>
                            @foreach($workers as $worker)
                            <option value="{{ $worker->user_id }}">{{ $worker->user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white">Priority Filter</label>
                        <select name="priority" class="form-select bg-gray-900 border-gray-700 text-white">
                            <option value="">All Priorities</option>
                            <option value="urgent">Urgent Only</option>
                            <option value="high">High Priority</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white">Max Items</label>
                        <input type="number" name="max_items" class="form-control bg-gray-900 border-gray-700 text-white" value="20" min="1" max="100">
                        <small class="text-gray-400">Maximum number of shipments to include</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white">Notes</label>
                        <textarea name="notes" class="form-control bg-gray-900 border-gray-700 text-white" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Generate Pick List</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Select all checkbox
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.shipment-select').forEach(cb => cb.checked = this.checked);
});

function createPickListFromSelected() {
    const selected = Array.from(document.querySelectorAll('.shipment-select:checked')).map(cb => cb.value);
    if (selected.length === 0) {
        alert('Please select at least one shipment');
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("branch.warehouse.picking.store") }}';
    
    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = '{{ csrf_token() }}';
    form.appendChild(csrf);
    
    selected.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'shipment_ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
}
</script>
@endsection
