@extends('backend.partials.master')
@section('maincontent')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>AWB Stock</h3>
    <a href="{{ route('admin.awb-stock.create') }}" class="btn btn-primary btn-sm">Add Range</a>
  </div>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead><tr><th>Carrier</th><th>IATA</th><th>Start</th><th>End</th><th>Used</th><th>Voided</th><th>Status</th><th></th></tr></thead>
        <tbody>
          @foreach($items as $s)
          <tr>
            <td>{{ $s->carrier_code }}</td>
            <td>{{ $s->iata_prefix }}</td>
            <td>{{ $s->range_start }}</td>
            <td>{{ $s->range_end }}</td>
            <td>{{ $s->used_count }}</td>
            <td>{{ $s->voided_count }}</td>
            <td><span class="badge bg-secondary">{{ $s->status }}</span></td>
            <td><a href="{{ route('admin.awb-stock.edit',$s) }}" class="btn btn-sm btn-outline-secondary">Edit</a></td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="card-footer">{{ $items->links() }}</div>
  </div>
</div>
@endsection

