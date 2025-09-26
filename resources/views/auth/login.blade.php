@extends('frontend.layouts.master')
@section('title', __('levels.login'))
@section('content')
<section class="py-5 bg-light">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-5 col-md-7">
        <div class="card shadow-sm">
          <div class="card-body p-4">
            <div class="text-center mb-3">
              <a href="{{ url('/') }}" class="navbar-brand d-inline-flex align-items-center justify-content-center">
                <img class="logo-img" src="{{ optional(settings())->logo_image ?? static_asset('images/default/logo1.png') }}" alt="logo" height="48">
              </a>
              <h1 class="h5 mt-3 mb-0">{{ __('levels.login') }}</h1>
              <p class="text-muted small mb-0">{{ __('messages.sign_in_to_continue') ?? 'Please enter your credentials to continue.' }}</p>
            </div>

            <form method="POST" action="{{ route('login') }}" novalidate>
              @csrf
              <div class="mb-3">
                <label for="email" class="form-label">{{ __('E-Mail Address') }}</label>
                <input id="email" type="text" class="form-control @error('email') is-invalid @enderror" name="email" required autocomplete="email" autofocus placeholder="{{ __('Enter Email or Mobile') }}" value="{{ old('email') }}">
                @error('email')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center">
                  <label for="password" class="form-label mb-0">{{ __('Password') }}</label>
                  <a href="{{ route('password.request') }}" class="small">{{ __('Forgot Password') }}</a>
                </div>
                <div class="input-group">
                  <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="••••••••">
                  <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Show password"><i class="fa fa-eye"></i></button>
                  @error('password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>
                <div class="form-check mt-2">
                  <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                  <label class="form-check-label" for="remember">{{ __('Remember Me') }}</label>
                </div>
              </div>

              <button type="submit" class="btn btn-primary w-100">{{ __('levels.login') }}</button>

              @if(globalSettings('facebook_status') || globalSettings('google_status'))
                <div class="text-center my-3 small text-muted">{{ __('levels.or') ?? 'OR' }}</div>
                <div class="row g-2">
                  @if(globalSettings('facebook_status') == App\Enums\Status::ACTIVE)
                    <div class="col-12 col-sm-6">
                      <a href="{{ route('social.login','facebook') }}" class="btn w-100 btn-primary" type="button"><i class="fab fa-facebook me-1"></i> Facebook</a>
                    </div>
                  @endif
                  @if(globalSettings('google_status') == App\Enums\Status::ACTIVE)
                    <div class="col-12 col-sm-6">
                      <a href="{{ route('social.login','google') }}" class="btn w-100 btn-outline-secondary" type="button"><i class="fab fa-google me-1"></i> Google</a>
                    </div>
                  @endif
                </div>
              @endif
            </form>
          </div>
          <div class="card-footer bg-white text-center py-3">
            <span class="small text-muted">{{ __('levels.no_account') ?? 'Don\'t have an account?' }}</span>
            <a href="{{ route('customer.sign-up') }}" class="ms-1">{{ __('levels.register') }}</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

@push('styles')
<style>
  .logo-img { max-height: 48px; }
</style>
@endpush

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('togglePassword');
    const input = document.getElementById('password');
    if (toggle && input) {
      toggle.addEventListener('click', function(){
        const is = input.getAttribute('type') === 'password';
        input.setAttribute('type', is ? 'text' : 'password');
        this.innerHTML = is ? '<i class="fa fa-eye-slash"></i>' : '<i class="fa fa-eye"></i>';
      });
    }
  });
</script>
@endpush
@endsection
