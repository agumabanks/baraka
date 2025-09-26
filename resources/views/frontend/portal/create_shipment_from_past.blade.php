@extends('frontend.layouts.master')
@section('title', __('levels.ship') . ' ' . __('levels.from') . ' ' . __('levels.past') . ' - ' . __('levels.create_shipment'))
@section('content')
<section class="py-5 bg-light">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">
            <h4 class="mb-0">{{ __('levels.create_shipment') }} {{ __('levels.from') }} {{ __('levels.past') }}</h4>
          </div>
          <div class="card-body">
            @if($pastShipments->count() > 0)
              <div class="row">
                @foreach($pastShipments as $shipment)
                  <div class="col-md-6 mb-3">
                    <div class="card h-100">
                      <div class="card-body">
                        <h6 class="card-title">{{ $shipment->customer_name }}</h6>
                        <p class="card-text small text-muted">{{ $shipment->customer_address }}</p>
                        <p class="card-text small">{{ __('levels.phone') }}: {{ $shipment->customer_phone }}</p>
                        <p class="card-text small">{{ __('levels.amount') }}: ${{ number_format($shipment->cash_collection, 2) }}</p>
                        <div class="d-flex justify-content-between">
                          <a href="{{ route('portal.create_shipment_from_past', ['id' => $shipment->id]) }}" class="btn btn-primary btn-sm">{{ __('levels.use_this') }}</a>
                          <small class="text-muted">{{ $shipment->created_at->format('M d, Y') }}</small>
                        </div>
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
            @else
              <div class="text-center py-5">
                <i class="fa fa-box-open fa-3x text-muted mb-3"></i>
                <h5>{{ __('levels.no_past_shipments') }}</h5>
                <p class="text-muted">{{ __('levels.create_your_first_shipment') }}</p>
                <a href="{{ route('portal.create_shipment') }}" class="btn btn-primary">{{ __('levels.create_shipment') }}</a>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
