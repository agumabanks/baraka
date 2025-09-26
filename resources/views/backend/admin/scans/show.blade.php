@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Scan #{{ $scan->id }}</h3>
  <div class="card"><div class="card-body">
    <div class="row g-3">
      <div class="col-md-3"><strong>Shipment:</strong> {{ $scan->shipment_id }}</div>
      <div class="col-md-3"><strong>Hub:</strong> {{ $scan->hub_id }}</div>
      <div class="col-md-3"><strong>Event:</strong> {{ $scan->event }}</div>
      <div class="col-md-3"><strong>At:</strong> {{ $scan->created_at }}</div>
    </div>
    @if($scan->notes)
    <hr>
    <p class="mb-0"><strong>Notes:</strong> {{ $scan->notes }}</p>
    @endif
  </div></div>
</div>
@endsection

