@extends('backend.partials.master')
@section('maincontent')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Commodities</h3>
    <a href="{{ route('admin.commodities.create') }}" class="btn btn-primary btn-sm">Create</a>
  </div>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead><tr><th>#</th><th>Name</th><th>HS Code</th></tr></thead>
        <tbody>
          @foreach($items as $c)
            <tr><td>{{ $c->id }}</td><td>{{ $c->name }}</td><td>{{ $c->hs_code }}</td></tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="card-footer">{{ $items->links() }}</div>
  </div>
</div>
@endsection

