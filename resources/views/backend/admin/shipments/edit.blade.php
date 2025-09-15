@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Edit Shipment #{{ $shipment->id }}</h3>
  <p class="text-muted">Editing not implemented.</p>
  <a class="btn btn-secondary" href="{{ route('admin.shipments.show',$shipment) }}">Back</a>
</div>
@endsection

