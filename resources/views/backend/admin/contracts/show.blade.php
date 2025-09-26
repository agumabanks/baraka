@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Contract #{{ $contract->id }}</h3>
    <a href="{{ route('admin.contracts.edit',$contract) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
  </div>
  <div class="card"><div class="card-body">
    <div class="row g-3">
      <div class="col-md-3"><strong>Customer:</strong> {{ $contract->customer_id }}</div>
      <div class="col-md-3"><strong>Name:</strong> {{ $contract->name }}</div>
      <div class="col-md-3"><strong>Start:</strong> {{ $contract->start_date }}</div>
      <div class="col-md-3"><strong>End:</strong> {{ $contract->end_date }}</div>
      <div class="col-md-3"><strong>Status:</strong> <span class="badge bg-secondary">{{ $contract->status }}</span></div>
    </div>
  </div></div>
</div>
@endsection

