@extends('backend.partials.master')
@section('title', 'Clients by Branch')
@section('maincontent')
<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-1">Local Clients by Branch</h2>
                    <p class="text-muted mb-0">View and manage clients assigned to each branch</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.branches.clients') }}" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Search Clients</label>
                            <input type="text" name="search" class="form-control" placeholder="Search by name, email, phone..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-4">
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
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-fill">
                                    <i class="fas fa-search me-2"></i>Filter
                                </button>
                                <a href="{{ route('admin.branches.clients') }}" class="btn btn-outline-secondary">
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
                                    <th>Client Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Primary Branch</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($clients as $client)
                                    <tr>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-semibold">{{ $client->contact_person }}</span>
                                                @if($client->company_name)
                                                    <small class="text-muted">{{ $client->company_name }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>{{ $client->email }}</td>
                                        <td>{{ $client->phone }}</td>
                                        <td>
                                            @if($client->primaryBranch)
                                                <a href="{{ route('admin.branches.show', $client->primaryBranch) }}" class="text-decoration-none">
                                                    {{ $client->primaryBranch->name }}
                                                </a>
                                            @else
                                                <span class="text-muted fst-italic">Not assigned</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($client->status === 'active')
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.customers.show', $client->id) }}" class="btn btn-sm btn-outline-primary" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                            No clients found for the selected filters.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($clients->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $clients->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
