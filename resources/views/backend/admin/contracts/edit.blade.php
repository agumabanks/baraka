@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Edit Contract #{{ $contract->id }}</h3>
  <form method="post" action="{{ route('admin.contracts.update',$contract) }}">@csrf @method('PUT')
    <div class="row g-3">
      <div class="col-md-4"><label class="form-label">Rate Card ID</label><input type="number" name="rate_card_id" class="form-control" value="{{ $contract->rate_card_id }}"></div>
      <div class="col-md-3"><label class="form-label">End Date</label><input type="date" name="end_date" class="form-control" value="{{ $contract->end_date }}"></div>
      <div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active" @selected($contract->status=='active')>Active</option><option value="suspended" @selected($contract->status=='suspended')>Suspended</option><option value="ended" @selected($contract->status=='ended')>Ended</option></select></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary">Update</button> <a class="btn btn-secondary" href="{{ route('admin.contracts.show',$contract) }}">Cancel</a></div>
  </form>
</div>
@endsection

