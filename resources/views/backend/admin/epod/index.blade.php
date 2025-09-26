@extends('backend.partials.master')
@section('maincontent')
<div class="container-fluid">
  <h3>ePOD Gallery</h3>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead><tr><th>#</th><th>Shipment</th><th>Type</th><th>Path</th></tr></thead>
        <tbody>
          @foreach($items as $e)
            <tr><td>{{ $e->id }}</td><td>{{ $e->shipment_id }}</td><td>{{ $e->type }}</td><td>{{ $e->file_path }}</td></tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="card-footer">{{ $items->links() }}</div>
  </div>
</div>
@endsection

