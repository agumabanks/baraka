@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Add FX Rate</h3>
  <form method="post" action="{{ route('admin.fx.store') }}">@csrf
    <div class="row g-3">
      <div class="col-md-2"><label class="form-label">Base</label><input name="base" class="form-control" placeholder="USD" required></div>
      <div class="col-md-2"><label class="form-label">Counter</label><input name="counter" class="form-control" placeholder="KES" required></div>
      <div class="col-md-3"><label class="form-label">Rate</label><input type="number" step="0.00000001" name="rate" class="form-control" required></div>
      <div class="col-md-3"><label class="form-label">Provider</label><input name="provider" class="form-control" required></div>
      <div class="col-md-2"><label class="form-label">Effective</label><input type="date" name="effective_at" class="form-control" required></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary">Save</button> <a class="btn btn-secondary" href="{{ route('admin.fx.index') }}">Cancel</a></div>
  </form>
</div>
@endsection

