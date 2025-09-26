@extends('backend.partials.master')
@section('maincontent')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Manifests</h3>
    <a href="{{ route('admin.manifests.create') }}" class="btn btn-primary btn-sm">Create</a>
  </div>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead><tr><th>#</th><th>Number</th><th>Mode</th><th>Depart</th><th>Arrive</th><th>Status</th><th></th></tr></thead>
        <tbody>
          @foreach($items as $m)
          <tr>
            <td>{{ $m->id }}</td>
            <td>{{ $m->number }}</td>
            <td>{{ strtoupper($m->mode) }}</td>
            <td>{{ optional($m->departure_at)->format('Y-m-d H:i') }}</td>
            <td>{{ optional($m->arrival_at)->format('Y-m-d H:i') }}</td>
            <td><span class="badge bg-secondary">{{ $m->status }}</span></td>
            <td><a href="{{ route('admin.manifests.show',$m) }}" class="btn btn-sm btn-outline-secondary">View</a></td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="card-footer">{{ $items->links() }}</div>
  </div>
</div>
@endsection

