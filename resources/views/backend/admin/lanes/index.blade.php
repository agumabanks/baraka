@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Lanes</h3>
  <a href="{{ route('admin.lanes.create') }}" class="btn btn-primary btn-sm">Create</a>
  <table class="table mt-3">
    <thead><tr><th>Origin</th><th>Destination</th><th>Mode</th><th>STD</th><th>Dim</th><th>e-AWB</th><th></th></tr></thead>
    <tbody>
      @foreach($lanes as $l)
      <tr>
        <td>{{ optional($l->origin)->code }}</td>
        <td>{{ optional($l->destination)->code }}</td>
        <td>{{ strtoupper($l->mode) }}</td>
        <td>{{ $l->std_transit_days }}</td>
        <td>{{ $l->dim_divisor }}</td>
        <td>{{ $l->eawb_required ? 'Yes':'No' }}</td>
        <td><a href="{{ route('admin.lanes.edit',$l) }}" class="btn btn-sm btn-secondary">Edit</a></td>
      </tr>
      @endforeach
    </tbody>
  </table>
  {{ $lanes->links() }}
</div>
@endsection

