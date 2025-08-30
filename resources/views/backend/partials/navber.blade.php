<!-- navbar -->
<nav class="navbar navbar-expand-lg center-nav transparent navbar-light p-3 fixed-top">
    <div class="container flex-lg-row flex-nowrap align-items-center">
        <div class=" d-none d-lg-block navbar-collapse text-bg-dark"  > 
            <div class="ms-lg-auto d-flex flex-column h-100 w-90">
                <div class="dashboard-header">
                    <nav class="navbar navbar-light  fixed-top   ">
                        <a class="navbar-brand" href="{{ url('/dashboard') }}">
                            <img src="{{ settings()->logo_image }}" class="logo" />
                        </a> 
                        <div class=" navbar-collapse" id="navbarSupportedContent">
                            <ul class="navbar-nav ml-auto navbar-right-top">
                                <li class="nav-item lang">
                                    <div class="form-group col-12 pt-1">
                                        <div class="dropdown lang-dropdown">
                                           @include('backend.partials.language')
                                        </div>
                                    </div>
                                </li>
                           
                                <li class="nav-item dropdown admin-panel notification  d-lg-block">
                                    <a href="{{ url('/') }}" target="_blank" class="me-2"><i class="fa fa-globe navbar-globe"></i></a>
                                </li>

                                <li class="nav-item dropdown admin-panel notification d-lg-block">
                                    <a class="nav-link nav-icons mt-md-3" href="#" id="navbarDropdownMenuLink1"
                                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i
                                            class="fas fa-fw fa-bell"></i> <span class="indicator"></span></a>
                                    <ul class="dropdown-menu dropdown-menu-right notification-dropdown">
                                        <li>
                                            <div class="notification-title"> Notification</div>
                                            <div class="notification-list">
                                                <div class="list-group">
                                                    @include('backend.partials.notification')
                                                </div>
                                            </div>
                                        </li>
                                    </ul>
                                </li>
                                 
                                @if (hasPermission('todo_create') == true)
                                    <li class="nav-item dropdown connection mt-xl-2 mt-md-2 mt-lg-2 d-lg-block">
                                        <label id="todoModal1" data-target="#todoModal"
                                            class="btn btn-primary btn-sm mr-2" data-toggle="modal"
                                            data-url="{{ route('todo.modal') }}"><i class="fa fa-edit"></i>
                                            {{ __('to_do.to_do') }}</label>
                                    </li>
                                @endif
                             
                                <li class="nav-item dropdown nav-user d-lg-block"> 
                                    @include('backend.partials.profile_menu') 
                                </li>
                            </ul>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
        <div class="navbar-other w-100 d-flex justify-content-between ">
            <div class="d-lg-none">
                <a href="{{ url('/') }}">
                    <img src="{{ settings()->logo_image }}" style="margin-top: 10px" width="150"
                        alt="Logo">
                </a>
            </div>
            <ul class="navbar-nav flex-row align-items-center ">
                <li class="nav-item dropdown admin-panel notification  d-lg-none">
                    <a href="{{ url('/') }}" class="me-2"><i class="fa fa-globe"></i></a>
                </li>
                <li class="nav-item dropdown admin-panel notification  d-lg-none">
                    <a class="nav-link nav-icons mt-md-3" href="#" id="navbarDropdownMenuLink1"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i
                            class="fas fa-fw fa-bell"></i> <span
                            class="mobile-notification indicator admin"></span></a>
                    <ul class="dropdown-menu dropdown-menu-right notification-dropdown">
                        <li>
                            <div class="notification-title"> Notification</div>
                            <div class="notification-list">
                                <div class="list-group">
                                   @include('backend.partials.notification')
                                </div>
                            </div>
                        </li>
                    </ul>
                </li>
                
                @if (hasPermission('todo_create') == true)
                    <li class="nav-item dropdown connection mt-lg-3 d-lg-none">
                        <label id="todoModal1" data-target="#todoModal" class="btn btn-primary btn-sm mr-2"
                            data-toggle="modal" data-url="{{ route('todo.modal') }}"><i class="fa fa-edit"></i>
                            {{ __('to_do.to_do') }}</label>
                    </li>
                @endif
               
                <li class="nav-item dropdown nav-user mobile d-lg-none">
                   @include('backend.partials.profile_menu')
                </li>
                <li class="nav-item d-lg-none">
                    <button class="offcanvas-nav-btn" type="button" data-bs-toggle="offcanvas"
                        data-bs-target="#offcanvasDarkNavbar" aria-controls="offcanvasDarkNavbar"><span
                            class="navbar-toggler-icon"></span></button>
                </li>
            </ul>
        </div>
    </div>
</nav>
 

@include('backend.todo.to_do_list')
