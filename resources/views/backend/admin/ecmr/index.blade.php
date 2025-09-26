@extends('backend.partials.master')
@section('maincontent')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>e-CMR</h3>
    <a href="{{ route('admin.ecmr.create') }}" class="btn btn-primary btn-sm">Create</a>
  </div>
  <div class="card"><div class="table-responsive">
    <table class="table table-striped mb-0">
      <thead><tr><th>#</th><th>CMR #</th><th>Carrier</th><th>Origin</th><th>Destination</th><th>Status</th><th></th></tr></thead>
      <tbody>
        @foreach($items as $e)
        <tr>
          <td>{{ $e->id }}</td>
          <td>{{ $e->cmr_number }}</td>
          <td>{{ $e->road_carrier }}</td>
          <td>{{ $e->origin_branch_id }}</td>
          <td>{{ $e->destination_branch_id }}</td>
          <td><span class="badge bg-secondary">{{ $e->status }}</span></td>
          <td><a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.ecmr.show',$e) }}">View</a></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div><div class="card-footer">{{ $items->links() }}</div></div>
</div>
@endsection

