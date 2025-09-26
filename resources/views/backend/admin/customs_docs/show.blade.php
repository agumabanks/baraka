@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Customs Doc #{{ $doc->id }}</h3>
  <div class="card"><div class="card-body">
    <div class="row g-3">
      <div class="col-md-3"><strong>Shipment:</strong> {{ $doc->shipment_id }}</div>
      <div class="col-md-3"><strong>Type:</strong> {{ $doc->doc_type }}</div>
    </div>
  </div></div>
</div>
@endsection

