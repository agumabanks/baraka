@extends('backend.partials.master')
@section('title')
    {{ __('levels.maintenances') }} {{ __('levels.edit') }}
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
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link">{{ __('levels.maintenances') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.edit') }}</a></li>
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
                        <x-back-button route="maintenance.index" />
                     </div>
                    <h2 class="pageheader-title">{{ __('levels.edit') }} {{ __('levels.maintenance') }}</h2>
                    <form action="{{route('maintenance.update',['id'=>$maintenance->id])}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @csrf 
                        @method('PUT') 
                        <div class="row">  
                            <div class="form-group col-md-6">
                                <label for="asset_id">{{ __('asset.asset') }}</label> <span class="text-danger">*</span>
                                <select class="form-control select2 @error('asset_id') is-invalid @enderror" id="input-select" name="asset_id">
                                    <option value="">{{ __('menus.select') }} {{ __('asset.asset') }}</option>
                                    @foreach($assets as $asset)
                                        <option value="{{$asset->id}}" @selected(old('asset_id',$maintenance->asset_id) == $asset->id)>{{$asset->name}}</option>
                                    @endforeach
                                </select>
                                @error('asset_id')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>

                             
                            <div class="form-group col-md-6">
                                <div class="row align-items-center">
                                    <div class="col-md-12">
                                        <label for="invoice_of_the_purchases">{{ __('levels.invoice_of_the_purchases') }}  <a class="text-danger" href="{{ $maintenance->MyInvoiceOfThePurchases }}" download="" >( Download )</a> </label> 
                                        <input id="invoice_of_the_purchases" type="file" name="invoice_of_the_purchases" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_invoice_of_the_purchases') }}" autocomplete="off" class="form-control @error('invoice_of_the_purchases') is-invalid @enderror" value="{{old('invoice_of_the_purchases')}}" require>
                                        @error('invoice_of_the_purchases')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div> 
                                </div>
                            </div> 
                          

                            <div class="form-group col-md-6">
                                <label for="start_date">{{ __('levels.start_date') }} <span class="text-danger ms-1">*</span></label>
                                <input id="start_date" type="date" name="start_date" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_start_date') }}" autocomplete="off" class="form-control @error('start_date') is-invalid @enderror" value="{{old('start_date',$maintenance->start_date)}}" require>
                                @error('start_date')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div> 
                            <div class="form-group col-md-6">
                                <label for="end_date">{{ __('levels.end_date') }} <span class="text-danger ms-1">*</span></label>
                                <input id="end_date" type="date" name="end_date" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_end_date') }}" autocomplete="off" class="form-control @error('end_date') is-invalid @enderror" value="{{old('end_date',$maintenance->end_date)}}" require>
                                @error('end_date')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div> 
                            <div class="form-group col-md-6">
                                <label for="repair_details">{{ __('levels.repair_details') }} <span class="text-danger ms-1">*</span></label>
                                <textarea id="repair_details" name="repair_details"   placeholder="{{ __('placeholder.enter_repair_details') }}"   class="form-control @error('repair_details') is-invalid @enderror"  > {{old('repair_details',$maintenance->repair_details)}}</textarea>
                                @error('repair_details')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div> 

                            <div class="form-group col-md-6">
                                <label for="spare_parts_purchased_details">{{ __('levels.spare_parts_purchased_details') }} <span class="text-danger ms-1">*</span></label>
                                <textarea id="spare_parts_purchased_details" name="spare_parts_purchased_details"   placeholder="{{ __('placeholder.enter_spare_parts_purchased_details') }}"   class="form-control @error('spare_parts_purchased_details') is-invalid @enderror"  > {{old('spare_parts_purchased_details',$maintenance->spare_parts_purchased_details)}}</textarea>
                                @error('spare_parts_purchased_details')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div> 


      
                        </div>

                        <div class="row">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save_change') }}</button>
                                <a href="{{ route('maintenance.index') }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- end basic form -->
    </div>
</div>
<!-- end wrapper  -->
@endsection()


@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  
@endpush