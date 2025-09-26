@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Bag #{{ $bag->id }}</h3>
  <div class="card"><div class="card-body">
    <div class="row g-3">
      <div class="col-md-4"><strong>SSCC:</strong> {{ $bag->sscc }}</div>
      <div class="col-md-4"><strong>Status:</strong> {{ $bag->status }}</div>
    </div>
  </div></div>
</div>
@endsection

