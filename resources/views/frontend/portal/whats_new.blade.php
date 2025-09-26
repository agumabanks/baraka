@extends('frontend.layouts.master')
@section('title', __('levels.whats_new_with_mybaraka_plus'))
@section('content')
<section class="py-5 bg-light">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">
            <h4 class="mb-0">{{ __('levels.whats_new_with_mybaraka_plus') }}</h4>
          </div>
          <div class="card-body">
            <div class="timeline">
              <div class="timeline-item mb-4">
                <div class="timeline-marker bg-success"></div>
                <div class="timeline-content">
                  <h6 class="mb-1">{{ __('levels.new_feature') ?? 'New Feature' }}: {{ __('levels.real_time_tracking') ?? 'Real-Time Tracking' }}</h6>
                  <p class="text-muted small mb-1">{{ __('levels.released_on') ?? 'Released on' }} January 15, 2024</p>
                  <p>{{ __('levels.real_time_tracking_description') ?? 'Track your shipments in real-time with our enhanced GPS tracking system.' }}</p>
                </div>
              </div>

              <div class="timeline-item mb-4">
                <div class="timeline-marker bg-info"></div>
                <div class="timeline-content">
                  <h6 class="mb-1">{{ __('levels.improvement') ?? 'Improvement' }}: {{ __('levels.mobile_app') ?? 'Mobile App' }}</h6>
                  <p class="text-muted small mb-1">{{ __('levels.released_on') ?? 'Released on' }} December 20, 2023</p>
                  <p>{{ __('levels.mobile_app_description') ?? 'Our mobile app now supports biometric authentication for enhanced security.' }}</p>
                </div>
              </div>

              <div class="timeline-item mb-4">
                <div class="timeline-marker bg-warning"></div>
                <div class="timeline-content">
                  <h6 class="mb-1">{{ __('levels.update') ?? 'Update' }}: {{ __('levels.api_enhancements') ?? 'API Enhancements' }}</h6>
                  <p class="text-muted small mb-1">{{ __('levels.released_on') ?? 'Released on' }} November 10, 2023</p>
                  <p>{{ __('levels.api_enhancements_description') ?? 'New API endpoints for bulk shipment creation and advanced reporting.' }}</p>
                </div>
              </div>

              <div class="timeline-item">
                <div class="timeline-marker bg-primary"></div>
                <div class="timeline-content">
                  <h6 class="mb-1">{{ __('levels.coming_soon') ?? 'Coming Soon' }}: {{ __('levels.ai_powered_insights') ?? 'AI-Powered Insights' }}</h6>
                  <p class="text-muted small mb-1">{{ __('levels.expected_release') ?? 'Expected Release' }} Q2 2024</p>
                  <p>{{ __('levels.ai_insights_description') ?? 'Get intelligent recommendations for optimizing your shipping costs and delivery times.' }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<style>
.timeline {
  position: relative;
  padding-left: 30px;
}

.timeline::before {
  content: '';
  position: absolute;
  left: 15px;
  top: 0;
  bottom: 0;
  width: 2px;
  background: #e9ecef;
}

.timeline-item {
  position: relative;
  margin-left: -30px;
  padding-left: 45px;
}

.timeline-marker {
  position: absolute;
  left: 0;
  top: 5px;
  width: 30px;
  height: 30px;
  border-radius: 50%;
  border: 3px solid #fff;
  box-shadow: 0 0 0 2px #e9ecef;
}
</style>
@endsection