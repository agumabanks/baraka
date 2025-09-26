@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Create Zone</h3>
  <form method="post" action="{{ route('admin.zones.store') }}">@csrf
    <div class="form-group"><label>Code</label><input name="code" class="form-control" required></div>
    <div class="form-group"><label>Name</label><input name="name" class="form-control" required></div>
    <div class="form-group"><label>Countries (comma codes)</label><input name="countries[]" class="form-control" placeholder="DE,FR,NL"></div>
    <button class="btn btn-primary">Save</button>
  </form>
</div>
@endsection

