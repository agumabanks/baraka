@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>KYC Record #{{ $kyc->id }}</h3>
  <div class="card"><div class="card-body">
    <div class="row g-3">
      <div class="col-md-3"><strong>Customer:</strong> {{ $kyc->customer_id }}</div>
      <div class="col-md-3"><strong>Status:</strong> {{ $kyc->status }}</div>
      <div class="col-md-3"><strong>Reviewed At:</strong> {{ $kyc->reviewed_at }}</div>
    </div>
    <hr>
    <h5>Recent Screenings</h5>
    <ul class="mb-0">
      @foreach($screenings as $s)
        <li>{{ $s->screened_at }} — {{ $s->result }} ({{ $s->match_score }})</li>
      @endforeach
    </ul>
  </div></div>
</div>
@endsection

