@extends('backend.partials.master')
@section('maincontent')
<div class="container-fluid">
  <h3>Claims</h3>
  <div class="card"><div class="table-responsive">
    <table class="table table-striped mb-0"><thead><tr><th>#</th><th>Shipment</th><th>Type</th><th>Amount</th><th>Status</th></tr></thead>
      <tbody>
        @foreach($items as $c)
          <tr><td>{{ $c->id }}</td><td>{{ $c->shipment_id }}</td><td>{{ $c->type }}</td><td>{{ $c->amount_claimed }}</td><td><span class="badge bg-secondary">{{ $c->status }}</span></td></tr>
        @endforeach
      </tbody>
    </table>
  </div><div class="card-footer">{{ $items->links() }}</div></div>
</div>
@endsection

