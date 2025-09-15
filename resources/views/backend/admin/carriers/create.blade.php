@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Create Carrier</h3>
  <form method="post" action="{{ route('admin.carriers.store') }}">@csrf
    <div class="form-group"><label>Name</label><input name="name" class="form-control" required></div>
    <div class="form-group"><label>Code</label><input name="code" class="form-control" required></div>
    <div class="form-group"><label>Mode</label><select name="mode" class="form-control"><option value="air">Air</option><option value="road">Road</option></select></div>
    <button class="btn btn-primary">Save</button>
  </form>
</div>
@endsection

