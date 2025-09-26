@extends('frontend.layouts.master')
@section('title', __('levels.get_rate_quote'))
@section('content')
<section class="py-5 bg-light">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">
            <h4 class="mb-0">{{ __('levels.get_rate_quote') }}</h4>
          </div>
          <div class="card-body">
            <form id="rateQuoteForm">
              @csrf
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="from_location" class="form-label">{{ __('levels.from') }} <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="from_location" name="from_location" placeholder="{{ __('levels.enter_pickup_location') }}" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="to_location" class="form-label">{{ __('levels.to') }} <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="to_location" name="to_location" placeholder="{{ __('levels.enter_delivery_location') }}" required>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="weight" class="form-label">{{ __('levels.weight') }} (kg) <span class="text-danger">*</span></label>
                  <input type="number" step="0.1" class="form-control" id="weight" name="weight" placeholder="0.0" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="service_type" class="form-label">{{ __('levels.service_type') }} <span class="text-danger">*</span></label>
                  <select class="form-select" id="service_type" name="service_type" required>
                    <option value="">{{ __('levels.select_service_type') }}</option>
                    <option value="standard">{{ __('levels.standard') }}</option>
                    <option value="express">{{ __('levels.express') }}</option>
                    <option value="same_day">{{ __('levels.same_day') }}</option>
                  </select>
                </div>
              </div>
              <div class="d-grid">
                <button type="submit" class="btn btn-primary">{{ __('levels.get_quote') }}</button>
              </div>
            </form>

            <div id="quoteResult" class="mt-4" style="display: none;">
              <div class="card border-success">
                <div class="card-header bg-success text-white">
                  <h5 class="mb-0">{{ __('levels.quote_result') }}</h5>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6">
                      <p><strong>{{ __('levels.estimated_cost') }}:</strong> <span id="estimatedCost" class="text-success fw-bold">$0.00</span></p>
                      <p><strong>{{ __('levels.delivery_time') }}:</strong> <span id="deliveryTime">1-2 days</span></p>
                    </div>
                    <div class="col-md-6">
                      <p><strong>{{ __('levels.service_type') }}:</strong> <span id="serviceTypeResult">Standard</span></p>
                      <p><strong>{{ __('levels.weight') }}:</strong> <span id="weightResult">0 kg</span></p>
                    </div>
                  </div>
                  <div class="d-grid mt-3">
                    <a href="{{ route('portal.create_shipment') }}" class="btn btn-success">{{ __('levels.create_shipment') }}</a>
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

<script>
document.getElementById('rateQuoteForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Simple calculation for demo purposes
    const weight = parseFloat(document.getElementById('weight').value) || 0;
    const serviceType = document.getElementById('service_type').value;
    
    let baseRate = 10;
    let multiplier = 1;
    
    if (serviceType === 'express') multiplier = 1.5;
    if (serviceType === 'same_day') multiplier = 2;
    
    const estimatedCost = (baseRate + (weight * 2)) * multiplier;
    const deliveryTime = serviceType === 'same_day' ? 'Same day' : 
                        serviceType === 'express' ? '1 day' : '1-2 days';
    
    document.getElementById('estimatedCost').textContent = '$' + estimatedCost.toFixed(2);
    document.getElementById('deliveryTime').textContent = deliveryTime;
    document.getElementById('serviceTypeResult').textContent = serviceType.charAt(0).toUpperCase() + serviceType.slice(1);
    document.getElementById('weightResult').textContent = weight + ' kg';
    document.getElementById('quoteResult').style.display = 'block';
});
</script>
@endsection
