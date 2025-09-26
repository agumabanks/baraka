@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Edit e-CMR</h3>
  <form method="post" action="{{ route('admin.ecmr.update',$ecmr) }}">@csrf @method('PUT')
    <div class="row g-3">
      <div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="draft" @selected($ecmr->status=='draft')>Draft</option><option value="issued" @selected($ecmr->status=='issued')>Issued</option><option value="delivered" @selected($ecmr->status=='delivered')>Delivered</option></select></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary">Update</button> <a class="btn btn-secondary" href="{{ route('admin.ecmr.show',$ecmr) }}">Cancel</a></div>
  </form>
</div>
@endsection

