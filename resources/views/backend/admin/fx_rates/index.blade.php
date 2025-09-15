@extends('backend.partials.master')
@section('maincontent')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>FX Rates</h3>
    <a href="{{ route('admin.fx.create') }}" class="btn btn-primary btn-sm">Add</a>
  </div>
  <div class="card"><div class="table-responsive">
    <table class="table table-striped mb-0">
      <thead><tr><th>Pair</th><th>Rate</th><th>Provider</th><th>Effective</th><th></th></tr></thead>
      <tbody>
        @foreach($items as $fx)
        <tr>
          <td>{{ $fx->base }}/{{ $fx->counter }}</td>
          <td>{{ $fx->rate }}</td>
          <td>{{ $fx->provider }}</td>
          <td>{{ $fx->effective_at }}</td>
          <td><a href="{{ route('admin.fx.edit',$fx) }}" class="btn btn-sm btn-outline-secondary">Edit</a></td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div><div class="card-footer">{{ $items->links() }}</div></div>
</div>
@endsection

