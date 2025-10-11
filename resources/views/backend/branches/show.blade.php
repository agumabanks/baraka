@extends('backend.partials.master')
@section('title', 'Branch Details - ' . $branch->name)
@section('maincontent')
<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-1">{{ $branch->name }}</h2>
                    <p class="text-muted mb-0">
                        <span class="badge bg-secondary">{{ $branch->code }}</span>
                        @if($branch->is_hub)
                            <span class="badge bg-primary">CENTRAL HUB</span>
                        @endif
                        @if($branch->status === App\Enums\Status::ACTIVE)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-danger">Inactive</span>
                        @endif
                    </p>
                </div>
                <div>
                    @can('update', $branch)
                        <a href="{{ route('branches.edit', $branch) }}" class="btn btn-warning">
                            <i class="fas fa-edit me-2"></i>Edit
                        </a>
                    @endcan
                    <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-bold">Branch Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">Type</small>
                        <span class="badge 
                            @if($branch->type === 'HUB') bg-primary
                            @elseif($branch->type === 'REGIONAL') bg-info
                            @else bg-success
                            @endif">
                            {{ $branch->type }}
                        </span>
                    </div>
                    
                    @if($branch->parent)
                        <div class="mb-3">
                            <small class="text-muted d-block">Parent Branch</small>
                            <a href="{{ route('branches.show', $branch->parent) }}" class="text-decoration-none">
                                {{ $branch->parent->name }}
                            </a>
                        </div>
                    @endif

                    @if($branch->address)
                        <div class="mb-3">
                            <small class="text-muted d-block">Address</small>
                            <p class="mb-0">{{ $branch->address }}</p>
                        </div>
                    @endif

                    @if($branch->phone)
                        <div class="mb-3">
                            <small class="text-muted d-block">Phone</small>
                            <p class="mb-0">{{ $branch->phone }}</p>
                        </div>
                    @endif

                    @if($branch->email)
                        <div class="mb-3">
                            <small class="text-muted d-block">Email</small>
                            <p class="mb-0">{{ $branch->email }}</p>
                        </div>
                    @endif

                    @if($branch->latitude && $branch->longitude)
                        <div class="mb-3">
                            <small class="text-muted d-block">Coordinates</small>
                            <p class="mb-0">{{ $branch->latitude }}, {{ $branch->longitude }}</p>
                        </div>
                    @endif
                </div>
            </div>

            @if($branch->branchManager)
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-header bg-white">
                        <h6 class="mb-0 fw-bold">Branch Manager</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">{{ $branch->branchManager->user->name ?? 'N/A' }}</h6>
                                <small class="text-muted">{{ $branch->branchManager->user->email ?? '' }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-8">
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <h3 class="mb-1 text-primary">{{ $analytics['capacity_metrics']['active_workers'] }}</h3>
                            <small class="text-muted">Active Workers</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <h3 class="mb-1 text-warning">{{ $analytics['capacity_metrics']['pending_shipments'] }}</h3>
                            <small class="text-muted">Pending Shipments</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <h3 class="mb-1 text-success">{{ $analytics['capacity_metrics']['active_clients'] }}</h3>
                            <small class="text-muted">Active Clients</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <h3 class="mb-1 text-info">{{ number_format($analytics['capacity_metrics']['utilization_rate'], 1) }}%</h3>
                            <small class="text-muted">Utilization Rate</small>
                        </div>
                    </div>
                </div>
            </div>

            @if($branch->children->count() > 0)
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white">
                        <h6 class="mb-0 fw-bold">Child Branches ({{ $branch->children->count() }})</h6>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            @foreach($branch->children as $child)
                                <a href="{{ route('branches.show', $child) }}" class="list-group-item list-group-item-action border-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0">{{ $child->name }}</h6>
                                            <small class="text-muted">{{ $child->code }}</small>
                                        </div>
                                        <span class="badge bg-{{ $child->type === 'REGIONAL' ? 'info' : 'success' }}">
                                            {{ $child->type }}
                                        </span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-bold">Branch Workers ({{ $branch->activeWorkers->count() }})</h6>
                </div>
                <div class="card-body">
                    @if($branch->activeWorkers->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($branch->activeWorkers->take(5) as $worker)
                                <div class="list-group-item border-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0">{{ $worker->user->name ?? 'N/A' }}</h6>
                                            <small class="text-muted">{{ $worker->user->email ?? '' }}</small>
                                        </div>
                                        <span class="badge bg-success">Active</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if($branch->activeWorkers->count() > 5)
                            <div class="text-center mt-3">
                                <a href="{{ route('branch-workers.index', ['branch_id' => $branch->id]) }}" class="btn btn-sm btn-outline-primary">
                                    View All {{ $branch->activeWorkers->count() }} Workers
                                </a>
                            </div>
                        @endif
                    @else
                        <p class="text-muted text-center mb-0">No active workers assigned to this branch.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
