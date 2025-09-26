@extends('frontend.layouts.master')
@section('title', __('levels.schedule_pickup'))
@section('content')
<section class="py-5 bg-light">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">
            <h4 class="mb-0">{{ __('levels.schedule_pickup') }}</h4>
          </div>
          <div class="card-body">
            <form action="{{ route('portal.schedule_pickup_store') }}" method="POST">
              @csrf
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="pickup_date" class="form-label">{{ __('levels.pickup_date') }} <span class="text-danger">*</span></label>
                  <input type="date" class="form-control" id="pickup_date" name="pickup_date" min="{{ date('Y-m-d') }}" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="pickup_time" class="form-label">{{ __('levels.preferred_time') }} <span class="text-danger">*</span></label>
                  <select class="form-select" id="pickup_time" name="pickup_time" required>
                    <option value="">{{ __('levels.select_time_slot') }}</option>
                    <option value="09:00-12:00">9:00 AM - 12:00 PM</option>
                    <option value="12:00-15:00">12:00 PM - 3:00 PM</option>
                    <option value="15:00-18:00">3:00 PM - 6:00 PM</option>
                    <option value="18:00-21:00">6:00 PM - 9:00 PM</option>
                  </select>
                </div>
              </div>
              
              <div class="mb-3">
                <label for="pickup_address" class="form-label">{{ __('levels.pickup_address') }} <span class="text-danger">*</span></label>
                <textarea class="form-control" id="pickup_address" name="pickup_address" rows="3" placeholder="{{ __('levels.enter_pickup_address') }}" required></textarea>
              </div>
              
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="contact_name" class="form-label">{{ __('levels.contact_name') }} <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="contact_name" name="contact_name" placeholder="{{ __('levels.enter_contact_name') }}" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="contact_phone" class="form-label">{{ __('levels.contact_phone') }} <span class="text-danger">*</span></label>
                  <input type="tel" class="form-control" id="contact_phone" name="contact_phone" placeholder="{{ __('levels.enter_contact_phone') }}" required>
                </div>
              </div>
              
              <div class="mb-3">
                <label for="special_instructions" class="form-label">{{ __('levels.special_instructions') }}</label>
                <textarea class="form-control" id="special_instructions" name="special_instructions" rows="2" placeholder="{{ __('levels.any_special_instructions') }}"></textarea>
              </div>
              
              <div class="mb-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="urgent_pickup" name="urgent_pickup">
                  <label class="form-check-label" for="urgent_pickup">
                    {{ __('levels.urgent_pickup') }} (+$5.00)
                  </label>
                </div>
              </div>
              
              <div class="d-grid">
                <button type="submit" class="btn btn-primary">{{ __('levels.schedule_pickup') }}</button>
              </div>
            </form>
          </div>
        </div>
        
        <div class="card shadow-sm mt-4">
          <div class="card-header bg-info text-white">
            <h5 class="mb-0">{{ __('levels.pickup_information') }}</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <h6>{{ __('levels.service_hours') }}</h6>
                <ul class="list-unstyled">
                  <li><i class="fa fa-clock text-primary me-2"></i>Monday - Friday: 9:00 AM - 9:00 PM</li>
                  <li><i class="fa fa-clock text-primary me-2"></i>Saturday: 9:00 AM - 6:00 PM</li>
                  <li><i class="fa fa-clock text-primary me-2"></i>Sunday: 12:00 PM - 6:00 PM</li>
                </ul>
              </div>
              <div class="col-md-6">
                <h6>{{ __('levels.pickup_requirements') }}</h6>
                <ul class="list-unstyled">
                  <li><i class="fa fa-check text-success me-2"></i>{{ __('levels.packages_must_be_ready') }}</li>
                  <li><i class="fa fa-check text-success me-2"></i>{{ __('levels.proper_packaging_required') }}</li>
                  <li><i class="fa fa-check text-success me-2"></i>{{ __('levels.contact_person_available') }}</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
