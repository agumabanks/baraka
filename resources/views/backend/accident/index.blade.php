@extends('backend.partials.master')
@section('title')
    {{ __('levels.accidents') }} {{ __('levels.list') }}
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
                                <li class="breadcrumb-item"><a href="" class="breadcrumb-link">{{ __('levels.accidents') }}</a></li>
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
                            <p class="h3">{{ __('levels.accidents') }}</p>
                        </div>
                        @if (hasPermission('accidents_create') == true)
                            <div class="col-6">
                                <a href="{{ route('accident.create') }}" class="btn btn-primary btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i class="fa fa-plus"></i></a>
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
                                        <th>{{ __('levels.date_of_accident') }}</th>
                                        <th>{{ __('levels.driver_responsible') }}</th>
                                        <th>{{ __('levels.cost_of_repair') }}</th> 
                                        <th>{{ __('levels.spare_parts') }}</th> 
                                        <th>{{ __('levels.upload_documents') }}</th> 
                                        @if (hasPermission('accidents_update') == true || hasPermission('accidents_delete') == true)
                                            <th>{{ __('levels.actions') }}</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $i=1; @endphp
                                    @foreach ($accidents as $accident)
                                        <tr>
                                            <td>{{ $i++ }}</td>
                                            <td>{{ @$accident->asset->name }}</td> 
                                            <td>{{ $accident->date_of_accident }}</td>
                                            <td>{{ @$accident->driver->user->name }}</td>
                                            <td>{{ @currency($accident->cost_of_repair) }}</td>
                                            <td>{{ $accident->spare_parts }}</td>
                                            <td>
                                                @foreach ($accident->Documents as $document)
                                                    <a href="{{ @$document }}" download="">{{ $loop->index+1 }}. Download</a><br/>
                                                @endforeach
                                            </td>  
                                            @if (hasPermission('accidents_update') == true || hasPermission('accidents_delete') == true)
                                                <td>
                                                    <div class="row">
                                                        <button tabindex="-1" data-bs-toggle="dropdown" type="button"
                                                            class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span
                                                                class="sr-only">Toggle Dropdown</span></button>
                                                        <div class="dropdown-menu">
                                                            @if (hasPermission('accidents_update') == true)
                                                                <a href="{{ route('accident.edit', $accident->id) }}"
                                                                    class="dropdown-item"><i class="fas fa-edit"
                                                                        aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                                            @endif
                                                            @if (hasPermission('accidents_delete') == true)
                                                                <form id="delete" value="Test"
                                                                    action="{{ route('accident.delete', $accident->id) }}"
                                                                    method="POST" data-title="{{ __('delete.accident') }}">
                                                                    @method('DELETE')
                                                                    @csrf
                                                                    <input type="hidden" name=""
                                                                        value="{{ __('levels.accidents') }}" id="deleteTitle">
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
                        <span>{{ $accidents->links() }}</span>
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
            <!-- end data table  -->
        </div>
    </div>
    <!-- end wrapper  -->
@endsection()
 