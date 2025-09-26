@extends('frontend.layouts.master')
@section('title', __('levels.surcharges'))
@section('content')
<section class="py-5 bg-light">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">
            <h4 class="mb-0">{{ __('levels.surcharges') }}</h4>
          </div>
          <div class="card-body">
            <div class="alert alert-info">
              <i class="fa fa-info-circle me-2"></i>
              {{ __('levels.surcharges_description') ?? 'Additional fees that may apply to your shipments.' }}
            </div>
            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>{{ __('levels.surcharge_type') ?? 'Surcharge Type' }}</th>
                    <th>{{ __('levels.description') }}</th>
                    <th>{{ __('levels.amount') }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>{{ __('levels.fuel_surcharge') ?? 'Fuel Surcharge' }}</td>
                    <td>{{ __('levels.fuel_surcharge_description') ?? 'Additional fee to cover fuel costs' }}</td>
                    <td>$2.50</td>
                  </tr>
                  <tr>
                    <td>{{ __('levels.peak_season_surcharge') ?? 'Peak Season Surcharge' }}</td>
                    <td>{{ __('levels.peak_season_description') ?? 'Additional fee during peak shipping seasons' }}</td>
                    <td>$3.00</td>
                  </tr>
                  <tr>
                    <td>{{ __('levels.remote_area_surcharge') ?? 'Remote Area Surcharge' }}</td>
                    <td>{{ __('levels.remote_area_description') ?? 'Additional fee for deliveries to remote areas' }}</td>
                    <td>$5.00</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection