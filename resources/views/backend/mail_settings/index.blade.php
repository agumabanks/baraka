@extends('backend.partials.master')
@section('title')
   {{ __('menus.mail_settings') }}
@endsection
@section('maincontent')
<div class="container-fluid  dashboard-content">
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}" class="breadcrumb-link">{{ __('menus.dashboard') }}</a></li>
                            <li class="breadcrumb-item"><a href="#" class="breadcrumb-link active">{{ __('menus.settings') }}</a></li>
                            <li class="breadcrumb-item"><a href="{{route('googlemap-settings.index')}}" class="breadcrumb-link active">{{ __('menus.google_map_settings') }}</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h2 class="h3 mb-3 pageheader-title">{{__('menus.mail_settings')}}</h2>
                    <form action="{{route('mail-settings.update')}}"  method="POST" id="basicform">
                        @method('PUT')
                        @csrf
                        <div > 
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="mail_mailer">{{ __('levels.mail_mailer') }}</label> <span class="text-danger">*</span>
                                    <input id="mail_mailer" type="text" name="mail_mailer" data-parsley-trigger="change" placeholder="" autocomplete="off" class="form-control @error('mail_mailer') is-invalid @enderror" value="{{old('mail_mailer',env('MAIL_MAILER')) }}" require>
                                    @error('mail_mailer')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="mail_host">{{ __('levels.mail_host') }}</label> <span class="text-danger">*</span>
                                    <input id="mail_host" type="text" name="mail_host" data-parsley-trigger="change" placeholder="" autocomplete="off" class="form-control @error('mail_host') is-invalid @enderror" value="{{old('mail_host',env('MAIL_HOST')) }}" require>
                                    @error('mail_host')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="mail_port">{{ __('levels.mail_port') }}</label> <span class="text-danger">*</span>
                                    <input id="mail_port" type="text" name="mail_port" data-parsley-trigger="change" placeholder="" autocomplete="off" class="form-control @error('mail_port') is-invalid @enderror" value="{{old('mail_port',env('MAIL_PORT')) }}" require>
                                    @error('mail_port')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="mail_username">{{ __('levels.mail_username') }}</label> <span class="text-danger">*</span>
                                    <input id="mail_username" type="text" name="mail_username" data-parsley-trigger="change" placeholder="" autocomplete="off" class="form-control @error('mail_username') is-invalid @enderror" value="{{old('mail_username',env('MAIL_USERNAME')) }}" require>
                                    @error('mail_username')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="mail_from_name">{{ __('levels.mail_from_name') }}</label> <span class="text-danger">*</span>
                                    <input id="mail_from_name" type="text" name="mail_from_name" data-parsley-trigger="change" placeholder="" autocomplete="off" class="form-control @error('mail_from_name') is-invalid @enderror" value="{{old('mail_from_name',env('MAIL_FROM_NAME')) }}" require>
                                    @error('mail_from_name')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="mail_from_address">{{ __('levels.mail_from_address') }}</label> <span class="text-danger">*</span>
                                    <input id="mail_from_address" type="text" name="mail_from_address" data-parsley-trigger="change" placeholder="" autocomplete="off" class="form-control @error('mail_from_address') is-invalid @enderror" value="{{old('mail_from_address',env('MAIL_FROM_ADDRESS')) }}" require>
                                    @error('mail_from_address')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="mail_password">{{ __('levels.mail_password') }}</label> <span class="text-danger">*</span>
                                    <input id="mail_password" type="text" name="mail_password" data-parsley-trigger="change" placeholder="" autocomplete="off" class="form-control @error('mail_password') is-invalid @enderror" value="{{old('mail_password',env('MAIL_PASSWORD')) }}" require>
 
                                    @error('mail_password')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="mail_encryption">{{ __('levels.mail_encryption') }}</label> <span class="text-danger">*</span> 
                                    <select name="mail_encryption" class="form-control" >
                                        <option value="tls" @selected(env('MAIL_ENCRYPTION') == 'tls')>TLS</option>
                                        <option value="ssl" @selected(env('MAIL_ENCRYPTION') == 'ssl')>SSL</option>
                                    </select>

                                    @error('mail_encryption')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                            <div class="row pt-4">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-space btn-primary">{{ __('levels.save_change') }}</button>
                                </div>
                            </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <form action="{{route('mail-settings.test-mail')}}"  method="GET" id="basicform">  
                        <div> 
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label for="email">{{ __('levels.email') }}</label> <span class="text-danger">*</span>
                                    <input id="email" type="text" name="email" data-parsley-trigger="change" placeholder="Enter your email" autocomplete="off" class="form-control @error('email') is-invalid @enderror" value="{{old('email') }}" require>
                                    @error('email')
                                        <small class="text-danger mt-2">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    
                                    <button type="submit" class="btn btn-primary mt-4">Send Test mail</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection()

