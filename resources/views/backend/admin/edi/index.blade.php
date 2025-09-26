@extends('backend.partials.master')
@section('maincontent')
<div class="container"><h3>EDI Providers</h3>
  <a href="{{ route('admin.edi.create') }}" class="btn btn-primary btn-sm">Create</a>
  <table class="table mt-3"><thead><tr><th>Name</th><th>Type</th></tr></thead>
    <tbody>
      @foreach($providers as $p)<tr><td>{{ $p->name }}</td><td>{{ $p->type }}</td></tr>@endforeach
    </tbody>
  </table>
</div>
@endsection

