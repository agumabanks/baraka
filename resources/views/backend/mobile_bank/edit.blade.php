@extends('backend.partials.master')
@section('title')
    {{ __('mobileBank.edit_mobile_bank') }}
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
                                <li class="breadcrumb-item"><a href="{{ route('mobile-bank.index') }}"
                                        class="breadcrumb-link">{{ __('mobileBank.title') }}</a></li>
                                <li class="breadcrumb-item"><a href="#"
                                        class="breadcrumb-link active">{{ __('mobileBank.edit_mobile_bank') }}</a></li>
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
                        <h4 class="mb-0">{{ __('mobileBank.edit_mobile_bank') }}</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('mobile-bank.update', $mobile_bank->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="form-group">
                                <label for="name">{{ __('mobileBank.name') }}</label>
                                <input type="text" name="name" id="name" class="form-control" placeholder="{{ __('mobileBank.name') }}" value="{{ old('name', $mobile_bank->name) }}" required>
                                @error('name')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('levels.update') }}</button>
                            <a href="{{ route('mobile-bank.index') }}" class="btn btn-secondary">{{ __('levels.cancel') }}</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
