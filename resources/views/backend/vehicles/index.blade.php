@extends('backend.partials.master')
@section('title')
    {{ __('designation.title') }} {{ __('levels.list') }}
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
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="javascript:void(0)" class="breadcrumb-link">{{ __('menus.resources') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{__('menus.user_role')}}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link">{{ __('designation.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.list') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
<<<<<<< HEAD
        <!-- end pageheader -->
        <div class="row">
            <!-- data table  -->
            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

                <div class="card">
                    <div class="card-body">
                        <form action="{{route('vehicles.index')}}"  method="GET">
                        
                            <div class="row">
                                <div class="form-group col-12 col-md-3 col-sm-6  col-lg-3 col-xl-2">
                                    <label for="parcel_date">{{ __('parcel.date') }}</label>
                                    <input type="text" autocomplete="off" id="date" name="date" class="form-control date_range_picker" value="{{ old('parcel_date',$request->date) }}" placeholder="{{ __('merchantPlaceholder.date') }}">
                                    @error('parcel_date')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                

                                <div class="form-group col-12 col-md-3 col-sm-6  col-lg-3 col-xl-2">
                                    <label for="name">{{ __('levels.name') }}</label>
                                    <input id="name" type="text" name="name"  placeholder="{{ __('placeholder.enter_name') }}" autocomplete="off" class="form-control" value="{{old('name',$request->name)}}">
                                    @error('name')
                                    <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                              
                                <div class="form-group col-12 col-md-3 col-sm-6  col-lg-3 col-xl-2">
                                    <label for="driver_id">{{ __('menus.driver') }} </label>
                                    <select  id="driver_id" class="form-control select2"  name="driver_id">
                                        <option value="">{{ __('menus.select') }} {{ __('menus.driver') }}</option>
                                        @foreach ($deliverymans as $deliveryman )
                                            <option value="{{ $deliveryman->id }}">{{ @$deliveryman->user->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('driver_id')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group col-12 col-md-3 col-sm-6  col-lg-3 col-xl-2">
                                    <label for="status">{{ __('parcel.status') }}</label>
                                    <select style="width: 100%" id="status"  name="status" class="form-control @error('status') is-invalid @enderror" >
                                        <option value="" selected> {{ __('menus.select') }} {{ __('parcel.status') }}</option>
                                        @foreach (trans('status') as $key => $status)
                                            <option value="{{ $key}}" {{ (old('status',$request->status) == $key) ? 'selected' : '' }}>{{ $status }}</option>
                                        @endforeach
                                    </select> 
                                </div>

                            
                                <div class="form-group col-md-3 col-lg-3 col-xl-2">
                                    <div class="pt-4 d-flex margin-top-5px">
                                        <button type="submit" class="btn btn-space btn-primary"><i class="fa fa-filter"></i> {{ __('levels.filter') }}</button>
                                        <a href="{{ route('vehicles.index') }}" class="btn btn-space btn-secondary"><i class="fa fa-eraser"></i> {{ __('levels.clear') }}</a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="row pl-4 pr-4 pt-4">
                        <div class="col-6">
                            <p class="h3">{{ __('menus.vehicles') }}</p>
                        </div>
                        @if (hasPermission('vehicles_create') == true)
                            <div class="col-6">
                                <a href="{{ route('vehicles.create') }}" class="btn btn-primary btn-sm float-right"
                                    data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i
                                        class="fa fa-plus"></i></a>
                            </div>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table   " style="width:100%">
                                <thead>
                                    <tr>
                                        <th>{{ __('levels.id') }}</th>
                                        <th>{{ __('levels.name') }}</th>
                                        <th>{{ __('levels.reminder_to_renew_insurance') }}</th>
                                        <th>{{ __('levels.plate_no') }}</th>
                                        <th>{{ __('levels.chasis_number') }}</th>
                                        <th>{{ __('levels.model') }}</th>
                                        <th>{{ __('levels.year') }}</th>
                                        <th>{{ __('levels.brand') }}</th>
                                        <th>{{ __('levels.color') }}</th>
                                        <th>{{ __('menus.driver') }}</th>
                                        <th>{{ __('levels.description') }}</th>
                                        <th>{{ __('levels.status') }}</th>
                                        @if (hasPermission('vehicles_update') == true || hasPermission('vehicles_delete') == true)
                                            <th>{{ __('levels.actions') }}</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $i=1; @endphp
                                    @foreach ($vehicles as $vehicle)
                                        <tr>
                                            <td>{{ $i++ }}</td>
                                            <td>{{ $vehicle->name }}</td> 
                                            <td>{!! @$vehicle->RenewInsurance !!}</td> 
                                            <td>{{ $vehicle->plate_no }}</td>
                                            <td>{{ $vehicle->chasis_number }}</td>
                                            <td>{{ $vehicle->model }}</td>
                                            <td>{{ $vehicle->year }}</td>
                                            <td>{{ $vehicle->brand }}</td>
                                            <td>{{ $vehicle->color }}</td>
                                            <td>
                                                {{ @$vehicle->driver->user->name }}<br/>
                                                {{ @$vehicle->driver->user->mobile }}
                                            </td>
                                            <td>{{ $vehicle->description }}</td>
                                            <td>{!! $vehicle->my_status !!}</td>

                                            @if (hasPermission('vehicles_update') == true || hasPermission('vehicles_delete') == true)
                                                <td>
                                                    <div class="row">
                                                        <button tabindex="-1" data-bs-toggle="dropdown" type="button"
                                                            class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span
                                                                class="sr-only">Toggle Dropdown</span></button>
                                                        <div class="dropdown-menu">
                                                            
                                                            <a href="{{ route('vehicles.view', $vehicle->id) }}"
                                                                    class="dropdown-item"><i class="fas fa-eye"
                                                                        aria-hidden="true"></i> {{ __('levels.view') }}</a>
                                                           
                                                            @if (hasPermission('vehicles_update') == true)
                                                                <a href="{{ route('vehicles.edit', $vehicle->id) }}"
                                                                    class="dropdown-item"><i class="fas fa-edit"
                                                                        aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                                            @endif
                                                            @if (hasPermission('vehicles_delete') == true)
                                                                <form id="delete" value="Test"
                                                                    action="{{ route('vehicles.delete', $vehicle->id) }}"
                                                                    method="POST" data-title="{{ __('delete.vehicle') }}">
                                                                    @method('DELETE')
                                                                    @csrf
                                                                    <input type="hidden" name=""
                                                                        value="{{ __('menus.vehicle') }}" id="deleteTitle">
                                                                    <button type="submit" class="dropdown-item"><i
                                                                            class="fa fa-trash" aria-hidden="true"></i>
                                                                        {{ __('levels.delete') }}</button>
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
                        <span>{{ $vehicles->links() }}</span>
                        <p class="p-2 small">
                            {!! __('Showing') !!}
                            <span class="font-medium">{{ $vehicles->firstItem() }}</span>
                            {!! __('to') !!}
                            <span class="font-medium">{{ $vehicles->lastItem() }}</span>
                            {!! __('of') !!}
                            <span class="font-medium">{{ $vehicles->total() }}</span>
                            {!! __('results') !!}
                        </p>
                    </div>
                </div>
            </div>
            <!-- end data table  -->
        </div>
    </div>
    <!-- end wrapper  -->
@endsection()
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endpush

@push('scripts')
<script type="text/javascript">
    var dateParcel = '{{ $request->parcel_date }}';
</script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script type="text/javascript" src="{{ static_asset('backend/js/date-range-picker/date-range-picker-custom.js') }}"></script> 
@endpush
=======
    </div>
    <!-- end pageheader -->
    <div class="row">
        <!-- data table  -->
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="card">
                <div class="row pl-4 pr-4 pt-4">
                    <div class="col-6">
                        <p class="h3">{{ __('designation.title') }}</p>
                    </div>
                    @if (hasPermission('designation_create') == true )
                        <div class="col-6">
                            <a href="{{url('admin/designations/create')}}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i class="fa fa-plus"></i></a>
                        </div>
                    @endif
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table   " style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('levels.id') }}</th>
                                    <th>{{ __('levels.title') }}</th>
                                    <th>{{ __('levels.status') }}</th>

                                    @if (hasPermission('designation_update') == true || hasPermission('designation_delete') == true)
                                    <th>{{ __('levels.actions') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @php $i=1; @endphp
                                @foreach($designations as $designation)
                                <tr>
                                    <td>{{$i++}}</td>
                                    <td>{{$designation->title}}</td>
                                    <td>{!! $designation->my_status !!}</td>

                                    @if (hasPermission('designation_update') == true || hasPermission('designation_delete') == true)
                                        <td>
                                            <div class="row">
                                                <button tabindex="-1" data-bs-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span class="sr-only">Toggle Dropdown</span></button>
                                                <div class="dropdown-menu">
                                                    @if (hasPermission('designation_update') == true)
                                                        <a href="{{route('designations.edit',$designation->id)}}" class="dropdown-item"><i class="fas fa-edit" aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                                    @endif
                                                    @if (hasPermission('designation_delete') == true)
                                                        <form id="delete" value="Test" action="{{route('designation.delete',$designation->id)}}" method="POST" data-title="{{ __('delete.designation') }}">
                                                            @method('DELETE')
                                                            @csrf
                                                            <input type="hidden" name="" value="{{ __('designation.title') }}" id="deleteTitle">
                                                            <button type="submit" class="dropdown-item"><i class="fa fa-trash" aria-hidden="true"></i> {{ __('levels.delete') }}</button>
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
                    <span>{{ $designations->links() }}</span>
                    <p class="p-2 small">
                        {!! __('Showing') !!}
                        <span class="font-medium">{{ $designations->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium">{{ $designations->lastItem() }}</span>
                        {!! __('of') !!}
                        <span class="font-medium">{{ $designations->total() }}</span>
                        {!! __('results') !!}
                    </p>
                </div>
            </div>
        </div>
        <!-- end data table  -->
    </div>
</div>
<!-- end wrapper  -->
@endsection()

>>>>>>> sajib
