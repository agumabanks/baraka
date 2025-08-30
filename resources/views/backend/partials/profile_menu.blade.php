<a class="nav-link nav-user-img" href="#" id="navbarDropdownMenuLink2"
data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
<img src="{{ Auth::user()->image }}" alt="" class="user-avatar-md rounded-circle">
</a>
<div class="dropdown-menu dropdown-menu-right nav-user-dropdown"
aria-labelledby="navbarDropdownMenuLink2">
<div class="nav-user-info">
    <h5 class="mb-0 text-white nav-user-name">{{ Auth::user()->name }}</h5>
</div>
<a class="dropdown-item" href="{{ route('profile.index', Auth::user()->id) }}"><i
        class="fas fa-user mr-2"></i>{{ __('menus.profile') }}</a>
<a class="dropdown-item" href="{{ route('password.change', Auth::user()->id) }}"><i
        class="fas fa-key mr-2"></i>{{ __('menus.change_password') }}</a>

<a class="dropdown-item" href="{{ route('logout') }}"
    onclick="event.preventDefault();
            document.getElementById('logout-form').submit();">
    <i class="fas fa-power-off mr-2"></i>
    {{ __('menus.logout') }}
</a>
<form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
    @csrf
</form>
</div>