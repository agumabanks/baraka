@extends('backend.partials.master')
@section('maincontent')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Webhooks</h3>
    <form method="post" action="{{ route('admin.webhooks.store') }}">@csrf <button class="btn btn-primary btn-sm">Add</button></form>
  </div>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead><tr><th>#</th><th>URL</th><th>Secret</th><th></th></tr></thead>
        <tbody>
          @forelse($hooks as $h)
            <tr><td>{{ $h->id }}</td><td>{{ $h->url }}</td><td>{{ $h->secret }}</td><td><form method="post" action="{{ route('admin.webhooks.destroy',$h) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr>
          @empty
            <tr><td colspan="4" class="text-center text-muted py-4">No webhooks</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-footer">{{ $hooks->links() }}</div>
  </div>
</div>
@endsection

