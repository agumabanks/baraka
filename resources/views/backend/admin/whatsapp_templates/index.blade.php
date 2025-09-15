@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>WhatsApp Templates</h3>
  <a href="{{ route('admin.whatsapp-templates.create') }}" class="btn btn-primary btn-sm">Create</a>
  <table class="table mt-3"><thead><tr><th>Name</th><th>Lang</th><th>Approved</th></tr></thead>
    <tbody>
      @foreach($templates as $t)
      <tr><td>{{ $t->name }}</td><td>{{ $t->language }}</td><td>{{ $t->approved?'Yes':'No' }}</td></tr>
      @endforeach
    </tbody>
  </table>
  {{ $templates->links() }}
</div>
@endsection

