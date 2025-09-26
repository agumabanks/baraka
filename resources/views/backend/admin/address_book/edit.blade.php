@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Edit Address #{{ $address->id }}</h3>
  <form method="post" action="{{ route('admin.address-book.update',$address) }}">@csrf @method('PUT')
    <div class="row g-3">
      <div class="col-md-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ $address->name }}" required></div>
      <div class="col-md-3"><label class="form-label">Phone</label><input name="phone_e164" class="form-control" value="{{ $address->phone_e164 }}" required></div>
      <div class="col-md-3"><label class="form-label">Email</label><input name="email" type="email" class="form-control" value="{{ $address->email }}"></div>
      <div class="col-md-3"><label class="form-label">City</label><input name="city" class="form-control" value="{{ $address->city }}" required></div>
      <div class="col-md-6"><label class="form-label">Address</label><input name="address_line" class="form-control" value="{{ $address->address_line }}" required></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary">Update</button> <a class="btn btn-secondary" href="{{ route('admin.address-book.show',$address) }}">Cancel</a></div>
  </form>
</div>
@endsection

