@extends('backend.partials.master')
@section('maincontent')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Shipments</h3>
    <a href="{{ route('admin.shipments.create') }}" class="btn btn-primary btn-sm">Create</a>
  </div>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-striped mb-0"><thead><tr><th>#</th><th>Tracking</th><th>Status</th><th>Origin</th><th>Dest</th><th>Created</th><th></th></tr></thead>
        <tbody>
          @foreach($shipments as $s)
          <tr>
            <td>{{ $s->id }}</td><td>{{ $s->tracking }}</td><td>{{ $s->current_status }}</td><td>{{ $s->origin_branch_id }}</td><td>{{ $s->dest_branch_id }}</td><td>{{ $s->created_at }}</td>
            <td><a href="{{ route('admin.shipments.show',$s) }}" class="btn btn-sm btn-outline-secondary">View</a></td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="card-footer">{{ $shipments->links() }}</div>
  </div>
</div>
@endsection

