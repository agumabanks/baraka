@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Leg #{{ $leg->id }}</h3>
    <a href="{{ route('admin.linehaul-legs.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
  </div>
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-3"><strong>Shipment:</strong> #{{ $leg->shipment_id }}</div>
        <div class="col-md-3"><strong>Mode:</strong> {{ $leg->mode }}</div>
        <div class="col-md-3"><strong>Carrier:</strong> {{ $leg->carrier }}</div>
        <div class="col-md-3"><strong>Status:</strong> <span class="badge bg-secondary">{{ $leg->status }}</span></div>
        <div class="col-md-3"><strong>AWB:</strong> {{ $leg->awb }}</div>
        <div class="col-md-3"><strong>CMR:</strong> {{ $leg->cmr }}</div>
        <div class="col-md-3"><strong>Depart:</strong> {{ optional($leg->depart_at)->format('Y-m-d H:i') }}</div>
        <div class="col-md-3"><strong>Arrive:</strong> {{ optional($leg->arrive_at)->format('Y-m-d H:i') }}</div>
      </div>
    </div>
  </div>

  @can('update', $leg)
  <div class="card">
    <div class="card-header">Update Leg</div>
    <div class="card-body">
      <form method="post" action="{{ route('admin.linehaul-legs.update', $leg) }}">@csrf @method('PUT')
        <div class="row g-3">
          <div class="col-md-3"><label class="form-label">Carrier</label><input name="carrier" class="form-control" value="{{ $leg->carrier }}"></div>
          <div class="col-md-3"><label class="form-label">AWB</label><input name="awb" class="form-control" value="{{ $leg->awb }}"></div>
          <div class="col-md-3"><label class="form-label">CMR</label><input name="cmr" class="form-control" value="{{ $leg->cmr }}"></div>
          <div class="col-md-3"><label class="form-label">Status</label><input name="status" class="form-control" value="{{ $leg->status }}"></div>
          <div class="col-md-3"><label class="form-label">Depart At</label><input type="datetime-local" name="depart_at" value="{{ optional($leg->depart_at)->format('Y-m-d\TH:i') }}" class="form-control"></div>
          <div class="col-md-3"><label class="form-label">Arrive At</label><input type="datetime-local" name="arrive_at" value="{{ optional($leg->arrive_at)->format('Y-m-d\TH:i') }}" class="form-control"></div>
        </div>
        <div class="mt-3"><button class="btn btn-primary">Update</button></div>
      </form>
    </div>
  </div>
  @endcan
  
</div>
@endsection

