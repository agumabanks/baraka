@extends('backend.partials.master')
@section('maincontent')
<div class="container"><h3>Create EDI Provider</h3>
  <form method="post" action="{{ route('admin.edi.store') }}">@csrf
    <div class="form-group"><label>Name</label><input name="name" class="form-control" required></div>
    <div class="form-group"><label>Type</label><select name="type" class="form-control"><option value="mock">Mock</option><option value="airline">Airline</option><option value="broker">Broker</option></select></div>
    <button class="btn btn-primary">Save</button>
  </form>
</div>
@endsection

