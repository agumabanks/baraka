@extends('backend.partials.master')
@section('maincontent')
<div class="container-fluid">
  <h3>Sortation</h3>
  <div class="card"><div class="table-responsive">
    <table class="table table-striped mb-0">
      <thead><tr><th>Code</th><th>Lane</th><th>Status</th></tr></thead>
      <tbody>
        @foreach($items as $b)
        <tr>
          <td>{{ $b->code }}</td>
          <td>{{ $b->lane }}</td>
          <td><span class="badge bg-secondary">{{ $b->status }}</span></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div><div class="card-footer">{{ $items->links() }}</div></div>
</div>
@endsection

