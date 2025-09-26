@extends('backend.partials.master')
@section('maincontent')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Delivery Routes</h3>
    <a href="{{ route('admin.routes.create') }}" class="btn btn-primary btn-sm">Create</a>
  </div>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead><tr><th>#</th><th>Name</th><th>Stops</th><th></th></tr></thead>
        <tbody>
          @foreach($routes as $r)
            <tr><td>{{ $r->id }}</td><td>{{ $r->name }}</td><td>{{ $r->stops_count ?? '-' }}</td><td><a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.routes.show',$r) }}">View</a></td></tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="card-footer">{{ $routes->links() }}</div>
  </div>
</div>
@endsection

