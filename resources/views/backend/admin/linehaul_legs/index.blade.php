@extends('backend.partials.master')
@section('maincontent')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Linehaul Legs</h3>
    <a href="{{ route('admin.linehaul-legs.create') }}" class="btn btn-primary btn-sm">Add Leg</a>
  </div>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead>
          <tr>
            <th>ID</th>
            <th>Shipment</th>
            <th>Mode</th>
            <th>Carrier</th>
            <th>AWB/CMR</th>
            <th>Depart</th>
            <th>Arrive</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($legs as $leg)
            <tr>
              <td>{{ $leg->id }}</td>
              <td>#{{ $leg->shipment_id }}</td>
              <td>{{ $leg->mode }}</td>
              <td>{{ $leg->carrier }}</td>
              <td>{{ $leg->awb ?? $leg->cmr }}</td>
              <td>{{ optional($leg->depart_at)->format('Y-m-d H:i') }}</td>
              <td>{{ optional($leg->arrive_at)->format('Y-m-d H:i') }}</td>
              <td><span class="badge bg-secondary">{{ $leg->status }}</span></td>
              <td><a href="{{ route('admin.linehaul-legs.show', $leg) }}" class="btn btn-sm btn-outline-secondary">View</a></td>
            </tr>
          @empty
            <tr><td colspan="9" class="text-center text-muted py-4">No legs found</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-footer">{{ $legs->links() }}</div>
  </div>
</div>
@endsection

