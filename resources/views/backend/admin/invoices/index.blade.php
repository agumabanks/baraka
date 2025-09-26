@extends('backend.partials.master')
@section('maincontent')
<div class="container-fluid">
  <h3>Invoices</h3>
  <div class="card"><div class="table-responsive">
    <table class="table table-striped mb-0"><thead><tr><th>#</th><th>Customer</th><th>Total</th><th>Status</th><th></th></tr></thead>
      <tbody>
        @foreach($items as $inv)
          <tr><td>{{ $inv->id }}</td><td>{{ $inv->customer_id }}</td><td>{{ $inv->total }}</td><td>{{ $inv->status }}</td><td><a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.invoices.show',$inv) }}">View</a></td></tr>
        @endforeach
      </tbody>
    </table>
  </div><div class="card-footer">{{ $items->links() }}</div></div>
</div>
@endsection

