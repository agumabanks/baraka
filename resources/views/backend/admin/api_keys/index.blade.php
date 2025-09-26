@extends('backend.partials.master')
@section('maincontent')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>API Keys</h3>
    <form method="post" action="{{ route('admin.api-keys.store') }}">@csrf <button class="btn btn-primary btn-sm">Generate</button></form>
  </div>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead><tr><th>#</th><th>Name</th><th>Token</th><th></th></tr></thead>
        <tbody>
          @forelse($keys as $k)
            <tr><td>{{ $k->id }}</td><td>{{ $k->name }}</td><td><code>{{ $k->token }}</code></td><td><form method="post" action="{{ route('admin.api-keys.destroy',$k) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr>
          @empty
            <tr><td colspan="4" class="text-center text-muted py-4">No keys</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-footer">{{ $keys->links() }}</div>
  </div>
</div>
@endsection
