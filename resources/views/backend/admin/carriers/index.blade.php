@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Carriers</h3>
  <a href="{{ route('admin.carriers.create') }}" class="btn btn-primary btn-sm">Create</a>
  <table class="table mt-3">
    <thead><tr><th>Code</th><th>Name</th><th>Mode</th><th></th></tr></thead>
    <tbody>
      @foreach($carriers as $c)
      <tr>
        <td>{{ $c->code }}</td><td>{{ $c->name }}</td><td>{{ strtoupper($c->mode) }}</td>
        <td><a href="{{ route('admin.carriers.edit',$c) }}" class="btn btn-sm btn-secondary">Edit</a></td>
      </tr>
      @endforeach
    </tbody>
  </table>
  {{ $carriers->links() }}
</div>
@endsection

