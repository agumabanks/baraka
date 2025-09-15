@extends('backend.partials.master')
@section('maincontent')
<div class="container-fluid">
  <h3>Warehouse Locations</h3>
  <div class="card"><div class="table-responsive">
    <table class="table table-striped mb-0">
      <thead><tr><th>Code</th><th>Type</th><th>Capacity</th><th>Status</th></tr></thead>
      <tbody>
        @foreach($items as $loc)
        <tr>
          <td>{{ $loc->code }}</td>
          <td>{{ $loc->type }}</td>
          <td>{{ $loc->capacity }}</td>
          <td><span class="badge bg-secondary">{{ $loc->status }}</span></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div><div class="card-footer">{{ $items->links() }}</div></div>
</div>
@endsection

