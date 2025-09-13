@extends('backend.partials.master')
@section('title')
    Customers
@endsection
@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}" class="breadcrumb-link">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Customers</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-6">
                        <p class="h3 m-0">Customers</p>
                    </div>
                    @can('create', \App\Models\Customer::class)
                    <div class="col-6">
                        <a href="{{ route('admin.customers.create') }}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" title="Add"><i class="fa fa-plus"></i></a>
                    </div>
                    @endcan
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Phone</th>
                                    <th>Branch</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($customers as $customer)
                                    <tr>
                                        <td>{{ $customer->id }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="ml-2">
                                                    <div class="font-weight-bold">{{ $customer->name }}</div>
                                                    <div class="small text-muted">{{ $customer->email }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $customer->phone }}</td>
                                        <td>{{ optional($customer->hub)->name ?? '—' }}</td>
                                        <td>{!! $customer->my_status ?? ($customer->status ?? 'ACTIVE') !!}</td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-sm btn-outline-secondary" title="View"><i class="fa fa-eye"></i></a>
                                                @can('update', $customer)
                                                    <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fa fa-edit"></i></a>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No customers found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if(method_exists($customers, 'links'))
                <div class="px-3 d-flex flex-row-reverse align-items-center">
                    <span>{{ $customers->links() }}</span>
                    <p class="p-2 small">
                        Showing <span class="font-medium">{{ $customers->firstItem() }}</span> to
                        <span class="font-medium">{{ $customers->lastItem() }}</span> of
                        <span class="font-medium">{{ $customers->total() }}</span> results
                    </p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

