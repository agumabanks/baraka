@extends('backend.partials.master')
@section('maincontent')
<div class="container-fluid">
  <h3>Control Board</h3>
  <div class="row g-3">
    <div class="col-md-3"><div class="card text-center"><div class="card-body"><h5 class="card-title">Shipments Today</h5><p class="display-6">--</p></div></div></div>
    <div class="col-md-3"><div class="card text-center"><div class="card-body"><h5 class="card-title">On-Time %</h5><p class="display-6">--</p></div></div></div>
    <div class="col-md-3"><div class="card text-center"><div class="card-body"><h5 class="card-title">Open Claims</h5><p class="display-6">--</p></div></div></div>
    <div class="col-md-3"><div class="card text-center"><div class="card-body"><h5 class="card-title">Cash Variance</h5><p class="display-6">--</p></div></div></div>
  </div>
</div>
@endsection

