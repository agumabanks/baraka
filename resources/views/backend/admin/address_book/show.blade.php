@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Address #{{ $address->id }}</h3>
    <a href="{{ route('admin.address-book.edit',$address) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
  </div>
  <div class="card"><div class="card-body">
    <div class="row g-3">
      <div class="col-md-3"><strong>Customer:</strong> {{ $address->customer_id }}</div>
      <div class="col-md-3"><strong>Type:</strong> {{ $address->type }}</div>
      <div class="col-md-3"><strong>Name:</strong> {{ $address->name }}</div>
      <div class="col-md-3"><strong>Phone:</strong> {{ $address->phone_e164 }}</div>
      <div class="col-md-3"><strong>Email:</strong> {{ $address->email }}</div>
      <div class="col-md-3"><strong>Country:</strong> {{ $address->country }}</div>
      <div class="col-md-3"><strong>City:</strong> {{ $address->city }}</div>
      <div class="col-md-6"><strong>Address:</strong> {{ $address->address_line }}</div>
    </div>
  </div></div>
</div>
@endsection

