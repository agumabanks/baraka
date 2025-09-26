@extends('frontend.layouts.master')
@section('title', __('levels.order_supplies'))
@section('content')
<section class="py-5 bg-light">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">
            <h4 class="mb-0">{{ __('levels.order_supplies') }}</h4>
          </div>
          <div class="card-body">
            @if($supplies->count() > 0)
              <form id="orderSuppliesForm">
                @csrf
                <div class="row">
                  @foreach($supplies as $supply)
                    <div class="col-md-6 col-lg-4 mb-4">
                      <div class="card h-100 border">
                        <div class="card-body text-center">
                          <div class="mb-3">
                            <i class="fa fa-box fa-3x text-primary"></i>
                          </div>
                          <h6 class="card-title">{{ $supply->title }}</h6>
                          <p class="card-text small text-muted">{{ $supply->description ?? 'Packaging material for shipments' }}</p>
                          <div class="mb-3">
                            <span class="h5 text-success">${{ number_format($supply->price ?? 5.00, 2) }}</span>
                          </div>
                          <div class="d-flex align-items-center justify-content-center">
                            <button type="button" class="btn btn-outline-secondary btn-sm me-2" onclick="changeQuantity('{{ $supply->id }}', -1)">-</button>
                            <input type="number" class="form-control form-control-sm text-center" id="quantity_{{ $supply->id }}" name="quantities[{{ $supply->id }}]" value="0" min="0" style="width: 60px;">
                            <button type="button" class="btn btn-outline-secondary btn-sm ms-2" onclick="changeQuantity('{{ $supply->id }}', 1)">+</button>
                          </div>
                        </div>
                      </div>
                    </div>
                  @endforeach
                </div>
                
                <div class="row mt-4">
                  <div class="col-md-8">
                    <div class="card">
                      <div class="card-body">
                        <h6>{{ __('levels.delivery_address') }}</h6>
                        <div class="mb-3">
                          <label for="delivery_address" class="form-label">{{ __('levels.address') }} <span class="text-danger">*</span></label>
                          <textarea class="form-control" id="delivery_address" name="delivery_address" rows="3" placeholder="{{ __('levels.enter_delivery_address') }}" required></textarea>
                        </div>
                        <div class="row">
                          <div class="col-md-6">
                            <label for="contact_name" class="form-label">{{ __('levels.contact_name') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="contact_name" name="contact_name" placeholder="{{ __('levels.enter_contact_name') }}" required>
                          </div>
                          <div class="col-md-6">
                            <label for="contact_phone" class="form-label">{{ __('levels.contact_phone') }} <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="contact_phone" name="contact_phone" placeholder="{{ __('levels.enter_contact_phone') }}" required>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="card">
                      <div class="card-body">
                        <h6>{{ __('levels.order_summary') }}</h6>
                        <div id="orderSummary">
                          <p class="text-muted">{{ __('levels.no_items_selected') }}</p>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                          <strong>{{ __('levels.total') }}:</strong>
                          <strong id="totalAmount">$0.00</strong>
                        </div>
                        <div class="mt-3">
                          <button type="submit" class="btn btn-primary w-100" id="placeOrderBtn" disabled>{{ __('levels.place_order') }}</button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </form>
            @else
              <div class="text-center py-5">
                <i class="fa fa-box-open fa-3x text-muted mb-3"></i>
                <h5>{{ __('levels.no_supplies_available') }}</h5>
                <p class="text-muted">{{ __('levels.supplies_will_be_available_soon') }}</p>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
function changeQuantity(supplyId, change) {
    const quantityInput = document.getElementById('quantity_' + supplyId);
    let currentValue = parseInt(quantityInput.value) || 0;
    currentValue += change;
    if (currentValue < 0) currentValue = 0;
    quantityInput.value = currentValue;
    updateOrderSummary();
}

function updateOrderSummary() {
    let totalItems = 0;
    let totalAmount = 0;
    const summaryDiv = document.getElementById('orderSummary');
    
    // This would need to be updated with actual supply data from PHP
    // For now, using dummy calculation
    const quantities = document.querySelectorAll('input[name^="quantities["]');
    quantities.forEach(function(input) {
        const quantity = parseInt(input.value) || 0;
        if (quantity > 0) {
            totalItems += quantity;
            totalAmount += quantity * 5.00; // Dummy price
        }
    });
    
    if (totalItems > 0) {
        summaryDiv.innerHTML = '<p>' + totalItems + ' {{ __("levels.items") }} selected</p>';
        document.getElementById('totalAmount').textContent = '$' + totalAmount.toFixed(2);
        document.getElementById('placeOrderBtn').disabled = false;
    } else {
        summaryDiv.innerHTML = '<p class="text-muted">{{ __("levels.no_items_selected") }}</p>';
        document.getElementById('totalAmount').textContent = '$0.00';
        document.getElementById('placeOrderBtn').disabled = true;
    }
}

// Update summary on page load and quantity changes
document.addEventListener('DOMContentLoaded', updateOrderSummary);
document.addEventListener('input', updateOrderSummary);
</script>
@endsection
