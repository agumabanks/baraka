@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Create Contract</h3>
  <form method="post" action="{{ route('admin.contracts.store') }}">@csrf
    <div class="row g-3">
      <div class="col-md-3"><label class="form-label">Customer ID</label><input name="customer_id" type="number" class="form-control" required></div>
      <div class="col-md-4"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
      <div class="col-md-2"><label class="form-label">Start</label><input type="date" name="start_date" class="form-control" required></div>
      <div class="col-md-2"><label class="form-label">End</label><input type="date" name="end_date" class="form-control" required></div>
      <div class="col-md-2"><label class="form-label">Rate Card ID</label><input type="number" name="rate_card_id" class="form-control"></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary">Save</button> <a class="btn btn-secondary" href="{{ route('admin.contracts.index') }}">Cancel</a></div>
  </form>
</div>
@endsection

