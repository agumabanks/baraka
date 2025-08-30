@extends('backend.partials.master')
@section('title')
    {{ __('levels.maintenance') }} {{ __('levels.list') }}
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
                                <li class="breadcrumb-item"><a href="#" class="breadcrumb-link">{{ __('menus.asset_management') }}</a></li>
                                <li class="breadcrumb-item"><a href="" class="breadcrumb-link">{{ __('levels.maintenances') }}</a></li>
                                <li class="breadcrumb-item"><a href="" class="breadcrumb-link active">{{ __('levels.list') }}</a></li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <!-- end pageheader -->
        <div class="row">
            <!-- data table  -->
            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
                <div class="card">
                    <div class="row pl-4 pr-4 pt-4">
                        <div class="col-6">
                            <p class="h3">{{ __('levels.maintenances') }}</p>
                        </div>
                        @if (hasPermission('maintenance_create') == true)
                            <div class="col-6">
                                <a href="{{ route('maintenance.create') }}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i class="fa fa-plus"></i></a>
                            </div>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" style="width:100%">
                                <thead>
                                    <tr> 
                                        <th>{{ __('levels.id') }}</th> 
                                        <th>{{ __('asset.asset') }}</th>
                                        <th>{{ __('levels.start_date') }}</th>
                                        <th>{{ __('levels.end_date') }}</th>
                                        <th>{{ __('levels.due_days') }}</th> 
                                        <th>{{ __('levels.repair_details') }}</th>
                                        <th>{{ __('levels.spare_parts_purchased_details') }}</th> 
                                        <th>{{ __('levels.invoice_of_the_purchases') }}</th> 
                                        @if (hasPermission('maintenance_update') == true || hasPermission('maintenance_delete') == true)
                                            <th>{{ __('levels.actions') }}</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $i=1; @endphp
                                    @foreach ($maintenances as $maintenance)
                                        <tr>
                                            <td>{{ $i++ }}</td>
                                            <td>{{ @$maintenance->asset->name }}</td>
                                            <td>{{ $maintenance->start_date }}</td>
                                            <td>{{ $maintenance->end_date }}</td>
                                            <td>{{ $maintenance->DueDays['due_days'] }} Due Days / {{ $maintenance->DueDays['total_days'] }} Days</td> 
                                            <td>{{ $maintenance->repair_details }}</td>
                                            <td>{{ $maintenance->spare_parts_purchased_details }}</td>
                                            <td><a href="{{ $maintenance->MyInvoiceOfThePurchases }}" download="">Download</a></td>  
                                            @if (hasPermission('maintenance_update') == true || hasPermission('maintenance_delete') == true)
                                                <td>
                                                    <div class="row">
                                                        <button tabindex="-1" data-toggle="dropdown" type="button"
                                                            class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span
                                                                class="sr-only">Toggle Dropdown</span></button>
                                                        <div class="dropdown-menu">
                                                            @if (hasPermission('maintenance_update') == true)
                                                                <a href="{{ route('maintenance.edit', $maintenance->id) }}"
                                                                    class="dropdown-item"><i class="fas fa-edit"
                                                                        aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                                            @endif
                                                            @if (hasPermission('maintenance_delete') == true)
                                                                <form id="delete" value="Test"
                                                                    action="{{ route('maintenance.delete', $maintenance->id) }}"
                                                                    method="POST" data-title="{{ __('delete.maintenance') }}">
                                                                    @method('DELETE')
                                                                    @csrf
                                                                    <input type="hidden" name=""
                                                                        value="{{ __('levels.maintenance') }}" id="deleteTitle">
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
                        <span>{{ $maintenances->links() }}</span>
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
            <!-- end data table  -->
        </div>
    </div>
    <!-- end wrapper  -->
@endsection()
 