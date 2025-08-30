@extends('backend.partials.master')
@section('title')
    {{ __('levels.accidents') }} {{ __('levels.edit') }}
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
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link">{{ __('levels.accidents') }}</a></li>
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
                        <x-back-button route="accident.index" />
                     </div>
                    <h2 class="pageheader-title">{{ __('levels.edit') }} {{ __('levels.accident') }}</h2>
                    <form action="{{route('accident.update',['id'=>$accident->id])}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @csrf 
                        @method('PUT') 
                        <div class="row"> 
                                  
                            <div class="form-group col-md-6">
                                <label for="asset_id">{{ __('asset.asset') }}</label> <span class="text-danger">*</span>
                                <select class="form-control select2 @error('asset_id') is-invalid @enderror" id="input-select" name="asset_id">
                                    <option value="">{{ __('menus.select') }} {{ __('asset.asset') }}</option>
                                    @foreach($assets as $asset)
                                        <option value="{{$asset->id}}" @selected(old('asset_id',$accident->asset_id) == $asset->id)>{{$asset->name}}</option>
                                    @endforeach
                                </select>
                                @error('asset_id')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div>
                             

                            <div class="form-group col-md-6">
                                <label for="date_of_accident">{{ __('levels.date_of_accident') }} <span class="text-danger ms-1">*</span></label>
                                <input id="date_of_accident" type="date" name="date_of_accident" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_date_of_accident') }}" autocomplete="off" class="form-control @error('date_of_accident') is-invalid @enderror" value="{{old('date_of_accident',$accident->date_of_accident)}}" require>
                                @error('date_of_accident')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div> 
                            
                            {{-- <div class="form-group col-md-6">
                                <label for="driver_responsible">{{ __('levels.driver_responsible') }} <span class="text-danger ms-1">*</span></label>
                                <input id="driver_responsible" type="text" name="driver_responsible"   placeholder="{{ __('placeholder.enter_driver_responsible') }}"   class="form-control @error('driver_responsible') is-invalid @enderror"  value="{{old('driver_responsible',currency($accident->driver_responsible))}}" />  
                                @error('driver_responsible')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div> 
                    --}}

                            <div class="form-group col-md-6">
                                <label for="driver_responsible">{{ __('levels.driver_responsible') }} <span class="text-danger ms-1">*</span></label> 
                                <select class="form-control select2 @error('driver_responsible') is-invalid @enderror" name="driver_responsible">
                                    <option value="">{{ __('menus.select') }} {{ __('levels.driver_responsible') }}</option>
                                    @foreach ($deliverymans as $driver)
                                        <option value="{{ $driver->id }}" @selected($driver->id == $accident->driver_responsible)>{{ $driver->user->name }}</option>
                                    @endforeach
                                </select>
                                @error('driver_responsible')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div> 

                            <div class="form-group col-md-6">
                                <label for="cost_of_repair">{{ __('levels.cost_of_repair') }} <span class="text-danger ms-1">*</span></label>
                                <input id="cost_of_repair" type="text" name="cost_of_repair"   placeholder="{{ __('placeholder.enter_cost_of_repair') }}"   class="form-control @error('cost_of_repair') is-invalid @enderror"  value="{{old('cost_of_repair',$accident->cost_of_repair)}}" />  
                                @error('cost_of_repair')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div> 


                            <div class="form-group col-md-6">
                                <label for="spare_parts">{{ __('levels.spare_parts') }} <span class="text-danger ms-1">*</span></label>
                                <textarea id="spare_parts" type="text" name="spare_parts"   placeholder="{{ __('placeholder.enter_spare_parts') }}"   class="form-control @error('spare_parts') is-invalid @enderror"  >{{old('spare_parts',$accident->spare_parts)}}</textarea>
                                @error('spare_parts')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                @enderror
                            </div> 
 
                            <div class="col-md-6">
                                <div id="documents">
                                    <div class="form-group" id="document">
                                        <label for="multi_documents">{{ __('levels.upload_documents') }}<span class="text-danger ms-1">*</span></label> 
                                        <div class="d-flex">
                                            <input id="multi_documents" type="file" name="multi_documents[]" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_multi_documents') }}" autocomplete="off" class="form-control @error('multi_documents') is-invalid @enderror" value="{{old('multi_documents')}}" >
                                            <label class="btn btn-sm btn-primary" id="add_document"><i class="fa fa-plus"></i></label>
                                        </div>
                                        @error('multi_documents')
                                            <small class="text-danger mt-2">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div> 
                                <div>
                                    @foreach ($accident->Documents as $document)
                                        <a href="{{ @$document }}" download="">{{ $loop->index+1 }}. Download</a><br/>
                                    @endforeach
                                </div> 
                            </div>
                           
                        </div>

                        <div class="row">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 ">
                                <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save_change') }}</button>
                                <a href="{{ route('accident.index') }}" class="btn btn-space btn-secondary">{{ __('levels.cancel') }}</a>
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
    <script type="text/javascript">
    
        $('#add_document').click(function(){ 
            var input = $('#document input')[0]; 
            var row  = '';
                row += '<div class="form-group">';
                row += '<div class="d-flex">';
                row += '<input type="file" name="multi_documents[]" data-parsley-trigger="change" autocomplete="off" class="form-control ">';
                row += '<a href="javascript:void(0)" class="document_cancel"><i class="fa fa-times text-danger" style="padding:10px"></i></a>';
                row += '</div>';
                row += '</div>';  
            $('#documents').append(row); 
            $('.document_cancel').click(function(){
                $(this).closest('.form-group').remove();
            }); 
        });
      
    </script>
@endpush