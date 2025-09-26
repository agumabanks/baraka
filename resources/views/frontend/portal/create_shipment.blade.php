@extends('frontend.layouts.master')
@section('title', __('levels.ship') . ' - ' . __('levels.create_shipment'))
@section('content')
<section class="py-5 bg-light">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">
            <h4 class="mb-0">{{ __('levels.create_shipment') }}</h4>
          </div>
          <div class="card-body">
            <form action="{{ route('portal.store_shipment') }}" method="POST" enctype="multipart/form-data">
              @csrf

              <!-- Pickup Information -->
              <h5 class="mb-3">{{ __('levels.pickup') }} {{ __('levels.details') }}</h5>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="pickup_phone" class="form-label">{{ __('levels.phone') }} <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="pickup_phone" name="pickup_phone"
                         value="{{ old('pickup_phone') }}" required>
                  @error('pickup_phone')
                    <div class="text-danger small">{{ $message }}</div> 
                  @enderror
                </div>
                <div class="col-md-6 mb-3">
                  <label for="pickup_address" class="form-label">{{ __('levels.address') }} <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="pickup_address" name="pickup_address"
                         value="{{ old('pickup_address') }}" required>
                  @error('pickup_address')
                    <div class="text-danger small">{{ $message }}</div>
                  @enderror
                </div>
              </div>

              <!-- Delivery Information -->
              <h5 class="mb-3">{{ __('levels.delivery') }} {{ __('levels.details') }}</h5>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="customer_name" class="form-label">{{ __('levels.customer_name') }} <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="customer_name" name="customer_name"
                         value="{{ old('customer_name') }}" required>
                  @error('customer_name')
                    <div class="text-danger small">{{ $message }}</div>
                  @enderror
                </div>
                <div class="col-md-6 mb-3">
                  <label for="customer_phone" class="form-label">{{ __('levels.customer_phone') }} <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="customer_phone" name="customer_phone"
                         value="{{ old('customer_phone') }}" required>
                  @error('customer_phone')
                    <div class="text-danger small">{{ $message }}</div>
                  @enderror
                </div>
                <div class="col-md-12 mb-3">
                  <label for="customer_address" class="form-label">{{ __('levels.customer_address') }} <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="customer_address" name="customer_address"
                         value="{{ old('customer_address') }}" required>
                  @error('customer_address')
                    <div class="text-danger small">{{ $message }}</div>
                  @enderror
                </div>
              </div>

              <!-- Package Information -->
              <h5 class="mb-3">{{ __('levels.package') }} {{ __('levels.details') }}</h5>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="category_id" class="form-label">{{ __('levels.category') }} <span class="text-danger">*</span></label>
                  <select class="form-select" id="category_id" name="category_id" required>
                    <option value="">{{ __('menus.select') }} {{ __('levels.category') }}</option>
                    @foreach($deliveryCategories as $category)
                      <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                        {{ $category->title }}
                      </option>
                    @endforeach
                  </select>
                  @error('category_id')
                    <div class="text-danger small">{{ $message }}</div>
                  @enderror
                </div>
                <div class="col-md-6 mb-3">
                  <label for="delivery_type_id" class="form-label">{{ __('levels.delivery_type') }} <span class="text-danger">*</span></label>
                  <select class="form-select" id="delivery_type_id" name="delivery_type_id" required>
                    <option value="">{{ __('menus.select') }} {{ __('levels.delivery_type') }}</option>
                    @foreach($deliveryTypes as $type)
                      <option value="{{ $type->id }}" {{ old('delivery_type_id') == $type->id ? 'selected' : '' }}>
                        {{ $type->name }}
                      </option>
                    @endforeach
                  </select>
                  @error('delivery_type_id')
                    <div class="text-danger small">{{ $message }}</div>
                  @enderror
                </div>
                <div class="col-md-6 mb-3">
                  <label for="cash_collection" class="form-label">{{ __('levels.amount') }} <span class="text-danger">*</span></label>
                  <input type="number" step="0.01" class="form-control" id="cash_collection" name="cash_collection"
                         value="{{ old('cash_collection') }}" required>
                  @error('cash_collection')
                    <div class="text-danger small">{{ $message }}</div>
                  @enderror
                </div>
                <div class="col-md-6 mb-3">
                  <label for="invoice_no" class="form-label">{{ __('levels.invoice') }}</label>
                  <input type="text" class="form-control" id="invoice_no" name="invoice_no"
                         value="{{ old('invoice_no') }}">
                  @error('invoice_no')
                    <div class="text-danger small">{{ $message }}</div>
                  @enderror
                </div>
              </div>

              <!-- Additional Options -->
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="packaging_id" class="form-label">{{ __('levels.packaging') }}</label>
                  <select class="form-select" id="packaging_id" name="packaging_id">
                    <option value="">{{ __('menus.select') }} {{ __('levels.packaging') }}</option>
                    @foreach($packagingTypes as $packaging)
                      <option value="{{ $packaging->id }}" {{ old('packaging_id') == $packaging->id ? 'selected' : '' }}>
                        {{ $packaging->name }} - ${{ number_format($packaging->price, 2) }}
                      </option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="note" class="form-label">{{ __('levels.note') }}</label>
                  <textarea class="form-control" id="note" name="note" rows="3">{{ old('note') }}</textarea>
                </div>
              </div>

              <!-- Submit Buttons -->
              <div class="d-flex justify-content-between mt-4">
                <a href="{{ route('portal.index') }}" class="btn btn-secondary">{{ __('levels.cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ __('levels.create_shipment') }}</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection