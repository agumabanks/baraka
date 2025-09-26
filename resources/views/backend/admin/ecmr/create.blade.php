@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Create e-CMR</h3>
  <form method="post" action="{{ route('admin.ecmr.store') }}">@csrf
    <div class="row g-3">
      <div class="col-md-3"><label class="form-label">CMR Number</label><input name="cmr_number" class="form-control" required></div>
      <div class="col-md-3"><label class="form-label">Carrier</label><input name="road_carrier" class="form-control" required></div>
      <div class="col-md-3"><label class="form-label">Origin Branch</label><input name="origin_branch_id" type="number" class="form-control" required></div>
      <div class="col-md-3"><label class="form-label">Destination Branch</label><input name="destination_branch_id" type="number" class="form-control" required></div>
      <div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="draft">Draft</option><option value="issued">Issued</option><option value="delivered">Delivered</option></select></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary">Save</button> <a class="btn btn-secondary" href="{{ route('admin.ecmr.index') }}">Cancel</a></div>
  </form>
</div>
@endsection

