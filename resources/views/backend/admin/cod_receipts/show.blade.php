@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>COD Receipt #{{ $cod->id }}</h3>
  <div class="card"><div class="card-body">
    <div class="row g-3">
      <div class="col-md-3"><strong>Shipment:</strong> {{ $cod->shipment_id }}</div>
      <div class="col-md-3"><strong>Amount:</strong> {{ $cod->amount }}</div>
      <div class="col-md-3"><strong>Currency:</strong> {{ $cod->currency }}</div>
      <div class="col-md-3"><strong>Status:</strong> {{ $cod->status }}</div>
    </div>
  </div></div>
</div>
@endsection

