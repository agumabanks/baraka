@extends('backend.partials.master')
@section('title')
    {{ __('levels.fuels') }} {{ __('levels.add') }}
@endsection
@section('maincontent')
<div class="container-fluid  dashboard-content">
    <!-- pageheader -->
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{ __('menus.asset_management') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link">{{ __('levels.fuels') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.create') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- end pageheader -->
    <div class="row">
        <!-- basic form -->
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <div class="card-body">
                    <div class="text-right mb-2">
                        <x-back-button route="fuels.index" />
                     </div>
 
                    <h2 class="pageheader-title">{{ __('levels.create') }} {{ __('levels.fuel') }}</h2>
                    <form action="{{route('fuels.store')}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @csrf
                        <div class="row">
                             
                            <div class="form-group col-md-6">
                                <label for="asset_id">{{ __('asset.asset') }}</label> <span class="text-danger">*</span>
                                <select class="form-control select2 @error('asset_id') is-invalid @enderror" id="input-select" name="asset_id">
                                    <option value="">{{ __('menus.select') }} {{ __('asset.asset') }}</option>
                                    @foreach($assets as $asset)
                                        <option value="{{$asset->id}}" @selected(old('asset_id') == $asset->id)>{{$asset->name}}</option>
                                    @endforeach
                                </select>
                                @error('asset_id')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                        
                            <div class="form-group col-md-6">
                                <label for="fuel_type">{{ __('levels.fuel_type') }} <span class="text-danger ms-1">*</span></label>
                                <input id="fuel_type" type="text" name="fuel_type" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_fuel_type') }}" autocomplete="off" class="form-control @error('fuel_type') is-invalid @enderror" value="{{old('fuel_type')}}" require>
                                @error('fuel_type')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
    
                            <div class="form-group col-md-6">
                                <label for="invoice_of_fuel">{{ __('levels.invoice_of_fuel') }}<span class="text-danger ms-1">*</span></label> 
                                <input id="invoice_of_fuel" type="file" name="invoice_of_fuel" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_invoice_of_fuel') }}" autocomplete="off" class="form-control @error('invoice_of_fuel') is-invalid @enderror" value="{{old('invoice_of_fuel')}}" require>
                                @error('invoice_of_fuel')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
    
                            <div class="form-group col-md-6">
                                <label for="amount">{{ __('levels.amount') }}<span class="text-danger ms-1">*</span></label> 
                                <input id="amount" type="text" name="amount" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_amount') }}" autocomplete="off" class="form-control @error('amount') is-invalid @enderror" value="{{old('amount')}}" require>
                                @error('amount')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                        
                        </div>
 
                        <div class="row">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save') }}</button>
                                <a href="{{ route('fuels.index') }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- end basic form -->
    </div>
</div>
@endsection()

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> 
  
@endpush