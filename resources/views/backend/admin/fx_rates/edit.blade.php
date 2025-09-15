@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Edit FX Rate</h3>
  <form method="post" action="{{ route('admin.fx.update',$fx) }}">@csrf @method('PUT')
    <div class="row g-3">
      <div class="col-md-2"><label class="form-label">Base</label><input class="form-control" value="{{ $fx->base }}" disabled></div>
      <div class="col-md-2"><label class="form-label">Counter</label><input class="form-control" value="{{ $fx->counter }}" disabled></div>
      <div class="col-md-3"><label class="form-label">Rate</label><input type="number" step="0.00000001" name="rate" class="form-control" value="{{ $fx->rate }}" required></div>
      <div class="col-md-3"><label class="form-label">Effective</label><input type="date" name="effective_at" class="form-control" value="{{ optional($fx->effective_at)->format('Y-m-d') }}" required></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary">Update</button> <a class="btn btn-secondary" href="{{ route('admin.fx.index') }}">Cancel</a></div>
  </form>
</div>
@endsection

