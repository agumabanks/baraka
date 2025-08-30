@extends('backend.partials.master')
@section('title')
    {{ __('bank.add_bank') }}
@endsection
@section('maincontent')
    <div class="container-fluid dashboard-content">
        <!-- page header -->
        <div class="row">
            <div class="col-12">
                <div class="page-header">
                    <div class="page-breadcrumb">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}"
                                        class="breadcrumb-link">{{ __('menus.dashboard') }}</a></li>
                                <li class="breadcrumb-item" ><a href="{{ route('bank.index') }}"
                                        class="breadcrumb-link" >{{ __('bank.bank') }}</a></li>
                                <li class="breadcrumb-item" ><a href="#"
                                        class="breadcrumb-link active" >{{ __('bank.add_bank') }}</a></li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <!-- end page header -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">{{ __('bank.add_bank') }}</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('bank.store') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="name">{{ __('bank.name') }}</label>
                                <input type="text" name="name" id="name" class="form-control" placeholder="{{ __('bank.name') }}" value="{{ old('name') }}" required>
                                @error('name')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('levels.save') }}</button>
                            <a href="{{ route('bank.index') }}" class="btn btn-secondary">{{ __('levels.cancel') }}</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
