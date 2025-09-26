@extends('frontend.layouts.master')
@section('title', __('levels.customs_services'))
@section('content')
<section class="py-5 bg-light">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">
            <h4 class="mb-0">{{ __('levels.customs_services') }}</h4>
          </div>
          <div class="card-body">
            <div class="alert alert-info">
              <i class="fa fa-info-circle me-2"></i>
              {{ __('levels.customs_services_description') ?? 'Customs clearance and documentation services for international shipments.' }}
            </div>
            <div class="row">
              <div class="col-md-6 mb-4">
                <div class="card h-100">
                  <div class="card-body">
                    <h5 class="card-title">{{ __('levels.customs_clearance') ?? 'Customs Clearance' }}</h5>
                    <p class="card-text">{{ __('levels.customs_clearance_description') ?? 'Complete customs clearance for your international shipments.' }}</p>
                    <div class="d-flex justify-content-between align-items-center">
                      <span class="text-success fw-bold">{{ __('levels.from') }} $25.00</span>
                      <button class="btn btn-outline-primary btn-sm">{{ __('levels.contact_us') }}</button>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-6 mb-4">
                <div class="card h-100">
                  <div class="card-body">
                    <h5 class="card-title">{{ __('levels.documentation_assistance') ?? 'Documentation Assistance' }}</h5>
                    <p class="card-text">{{ __('levels.documentation_description') ?? 'Help with customs documentation and paperwork.' }}</p>
                    <div class="d-flex justify-content-between align-items-center">
                      <span class="text-success fw-bold">$15.00</span>
                      <button class="btn btn-outline-primary btn-sm">{{ __('levels.contact_us') }}</button>
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