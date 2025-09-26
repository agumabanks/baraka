@extends('backend.partials.master')
@section('maincontent')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Contracts</h3>
    <a href="{{ route('admin.contracts.create') }}" class="btn btn-primary btn-sm">Create</a>
  </div>
  <div class="card"><div class="table-responsive">
    <table class="table table-striped mb-0">
      <thead><tr><th>#</th><th>Customer</th><th>Name</th><th>Start</th><th>End</th><th>Status</th><th></th></tr></thead>
      <tbody>
        @foreach($items as $c)
        <tr>
          <td>{{ $c->id }}</td><td>{{ $c->customer_id }}</td><td>{{ $c->name }}</td><td>{{ $c->start_date }}</td><td>{{ $c->end_date }}</td><td><span class="badge bg-secondary">{{ $c->status }}</span></td>
          <td><a href="{{ route('admin.contracts.show',$c) }}" class="btn btn-sm btn-outline-secondary">View</a></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div><div class="card-footer">{{ $items->links() }}</div></div>
</div>
@endsection

