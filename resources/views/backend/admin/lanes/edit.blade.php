@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Edit Lane</h3>
  <form method="post" action="{{ route('admin.lanes.update',$lane) }}">@csrf @method('PUT')
    <div class="form-group"><label>STD Transit Days</label><input type="number" name="std_transit_days" class="form-control" value="{{ $lane->std_transit_days }}" min="0"></div>
    <div class="form-group"><label>Dim Divisor</label><select name="dim_divisor" class="form-control"><option {{ $lane->dim_divisor==5000?'selected':'' }}>5000</option><option {{ $lane->dim_divisor==6000?'selected':'' }}>6000</option></select></div>
    <div class="form-group form-check"><input class="form-check-input" type="checkbox" name="eawb_required" value="1" id="eawb" {{ $lane->eawb_required?'checked':'' }}><label class="form-check-label" for="eawb">e-AWB Required</label></div>
    <button class="btn btn-primary">Save</button>
  </form>
</div>
@endsection

