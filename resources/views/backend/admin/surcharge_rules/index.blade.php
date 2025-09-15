@extends('backend.partials.master')
@section('maincontent')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Surcharge Rules</h3>
    <a href="{{ route('admin.surcharges.create') }}" class="btn btn-primary btn-sm">Add Rule</a>
  </div>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead>
          <tr>
            <th>Code</th>
            <th>Name</th>
            <th>Trigger</th>
            <th>Type</th>
            <th>Amount</th>
            <th>Active</th>
            <th>From</th>
            <th>To</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($items as $r)
            <tr>
              <td>{{ $r->code }}</td>
              <td>{{ $r->name }}</td>
              <td>{{ $r->trigger }}</td>
              <td>{{ $r->rate_type }}</td>
              <td>{{ $r->amount }} {{ $r->currency }}</td>
              <td>{!! $r->active ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' !!}</td>
              <td>{{ optional($r->active_from)->format('Y-m-d') }}</td>
              <td>{{ optional($r->active_to)->format('Y-m-d') }}</td>
              <td>
                <a href="{{ route('admin.surcharges.edit',$r) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                <form method="post" action="{{ route('admin.surcharges.destroy',$r) }}" class="d-inline">@csrf @method('DELETE')
                  <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete rule?')">Delete</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="9" class="text-center text-muted py-4">No rules found</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-footer">{{ $items->links() }}</div>
  </div>
</div>
@endsection

