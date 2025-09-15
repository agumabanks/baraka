@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Add AWB Stock Range</h3>
  <form method="post" action="{{ route('admin.awb-stock.store') }}">@csrf
    <div class="row g-3">
      <div class="col-md-2"><label class="form-label">Carrier Code</label><input name="carrier_code" class="form-control" placeholder="DHL" required></div>
      <div class="col-md-2"><label class="form-label">IATA Prefix</label><input name="iata_prefix" class="form-control" placeholder="123" required></div>
      <div class="col-md-3"><label class="form-label">Range Start</label><input name="range_start" type="number" class="form-control" required></div>
      <div class="col-md-3"><label class="form-label">Range End</label><input name="range_end" type="number" class="form-control" required></div>
    </div>
    <div class="mt-3">
      <button class="btn btn-primary">Save</button>
      <a class="btn btn-secondary" href="{{ route('admin.awb-stock.index') }}">Cancel</a>
    </div>
  </form>
</div>
@endsection

