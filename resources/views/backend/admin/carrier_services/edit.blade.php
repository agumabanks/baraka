@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Edit Carrier Service</h3>
  <form method="post" action="{{ route('admin.carrier-services.update',$service) }}">@csrf @method('PUT')
    <div class="form-group"><label>Name</label><input name="name" class="form-control" value="{{ $service->name }}" required></div>
    <div class="form-group form-check"><input class="form-check-input" type="checkbox" name="requires_eawb" value="1" id="eawb" {{ $service->requires_eawb?'checked':'' }}><label class="form-check-label" for="eawb">Requires e-AWB</label></div>
    <button class="btn btn-primary">Save</button>
  </form>
</div>
@endsection

