@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Create Carrier Service</h3>
  <form method="post" action="{{ route('admin.carrier-services.store') }}">@csrf
    <div class="form-group"><label>Carrier</label>
      <select name="carrier_id" class="form-control">@foreach($carriers as $c)<option value="{{ $c->id }}">{{ $c->code }} - {{ $c->name }}</option>@endforeach</select>
    </div>
    <div class="form-group"><label>Code</label><input name="code" class="form-control" required></div>
    <div class="form-group"><label>Name</label><input name="name" class="form-control" required></div>
    <div class="form-group form-check"><input class="form-check-input" type="checkbox" name="requires_eawb" value="1" id="eawb"><label class="form-check-label" for="eawb">Requires e-AWB</label></div>
    <button class="btn btn-primary">Save</button>
  </form>
</div>
@endsection

