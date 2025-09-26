@extends('frontend.layouts.master')

@section('title', __('levels.register'))

@section('content')
<!-- signup form  -->
<section class="py-5 bg-light">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8 col-md-9">
        <div class="card shadow-sm">
          <div class="card-body p-4">
            <div class="text-center mb-3">
              <a href="{{ url('/') }}" class="navbar-brand d-inline-flex align-items-center justify-content-center">
                <img src="{{ optional(settings())->logo_image ?? static_asset('images/default/logo1.png') }}" alt="Logo" height="48"/>
              </a>
              <h1 class="h5 mt-3 mb-0">{{ __('levels.register') }}</h1>
              <p class="text-muted small mb-0">{{ __('messages.create_account') ?? 'Create your customer account' }}</p>
            </div>
            <form method="POST" action="{{ route('customer.sign-up-store') }}" novalidate>
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">{{ __('Full Name') }} *</label>
                                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus placeholder="Enter your full name">
                                    @error('name')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">{{ __('E-Mail Address') }} *</label>
                                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="Enter your email">
                                    @error('email')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">{{ __('Phone Number') }}</label>
                                    <input id="phone" type="tel" inputmode="tel" pattern="[0-9+\-\s()]{6,}" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone') }}" autocomplete="tel" placeholder="e.g. +254700000000">
                                    <div class="form-text">{{ __('Use international format (E.164), e.g. +254700000000') }}</div>
                                    @error('phone')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">{{ __('Password') }} *</label>
                                    <div class="input-group">
                                      <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" placeholder="••••••••" minlength="8">
                                      <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Show password"><i class="fa fa-eye"></i></button>
                                    </div>
                                    <div class="progress mt-2" style="height:6px">
                                      <div id="pwdMeter" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    @error('password')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password_confirmation" class="form-label">{{ __('Confirm Password') }} *</label>
                                    <div class="input-group">
                                      <input id="password_confirmation" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••">
                                      <button class="btn btn-outline-secondary" type="button" id="togglePassword2" aria-label="Show password"><i class="fa fa-eye"></i></button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="address" class="form-label">{{ __('Address') }}</label>
                                    <textarea id="address" class="form-control @error('address') is-invalid @enderror" name="address" rows="3" placeholder="Enter your address">{{ old('address') }}</textarea>
                                    @error('address')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                          <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">
                              {{ __('I agree to the') }} <a href="{{ route('termsof.condition.index') }}" target="_blank">{{ __('Terms of Service') }}</a> {{ __('and') }} <a href="{{ route('privacy.policy.index') }}" target="_blank">{{ __('Privacy Policy') }}</a>
                            </label>
                          </div>
                        </div>

                        <div class="d-grid">
                          <button type="submit" class="btn btn-primary">{{ __('levels.register') }}</button>
                        </div>
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
<!-- end signup form  -->
@endsection

@push('scripts')
<script>
  function scorePassword(p) {
    let score = 0;
    if (!p) return 0;
    const letters = {};
    for (let i=0; i<p.length; i++) {
      letters[p[i]] = (letters[p[i]] || 0) + 1;
      score += 5.0 / letters[p[i]];
    }
    const variations = {
      digits: /\d/.test(p),
      lower: /[a-z]/.test(p),
      upper: /[A-Z]/.test(p),
      nonWords: /[^\w]/.test(p)
    };
    let variationCount = 0;
    for (const check in variations) {
      variationCount += (variations[check] === true) ? 1 : 0;
    }
    score += (variationCount - 1) * 10;
    return parseInt(score);
  }
  function meterColor(score){
    if (score > 80) return 'bg-success';
    if (score > 60) return 'bg-info';
    if (score > 40) return 'bg-warning';
    return 'bg-danger';
  }
  document.addEventListener('DOMContentLoaded', function(){
    const pwd = document.getElementById('password');
    const pwd2 = document.getElementById('password_confirmation');
    const meter = document.getElementById('pwdMeter');
    const t1 = document.getElementById('togglePassword');
    const t2 = document.getElementById('togglePassword2');
    function updateMeter(){
      const score = Math.min(scorePassword(pwd.value), 100);
      meter.style.width = score + '%';
      meter.className = 'progress-bar ' + meterColor(score);
    }
    if (pwd && meter) {
      pwd.addEventListener('input', updateMeter);
    }
    if (t1 && pwd) {
      t1.addEventListener('click', function(){
        const is = pwd.getAttribute('type') === 'password';
        pwd.setAttribute('type', is ? 'text' : 'password');
        this.innerHTML = is ? '<i class="fa fa-eye-slash"></i>' : '<i class="fa fa-eye"></i>';
      });
    }
    if (t2 && pwd2) {
      t2.addEventListener('click', function(){
        const is = pwd2.getAttribute('type') === 'password';
        pwd2.setAttribute('type', is ? 'text' : 'password');
        this.innerHTML = is ? '<i class="fa fa-eye-slash"></i>' : '<i class="fa fa-eye"></i>';
      });
    }
  });
</script>
@endpush
