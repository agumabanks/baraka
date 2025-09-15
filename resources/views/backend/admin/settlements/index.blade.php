@extends('backend.partials.master')
@section('maincontent')
<div class="container-fluid">
  <h3>Settlements</h3>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead><tr><th>#</th><th>Branch</th><th>Amount</th><th>Status</th><th></th></tr></thead>
        <tbody>
          @foreach($items as $s)
            <tr><td>{{ $s->id }}</td><td>{{ $s->branch_id }}</td><td>{{ $s->amount }}</td><td>{{ $s->status }}</td><td><a href="{{ route('admin.settlements.show',$s) }}" class="btn btn-sm btn-outline-secondary">View</a></td></tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="card-footer">{{ $items->links() }}</div>
  </div>
</div>
@endsection

