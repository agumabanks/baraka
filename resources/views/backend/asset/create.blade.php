@extends('backend.partials.master')
@section('title')
    {{ __('asset.title') }} {{ __('levels.add') }}
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
                            <li class="breadcrumb-item"><a href="javascript:void(0)" class="breadcrumb-link">{{ __('levels.asset_management') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('asset.index') }}" class="breadcrumb-link">{{ __('asset.title') }}</a></li>
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
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="text-right mb-2">
                        <x-back-button route="asset.index" />
                    </div>
                    <h2 class="pageheader-title">{{ __('asset.asset_add') }}</h2>
                    <form action="{{route('asset.store')}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @csrf
                        <div class="row">

                                <div class="form-group col-md-6">
                                    <label for="name">{{ __('asset.name') }}</label> <span class="text-danger">*</span>
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="{{ __('placeholder.enter_name') }}" value="{{ old('name') }}"/>
                                    @error('name')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="asset_type">{{ __('asset.asset_type') }}</label> <span class="text-danger">*</span>
                                    <input type="text" name="asset_type" class="form-control @error('asset_type') is-invalid @enderror" placeholder="{{ __('placeholder.enter_asset_type') }}" value="{{ old('asset_type') }}"/>
                                    @error('asset_type')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="input-select">{{ __('asset.assetcategory_id') }}</label> <span class="text-danger">*</span>
                                    <select class="form-control @error('assetcategory_id') is-invalid @enderror" id="input-select" name="assetcategory_id" required>
                                        <option disabled selected>{{ __('menus.select') }} {{ __('asset_category.title_name') }}</option>
                                        @foreach($assetcategorys as $assetcategory)
                                            <option value="{{$assetcategory->id}}" {{ (old('assetcategory_id') == $assetcategory->id) ? 'selected' : '' }}>{{$assetcategory->title}}</option>
                                        @endforeach
                                    </select>
                                    @error('assetcategory_id')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                {{-- start vehicle information --}}
                                <div class="form-group col-md-6">
                                    <label for="plate_no">{{ __('levels.plate_no') }} <span class="text-danger ms-1">*</span></label>
                                    <input id="plate_no" type="text" name="plate_no" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_plate_no') }}" autocomplete="off" class="form-control @error('plate_no') is-invalid @enderror" value="{{old('plate_no')}}" require>
                                    @error('plate_no')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="chasis_number">{{ __('levels.chasis_number') }}<span class="text-danger ms-1">*</span></label>
                                    <input id="chasis_number" type="text" name="chasis_number" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_chasis_number') }}" autocomplete="off" class="form-control @error('chasis_number') is-invalid @enderror" value="{{old('chasis_number')}}" require>
                                    @error('chasis_number')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="model">{{ __('levels.model') }}<span class="text-danger ms-1">*</span></label>
                                    <input id="model" type="text" name="model" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_model') }}" autocomplete="off" class="form-control @error('model') is-invalid @enderror" value="{{old('model')}}" require>
                                    @error('model')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="year">{{ __('levels.year') }}<span class="text-danger ms-1">*</span></label>
                                    <input id="year" type="text" name="year"  placeholder="{{ __('placeholder.enter_year') }}" autocomplete="off" class="form-control @error('year') is-invalid @enderror" value="{{old('year')}}" require>
                                    @error('year')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="brand">{{ __('levels.brand') }}<span class="text-danger ms-1">*</span></label>
                                    <input id="brand" type="text" name="brand" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_brand') }}" autocomplete="off" class="form-control @error('brand') is-invalid @enderror" value="{{old('brand')}}" require>
                                    @error('brand')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="color">{{ __('levels.color') }}<span class="text-danger ms-1">*</span></label>
                                    <input id="color" type="text" name="color" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_color') }}" autocomplete="off" class="form-control @error('color') is-invalid @enderror" value="{{old('color')}}" require>
                                    @error('color')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                {{-- end vehicle information --}}


                                <div class="form-group col-md-6">
                                    <label for="registration_documents">{{ __('asset.registration_documents') }}<span class="text-danger">*</span></label>
                                    <input id="registration_documents" type="file" name="registration_documents" autocomplete="off" class="form-control">
                                    @error('registration_documents')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                {{-- <div class="form-group col-md-6">
                                    <label for="yearly_depreciation_value">{{ __('asset.yearly_depreciation_value') }}<span class="text-danger">*</span></label>
                                    <input id="yearly_depreciation_value" type="text" name="yearly_depreciation_value" data-parsley-trigger="change" placeholder="{{ __('asset.yearly_depreciation_value') }}" autocomplete="off" class="form-control @error('yearly_depreciation_value') is-invalid @enderror" value="{{ old('yearly_depreciation_value') }}">
                                    @error('yearly_depreciation_value')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div> --}}
                                <div class="form-group col-md-6">
                                    <label for="insurance_documents">{{ __('asset.insurance_documents') }}</label>
                                    <input id="insurance_documents" type="file" name="insurance_documents" autocomplete="off" class="form-control">
                                    @error('insurance_documents')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="insurance_amount">{{ __('asset.insurance_amount') }}</label>
                                    <input id="insurance_amount" type="number" name="insurance_amount" data-parsley-trigger="change" placeholder="{{ __('asset.insurance_amount') }}" autocomplete="off" class="form-control @error('insurance_amount') is-invalid @enderror" value="{{ old('insurance_amount') }}">
                                    @error('insurance_amount')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>



                                <div class="form-group col-md-6">
                                    <label for="amount">{{ __('asset.amount') }}</label> <span class="text-danger">*</span>
                                    <input id="amount" type="number" name="amount" data-parsley-trigger="change" placeholder="{{ __('asset.amount') }}" autocomplete="off" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}">
                                    @error('amount')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="purchase_date">{{ __('asset.purchase_date') }}</label> <span class="text-danger">*</span>
                                    <input type="date" id="purchase_date"  name="purchase_date" autocomplete="off" class="form-control @error('purchase_date') is-invalid @enderror" value="{{ old('purchase_date') }}">
                                    @error('purchase_date')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>




                                <div class="form-group col-md-6">
                                    <label for="registration_date">{{ __('asset.registration_date') }}</label> <span class="text-danger">*</span>
                                    <input type="date" id="registration_date"  name="registration_date" autocomplete="off" class="form-control @error('registration_date') is-invalid @enderror" value="{{ old('registration_date') }}">
                                    @error('registration_date')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="registration_expiry_date">{{ __('asset.registration_expiry_date') }}</label> <span class="text-danger">*</span>
                                    <input type="date" id="registration_expiry_date" name="registration_expiry_date" autocomplete="off" class="form-control @error('registration_expiry_date') is-invalid @enderror" value="{{ old('registration_expiry_date') }}">
                                    @error('registration_expiry_date')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="insurance_status">{{ __('asset.insurance_status') }}</label> <span class="text-danger">*</span>
                                    <select class="form-control" id="insurance_status" name="insurance_status" required>
                                        <option value="1" @selected(old('insurance_status') == 1)>{{ __('asset.yes') }}</option>
                                        <option value="2" @selected(old('insurance_status') == 2)>{{ __('asset.no') }}</option>
                                    </select>
                                    @error('insurance_status')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="insurance_registration">{{ __('levels.insurance_registration') }}</label>
                                    <input type="date" id="insurance_registration" type="insurance_registration" name="insurance_registration" autocomplete="off" class="form-control @error('insurance_registration') is-invalid @enderror" value="{{ old('insurance_registration') }}">
                                    @error('insurance_registration')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="insurance_expiry_date">{{ __('asset.insurance_expiry_date') }}</label>
                                    <input type="date" id="insurance_expiry_date" type="insurance_expiry_date" name="insurance_expiry_date" autocomplete="off" class="form-control @error('insurance_expiry_date') is-invalid @enderror" value="{{ old('insurance_expiry_date') }}">
                                    @error('insurance_expiry_date')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="maintenance_schedule">{{ __('asset.maintenance_schedule') }}<span class="text-danger">*</span></label>
                                    <input type="date" id="maintenance_schedule" type="maintenance_schedule" name="maintenance_schedule" autocomplete="off" class="form-control @error('maintenance_schedule') is-invalid @enderror" value="{{ old('maintenance_schedule') }}">
                                    @error('maintenance_schedule')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="description">{{ __('levels.description') }}</label>
                                    <textarea placeholder="{{ __('placeholder.Enter_description') }}" autocomplete="off" class="form-control" name="description" id="description" rows="5"></textarea>
                                    @error('description')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                        </div>

                        <div class="row">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save') }}</button>
                                <a href="{{ route('asset.index') }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
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
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script type="text/javascript">
    $('#year').datepicker({
            format: "yyyy",
            weekStart: 1,
            orientation: "bottom",
            keyboardNavigation: false,
            viewMode: "years",
            minViewMode: "years"
        });

    </script>
@endpush

