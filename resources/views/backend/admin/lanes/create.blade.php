@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Create Lane</h3>
  <form method="post" action="{{ route('admin.lanes.store') }}">@csrf
    <div class="form-group"><label>Origin Zone</label>
      <select name="origin_zone_id" class="form-control">@foreach($zones as $z)<option value="{{ $z->id }}">{{ $z->code }} - {{ $z->name }}</option>@endforeach</select>
    </div>
    <div class="form-group"><label>Destination Zone</label>
      <select name="dest_zone_id" class="form-control">@foreach($zones as $z)<option value="{{ $z->id }}">{{ $z->code }} - {{ $z->name }}</option>@endforeach</select>
    </div>
    <div class="form-group"><label>Mode</label>
      <select name="mode" class="form-control"><option value="air">Air</option><option value="road">Road</option></select>
    </div>
    <div class="form-group"><label>STD Transit Days</label><input type="number" name="std_transit_days" class="form-control" value="3" min="0"></div>
    <div class="form-group"><label>Dim Divisor</label><select name="dim_divisor" class="form-control"><option>5000</option><option>6000</option></select></div>
    <div class="form-group form-check"><input class="form-check-input" type="checkbox" name="eawb_required" value="1" id="eawb"><label class="form-check-label" for="eawb">e-AWB Required</label></div>
    <button class="btn btn-primary">Save</button>
  </form>
</div>
@endsection

