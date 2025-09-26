@extends('frontend.layouts.master')
@section('title', __('levels.solutions'))
@section('content')
<section class="py-5 bg-light">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">
            <h4 class="mb-0">{{ __('levels.solutions') }}</h4>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6 mb-4">
                <div class="card h-100">
                  <div class="card-body text-center">
                    <i class="fa fa-building fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">{{ __('levels.business_solutions') ?? 'Business Solutions' }}</h5>
                    <p class="card-text">{{ __('levels.business_solutions_description') ?? 'Comprehensive shipping solutions for businesses of all sizes.' }}</p>
                    <a href="#" class="btn btn-outline-primary">{{ __('levels.learn_more') ?? 'Learn More' }}</a>
                  </div>
                </div>
              </div>
              <div class="col-md-6 mb-4">
                <div class="card h-100">
                  <div class="card-body text-center">
                    <i class="fa fa-shopping-cart fa-3x text-success mb-3"></i>
                    <h5 class="card-title">{{ __('levels.ecommerce_solutions') ?? 'E-commerce Solutions' }}</h5>
                    <p class="card-text">{{ __('levels.ecommerce_description') ?? 'Integrated shipping solutions for online stores.' }}</p>
                    <a href="#" class="btn btn-outline-primary">{{ __('levels.learn_more') ?? 'Learn More' }}</a>
                  </div>
                </div>
              </div>
              <div class="col-md-6 mb-4">
                <div class="card h-100">
                  <div class="card-body text-center">
                    <i class="fa fa-globe fa-3x text-info mb-3"></i>
                    <h5 class="card-title">{{ __('levels.international_solutions') ?? 'International Solutions' }}</h5>
                    <p class="card-text">{{ __('levels.international_description') ?? 'Global shipping solutions for international business.' }}</p>
                    <a href="#" class="btn btn-outline-primary">{{ __('levels.learn_more') ?? 'Learn More' }}</a>
                  </div>
                </div>
              </div>
              <div class="col-md-6 mb-4">
                <div class="card h-100">
                  <div class="card-body text-center">
                    <i class="fa fa-clock fa-3x text-warning mb-3"></i>
                    <h5 class="card-title">{{ __('levels.express_solutions') ?? 'Express Solutions' }}</h5>
                    <p class="card-text">{{ __('levels.express_solutions_description') ?? 'Fast and reliable express delivery services.' }}</p>
                    <a href="#" class="btn btn-outline-primary">{{ __('levels.learn_more') ?? 'Learn More' }}</a>
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