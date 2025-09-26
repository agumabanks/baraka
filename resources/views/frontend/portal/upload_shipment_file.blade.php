@extends('frontend.layouts.master')
@section('title', __('levels.upload_shipment_file'))
@section('content')
<section class="py-5 bg-light">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">
            <h4 class="mb-0">{{ __('levels.upload_shipment_file') }}</h4>
          </div>
          <div class="card-body">
            <div class="alert alert-info">
              <i class="fa fa-info-circle me-2"></i>
              {{ __('levels.upload_shipment_file_description') }}
            </div>
            
            <form action="{{ route('portal.upload_shipment_file_store') }}" method="POST" enctype="multipart/form-data">
              @csrf
              <div class="mb-3">
                <label for="shipment_file" class="form-label">{{ __('levels.select_file') }} <span class="text-danger">*</span></label>
                <input type="file" class="form-control" id="shipment_file" name="shipment_file" accept=".csv,.xlsx,.xls" required>
                <div class="form-text">{{ __('levels.supported_formats') }}: CSV, Excel (.xlsx, .xls)</div>
              </div>
              
              <div class="mb-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="has_header" name="has_header" checked>
                  <label class="form-check-label" for="has_header">
                    {{ __('levels.first_row_is_header') }}
                  </label>
                </div>
              </div>
              
              <div class="d-grid">
                <button type="submit" class="btn btn-primary">{{ __('levels.upload_and_process') }}</button>
              </div>
            </form>
          </div>
        </div>
        
        <div class="card shadow-sm mt-4">
          <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">{{ __('levels.file_format_requirements') }}</h5>
          </div>
          <div class="card-body">
            <h6>{{ __('levels.required_columns') }}:</h6>
            <div class="table-responsive">
              <table class="table table-sm table-bordered">
                <thead class="table-light">
                  <tr>
                    <th>{{ __('levels.column_name') }}</th>
                    <th>{{ __('levels.description') }}</th>
                    <th>{{ __('levels.required') }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><code>pickup_address</code></td>
                    <td>{{ __('levels.pickup_address_description') }}</td>
                    <td><span class="badge bg-danger">{{ __('levels.yes') }}</span></td>
                  </tr>
                  <tr>
                    <td><code>pickup_phone</code></td>
                    <td>{{ __('levels.pickup_phone_description') }}</td>
                    <td><span class="badge bg-danger">{{ __('levels.yes') }}</span></td>
                  </tr>
                  <tr>
                    <td><code>customer_name</code></td>
                    <td>{{ __('levels.customer_name_description') }}</td>
                    <td><span class="badge bg-danger">{{ __('levels.yes') }}</span></td>
                  </tr>
                  <tr>
                    <td><code>customer_phone</code></td>
                    <td>{{ __('levels.customer_phone_description') }}</td>
                    <td><span class="badge bg-danger">{{ __('levels.yes') }}</span></td>
                  </tr>
                  <tr>
                    <td><code>customer_address</code></td>
                    <td>{{ __('levels.customer_address_description') }}</td>
                    <td><span class="badge bg-danger">{{ __('levels.yes') }}</span></td>
                  </tr>
                  <tr>
                    <td><code>cash_collection</code></td>
                    <td>{{ __('levels.cash_collection_description') }}</td>
                    <td><span class="badge bg-danger">{{ __('levels.yes') }}</span></td>
                  </tr>
                  <tr>
                    <td><code>invoice_no</code></td>
                    <td>{{ __('levels.invoice_no_description') }}</td>
                    <td><span class="badge bg-warning">{{ __('levels.optional') }}</span></td>
                  </tr>
                  <tr>
                    <td><code>weight</code></td>
                    <td>{{ __('levels.weight_description') }}</td>
                    <td><span class="badge bg-warning">{{ __('levels.optional') }}</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
            
            <div class="mt-3">
              <a href="{{ asset('sample_shipment_template.xlsx') }}" class="btn btn-outline-primary btn-sm" download>
                <i class="fa fa-download me-2"></i>{{ __('levels.download_sample_template') }}
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
