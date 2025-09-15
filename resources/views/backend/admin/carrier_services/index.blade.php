@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Carrier Services</h3>
  <a href="{{ route('admin.carrier-services.create') }}" class="btn btn-primary btn-sm">Create</a>
  <table class="table mt-3">
    <thead><tr><th>Carrier</th><th>Code</th><th>Name</th><th>e-AWB</th><th></th></tr></thead>
    <tbody>
      @foreach($services as $s)
      <tr>
        <td>{{ optional($s->carrier)->code }}</td>
        <td>{{ $s->code }}</td>
        <td>{{ $s->name }}</td>
        <td>{{ $s->requires_eawb ? 'Yes':'No' }}</td>
        <td><a href="{{ route('admin.carrier-services.edit',$s) }}" class="btn btn-sm btn-secondary">Edit</a></td>
      </tr>
      @endforeach
    </tbody>
  </table>
  {{ $services->links() }}
</div>
@endsection

