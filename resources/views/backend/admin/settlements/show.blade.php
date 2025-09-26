@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Settlement #{{ $settlement->id }}</h3>
  <div class="card"><div class="card-body">
    <div class="row g-3">
      <div class="col-md-3"><strong>Branch:</strong> {{ $settlement->branch_id }}</div>
      <div class="col-md-3"><strong>Amount:</strong> {{ $settlement->amount }}</div>
      <div class="col-md-3"><strong>Status:</strong> {{ $settlement->status }}</div>
    </div>
  </div></div>
</div>
@endsection

