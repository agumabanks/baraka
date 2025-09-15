@extends('backend.partials.master')
@section('maincontent')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Rate Cards</h3>
    <a href="{{ route('admin.rate-cards.create') }}" class="btn btn-primary btn-sm">Create</a>
  </div>
  <div class="card"><div class="table-responsive">
    <table class="table table-striped mb-0">
      <thead><tr><th>#</th><th>Name</th><th>Currency</th><th>Active</th></tr></thead>
      <tbody>
        @foreach($items as $r)
          <tr><td>{{ $r->id }}</td><td>{{ $r->name }}</td><td>{{ $r->currency }}</td><td>{{ $r->active ? 'Yes' : 'No' }}</td></tr>
        @endforeach
      </tbody>
    </table>
  </div><div class="card-footer">{{ $items->links() }}</div></div>
</div>
@endsection

