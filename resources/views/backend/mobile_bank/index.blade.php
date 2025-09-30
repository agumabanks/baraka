@extends('backend.partials.master')
@section('title')
    {{ __('mobileBank.title') }} {{ __('levels.list') }}
@endsection
@section('maincontent')
    <div class="container-fluid  dashboard-content">
        <!-- page header -->
        <div class="row">
            <div class="col-12">
                <div class="page-header">
                    <div class="page-breadcrumb">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}"
                                        class="breadcrumb-link">{{ __('menus.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('mobile-bank.index') }}"
                                        class="breadcrumb-link">{{ __('mobileBank.title') }}</a></li>
                                <li class="breadcrumb-item"><a href="#"
                                        class="breadcrumb-link active">{{ __('levels.list') }}</a></li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <!-- end page header -->
        <div class="row">
            <!-- data table  -->
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('mobile-bank.filter') }}" method="GET">
                            <div class="row d-flex align-items-center">
                                <label for="name">{{ __('mobileBank.name') }}</label>
                                <div class="form-group col-12 col-sm-6 col-md-4 col-lg-4 col-xl-2">
                                    <input type="text" autocomplete="off" id="name" name="name"
                                        placeholder="{{ __('mobileBank.name') }}" class="form-control"
                                        value="{{ old('name', request('name')) }}">
                                    @error('name')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-12 col-sm-6 col-md-4 col-lg-4 col-xl-2 pt-1 pl-0">
                                    <div class="col-12 d-flex justify-content text-right">
                                        <button type="submit" class="btn btn-sm btn-space btn-primary"><i
                                                class="fa fa-filter"></i> {{ __('levels.filter') }}</button>
                                        <a href="{{ route('mobile-bank.index') }}" class="btn btn-sm btn-space btn-secondary"><i
                                                class="fa fa-eraser"></i> {{ __('levels.clear') }}</a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <div class="row">
                                <div class="col-12 d-flex justify-content-between">
                                    <h4 class="mb-0">{{ __('mobileBank.title') }}</h4>
                                    <a href="{{ route('mobile-bank.create') }}" class="btn btn-primary btn-sm"
                                        data-toggle="tooltip" data-placement="top" title="{{ __('levels.add') }}"><i
                                            class="fa fa-plus"></i></a>
                                </div>
                            </div>
                            <table id="table" class="table    parcelTable" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>{{ __('###') }}</th>
                                        <th>{{ __('mobileBank.name') }}</th>
                                        <th>{{ __('levels.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $i = 1;
                                    @endphp
                                    @foreach ($mobile_banks as $mobile_bank)
                                        <tr>
                                            <td>{{ $i++ }}</td>
                                            <td>{{ $mobile_bank->name }}</td>
                                            <td>
                                                <div class="row">
                                                    <button tabindex="-1" data-bs-toggle="dropdown" type="button"
                                                        class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span
                                                            class="sr-only">Toggle Dropdown</span></button>
                                                    <div class="dropdown-menu">
                                                        <a href="{{ route('mobile-bank.edit', $mobile_bank->id) }}"
                                                            class="dropdown-item"><i class="fas fa-edit"
                                                                aria-hidden="true"></i> {{ __('levels.edit') }}</a>
                                                        <form id="delete" value="Test"
                                                            action="{{ route('mobile-bank.destroy', $mobile_bank->id) }}" method="POST"
                                                            data-title="{{ __('delete.mobile-bank') }}">
                                                            @method('DELETE')
                                                            @csrf
                                                            <input type="hidden" name="" value="MobileBank"
                                                                id="deleteTitle">
                                                            <button type="submit" class="dropdown-item"><i
                                                                    class="fa fa-trash" aria-hidden="true"></i>
                                                                {{ __('levels.delete') }}</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                        </div>
                    </div>
                    <div class="col-12 mt-3">
                        <div class="table-responsive">
                            <span>{{ $mobile_banks->links() }}</span>
                            <p class="p-2 small">
                                {!! __('Showing') !!}
                                <span class="font-medium">{{ $mobile_banks->firstItem() }}</span>
                                {!! __('to') !!}
                                <span class="font-medium">{{ $mobile_banks->lastItem() }}</span>
                                {!! __('of') !!}
                                <span class="font-medium">{{ $mobile_banks->total() }}</span>
                                {!! __('results') !!}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end data table  -->
        </div>
    </div>
    <!-- end wrapper  -->
@endsection()
