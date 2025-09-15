@extends('backend.partials.master')
@section('maincontent')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Scan Events</h3>
    <a href="{{ route('admin.scans.create') }}" class="btn btn-primary btn-sm">New Scan</a>
  </div>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead><tr><th>#</th><th>Shipment</th><th>Event</th><th>Hub</th><th>At</th><th></th></tr></thead>
        <tbody>
          @forelse($events as $e)
            <tr>
              <td>{{ $e->id }}</td>
              <td>{{ $e->shipment_id }}</td>
              <td>{{ $e->event }}</td>
              <td>{{ $e->hub_id }}</td>
              <td>{{ $e->created_at }}</td>
              <td><a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.scans.show',$e) }}">View</a></td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No scans</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-footer">{{ $events->links() }}</div>
  </div>
</div>
@endsection

