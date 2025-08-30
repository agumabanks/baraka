@extends('backend.partials.master')
@section('title')
    {{ __('designation.title') }} {{ __('levels.edit') }}
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
                            <li class="breadcrumb-item"><a href="javascript:void(0)" class="breadcrumb-link">{{ __('menus.resources') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{__('menus.user_role')}}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('designations.index') }}" class="breadcrumb-link">{{ __('designation.title') }}</a></li>
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
        <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-12">
            <div class="card">
                <div class="card-body">
                    <div class="text-right mb-2">
                        <x-back-button route="designations.index" />
                     </div>
<<<<<<< HEAD
                    <h2 class="pageheader-title">{{ __('levels.edit') }} {{ __('menus.vehicle') }}</h2>
                    <form action="{{route('vehicles.update',['id'=>$vehicle->id])}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @csrf 
                        @method('PUT') 
                        <div class="row"> 
                            <div class="form-group  col-md-6">
                                <label for="name">{{ __('levels.name') }}</label> <span class="text-danger">*</span>
                                <input id="name" type="text" name="name" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_name') }}" autocomplete="off" class="form-control @error('name') is-invalid @enderror" value="{{old('name',$vehicle->name)}}" require>
                                @error('name')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div> 
                            <div class="form-group col-md-6">
                                <label for="plate_no">{{ __('levels.plate_no') }}</label> <span class="text-danger">*</span>
                                <input id="plate_no" type="text" name="plate_no" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_plate_no') }}" autocomplete="off" class="form-control @error('plate_no') is-invalid @enderror" value="{{old('plate_no',$vehicle->plate_no)}}" require>
                                @error('plate_no')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
    
                            <div class="form-group col-md-6">
                                <label for="chasis_number">{{ __('levels.chasis_number') }}</label> <span class="text-danger">*</span>
                                <input id="chasis_number" type="text" name="chasis_number" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_chasis_number') }}" autocomplete="off" class="form-control @error('chasis_number') is-invalid @enderror" value="{{old('chasis_number',$vehicle->chasis_number)}}" require>
                                @error('chasis_number')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
    
                            <div class="form-group col-md-6">
                                <label for="model">{{ __('levels.model') }}</label> <span class="text-danger">*</span>
                                <input id="model" type="text" name="model" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_model') }}" autocomplete="off" class="form-control @error('model') is-invalid @enderror" value="{{old('model',$vehicle->model)}}" require>
                                @error('model')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label for="year">{{ __('levels.year') }}</label> <span class="text-danger">*</span>
                                <input id="year" type="year" name="year" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_year') }}" autocomplete="off" class="form-control @error('year') is-invalid @enderror" value="{{old('year',$vehicle->year)}}" require>
                                @error('year')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
    
                            <div class="form-group col-md-6">
                                <label for="brand">{{ __('levels.brand') }}</label> <span class="text-danger">*</span>
                                <input id="brand" type="text" name="brand" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_brand') }}" autocomplete="off" class="form-control @error('brand') is-invalid @enderror" value="{{old('brand',$vehicle->brand)}}" require>
                                @error('brand')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label for="color">{{ __('levels.color') }}</label> <span class="text-danger">*</span>
                                <input id="color" type="text" name="color" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_color') }}" autocomplete="off" class="form-control @error('color') is-invalid @enderror" value="{{old('color',$vehicle->color)}}" require>
                                @error('color')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
    
                            <div class="form-group col-md-6">
                                <label for="driver_id">{{ __('menus.driver') }}</label> <span class="text-danger">*</span>
                                <select  id="driver_id" class="form-control select2"  name="driver_id">
                                    <option value="">{{ __('menus.select') }} {{ __('menus.driver') }}</option>
                                    @foreach ($deliverymans as $deliveryman )
                                        <option value="{{ $deliveryman->id }}" @selected($vehicle->driver_id == $deliveryman->id)>{{ @$deliveryman->user->name }}</option>
                                    @endforeach
                                </select>
                                @error('driver_id')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
       
                            <div class="form-group col-md-6">
                                <label for="description">{{ __('levels.description') }}</label> 
                                <textarea id="description" name="description"  placeholder="{{ __('placeholder.enter_description') }}"   class="form-control @error('description') is-invalid @enderror">{{old('description',$vehicle->description)}}</textarea>
                                @error('description')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
          
                            <div class="form-group col-md-6">
                                <label for="status">{{ __('levels.status') }}</label> <span class="text-danger">*</span>
                                <select name="status" class="form-control @error('status') is-invalid @enderror">
                                    @foreach(trans('status') as $key => $status)
                                        <option value="{{ $key }}" {{ (old('status',$vehicle->status) == $key) ? 'selected' : '' }}>{{ $status }}</option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
=======
                    <h2 class="pageheader-title">{{ __('designation.edit_designation') }}</h2>
                    <form action="{{route('designations.update',['id'=>$designation->id])}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @csrf
                        @if (isset($designation))
                            @method('PUT')
                        @endif
                        <div class="form-group">
                            <label for="title">{{ __('levels.title') }}</label> <span class="text-danger">*</span>
                            <input id="title" type="text" name="title" data-parsley-trigger="change" placeholder="{{ __('placeholder.Enter_title') }}" autocomplete="off" class="form-control @error('title') is-invalid @enderror" value="{{old('title',$designation->title)}}" require>
                            @error('title')
                            <small class="text-danger mt-2">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="status">{{ __('levels.status') }}</label> <span class="text-danger">*</span>
                            <select name="status" class="form-control @error('status') is-invalid @enderror">
                                @foreach(trans('status') as $key => $status)
                                    <option value="{{ $key }}" {{ (old('status',$designation->status) == $key) ? 'selected' : '' }}>{{ $status }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                <small class="text-danger mt-2">{{ $message }}</small>
                            @enderror
>>>>>>> sajib
                        </div>
                        <div class="row">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save_change') }}</button>
                                <a href="{{ route('vehicles.index') }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
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

