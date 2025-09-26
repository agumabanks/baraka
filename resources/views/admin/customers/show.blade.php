@extends('backend.partials.master')
@section('title')
    Customer Details
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
                            <li class="breadcrumb-item"><a href="{{ route('admin.customers.index') }}" class="breadcrumb-link">Customers</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Details</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card mb-3">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1">{{ $customer->name }}</h4>
                        <div class="text-muted">{{ $customer->email }} • {{ $customer->phone }}</div>
                        <div class="text-muted">Branch: {{ optional($customer->hub)->name ?? '—' }}</div>
                        <div class="mt-2">Status: {!! $customer->my_status ?? ($customer->status ?? 'ACTIVE') !!}</div>
                    </div>
                    <div class="d-flex gap-2">
                        @can('update', $customer)
                            <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Edit</a>
                        @endcan
                        @if(auth()->user()->hasRole(['hq_admin','support','admin']))
                            <form action="{{ route('admin.impersonate.start', $customer) }}" method="POST" onsubmit="return confirm('Impersonate this customer? You will switch to their account until you stop.');">
                                @csrf
                                <button type="submit" class="btn btn-outline-warning btn-sm"><i class="fa fa-user-secret"></i> Impersonate</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <strong>Recent Shipments</strong>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tracking</th>
                                    <th>Origin</th>
                                    <th>Destination</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($customer->shipments as $shipment)
                                    <tr>
                                        <td>{{ $shipment->id }}</td>
                                        <td>{{ $shipment->tracking_number }}</td>
                                        <td>{{ optional($shipment->originBranch)->name ?? '—' }}</td>
                                        <td>{{ optional($shipment->destBranch)->name ?? '—' }}</td>
                                        <td>{{ $shipment->current_status?->name ?? '—' }}</td>
                                        <td>{{ $shipment->created_at?->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No shipments yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
