@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Route #{{ $routeModel->id }}</h3>
  <div class="card"><div class="card-body">
    <p><strong>Name:</strong> {{ $routeModel->name }}</p>
  </div></div>
</div>
@endsection

