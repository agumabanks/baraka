@extends('backend.partials.master')
@section('title')
    {{ __('menus.vehicles') }}    {{ __('levels.view') }}
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
                            <li class="breadcrumb-item"><a href="{{route('vehicles.index')}}" class="breadcrumb-link">{{ __('menus.vehicles') }}</a></li>
                            <li class="breadcrumb-item"><a href="javascript:void(0)" class="breadcrumb-link">{{ $vehicle->name }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{__('levels.details')}}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <div class="text-right mb-2">
        <x-back-button route="vehicles.index" />
     </div>
    <!-- end pageheader -->
    <div class="row">
        <!-- data table  -->
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="row"> 


                <div class="col-md-12">
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
                                            <th>{{ __('menus.vehicle') }}</th>
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
                                                <td>{{ @$fuel->vehicle->name }}</td> 
                                                <td>{{ $fuel->fuel_type }}</td>
                                                <td><a href="{{ $fuel->MyInvoiceOfFuel }}" download="">Download</a></td>   
                                                <td>{{ $fuel->amount }}</td>    
                                            </tr>
                                        @endforeach
                                        <tr>
                                            <td colspan="4" class="text-end font-weight-bold">Total Amount:</td>  
                                            <td class="font-weight-bold">  {{ @currency($vehicle->fuels->sum('amount')) }}</td> 
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="px-3 d-flex flex-row-reverse align-items-center mt-2">
                                <span>{{ $fuels->appends(request()->all())->links() }}</span>
                                <p class="p-2 small">
                                    {!! __('Showing') !!}
                                    <span class="font-medium">{{ $fuels->firstItem() }}</span>
                                    {!! __('to') !!}
                                    <span class="font-medium">{{ $fuels->lastItem() }}</span>
                                    {!! __('of') !!}
                                    <span class="font-medium">{{ $fuels->total() }}</span>
                                    {!! __('results') !!}
                                </p>
                            </div>

                        </div>
                    </div>
                </div>


                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <p class="h4">{{ __('levels.assets') }}</p>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>{{ __('asset.sl') }}</th> 
                                            <th>{{ __('asset.vehicle') }}</th>
                                            <th>{{ __('asset.assetcategory_id') }}</th>
                                            <th>{{ __('asset.purchase_date') }}</th>
                                            <th>{{ __('asset.yearly_depreciation_value') }}</th>
                                            <th>{{ __('asset.insurance_status') }}</th>
                                            <th>{{ __('levels.insurance_registration') }}</th>
                                            <th>{{ __('asset.insurance_expiry_date') }}</th>
                                            <th>{{ __('asset.insurance_amount') }}</th>
                                            <th>{{ __('asset.maintenance_schedule') }}</th> 
                                            <th>{{ __('asset.amount') }}</th>
                                            
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $i=1; @endphp
                                        @foreach ($assets as $asset)
                                            <tr>
                                                <td>{{ $i++ }}</td> 
                                                <td>{{ @$asset->vehicle->name }}</td>
                                                <td>{{ @$asset->assetcategorys->title }}</td>
                                                <td>{{ @$asset->purchase_date }}</td>
                                                <td>{{ @$asset->yearly_depreciation_value }}</td>
                                                <td>{{ @$asset->my_insurance_status }}</td>
                                                <td>{{ @$asset->insurance_registration }}</td>
                                                <td>{{ @$asset->insurance_expiry_date }}</td>
                                                <td>{{ @currency($asset->insurance_amount) }}</td>
                                                <td>{{ @$asset->maintenance_schedule }}</td> 
                                                <td>{{ $asset->amount }}</td> 
                                            </tr>
                                        @endforeach
                                        <tr>
                                            <td colspan="10" class="text-end font-weight-bold">Total Cost:</td>  
                                            <td class="font-weight-bold"> 
                                                {{ @currency($vehicle->assets->sum('amount')) }}</td> 
                                        </tr>
                                    </tbody> 
                                </table>
                            </div> 
                            <div class="px-3 d-flex flex-row-reverse align-items-center mt-2">
                                <span>{{ $assets->appends(request()->all())->links() }}</span>
                                <p class="p-2 small">
                                    {!! __('Showing') !!}
                                    <span class="font-medium">{{ $assets->firstItem() }}</span>
                                    {!! __('to') !!}
                                    <span class="font-medium">{{ $assets->lastItem() }}</span>
                                    {!! __('of') !!}
                                    <span class="font-medium">{{ $assets->total() }}</span>
                                    {!! __('results') !!}
                                </p>
                            </div> 
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
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
                                            <th>{{ __('levels.date') }}</th>
                                            <th>{{ __('menus.vehicle') }}</th>
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
                                                <td>{{ $maintenance->date }}</td>
                                                <td>{{ @$maintenance->vehicle->name }}</td> 
                                                <td>{{ $maintenance->repair_details }}</td>
                                                <td>{{ $maintenance->spare_parts_purchased_details }}</td>
                                                <td><a href="{{ $maintenance->MyInvoiceOfThePurchases }}" download="">Download</a></td>  
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div> 
                            <div class="px-3 d-flex flex-row-reverse align-items-center mt-2">
                                <span>{{ $maintenances->appends(request()->all())->links() }}</span>
                                <p class="p-2 small">
                                    {!! __('Showing') !!}
                                    <span class="font-medium">{{ $maintenances->firstItem() }}</span>
                                    {!! __('to') !!}
                                    <span class="font-medium">{{ $maintenances->lastItem() }}</span>
                                    {!! __('of') !!}
                                    <span class="font-medium">{{ $maintenances->total() }}</span>
                                    {!! __('results') !!}
                                </p>
                            </div> 
                        </div>
                    </div>
                </div>
 
                <div class="col-md-12">
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
                                            <th>{{ __('menus.vehicle') }}</th>
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
                                                <td>{{ @$accident->vehicle->name }}</td> 
                                                <td>{{ $accident->driver_responsible }}</td>
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
                                            <td colspan="4" class="text-end font-weight-bold">Total cost of repair:</td>  
                                            <td colspan="3" class="font-weight-bold"> 
                                                {{ @currency($vehicle->accidents->sum('cost_of_repair')) }}</td> 
                                        </tr>

                                    </tbody>
                                </table>
                            </div> 
                            <div class="px-3 d-flex flex-row-reverse align-items-center mt-2">
                                <span>{{ $accidents->appends(request()->all())->links() }}</span>
                                <p class="p-2 small">
                                    {!! __('Showing') !!}
                                    <span class="font-medium">{{ $accidents->firstItem() }}</span>
                                    {!! __('to') !!}
                                    <span class="font-medium">{{ $accidents->lastItem() }}</span>
                                    {!! __('of') !!}
                                    <span class="font-medium">{{ $accidents->total() }}</span>
                                    {!! __('results') !!}
                                </p>
                            </div> 
                        </div>
                    </div>
                </div>



            </div>
  
        </div>
        <!-- end data table  -->
    </div>
</div>
<!-- nd wrapper  -->
 
@endsection()


