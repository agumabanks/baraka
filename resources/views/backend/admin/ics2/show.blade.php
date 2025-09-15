@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>ICS2 Filing #{{ $filing->id }}</h3>
  <div class="card"><div class="card-body">
    <div class="row g-3">
      <div class="col-md-3"><strong>Mode:</strong> {{ $filing->mode }}</div>
      <div class="col-md-3"><strong>ENS Ref:</strong> {{ $filing->ens_ref }}</div>
      <div class="col-md-3"><strong>Status:</strong> {{ $filing->status }}</div>
      <div class="col-md-3"><strong>Lodged:</strong> {{ $filing->lodged_at }}</div>
    </div>
  </div></div>
</div>
@endsection

