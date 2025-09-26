@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Zones</h3>
  <a href="{{ route('admin.zones.create') }}" class="btn btn-primary btn-sm">Create</a>
  <table class="table mt-3">
    <thead><tr><th>Code</th><th>Name</th><th>Countries</th><th></th></tr></thead>
    <tbody>
    @foreach($zones as $z)
      <tr>
        <td>{{ $z->code }}</td>
        <td>{{ $z->name }}</td>
        <td>{{ is_array($z->countries)? implode(',', $z->countries): '' }}</td>
        <td><a href="{{ route('admin.zones.edit',$z) }}" class="btn btn-sm btn-secondary">Edit</a></td>
      </tr>
    @endforeach
    </tbody>
  </table>
  {{ $zones->links() }}
  </div>
@endsection

