@extends('backend.partials.master')
@section('maincontent')
<div class="container-fluid">
  <h3>Customs Docs</h3>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead><tr><th>#</th><th>Shipment</th><th>Type</th><th></th></tr></thead>
        <tbody>
          @foreach($items as $d)
            <tr><td>{{ $d->id }}</td><td>{{ $d->shipment_id }}</td><td>{{ $d->doc_type }}</td><td><a href="{{ route('admin.customs-docs.show',$d) }}" class="btn btn-sm btn-outline-secondary">View</a></td></tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="card-footer">{{ $items->links() }}</div>
  </div>
</div>
@endsection

