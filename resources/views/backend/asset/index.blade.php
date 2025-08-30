@extends('backend.partials.master')
@section('title')
    {{ __('asset.title') }} {{ __('levels.list') }}
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
                                <li class="breadcrumb-item"><a href=""
                                        class="breadcrumb-link active">{{ __('levels.list') }}</a></li>
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
                            <p class="h3">{{ __('asset.asset_list') }}</p>
                        </div>
                        @if (hasPermission('assets_create') == true)
                            <div class="col-6">
                                <a href="{{ route('asset.create') }}" class="btn btn-primary btn-sm float-right"
                                    data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i
                                    class="fa fa-plus"></i> {{ __('levels.add') }}</a>
                            </div>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>{{ __('asset.sl') }}</th>
                                        <th>{{ __('levels.name') }}</th>
                                        <th>{{ __('asset.asset_type') }}</th>
                                        <th>{{ __('asset.assetcategory_id') }}</th>
                                        <th>{{ __('asset.purchase_date') }}</th>
                                        <th>{{ __('levels.reminder_to_renew_registration') }}</th>
                                        <th>{{ __('asset.registration_date') }}</th>
                                        <th>{{ __('asset.registration_expiry_date') }}</th>
                                        <th>{{ __('levels.reminder_to_renew_insurance') }}</th>
                                        {{-- <th>{{ __('asset.yearly_depreciation_value') }}</th> --}}
                                        <th>{{ __('asset.insurance_status') }}</th>
                                        <th>{{ __('levels.insurance_registration') }}</th>
                                        <th>{{ __('asset.insurance_expiry_date') }}</th>
                                        <th>{{ __('asset.insurance_amount') }}</th>
                                        <th>{{ __('asset.maintenance_schedule') }}</th>
                                        <th>{{ __('asset.amount') }}</th>
                                        @if (hasPermission('assets_update') == true || hasPermission('assets_delete') == true)
                                            <th>{{ __('asset.action') }}</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $i=1; @endphp
                                    @foreach ($assets as $asset)
                                        <tr>
                                            <td>{{ $i++ }}</td>
                                            <td>{{ @$asset->name }}</td>
                                            <td>{{ @$asset->asset_type }}</td>
                                            <td>{{ @$asset->assetcategorys->title }}</td>
                                            <td>{{ @$asset->purchase_date }}</td>
                                            <td>{!! @$asset->RenewRegistration !!}</td>
                                            <td>{{ @$asset->registration_date }}</td>
                                            <td>{{ @$asset->registration_expiry_date }}</td>
                                            <td>{!! @$asset->RenewInsurance !!}</td>
                                            {{-- <td>{{ @$asset->yearly_depreciation_value }}</td> --}}
                                            <td>{{ @$asset->my_insurance_status }}</td>
                                            <td>{{ @$asset->insurance_registration }}</td>
                                            <td>{{ @$asset->insurance_expiry_date }}</td>
                                            <td>{{ @currency($asset->insurance_amount) }}</td>
                                            <td>{{ @$asset->maintenance_schedule }}</td>
                                            <td>{{ currency($asset->amount) }}</td>
                                            @if (hasPermission('assets_update') == true || hasPermission('assets_delete') == true)
                                                <td>
                                                    <div class="row">
                                                        <button tabindex="-1" data-toggle="dropdown" type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split">
                                                            <span class="sr-only">Toggle Dropdown</span>
                                                        </button>
                                                        <div class="dropdown-menu">
                                                            @if (hasPermission('assets_read') == true)
                                                                <a href="{{ route('asset.assign.driver', $asset->id) }}" class="dropdown-item"><i class="fas fa-reply"
                                                                    aria-hidden="true"></i> {{ __('levels.assign') }} {{ __('levels.driver') }}
                                                                </a>
                                                            @endif
                                                            @if (hasPermission('assets_read') == true)
                                                                <a href="{{ route('asset.view', $asset->id) }}" class="dropdown-item"><i class="fas fa-eye"
                                                                    aria-hidden="true"></i> {{ __('levels.view') }}
                                                                </a>
                                                            @endif
                                                            @if (hasPermission('assets_update') == true)
                                                                <a href="{{ route('asset.edit', $asset->id) }}" class="dropdown-item"><i class="fas fa-edit"
                                                                    aria-hidden="true"></i> {{ __('levels.edit') }}
                                                                </a>
                                                            @endif
                                                            @if (hasPermission('assets_delete') == true)
                                                                <form id="delete" value="Test" action="{{ route('asset.delete', $asset->id) }}" method="POST" data-title="{{ __('delete.asset') }}">
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
                        <span>{{ $assets->links() }}</span>
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
            <!-- end table  -->
        </div>
    </div>
    <!-- end wrapper  -->
@endsection()
