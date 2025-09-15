@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Shipment #{{ $shipment->id }}</h3>
    <a href="{{ route('admin.shipments.edit',$shipment) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
  </div>
  <div class="card"><div class="card-body">
    <div class="row g-3">
      <div class="col-md-3"><strong>Tracking:</strong> {{ $shipment->tracking }}</div>
      <div class="col-md-3"><strong>Status:</strong> {{ $shipment->current_status }}</div>
      <div class="col-md-3"><strong>Origin:</strong> {{ $shipment->origin_branch_id }}</div>
      <div class="col-md-3"><strong>Destination:</strong> {{ $shipment->dest_branch_id }}</div>
    </div>
  </div></div>
</div>
@endsection

