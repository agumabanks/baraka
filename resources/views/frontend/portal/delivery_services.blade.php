@extends('frontend.layouts.master')
@section('title', __('levels.delivery_services'))
@section('content')
<section class="py-5 bg-light">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">
            <h4 class="mb-0">{{ __('levels.delivery_services') }}</h4>
          </div>
          <div class="card-body">
            <div class="row">
              @if(isset($services) && $services->count() > 0)
                @foreach($services as $service)
                  <div class="col-md-6 mb-4">
                    <div class="card h-100">
                      <div class="card-body">
                        <h5 class="card-title">{{ $service->title }}</h5>
                        <p class="card-text">{{ $service->description ?? 'Delivery service description' }}</p>
                        <div class="d-flex justify-content-between align-items-center">
                          <span class="text-success fw-bold">${{ number_format($service->price ?? 0, 2) }}</span>
                          <small class="text-muted">{{ $service->delivery_time ?? '1-2 days' }}</small>
                        </div>
                      </div>
                    </div>
                  </div>
                @endforeach
              @else
                <div class="col-12">
                  <div class="text-center py-5">
                    <i class="fa fa-truck fa-3x text-muted mb-3"></i>
                    <h5>{{ __('levels.no_services_available') ?? 'No services available' }}</h5>
                    <p class="text-muted">{{ __('levels.services_will_be_available_soon') ?? 'Services will be available soon' }}</p>
                  </div>
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection