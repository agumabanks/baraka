@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Create Manifest</h3>
  <form method="post" action="{{ route('admin.manifests.store') }}">@csrf
    <div class="row g-3">
      <div class="col-md-3"><label class="form-label">Number</label><input name="number" class="form-control" required></div>
      <div class="col-md-2"><label class="form-label">Mode</label><select name="mode" class="form-select"><option value="air">Air</option><option value="road">Road</option></select></div>
      <div class="col-md-3"><label class="form-label">Departure At</label><input type="datetime-local" name="departure_at" class="form-control" required></div>
      <div class="col-md-3"><label class="form-label">Arrival At</label><input type="datetime-local" name="arrival_at" class="form-control"></div>
      <div class="col-md-3"><label class="form-label">Origin Branch</label><input type="number" name="origin_branch_id" class="form-control" required></div>
      <div class="col-md-3"><label class="form-label">Destination Branch</label><input type="number" name="destination_branch_id" class="form-control"></div>
      <div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select"><option>open</option><option>departed</option><option>arrived</option><option>closed</option></select></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary">Save</button> <a class="btn btn-secondary" href="{{ route('admin.manifests.index') }}">Cancel</a></div>
  </form>
</div>
@endsection

