@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Edit Zone</h3>
  <form method="post" action="{{ route('admin.zones.update',$zone) }}">@csrf @method('PUT')
    <div class="form-group"><label>Name</label><input name="name" class="form-control" value="{{ $zone->name }}" required></div>
    <div class="form-group"><label>Countries (comma codes)</label><input name="countries[]" class="form-control" value="{{ is_array($zone->countries)? implode(',', $zone->countries): '' }}"></div>
    <button class="btn btn-primary">Save</button>
  </form>
</div>
@endsection

