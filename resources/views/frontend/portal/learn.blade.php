@extends('frontend.layouts.master')
@section('title', __('levels.learn'))
@section('content')
<section class="py-5 bg-light">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">
            <h4 class="mb-0">{{ __('levels.learn') }}</h4>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6 mb-4">
                <div class="card h-100">
                  <div class="card-body">
                    <h5 class="card-title">{{ __('levels.shipping_guide') ?? 'Shipping Guide' }}</h5>
                    <p class="card-text">{{ __('levels.shipping_guide_description') ?? 'Learn the basics of shipping with our comprehensive guide.' }}</p>
                    <a href="#" class="btn btn-outline-primary">{{ __('levels.read_more') ?? 'Read More' }}</a>
                  </div>
                </div>
              </div>
              <div class="col-md-6 mb-4">
                <div class="card h-100">
                  <div class="card-body">
                    <h5 class="card-title">{{ __('levels.packaging_tips') ?? 'Packaging Tips' }}</h5>
                    <p class="card-text">{{ __('levels.packaging_tips_description') ?? 'Best practices for packaging your shipments safely.' }}</p>
                    <a href="#" class="btn btn-outline-primary">{{ __('levels.read_more') ?? 'Read More' }}</a>
                  </div>
                </div>
              </div>
              <div class="col-md-6 mb-4">
                <div class="card h-100">
                  <div class="card-body">
                    <h5 class="card-title">{{ __('levels.tracking_guide') ?? 'Tracking Guide' }}</h5>
                    <p class="card-text">{{ __('levels.tracking_guide_description') ?? 'How to track your shipments and understand delivery status.' }}</p>
                    <a href="#" class="btn btn-outline-primary">{{ __('levels.read_more') ?? 'Read More' }}</a>
                  </div>
                </div>
              </div>
              <div class="col-md-6 mb-4">
                <div class="card h-100">
                  <div class="card-body">
                    <h5 class="card-title">{{ __('levels.faq') }}</h5>
                    <p class="card-text">{{ __('levels.faq_description') ?? 'Frequently asked questions about our services.' }}</p>
                    <a href="{{ route('get.faq.index') }}" class="btn btn-outline-primary">{{ __('levels.view_faq') ?? 'View FAQ' }}</a>
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