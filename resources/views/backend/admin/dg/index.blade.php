@extends('backend.partials.master')
@section('maincontent')
<div class="container-fluid">
  <h3>Dangerous Goods</h3>
  <div class="card"><div class="table-responsive">
    <table class="table table-striped mb-0">
      <thead><tr><th>#</th><th>UN</th><th>Class</th><th>Packing</th><th>Status</th><th></th></tr></thead>
      <tbody>
        @foreach($items as $d)
          <tr><td>{{ $d->id }}</td><td>{{ $d->un_number }}</td><td>{{ $d->dg_class }}</td><td>{{ $d->packing_group }}</td><td>{{ $d->status }}</td><td><a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.dg.show',$d) }}">View</a></td></tr>
        @endforeach
      </tbody>
    </table>
  </div><div class="card-footer">{{ $items->links() }}</div></div>
</div>
@endsection

