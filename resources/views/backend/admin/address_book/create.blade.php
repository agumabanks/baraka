@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Create Address</h3>
  <form method="post" action="{{ route('admin.address-book.store') }}">@csrf
    <div class="row g-3">
      <div class="col-md-3"><label class="form-label">Customer ID</label><input name="customer_id" type="number" class="form-control" required></div>
      <div class="col-md-3"><label class="form-label">Type</label><select name="type" class="form-select"><option>shipper</option><option>consignee</option><option>payer</option></select></div>
      <div class="col-md-3"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
      <div class="col-md-3"><label class="form-label">Phone</label><input name="phone_e164" class="form-control" required></div>
      <div class="col-md-3"><label class="form-label">Email</label><input name="email" type="email" class="form-control"></div>
      <div class="col-md-2"><label class="form-label">Country</label><input name="country" class="form-control" placeholder="UG" required></div>
      <div class="col-md-3"><label class="form-label">City</label><input name="city" class="form-control" required></div>
      <div class="col-md-6"><label class="form-label">Address</label><input name="address_line" class="form-control" required></div>
      <div class="col-md-3"><label class="form-label">Tax ID</label><input name="tax_id" class="form-control"></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary">Save</button> <a class="btn btn-secondary" href="{{ route('admin.address-book.index') }}">Cancel</a></div>
  </form>
</div>
@endsection

