@extends('backend.partials.master')
@section('title')
{{ __('asset.title') }} {{ __('levels.view') }}
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
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}"
                                    class="breadcrumb-link">{{ __('levels.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="javascript:void(0)" class="breadcrumb-link">{{
                                    __('levels.asset_management') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('asset.index') }}" class="breadcrumb-link">{{
                                    __('asset.title') }}</a></li>
                            <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.view')
                                    }}</a></li>
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
                    <div class="text-right mb-2">
                        <x-back-button route="asset.index" />
                    </div>
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
        </div>
        <!-- end basic form -->
    </div>
</div>
<!-- end wrapper  -->
@endsection()