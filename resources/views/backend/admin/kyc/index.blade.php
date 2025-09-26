@extends('backend.partials.master')
@section('maincontent')
<div class="container-fluid">
  <h3>KYC & Screening</h3>
  <div class="card"><div class="table-responsive">
    <table class="table table-striped mb-0"><thead><tr><th>#</th><th>Customer</th><th>Status</th><th>Reviewed</th><th></th></tr></thead>
      <tbody>
        @foreach($items as $k)
          <tr><td>{{ $k->id }}</td><td>{{ $k->customer_id }}</td><td>{{ $k->status }}</td><td>{{ $k->reviewed_at }}</td><td><a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.kyc.show',$k) }}">View</a></td></tr>
        @endforeach
      </tbody>
    </table>
  </div><div class="card-footer">{{ $items->links() }}</div></div>
</div>
@endsection

