@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>e-CMR #{{ $ecmr->id }} ({{ $ecmr->cmr_number }})</h3>
    <a href="{{ route('admin.ecmr.edit',$ecmr) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
  </div>
  <div class="card"><div class="card-body">
    <div class="row g-3">
      <div class="col-md-3"><strong>Carrier:</strong> {{ $ecmr->road_carrier }}</div>
      <div class="col-md-3"><strong>Origin:</strong> {{ $ecmr->origin_branch_id }}</div>
      <div class="col-md-3"><strong>Destination:</strong> {{ $ecmr->destination_branch_id }}</div>
      <div class="col-md-3"><strong>Status:</strong> <span class="badge bg-secondary">{{ $ecmr->status }}</span></div>
    </div>
  </div></div>
</div>
@endsection

