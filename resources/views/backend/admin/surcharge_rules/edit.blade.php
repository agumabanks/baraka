@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Edit Surcharge Rule: {{ $rule->code }}</h3>
  <form method="post" action="{{ route('admin.surcharges.update',$rule) }}">@csrf @method('PUT')
    <div class="row g-3">
      <div class="col-md-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ $rule->name }}" required></div>
      <div class="col-md-3"><label class="form-label">Trigger</label>
        <select name="trigger" class="form-select">
          @foreach(['fuel','security','remote_area','oversize','weekend','dg','re_attempt','custom'] as $t)
            <option value="{{ $t }}" @selected($rule->trigger==$t)>{{ strtoupper($t) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3"><label class="form-label">Rate Type</label>
        <select name="rate_type" class="form-select">
          <option value="flat" @selected($rule->rate_type=='flat')>Flat</option>
          <option value="percent" @selected($rule->rate_type=='percent')>Percent</option>
        </select>
      </div>
      <div class="col-md-3"><label class="form-label">Amount</label><input type="number" step="0.0001" name="amount" class="form-control" value="{{ $rule->amount }}" required></div>
      <div class="col-md-2"><label class="form-label">Currency</label><input name="currency" class="form-control" value="{{ $rule->currency }}" placeholder="USD"></div>
      <div class="col-md-2"><label class="form-label">Active From</label><input type="date" name="active_from" class="form-control" value="{{ optional($rule->active_from)->format('Y-m-d') }}" required></div>
      <div class="col-md-2"><label class="form-label">Active To</label><input type="date" name="active_to" class="form-control" value="{{ optional($rule->active_to)->format('Y-m-d') }}"></div>
      <div class="col-md-2 form-check mt-4"><input type="checkbox" class="form-check-input" name="active" value="1" id="active" @checked($rule->active)><label class="form-check-label" for="active">Active</label></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary">Update</button> <a class="btn btn-secondary" href="{{ route('admin.surcharges.index') }}">Cancel</a></div>
  </form>
</div>
@endsection

