@extends('backend.partials.master')
@section('title')
    {{ __('asset.title') }} {{ __('levels.reports') }} 
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
                                <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}" class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><a href="javascript:void(0)"  class="breadcrumb-link">{{ __('levels.asset_management') }}</a></li> 
                                <li class="breadcrumb-item"><a href=""  class="breadcrumb-link active">{{ __('levels.reports') }}</a></li>
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
                    <div class="card-body"> 
                        <form action="{{route('assets.reports')}}" method="GET" > 
                            <div class="row"> 
                                <div class="form-group col-md-4">
                                    <label for="asset_id">{{ __('asset.asset') }} <span class="text-danger ms-1">*</span></label>
                                    <select  id="asset_id" class="form-control select2"  name="asset_id">
                                        <option value="">{{ __('menus.select') }} {{ __('asset.asset') }}</option>
                                        @foreach ($assets as $assetItem)
                                            <option value="{{ $assetItem->id }}" @selected($request->asset_id == $assetItem->id)>{{ $assetItem->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('asset_id')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div> 

                                <div class="form-group col-md-3">
                                    <label for="from_date">{{ __('levels.from_date') }} </label>
                                    <input id="from_date" type="date" name="from_date" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_from_date') }}" autocomplete="off" class="form-control @error('from_date') is-invalid @enderror" value="{{old('from_date', $request->from_date   )}}" require>
                                    @error('from_date')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div> 

                                <div class="form-group col-md-3">
                                    <label for="to_date">{{ __('levels.to_date') }} </label>
                                    <input id="to_date" type="date" name="to_date" data-parsley-trigger="change" placeholder="{{ __('placeholder.enter_to_date') }}" autocomplete="off" class="form-control @error('to_date') is-invalid @enderror" value="{{old('to_date', $request->to_date  )}}" require>
                                    @error('to_date')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div> 
                             
                                <div class="form-group col-md-2 ">
                                    <button type="submit" class="btn btn-space btn-primary mt-4"><i class="fa fa-filter"></i> {{ __('levels.reports') }} </button>
                                    <a  href="{{ route('assets.reports') }}" class="btn btn-space btn-secondary mt-4 text-white"><i class="fa fa-clear"></i> {{ __('levels.clear') }} </a>
                                </div> 
                        </form> 
                    </div> 
                </div> 
                </div>
            </div>
            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
               
                @if (isset($asset))                    
                    <div class="card">
                        <div class="card-body">
                            <h5>{{ __('asset.asset') }} {{ __('levels.details') }}</h5>
                            <table class="table table-bordered">
                                <tbody>
                                    {{-- vehicle information --}}
                                    <tr>
                                        <th>{{ __('levels.name') }}</th>
                                        <td>{{ $asset->name }}</td>  
                                        <th>{{ __('asset.asset_type') }}</th>
                                        <td>{{ $asset->asset_type }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('levels.plate_no') }}</th>
                                        <td> {{ @$asset->vehicle->plate_no }}</td> 
                                        <th>{{ __('levels.chasis_number') }}</th>
                                        <td> {{ @$asset->vehicle->chasis_number }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('levels.model') }}</th>
                                        <td>{{ @$asset->vehicle->model }}</td> 
                                        <th>{{ __('levels.year') }}</th>
                                        <td> {{ @$asset->vehicle->year }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('levels.brand') }}</th>
                                        <td>{{ @$asset->vehicle->brand }}</td> 
                                        <th>{{ __('levels.color') }}</th>
                                        <td> {{ @$asset->vehicle->color }}</td> 
                                    </tr>
                                    
                                    {{-- end vehicle information --}}
        
                                    <tr>
                                        <th style="width: 25%;">{{ __('asset.assetcategory_id') }}</th>
                                        <td style="width: 25%;">{{ @$asset->assetcategorys->title }}</td>
                                        <th style="width: 25%;">{{ __('asset.amount') }}</th>
                                        <td style="width: 25%;">{{ $asset->amount }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('asset.registration_documents') }}</th>
                                        <td>
                                            @if($asset->my_registration_documents)
                                                <a href="{{ $asset->my_registration_documents }}" download style="color: red;">Download</a>
                                            @endif
                                        </td>
                                        <th>{{ __('asset.purchase_date') }}</th>
                                        <td>{{ $asset->purchase_date }}</td>
                                    </tr>

                                    <tr> 
                                        <th>{{ __('asset.registration_date') }}</th>
                                        <td>{{ $asset->registration_date }}</td>
                                        <th>{{ __('asset.registration_expiry_date') }}</th>
                                        <td>{{ $asset->registration_expiry_date }}</td>
                                    </tr>

                                    <tr> 
                                        <th>{{ __('levels.reminder_to_renew_registration') }}</th>
                                        <td>{!! @$asset->RenewRegistration !!}</td>
                                        {{-- <th>{{ __('asset.yearly_depreciation_value') }}</th>
                                        <td>{{ $asset->yearly_depreciation_value }}</td> --}}
                                        <th>{{ __('asset.insurance_status') }}</th>
                                        <td>{{ $asset->my_insurance_status }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('asset.insurance_documents') }}</th>
                                        <td>
                                            @if($asset->my_insurance_documents)
                                                <a href="{{ $asset->my_insurance_documents }}" download style="color: red;">Download</a>
                                            @endif
                                        </td>
                                        <th>{{ __('asset.insurance_expiry_date') }}</th>
                                        <td>{{ $asset->insurance_expiry_date }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('asset.insurance_amount') }}</th>
                                        <td>{{ $asset->insurance_amount }}</td>
                                        <th>{{ __('asset.maintenance_schedule') }}</th>
                                        <td>{{ $asset->maintenance_schedule }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('levels.reminder_to_renew_insurance') }}</th>
                                        <td>{!! @$asset->RenewInsurance !!}</td>
                                        <th> </th>
                                        <td> </td>
                                    </tr>
                                    
                                </tbody>
                            </table>
                        </div>
                    </div>

            
                     
                        <div class="card">
                            <div class="card-header">
                                <p class="h4">{{ __('levels.assigned_drivers') }}</p>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>{{ __('levels.id') }}</th>  
                                                <th>{{ __('levels.driver') }}</th>
                                                <th>{{ __('levels.from_date') }}</th>
                                                <th>{{ __('levels.to_date') }}</th>  
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $i=1; @endphp
                                            @foreach ($assigned_drivers as $assignDriver)
                                                <tr>
                                                    <td>{{ $i++ }}</td> 
                                                    <td>{{ @$assignDriver->driver->user->name }}</td>
                                                    <td>{{ $assignDriver->from_date }}</td>   
                                                    <td>{{ $assignDriver->to_date }}</td>    
                                                </tr>
                                            @endforeach 
                                        </tbody>
                                    </table>
                                </div> 
                            </div>
                        </div> 
                    
                            
                        <div class="card">
                            <div class="card-header">
                                <p class="h4">{{ __('levels.fuels') }}</p>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>{{ __('levels.id') }}</th>  
                                                <th>{{ __('levels.fuel_type') }}</th>
                                                <th>{{ __('levels.invoice_of_fuel') }}</th>
                                                <th>{{ __('levels.amount') }}</th>  
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $i=1; @endphp
                                            @foreach ($fuels as $fuel)
                                                <tr>
                                                    <td>{{ $i++ }}</td> 
                                                    <td>{{ $fuel->fuel_type }}</td>
                                                    <td><a href="{{ $fuel->MyInvoiceOfFuel }}" download="">Download</a></td>   
                                                    <td>{{ $fuel->amount }}</td>    
                                                </tr>
                                            @endforeach
                                            <tr>
                                                <td colspan="3" class="text-end font-weight-bold">Total Amount:</td>  
                                                <td class="font-weight-bold">  {{ @currency($fuels->sum('amount')) }}</td> 
                                            </tr>
                                        </tbody>
                                    </table>
                                </div> 
                            </div>
                        </div>
                        
                    
                        <div class="card">
                            <div class="card-header">
                                <p class="h4">{{ __('levels.maintenances') }}</p>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table" style="width:100%">
                                        <thead>
                                            <tr> 
                                                <th>{{ __('levels.id') }}</th>  
                                                <th>{{ __('levels.start_date') }}</th>
                                                <th>{{ __('levels.end_date') }}</th>
                                                <th>{{ __('levels.due_days') }}</th>
                                                <th>{{ __('levels.repair_details') }}</th>
                                                <th>{{ __('levels.spare_parts_purchased_details') }}</th> 
                                                <th>{{ __('levels.invoice_of_the_purchases') }}</th>  
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $i=1; @endphp
                                            @foreach ($maintenances as $maintenance)
                                                <tr>
                                                    <td>{{ $i++ }}</td> 
                                                    <td>{{ $maintenance->start_date }}</td>
                                                    <td>{{ $maintenance->end_date }}</td>
                                                    <td>{{ $maintenance->DueDays['due_days'] }} Due Days / {{ $maintenance->DueDays['total_days'] }} Days</td>
                                                    <td>{{ $maintenance->repair_details }}</td>
                                                    <td>{{ $maintenance->spare_parts_purchased_details }}</td>
                                                    <td><a href="{{ $maintenance->MyInvoiceOfThePurchases }}" download="">Download</a></td>  
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div> 
                                
                            </div>
                        </div>
                     
                        
                        <div class="card">
                            <div class="card-header">
                                <p class="h4">{{ __('levels.accidents') }}</p>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table" style="width:100%">
                                        <thead>
                                            <tr>  
                                                <th>{{ __('levels.id') }}</th>  
                                                <th>{{ __('levels.date_of_accident') }}</th>
                                                <th>{{ __('levels.driver_responsible') }}</th>
                                                <th>{{ __('levels.cost_of_repair') }}</th> 
                                                <th>{{ __('levels.spare_parts') }}</th> 
                                                <th>{{ __('levels.upload_documents') }}</th>  
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $i=1; @endphp
                                            @foreach ($accidents as $accident)
                                                <tr>
                                                    <td>{{ $i++ }}</td> 
                                                    <td>{{ $accident->date_of_accident }}</td>
                                                    <td>{{ @$accident->driver->user->name }}</td>
                                                    <td>{{ @currency($accident->cost_of_repair) }}</td>
                                                    <td>{{ $accident->spare_parts }}</td>
                                                    <td>
                                                        @foreach ($accident->Documents as $document)
                                                            <a href="{{ @$document }}" download="">{{ $loop->index+1 }}. Download</a><br/>
                                                        @endforeach
                                                    </td>  
                                                </tr>
                                            @endforeach
    
                                            <tr>
                                                <td colspan="3" class="text-end font-weight-bold">Total cost of repair:</td>  
                                                <td colspan="3" class="font-weight-bold"> {{ @currency($accidents->sum('cost_of_repair')) }}</td> 
                                            </tr>
    
                                        </tbody>
                                    </table>
                                </div> 
                                
                            </div>
                        </div> 
                    
                      
                @endif
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