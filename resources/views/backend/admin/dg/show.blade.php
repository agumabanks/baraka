@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>DG Item #{{ $dg->id }}</h3>
  <div class="card"><div class="card-body">
    <div class="row g-3">
      <div class="col-md-3"><strong>UN:</strong> {{ $dg->un_number }}</div>
      <div class="col-md-3"><strong>Class:</strong> {{ $dg->dg_class }}</div>
      <div class="col-md-3"><strong>Packing Group:</strong> {{ $dg->packing_group }}</div>
      <div class="col-md-3"><strong>Status:</strong> {{ $dg->status }}</div>
    </div>
  </div></div>
</div>
@endsection

