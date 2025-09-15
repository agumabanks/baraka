@extends('backend.partials.master')
@section('maincontent')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Address Book</h3>
    <a href="{{ route('admin.address-book.create') }}" class="btn btn-primary btn-sm">Add</a>
  </div>
  <div class="card"><div class="table-responsive">
    <table class="table table-striped mb-0">
      <thead><tr><th>#</th><th>Customer</th><th>Type</th><th>Name</th><th>Phone</th><th>Country</th><th>City</th><th></th></tr></thead>
      <tbody>
        @foreach($items as $a)
        <tr>
          <td>{{ $a->id }}</td><td>{{ $a->customer_id }}</td><td>{{ $a->type }}</td><td>{{ $a->name }}</td><td>{{ $a->phone_e164 }}</td><td>{{ $a->country }}</td><td>{{ $a->city }}</td>
          <td><a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.address-book.show',$a) }}">View</a></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div><div class="card-footer">{{ $items->links() }}</div></div>
</div>
@endsection

