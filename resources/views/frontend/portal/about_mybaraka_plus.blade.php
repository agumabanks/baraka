@extends('frontend.layouts.master')
@section('title', __('levels.about_mybaraka_plus'))
@section('content')
<section class="py-5 bg-light">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">
            <h4 class="mb-0">{{ __('levels.about_mybaraka_plus') }}</h4>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-8">
                <h5>{{ __('levels.what_is_mybaraka_plus') ?? 'What is MyBaraka+?' }}</h5>
                <p class="mb-4">{{ __('levels.mybaraka_plus_description') ?? 'MyBaraka+ is our premium service offering enhanced features and benefits for our valued customers.' }}</p>

                <h6>{{ __('levels.benefits') ?? 'Benefits' }}</h6>
                <ul class="list-unstyled">
                  <li class="mb-2"><i class="fa fa-check text-success me-2"></i>{{ __('levels.priority_support') ?? 'Priority customer support' }}</li>
                  <li class="mb-2"><i class="fa fa-check text-success me-2"></i>{{ __('levels.discounts') ?? 'Exclusive discounts on services' }}</li>
                  <li class="mb-2"><i class="fa fa-check text-success me-2"></i>{{ __('levels.early_access') ?? 'Early access to new features' }}</li>
                  <li class="mb-2"><i class="fa fa-check text-success me-2"></i>{{ __('levels.dedicated_account_manager') ?? 'Dedicated account manager' }}</li>
                </ul>
              </div>
              <div class="col-md-4">
                <div class="card bg-light">
                  <div class="card-body text-center">
                    <i class="fa fa-star fa-3x text-warning mb-3"></i>
                    <h6>{{ __('levels.upgrade_to_premium') ?? 'Upgrade to Premium' }}</h6>
                    <p class="text-muted small">{{ __('levels.enjoy_premium_benefits') ?? 'Enjoy premium benefits and services' }}</p>
                    <button class="btn btn-primary">{{ __('levels.upgrade_now') ?? 'Upgrade Now' }}</button>
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