@extends('backend.partials.master')
@section('maincontent')
<div class="container-fluid">
  <h3>COD Receipts</h3>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead><tr><th>#</th><th>Shipment</th><th>Amount</th><th></th></tr></thead>
        <tbody>
          @foreach($items as $c)
            <tr><td>{{ $c->id }}</td><td>{{ $c->shipment_id }}</td><td>{{ $c->amount }}</td><td><a href="{{ route('admin.cod-receipts.show',$c) }}" class="btn btn-sm btn-outline-secondary">View</a></td></tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="card-footer">{{ $items->links() }}</div>
  </div>
</div>
@endsection

