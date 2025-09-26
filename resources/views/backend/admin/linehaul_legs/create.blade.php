@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Create Linehaul Leg</h3>
  <form method="post" action="{{ route('admin.linehaul-legs.store') }}">@csrf
    <div class="row g-3">
      <div class="col-md-3">
        <label class="form-label">Shipment ID</label>
        <input type="number" name="shipment_id" class="form-control" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Mode</label>
        <select name="mode" class="form-select">
          <option value="AIR">AIR</option>
          <option value="ROAD">ROAD</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Carrier</label>
        <input name="carrier" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label">Status</label>
        <input name="status" class="form-control" value="PLANNED">
      </div>
      <div class="col-md-3">
        <label class="form-label">AWB</label>
        <input name="awb" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label">CMR</label>
        <input name="cmr" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label">Flight #</label>
        <input name="flight_number" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label">Vehicle #</label>
        <input name="vehicle_number" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label">Depart At</label>
        <input type="datetime-local" name="depart_at" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label">Arrive At</label>
        <input type="datetime-local" name="arrive_at" class="form-control">
      </div>
    </div>
    <div class="mt-3">
      <button class="btn btn-primary">Save</button>
      <a class="btn btn-secondary" href="{{ route('admin.linehaul-legs.index') }}">Cancel</a>
    </div>
  </form>
</div>
@endsection

