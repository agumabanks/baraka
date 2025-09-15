@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Edit Carrier</h3>
  <form method="post" action="{{ route('admin.carriers.update',$carrier) }}">@csrf @method('PUT')
    <div class="form-group"><label>Name</label><input name="name" class="form-control" value="{{ $carrier->name }}" required></div>
    <div class="form-group"><label>Mode</label><select name="mode" class="form-control"><option value="air" {{ $carrier->mode=='air'?'selected':'' }}>Air</option><option value="road" {{ $carrier->mode=='road'?'selected':'' }}>Road</option></select></div>
    <button class="btn btn-primary">Save</button>
  </form>
</div>
@endsection

