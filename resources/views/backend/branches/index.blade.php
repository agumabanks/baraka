@extends('backend.partials.master')
@section('title', 'Branch Management')
@section('maincontent')
<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-1">Branch Management</h2>
                    <p class="text-muted mb-0">Manage your branch network and hierarchy</p>
                </div>
                <div>
                    @can('create', App\Models\Backend\Branch::class)
                        <a href="{{ route('branches.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i> Add New Branch
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="GET" action="{{ route('branches.index') }}" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Search branches..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select">
                                <option value="">All Types</option>
                                @foreach($branchTypes as $type)
                                    <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Is Hub</label>
                            <select name="is_hub" class="form-select">
                                <option value="">All</option>
                                <option value="1" {{ request('is_hub') == '1' ? 'selected' : '' }}>Hub Only</option>
                                <option value="0" {{ request('is_hub') == '0' ? 'selected' : '' }}>Non-Hub Only</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-fill">
                                    <i class="fas fa-search me-2"></i>Filter
                                </button>
                                <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary">
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
                                    <th>Code</th>
                                    <th>Branch Name</th>
                                    <th>Type</th>
                                    <th>Parent Branch</th>
                                    <th>Manager</th>
                                    <th>Workers</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($branches as $branch)
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary">{{ $branch->code }}</span>
                                            @if($branch->is_hub)
                                                <span class="badge bg-primary ms-1">HUB</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-semibold">{{ $branch->name }}</span>
                                                @if($branch->address)
                                                    <small class="text-muted">{{ Str::limit($branch->address, 50) }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge 
                                                @if($branch->type === 'HUB') bg-primary
                                                @elseif($branch->type === 'REGIONAL') bg-info
                                                @else bg-success
                                                @endif">
                                                {{ $branch->type }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($branch->parent)
                                                <span class="text-muted">{{ $branch->parent->name }}</span>
                                            @else
                                                <span class="text-muted fst-italic">Root</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($branch->branchManager)
                                                <span class="text-success">{{ $branch->branchManager->user->name ?? 'N/A' }}</span>
                                            @else
                                                <span class="text-muted fst-italic">No Manager</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                {{ $branch->activeWorkers->count() }} active
                                            </span>
                                        </td>
                                        <td>
                                            @if($branch->status === App\Enums\Status::ACTIVE)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                @can('view', $branch)
                                                    <a href="{{ route('branches.show', $branch) }}" class="btn btn-outline-primary" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                @endcan
                                                @can('update', $branch)
                                                    <a href="{{ route('branches.edit', $branch) }}" class="btn btn-outline-warning" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endcan
                                                @can('delete', $branch)
                                                    <button type="button" class="btn btn-outline-danger" title="Delete" onclick="deleteBranch({{ $branch->id }})">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                            No branches found. <a href="{{ route('branches.create') }}">Create your first branch</a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($branches->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $branches->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function deleteBranch(branchId) {
    if (confirm('Are you sure you want to delete this branch? This action cannot be undone.')) {
        fetch(`/admin/branches/${branchId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Failed to delete branch');
            }
        })
        .catch(error => {
            alert('An error occurred while deleting the branch');
            console.error('Error:', error);
        });
    }
}
</script>
@endpush
@endsection
