@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Manifest #{{ $manifest->id }} ({{ $manifest->number }})</h3>
    <a href="{{ route('admin.manifests.edit',$manifest) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
  </div>
  <div class="card"><div class="card-body">
    <div class="row g-3">
      <div class="col-md-3"><strong>Mode:</strong> {{ strtoupper($manifest->mode) }}</div>
      <div class="col-md-3"><strong>Depart:</strong> {{ optional($manifest->departure_at)->format('Y-m-d H:i') }}</div>
      <div class="col-md-3"><strong>Arrive:</strong> {{ optional($manifest->arrival_at)->format('Y-m-d H:i') }}</div>
      <div class="col-md-3"><strong>Status:</strong> <span class="badge bg-secondary">{{ $manifest->status }}</span></div>
    </div>
  </div></div>
</div>
@endsection

