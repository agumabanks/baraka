@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Invoice #{{ $invoice->id }}</h3>
  <div class="card"><div class="card-body">
    <div class="row g-3">
      <div class="col-md-3"><strong>Customer:</strong> {{ $invoice->customer_id }}</div>
      <div class="col-md-3"><strong>Total:</strong> {{ $invoice->total }}</div>
      <div class="col-md-3"><strong>Status:</strong> {{ $invoice->status }}</div>
    </div>
  </div></div>
</div>
@endsection

