@extends('backend.partials.master')
@section('maincontent')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Quotations</h3>
    <a href="{{ route('admin.quotations.create') }}" class="btn btn-primary btn-sm">New Quote</a>
  </div>
  <div class="card"><div class="table-responsive">
    <table class="table table-striped mb-0">
      <thead><tr><th>#</th><th>Customer</th><th>Dest</th><th>Service</th><th>Weight</th><th>Total</th><th>Status</th><th></th></tr></thead>
      <tbody>
        @foreach($quotes as $q)
        <tr>
          <td>{{ $q->id }}</td><td>{{ $q->customer_id }}</td><td>{{ $q->destination_country }}</td><td>{{ $q->service_type }}</td><td>{{ $q->weight_kg }}</td><td>{{ $q->total_amount }} {{ $q->currency }}</td><td><span class="badge bg-secondary">{{ $q->status }}</span></td>
          <td><a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.quotations.show',$q) }}">View</a></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div><div class="card-footer">{{ $quotes->links() }}</div></div>
</div>
@endsection

