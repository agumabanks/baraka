@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Quotation #{{ $quotation->id }}</h3>
    <a href="{{ route('admin.quotations.edit',$quotation) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
  </div>
  <div class="card"><div class="card-body">
    <div class="row g-3">
      <div class="col-md-3"><strong>Customer:</strong> {{ $quotation->customer_id }}</div>
      <div class="col-md-3"><strong>Destination:</strong> {{ $quotation->destination_country }}</div>
      <div class="col-md-3"><strong>Service:</strong> {{ $quotation->service_type }}</div>
      <div class="col-md-3"><strong>Weight:</strong> {{ $quotation->weight_kg }} kg</div>
      <div class="col-md-3"><strong>Total:</strong> {{ $quotation->total_amount }} {{ $quotation->currency }}</div>
      <div class="col-md-3"><strong>Status:</strong> <span class="badge bg-secondary">{{ $quotation->status }}</span></div>
    </div>
  </div></div>
</div>
@endsection

