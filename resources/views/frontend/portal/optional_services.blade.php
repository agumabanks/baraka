@extends('frontend.layouts.master')
@section('title', __('levels.optional_services'))
@section('content')
<section class="py-5 bg-light">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">
            <h4 class="mb-0">{{ __('levels.optional_services') }}</h4>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6 mb-4">
                <div class="card h-100">
                  <div class="card-body">
                    <h5 class="card-title">{{ __('levels.insurance') ?? 'Insurance' }}</h5>
                    <p class="card-text">{{ __('levels.insurance_description') ?? 'Protect your shipments with comprehensive insurance coverage.' }}</p>
                    <div class="d-flex justify-content-between align-items-center">
                      <span class="text-success fw-bold">{{ __('levels.from') }} $2.99</span>
                      <button class="btn btn-outline-primary btn-sm">{{ __('levels.add_to_shipment') ?? 'Add to Shipment' }}</button>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-6 mb-4">
                <div class="card h-100">
                  <div class="card-body">
                    <h5 class="card-title">{{ __('levels.signature_required') ?? 'Signature Required' }}</h5>
                    <p class="card-text">{{ __('levels.signature_description') ?? 'Require recipient signature upon delivery.' }}</p>
                    <div class="d-flex justify-content-between align-items-center">
                      <span class="text-success fw-bold">$1.50</span>
                      <button class="btn btn-outline-primary btn-sm">{{ __('levels.add_to_shipment') ?? 'Add to Shipment' }}</button>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-6 mb-4">
                <div class="card h-100">
                  <div class="card-body">
                    <h5 class="card-title">{{ __('levels.priority_handling') ?? 'Priority Handling' }}</h5>
                    <p class="card-text">{{ __('levels.priority_description') ?? 'Fast-track your shipment through our network.' }}</p>
                    <div class="d-flex justify-content-between align-items-center">
                      <span class="text-success fw-bold">$5.99</span>
                      <button class="btn btn-outline-primary btn-sm">{{ __('levels.add_to_shipment') ?? 'Add to Shipment' }}</button>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-6 mb-4">
                <div class="card h-100">
                  <div class="card-body">
                    <h5 class="card-title">{{ __('levels.saturday_delivery') ?? 'Saturday Delivery' }}</h5>
                    <p class="card-text">{{ __('levels.saturday_description') ?? 'Deliver on Saturdays for urgent shipments.' }}</p>
                    <div class="d-flex justify-content-between align-items-center">
                      <span class="text-success fw-bold">$3.99</span>
                      <button class="btn btn-outline-primary btn-sm">{{ __('levels.add_to_shipment') ?? 'Add to Shipment' }}</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection