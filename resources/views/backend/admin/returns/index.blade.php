@extends('backend.partials.master')
@section('maincontent')
<div class="container-fluid">
  <h3>Returns</h3>
  <div class="card"><div class="table-responsive">
    <table class="table table-striped mb-0"><thead><tr><th>#</th><th>Shipment</th><th>Reason</th><th>Status</th></tr></thead>
      <tbody>
        @foreach($items as $r)
          <tr><td>{{ $r->id }}</td><td>{{ $r->shipment_id }}</td><td>{{ $r->reason_code }}</td><td><span class="badge bg-secondary">{{ $r->status }}</span></td></tr>
        @endforeach
      </tbody>
    </table>
  </div><div class="card-footer">{{ $items->links() }}</div></div>
</div>
@endsection

