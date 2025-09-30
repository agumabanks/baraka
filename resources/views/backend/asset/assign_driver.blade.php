@extends('backend.partials.master')
@section('title')
    {{ __('asset.title') }} {{ __('levels.driver') }} {{ __('levels.assign') }}
@endsection
@section('maincontent')
    <!-- wrapper  -->
    <div class="container-fluid  dashboard-content">
        <!-- pageheader -->
        <div class="row">
            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                <div class="page-header">
                    <div class="page-breadcrumb">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}"
                                        class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><a href="javascript:void(0)"
                                        class="breadcrumb-link">{{ __('levels.asset_management') }}</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('asset.index') }}"
                                        class="breadcrumb-link">{{ __('asset.title') }}</a></li>
                                <li class="breadcrumb-item"><a href=""  class="breadcrumb-link">{{ __('levels.assign') }} {{ __('levels.driver') }}</a></li>
                                <li class="breadcrumb-item"><a href=""  class="breadcrumb-link active">{{ __('levels.list') }}</a></li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <!-- end pageheader -->
        <div class="row">
            <!-- table  -->


            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                <div class="card">
                    <div class="row pl-4 pr-4 pt-4">
                        <div class="col-6">
                            <p class="h3">{{ $asset->name }} - {{ __('levels.assign_to_driver') }}</p>
                        </div> 
                    </div> 
                    <div class="card-body">
                        @if (isset($assetAssigned))
                            <form action="{{route('asset.assign.driver.update')}}"  method="POST" enctype="multipart/form-data" id="basicform">
                             @method('put')
                             <input type="hidden" name="id" value="{{ $assetAssigned->id }}" />
                        @else
                            <form action="{{route('asset.assign.driver.store')}}"  method="POST" enctype="multipart/form-data" id="basicform">
                        @endif
                            @csrf
                            <input type="hidden" name="asset_id" value="{{ $asset->id }}" />
                            <div class="row"> 
                                <div class="form-group col-md-4">
                                    <label for="driver_id">{{ __('menus.driver') }} <span class="text-danger ms-1">*</span></label>
                                    <select  id="driver_id" class="form-control select2"  name="driver_id">
                                        <option value="">{{ __('menus.select') }} {{ __('menus.driver') }}</option>
                                        @foreach ($deliverymans as $deliveryman )
                                            <option value="{{ $deliveryman->id }}" {{ isset($assetAssigned)? $assetAssigned->driver_id == $deliveryman->id? 'selected':'':''  }}>{{ @$deliveryman->user->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('driver_id')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div> 

                                <div class="form-group col-md-3">
                                    <label for="from_date">{{ __('levels.from_date') }} <span class="text-danger ms-1">*</span></label>
                                    <input id="from_date" type="date" name="from_date" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_from_date') }}" autocomplete="off" class="form-control @error('from_date') is-invalid @enderror" value="{{old('from_date',  isset($assetAssigned)? $assetAssigned->from_date:''   )}}" require>
                                    @error('from_date')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div> 
                                <div class="form-group col-md-3">
                                    <label for="to_date">{{ __('levels.to_date') }} <span class="text-danger ms-1">*</span></label>
                                    <input id="to_date" type="date" name="to_date" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_to_date') }}" autocomplete="off" class="form-control @error('to_date') is-invalid @enderror" value="{{old('to_date',isset($assetAssigned)? $assetAssigned->to_date:''  )}}" require>
                                    @error('to_date')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div> 
                             
                                <div class="form-group col-md-2 ">
                                    <button type="submit" class="btn btn-space btn-primary mt-4"><i class="fa fa-save"></i>
                                        
                                        {{ isset($assetAssigned)? __('levels.update'):__('levels.assign')  }}
                                         
                                    </button>
                                </div>

                        </form>

                        <div class="table-responsive">
                            <table class="table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>{{ __('asset.sl') }}</th> 
                                        <th>{{ __('levels.driver') }}</th>
                                        <th>{{ __('levels.from_date') }}</th>
                                        <th>{{ __('levels.to_date') }}</th> 
                                        @if (hasPermission('assets_update') == true || hasPermission('assets_delete') == true)
                                            <th>{{ __('asset.action') }}</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $i=1; @endphp
                                    @foreach ($AssignedDrivers as $assignDriver)
                                        <tr>
                                            <td>{{ $i++ }}</td> 
                                            <td>{{ @$assignDriver->driver->user->name }}</td>
                                            <td>{{ @$assignDriver->from_date }}</td>
                                            <td>{{ @$assignDriver->to_date }}</td> 
                                            @if (hasPermission('assets_update') == true || hasPermission('assets_delete') == true)
                                                <td>
                                                    <div class="row">
                                                        <button tabindex="-1" data-bs-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split">
                                                            <span class="sr-only">Toggle Dropdown</span>
                                                        </button>
                                                        <div class="dropdown-menu">
                                                         
                                                            @if (hasPermission('assets_update') == true)
                                                                <a href="{{ route('asset.assign.edit', $assignDriver->id) }}" class="dropdown-item"><i class="fas fa-edit"
                                                                    aria-hidden="true"></i> {{ __('levels.edit') }}
                                                                </a>
                                                            @endif
                                                            @if (hasPermission('assets_delete') == true)
                                                                <form id="delete" value="Test" action="{{ route('asset.assign.driver.delete', $assignDriver->id) }}" method="POST" data-title="{{ __('delete.asset_assigned') }}">
                                                                    @method('DELETE')
                                                                    @csrf
                                                                    <button type="submit" class="dropdown-item"><i class="fa fa-trash" aria-hidden="true"></i>
                                                                        {{ __('levels.delete') }}
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>

                            </table>
                        </div>
                    </div>

                    <div class="px-3 d-flex flex-row-reverse align-items-center">
                        <span>{{ $AssignedDrivers->links() }}</span>
                        <p class="p-2 small">
                            {!! __('Showing') !!}
                            <span class="font-medium">{{ $AssignedDrivers->firstItem() }}</span>
                            {!! __('to') !!}
                            <span class="font-medium">{{ $AssignedDrivers->lastItem() }}</span>
                            {!! __('of') !!}
                            <span class="font-medium">{{ $AssignedDrivers->total() }}</span>
                            {!! __('results') !!}
                        </p>
                    </div>
                </div>
            </div>
            <!-- end table  -->
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