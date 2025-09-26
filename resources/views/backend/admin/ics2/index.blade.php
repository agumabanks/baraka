@extends('backend.partials.master')
@section('maincontent')
<div class="container-fluid">
  <h3>ICS2 Monitor</h3>
  <div class="card"><div class="table-responsive">
    <table class="table table-striped mb-0"><thead><tr><th>#</th><th>Mode</th><th>ENS</th><th>Status</th><th>Lodged</th><th></th></tr></thead>
      <tbody>
        @foreach($items as $f)
          <tr><td>{{ $f->id }}</td><td>{{ $f->mode }}</td><td>{{ $f->ens_ref }}</td><td>{{ $f->status }}</td><td>{{ $f->lodged_at }}</td><td><a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.ics2.show',$f) }}">View</a></td></tr>
        @endforeach
      </tbody>
    </table>
  </div><div class="card-footer">{{ $items->links() }}</div></div>
</div>
@endsection

