@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>New Scan Event</h3>
  <form method="post" action="{{ route('admin.scans.store') }}">@csrf
    <div class="row g-3">
      <div class="col-md-3"><label class="form-label">SSCC</label><input name="sscc" class="form-control" placeholder="(00) SSCC" required></div>
      <div class="col-md-3"><label class="form-label">Type</label><input name="type" class="form-control" placeholder="ARRIVED_AT_HUB" required></div>
      <div class="col-md-3"><label class="form-label">Branch ID</label><input name="branch_id" type="number" class="form-control"></div>
      <div class="col-md-3"><label class="form-label">Leg ID</label><input name="leg_id" type="number" class="form-control"></div>
      <div class="col-md-3"><label class="form-label">Occurred At</label><input name="occurred_at" type="datetime-local" class="form-control"></div>
      <div class="col-md-12"><label class="form-label">Note</label><textarea name="note" class="form-control" rows="3"></textarea></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary">Save</button> <a class="btn btn-secondary" href="{{ route('admin.scans.index') }}">Cancel</a></div>
  </form>
</div>
@endsection
