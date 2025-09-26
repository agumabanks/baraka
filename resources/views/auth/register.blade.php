@extends('frontend.layouts.master')
@section('title', __('levels.register'))
@section('content')
<section class="py-5 bg-light">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-6 col-md-8">
        <div class="card shadow-sm">
          <div class="card-body p-4">
            <div class="text-center mb-3">
              <a href="{{ url('/') }}" class="navbar-brand d-inline-flex align-items-center justify-content-center">
                <img src="{{ optional(settings())->logo_image ?? static_asset('images/default/logo1.png') }}" alt="Logo" height="48"/>
              </a>
              <h1 class="h5 mt-3 mb-0">{{ __('levels.register') }}</h1>
              <p class="text-muted small mb-0">{{ __('messages.create_account') ?? 'Create your account' }}</p>
            </div>

            <form method="POST" action="{{ route('register') }}" novalidate>
              @csrf
              <div class="mb-3">
                <label for="name" class="form-label">{{ __('Name') }}</label>
                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus placeholder="{{ __('Username') }}">
                @error('name')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="mb-3">
                <label for="email" class="form-label">{{ __('E-Mail Address') }}</label>
                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="name@example.com">
                @error('email')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              <div class="mb-3">
                <label for="password" class="form-label">{{ __('Password') }}</label>
                <div class="input-group">
                  <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" placeholder="••••••••">
                  <button class="btn btn-outline-secondary" type="button" id="togglePasswordReg" aria-label="Show password"><i class="fa fa-eye"></i></button>
                  @error('password')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>
              </div>

              <div class="mb-3">
                <label for="password-confirm" class="form-label">{{ __('Confirm Password') }}</label>
                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••">
              </div>

              <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="terms" required>
                <label class="form-check-label" for="terms">{{ __('By creating an account, you agree to the') }} <a href="{{ route('privacy.policy.index') }}">{{ __('terms and privacy policy') }}</a></label>
              </div>

              <button class="btn btn-primary w-100" type="submit">{{ __('levels.register') }}</button>
            </form>
          </div>
          <div class="card-footer bg-white text-center py-3">
            <span class="small text-muted">{{ __('levels.already_member') ?? 'Already a member?' }}</span>
            <a href="{{ route('login') }}" class="ms-1">{{ __('levels.login') }}</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('togglePasswordReg');
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
