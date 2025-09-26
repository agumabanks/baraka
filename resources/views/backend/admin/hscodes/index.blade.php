@extends('backend.partials.master')
@section('maincontent')
<div class="container-fluid">
  <h3>HS Codes</h3>
  <div class="card"><div class="table-responsive">
    <table class="table table-striped mb-0">
      <thead><tr><th>#</th><th>Code</th><th>Description</th></tr></thead>
      <tbody>
        @foreach($items as $h)
          <tr><td>{{ $h->id }}</td><td>{{ $h->code }}</td><td>{{ $h->description }}</td></tr>
        @endforeach
      </tbody>
    </table>
  </div><div class="card-footer">{{ $items->links() }}</div></div>
</div>
@endsection

