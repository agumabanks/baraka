<!-- left sidebar -->
<div class="col-12 ">
    <nav class="navbar navbar-expand-lg center-nav transparent navbar-light p-0 fixed-top sidebarNavigation">

        <div class="navbar-collapse offcanvas offcanvas-nav offcanvas-start text-bg-dark " tabindex="-1"
            id="offcanvasDarkNavbar" aria-labelledby="offcanvasDarkNavbarLabel">

            <div class="offcanvas-header w-90 ">
                <a class="navbar-brand" href="{{ url('/dashboard') }}">
                    <img src="{{ settings()->logo_image }}" class="logo" />
                </a>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
                    aria-label="Close"></button>
            </div>

            <div class="offcanvas-body ms-lg-auto d-flex flex-column h-100 w-90 mt-0 pt-0">
                <nav class="navbar navbar-expand-lg navbar-light fixed-top   ">
                    <div class="dropdown lang-dropdown navbar_menus changeLocale mobileLocale m-0 ">
                        @include('backend.partials.language')
                    </div>
                </nav>
                <div class="nav-left-sidebar sidebar-dark navbar-expand-lg ">
                    <ul class="navbar-nav">
                        <li class="nav-divider>
                            {{ __('menus.menu') }}
                        </li>
                        <li class="nav-item ">
                            @if (hasPermission('dashboard_read') == true)
                                <a class="nav-link {{ request()->is('/dashboard*') ? 'active' : '' }}"
                                    href="{{ url('/dashboard') }}" aria-expanded="false" data-target="#submenu-1"
                                    aria-controls="submenu-1"><i class="fa fa-home"></i>{{ __('menus.dashboard') }}</a>
                            @endif
                        </li>
                        @if (hasPermission('delivery_man_read') == true)
                            <li class="nav-item ">
                                <a class="nav-link {{ request()->is('admin/deliveryman*') ? 'active' : '' }}"
                                    href="{{ route('deliveryman.index') }}" aria-expanded="false"
                                    data-target="#submenu-1" aria-controls="submenu-1"><i
                                        class="fa fa-people-carry"></i>{{ __('menus.deliveryman') }}</a>
                            </li>
                        @endif
                        @if (hasPermission('hub_read') == true || hasPermission('hub_payment_read') == true)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/hubs*', 'admin/request/hub/payment*', 'admin/hub/incharge*', 'admin/hub/view*') ? 'active' : '' }}"
                                    href="#" data-toggle="collapse" aria-expanded="false"
                                    data-target="#hub-manage" aria-controls="hub-manage"><i
                                        class="fas fa-warehouse"></i>{{ __('menus.hub_mange') }}</a>
                                <div id="hub-manage"
                                    class="{{ request()->is('admin/hubs*', 'admin/request/hub/payment*', 'admin/hub/incharge*', 'admin/hub/view*') ? '' : 'collapse' }} submenu">
                                    <ul class="nav flex-column">
                                        @if (hasPermission('hub_read') == true)
                                            <li class="nav-item ">
                                                <a class="nav-link {{ request()->is('admin/hubs*', 'admin/hub*') ? 'active' : '' }}"
                                                    href="{{ route('hubs.index') }}">{{ __('menus.hubs') }}</a>
                                            </li>
                                        @endif
                                        @if (hasPermission('hub_payment_read') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/request/hub/payment*') ? 'active' : '' }}"
                                                    href="{{ route('hub.hub-payment.index') }}">{{ __('menus.payments') }}</a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </li>
                        @endif

                        @if (hasPermission('merchant_read') == true || hasPermission('payment_read') == true)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/merchant/*', 'admin/payment*') ? 'active' : '' }}"
                                    href="#" data-toggle="collapse" aria-expanded="false"
                                    data-target="#merchant-manage" aria-controls="merchant-manage"><i
                                        class="fas fa-users"></i>{{ __('menus.merchant_manage') }}</a>
                                <div id="merchant-manage"
                                    class="{{ request()->is('admin/merchant*', 'admin/payment*') ? '' : 'collapse' }} submenu">
                                    <ul class="nav flex-column">
                                        @if (hasPermission('merchant_read') == true)
                                            <li class="nav-item ">
                                                <a class="nav-link {{ request()->is('admin/merchant*') ? 'active' : '' }}"
                                                    href="{{ route('merchant.index') }}" aria-expanded="false"
                                                    data-target="#submenu-1"
                                                    aria-controls="submenu-1">{{ __('menus.merchants') }}</a>
                                            </li>
                                        @endif
                                        @if (hasPermission('payment_read') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/payment*') ? 'active' : '' }}"
                                                    href="{{ route('merchant.manage.payment.index') }}">{{ __('menus.payments') }}</a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </li>
                        @endif

                        @if (hasPermission('todo_read') == true)
                            <li class="nav-item ">
                                <a class="nav-link {{ request()->is('admin/todo/todo_list*') ? 'active' : '' }}"
                                    href="{{ route('todo.index') }}" aria-expanded="false" data-target="#hubs"
                                    aria-controls="hubs"><i class="fas fa-tasks"></i>{{ __('menus.todo_list') }}</a>
                            </li>
                        @endif


                        @if (hasPermission('support_read') == true)
                            <li class="nav-item ">
                                <a class="nav-link {{ request()->is('admin/support*') ? 'active' : '' }}"
                                    href="{{ route('support.index') }}" aria-expanded="false" data-target="#hubs"
                                    aria-controls="hubs"><i class="fa fa-comments"></i>{{ __('menus.support') }}</a>
                            </li>
                        @endif



                        @if (hasPermission('parcel_read') == true)
                            <li class="nav-item ">
                                <a class="nav-link {{ request()->is('admin/parcel*') ? 'active' : '' }}"
                                    href="{{ route('parcel.index') }}" aria-expanded="false" data-target="#submenu-1"
                                    aria-controls="submenu-1"><i class="fa fa-dolly"></i>{{ __('menus.parcel') }}</a>
                            </li>
                        @endif

                        <!-- ERP Section -->
                        <li class="nav-divider">ERP System</li>

                        @can('viewAny', \App\Models\User::class)
                            <li class="nav-item ">
                                <a class="nav-link {{ request()->is('admin/customers*') ? 'active' : '' }}"
                                    href="{{ route('admin.customers.index') }}" aria-expanded="false"
                                    data-target="#submenu-1" aria-controls="submenu-1"><i
                                        class="fa fa-users"></i>Customers</a>
                            </li>
                        @endcan

                        @can('viewAny', \App\Models\Shipment::class)
                            <li class="nav-item ">
                                <a class="nav-link {{ request()->is('admin/booking*') ? 'active' : '' }}"
                                    href="{{ route('admin.booking.step1') }}" aria-expanded="false"
                                    data-target="#submenu-1" aria-controls="submenu-1"><i
                                        class="fa fa-clipboard-plus"></i>Booking Wizard</a>
                            </li>
                        @endcan


                        @if (hasPermission('news_offer_read') == true)
                            <li class="nav-item ">
                                <a class="nav-link {{ request()->is('admin/news-offer*') ? 'active' : '' }}"
                                    href="{{ route('news-offer.index') }}"><i
                                        class="fa fa-newspaper"></i>{{ __('menus.news_offer') }}</a>
                            </li>
                        @endif

                        @if (hasPermission('log_read') == true)
                            <li class="nav-item ">
                                <a class="nav-link {{ request()->is('admin/logs*') ? 'active' : '' }}"
                                    href="{{ route('logs.index') }}" aria-expanded="false" data-target="#active_log"
                                    aria-controls="active_log"><i
                                        class="fa fa-history"></i>{{ __('menus.active_logs') }}</a>
                            </li>
                        @endif

                        @if (hasPermission('fraud_read') == true)
                            <li class="nav-item ">
                                <a class="nav-link {{ request()->is('admin/fraud*') ? 'active' : '' }}"
                                    href="{{ route('fraud.index') }}" aria-expanded="false"
                                    data-target="#active_log" aria-controls="active_log"><i
                                        class="fa fa-user-times"></i>{{ __('menus.fraud_check') }}</a>
                            </li>
                        @endif

                        @if (hasPermission('subscribe_read') == true)
                            <li class="nav-item ">
                                <a class="nav-link {{ request()->is('admin/subscribe*') ? 'active' : '' }}"
                                    href="{{ route('subscribe.index') }}" aria-expanded="false"
                                    data-target="#active_log" aria-controls="active_log"><i
                                        class="fas fa-users"></i>{{ __('account.subscribe') }}</a>
                            </li>
                        @endif



                        @if (hasPermission('pickup_request_regular') == true || hasPermission('pickup_request_express') == true)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/pickup-request*') ? 'active' : '' }}"
                                    href="#" data-toggle="collapse" aria-expanded="false"
                                    data-target="#pickup-request" aria-controls="hub-manage"><i
                                        class="fa fa-truck-moving"></i>{{ __('menus.pickup_request') }}</a>
                                <div id="pickup-request"
                                    class="{{ request()->is('admin/pickup-request*') ? '' : 'collapse' }} submenu">
                                    <ul class="nav flex-column">
                                        @if (hasPermission('pickup_request_regular') == true)
                                            <li class="nav-item ">
                                                <a class="nav-link {{ request()->is('admin/pickup-request/regular*') ? 'active' : '' }}"
                                                    href="{{ route('pickup.request.regular') }}">{{ __('menus.regular') }}</a>
                                            </li>
                                        @endif
                                        @if (hasPermission('pickup_request_express') == true)
                                            <li class="nav-item ">
                                                <a class="nav-link {{ request()->is('admin/pickup-request/express*') ? 'active' : '' }}"
                                                    href="{{ route('pickup.request.express') }}">{{ __('menus.express') }}</a>
                                            </li>
                                        @endif

                                    </ul>
                                </div>
                            </li>
                        @endif

                        @if (hasPermission('pickup_request_regular') == true || hasPermission('pickup_request_express') == true)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/bank*','admin/mobile-bank*') ? 'active' : '' }}"
                                    href="#" data-toggle="collapse" aria-expanded="false"
                                    data-target="#payment-method" aria-controls="hub-manage"><i
                                        class="fa fa-money-check"></i>{{ __('menus.payment_method') }}</a>
                                <div id="payment-method"
                                    class="{{ request()->is('admin/bank*','admin/mobile-bank*') ? '' : 'collapse' }} submenu">
                                    <ul class="nav flex-column">
                                        <li class="nav-item ">
                                            <a class="nav-link {{ request()->is('admin/bank*') ? 'active' : '' }}"
                                                href="{{ route('bank.index') }}">{{ __('menus.banks') }}</a>
                                        </li>
                                        <li class="nav-item ">
                                            <a class="nav-link {{ request()->is('admin/mobile-bank*') ? 'active' : '' }}"
                                                href="{{ route('mobile-bank.index') }}">{{ __('menus.mobile_banks') }}</a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                        @endif

                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('admin/asset-category*', 'admin/assets*') ? 'active' : '' }} "
                                href="#" data-toggle="collapse" aria-expanded="false" data-target="#asset_management"
                                aria-controls="asset_management"><i
                                    class="fa fa-fw fa-sitemap"></i>{{ __('menus.asset_management') }}</a>
                            <div id="asset_management"
                                class="{{ request()->is('admin/asset-category*', 'admin/assets*') ? '' : 'collapse' }} submenu">
                                <ul class="nav flex-column">

                                    @if (hasPermission('asset_category_read') == true)
                                        <li class="nav-item">
                                            <a class="nav-link  {{ request()->is('admin/asset-category*') ? 'active' : '' }} "
                                                href="{{ route('asset-category.index') }}">
                                                {{ __('menus.assets_category') }}
                                            </a>
                                        </li>
                                    @endif

                                    @if (hasPermission('assets_read') == true)
                                        <li class="nav-item ">
                                            <a class="nav-link {{ request()->is('admin/assets/index', 'admin/assets/create', 'admin/assets/edit*', 'admin/assets/view*') ? 'active' : '' }}"
                                                href="{{ route('asset.index') }}" aria-expanded="false" data-target="#hubs"
                                                aria-controls="hubs">
                                                {{ __('menus.assets') }}
                                            </a>
                                        </li>
                                    @endif
                                    @if (hasPermission('fuels_read') == true)
                                        <li class="nav-item ">
                                            <a class="nav-link {{ request()->is('admin/assets/fuels*') ? 'active' : '' }}"
                                                href="{{ route('fuels.index') }}">{{ __('levels.fuels') }}</a>
                                        </li>
                                    @endif


                                    @if (hasPermission('maintenance_read') == true)
                                        <li class="nav-item ">
                                            <a class="nav-link {{ request()->is('admin/assets/maintenance', 'admin/assets/maintenance/create', 'admin/assets/maintenance/edit*') ? 'active' : '' }}"
                                                href="{{ route('maintenance.index') }}" aria-expanded="false" data-target="#hubs"
                                                aria-controls="hubs">
                                                {{ __('levels.maintenances') }}
                                            </a>
                                        </li>
                                    @endif
                                    @if (hasPermission('accidents_read') == true)
                                        <li class="nav-item ">
                                            <a class="nav-link {{ request()->is('admin/assets/accident', 'admin/assets/accident/create', 'admin/assets/accident/edit*') ? 'active' : '' }}"
                                                href="{{ route('accident.index') }}" aria-expanded="false" data-target="#hubs"
                                                aria-controls="hubs">
                                                {{ __('levels.accidents') }}
                                            </a>
                                        </li>
                                    @endif
                                    @if (hasPermission('assets_reports') == true)
                                        <li class="nav-item ">
                                            <a class="nav-link {{ request()->is('admin/assets/reports*') ? 'active' : '' }}"
                                                href="{{ route('assets.reports') }}">
                                                {{ __('levels.reports') }}
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </li>

                        @if (hasPermission('wallet_request_read'))
                            <li class="nav-item ">
                                <a class="nav-link {{ request()->is('admin/wallet-request*') ? 'active' : '' }}"
                                    href="{{ route('wallet.request.index') }}"><i
                                        class="fa fa-wallet"></i>{{ __('parcel.wallet_request') }}</a>
                            </li>
                        @endif



                        @if (hasPermission('online_payment_read') == true)
                            <li class="nav-item ">
                                <a class="nav-link {{ request()->is('admin/online-payment-list*') ? 'active' : '' }}"
                                    href="{{ route('online.payment.list') }}"><i
                                        class="fas fa-credit-card"></i>{{ __('menus.payments_received') }}</a>
                            </li>
                        @endif

                        @if (hasPermission('payout_read') == true)
                            <li class="nav-item ">
                                <a class="nav-link {{ request()->is('admin/payout*') ? 'active' : '' }}"
                                    href="{{ route('payout.index') }}"><i
                                        class="fas fa-hand-holding-usd"></i>{{ __('menus.payout') }}</a>
                            </li>
                        @endif

                        @if (hasPermission('account_read') == true ||
                                hasPermission('fund_transfer_read') == true ||
                                hasPermission('cash_received_from_delivery_man_read') == true ||
                                auth()->user()->hub_id)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/accounts*', 'admin/fund-transfer*', 'admin/account-head*', 'admin/bank-transaction*', 'admin/hub/cash-received-deliveryman*', 'admin/hub/payment-request*', 'admin/paid/invoice*') ? 'active' : '' }} "
                                    href="#" data-toggle="collapse" aria-expanded="false"
                                    data-target="#account" aria-controls="account"><i
                                        class="fas fa-user"></i>{{ __('menus.accounts') }}</a>
                                <div id="account"
                                    class="{{ request()->is('admin/accounts*', 'admin/fund-transfer*', 'admin/expense*', 'admin/income*', 'admin/account-head*', 'admin/bank-transaction*', 'admin/hub/cash-received-deliveryman*', 'admin/hub/payment-request*', 'admin/paid/invoice*') ? '' : 'collapse' }} submenu">
                                    <ul class="nav flex-column">
                                        @if (hasPermission('account_heads_read') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/account-heads*') ? 'active' : '' }}"
                                                    href="{{ route('account.heads.index') }}">{{ __('menus.account_heads') }}</a>
                                            </li>
                                        @endif

                                        @if (hasPermission('account_read') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/accounts*') ? 'active' : '' }}"
                                                    href="{{ route('accounts.index') }}">{{ __('menus.accounts') }}</a>
                                            </li>
                                        @endif
                                        @if (hasPermission('fund_transfer_read') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/fund-transfer*') ? 'active' : '' }}"
                                                    href="{{ route('fund-transfer.index') }}">{{ __('menus.fund_transfer') }}</a>
                                            </li>
                                        @endif
                                        @if (hasPermission('income_read') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/income*') ? 'active' : '' }}"
                                                    href="{{ route('income.index') }}">{{ __('menus.income') }}</a>
                                            </li>
                                        @endif
                                        @if (hasPermission('expense_read') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/expense*') ? 'active' : '' }}"
                                                    href="{{ route('expense.index') }}">{{ __('menus.expense') }}</a>
                                            </li>
                                        @endif

                                        @if (hasPermission('bank_transaction_read') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/bank-transaction*') ? 'active' : '' }}"
                                                    href="{{ route('bank-transaction.index') }}">{{ __('menus.bank_transaction') }}</a>
                                            </li>
                                        @endif

                                        @if (hasPermission('cash_received_from_delivery_man_read') == true)
                                            @if (Auth::user()->hub_id)
                                                <li class="nav-item">
                                                    <a class="nav-link {{ request()->is('admin/hub/cash-received-deliveryman*') ? 'active' : '' }}"
                                                        href="{{ route('cash.received.deliveryman.index') }}">{{ __('permissions.cash_received_from_delivery_man') }}</a>
                                                </li>
                                            @endif
                                        @endif

                                        @if (hasPermission('hub_payment_request_read') == true)
                                            @if (auth()->user()->hub_id)
                                                <li class="nav-item">
                                                    <a class="nav-link {{ request()->is('admin/hub/payment-request*') ? 'active' : '' }}"
                                                        href="{{ route('hub-panel.payment-request.index') }}">{{ __('menus.hub_payment_request') }}</a>
                                                </li>
                                            @endif
                                        @endif


                                        @if (hasPermission('paid_invoice_read') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/paid/invoice*') ? 'active' : '' }}"
                                                    href="{{ route('paid.invoice.index') }}">{{ __('invoice.paid_invoice') }}</a>
                                            </li>
                                        @endif

                                    </ul>
                                </div>
                            </li>
                        @endif

                        @if (hasPermission('role_read') == true ||
                                hasPermission('designation_read') == true ||
                                hasPermission('department_read') == true ||
                                hasPermission('user_read') == true)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/roles*', 'admin/users*', 'admin/designations*', 'admin/departments*') ? 'active' : '' }} "
                                    href="#" data-toggle="collapse" aria-expanded="false"
                                    data-target="#submenu-2" aria-controls="submenu-2"><i
                                        class="fas fa-th"></i>{{ __('menus.user_role') }}</a>
                                <div id="submenu-2"
                                    class="{{ request()->is('admin/roles*', 'admin/users*', 'admin/designations*', 'admin/departments*') ? '' : 'collapse' }} submenu">
                                    <ul class="nav flex-column">

                                        @if (hasPermission('role_read') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/roles*') ? 'active' : '' }}"
                                                    href="{{ route('roles.index') }}">{{ __('menus.roles') }}</a>
                                            </li>
                                        @endif
                                        @if (hasPermission('designation_read') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/designations*') ? 'active' : '' }}"
                                                    href="{{ route('designations.index') }}">{{ __('menus.designations') }}</a>
                                            </li>
                                        @endif
                                        @if (hasPermission('department_read') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/departments*') ? 'active' : '' }}"
                                                    href="{{ route('departments.index') }}">{{ __('menus.departments') }}</a>
                                            </li>
                                        @endif

                                        @if (hasPermission('user_read') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/users*') ? 'active' : '' }}"
                                                    href="{{ route('users.index') }}">{{ __('menus.users') }}</a>
                                            </li>
                                        @endif

                                    </ul>
                                </div>
                            </li>
                        @endif



                        @if (hasPermission('salary_generate_read') == true || hasPermission('salary_read') == true)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/salary/salary-generate*', 'admin/salary*') ? 'active' : '' }}"
                                    href="#" data-toggle="collapse" aria-expanded="false"
                                    data-target="#salarygenerate" aria-controls="salarygenerate"><i
                                        class="fas fa-hand-holding-usd"></i>{{ __('salary.payroll') }}</a>
                                <div id="salarygenerate"
                                    class="{{ request()->is('admin/salary/salary-generate*', 'admin/salary*') ? '' : 'collapse' }} submenu">
                                    <ul class="nav flex-column">
                                        @if (hasPermission('salary_generate_read') == true)
                                            <li class="nav-item ">
                                                <a class="nav-link {{ request()->is('admin/salary/salary-generate*', 'admin/reports/parcel-filter-reports') ? 'active' : '' }}"
                                                    href="{{ route('salary.generate.index') }}"
                                                    aria-expanded="false" data-target="#submenu-1"
                                                    aria-controls="submenu-1">{{ __('salary.salary_generate') }}</a>
                                            </li>
                                        @endif

                                        @if (hasPermission('salary_read') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/salarys*') ? 'active' : '' }}"
                                                    href="{{ route('salary.index') }}">{{ __('menus.salary') }}</a>
                                            </li>
                                        @endif


                                    </ul>
                                </div>
                            </li>
                        @endif


                        @if (hasPermission('parcel_status_reports') == true || hasPermission('parcel_wise_profit') == true)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/reports/*') ? 'active' : '' }}"
                                    href="#" data-toggle="collapse" aria-expanded="false"
                                    data-target="#reports" aria-controls="reports"><i
                                        class="fas fa-print"></i>{{ __('reports.title') }}</a>
                                <div id="reports"
                                    class="{{ request()->is('admin/reports*') ? '' : 'collapse' }} submenu">
                                    <ul class="nav flex-column">
                                        @if (hasPermission('parcel_status_reports') == true)
                                            <li class="nav-item ">
                                                <a class="nav-link {{ request()->is('admin/reports/parcel-reports*', 'admin/reports/parcel-filter-reports') ? 'active' : '' }}"
                                                    href="{{ route('parcel.reports') }}" aria-expanded="false"
                                                    data-target="#submenu-1"
                                                    aria-controls="submenu-1">{{ __('reports.parcel_reports') }}</a>
                                            </li>
                                        @endif
                                        @if (hasPermission('parcel_wise_profit') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/reports/parcel-wise*') ? 'active' : '' }}"
                                                    href="{{ route('parcel.wise.profit.index') }}">{{ __('reports.parcel_wise_profit') }}</a>
                                            </li>
                                        @endif


                                        @if (hasPermission('salary_reports') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/reports/salary-reports*', 'admin/reports/reports-salary-reports*') ? 'active' : '' }}"
                                                    href="{{ route('salary.reports') }}">{{ __('reports.salary_reports') }}</a>
                                            </li>
                                        @endif
                                        @if (hasPermission('merchant_hub_deliveryman') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/reports/merchant-hub-deliveryman*', 'admin/reports/mhd-reports') ? 'active' : '' }}"
                                                    href="{{ route('merchant.hub.deliveryman.reports') }}">{{ __('reports.merchant_hub_deliveryman') }}</a>
                                            </li>
                                        @endif
                                        @if (hasPermission('parcel_total_summery') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/reports/parcel-total-summery*', 'admin/reports/parcel-filter-total-summery*') ? 'active' : '' }}"
                                                    href="{{ route('parcel.total.summery.index') }}">{{ __('menus.parcel_total_summery') }}</a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </li>
                        @endif



                        @if (hasPermission('push_notification_read') == true)
                            <li class="nav-item ">
                                <a class="nav-link {{ request()->is('admin/push-notification*') ? 'active' : '' }}"
                                    href="{{ route('push-notification.index') }}" aria-expanded="false"
                                    data-target="#submenu-1" aria-controls="submenu-1"><i
                                        class="fa fa-bell"></i>{{ __('menus.push_notification') }}</a>
                            </li>
                        @endif
                        @if (env('DEMO') == false)
                            <li class="nav-item ">
                                <a class="nav-link {{ request()->is('admin/addons*') ? 'active' : '' }}"
                                    href="{{ route('addons.index') }}" aria-expanded="false"
                                    data-target="#submenu-1" aria-controls="submenu-1"><i
                                        class="fa fa-upload"></i>{{ __('menus.addons') }}</a>
                            </li>
                        @endif


                        @if (hasPermission('social_link_read') == true || hasPermission('service_read') == true)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/front-web*') ? 'active' : '' }}"
                                    href="#" data-toggle="collapse" aria-expanded="false"
                                    data-target="#front-web" aria-controls="front-web"><i
                                        class="fas fa-globe"></i>{{ __('levels.front_web') }}</a>

                                <div id="front-web"
                                    class="{{ request()->is('admin/front-web*') ? '' : 'collapse' }} submenu">
                                    <ul class="nav flex-column">
                                        @if (hasPermission('social_link_read') == true)
                                            <li class="nav-item ">
                                                <a class="nav-link {{ request()->is('admin/front-web/social-link*') ? 'active' : '' }}"
                                                    href="{{ route('social.link.index') }}">{{ __('levels.social_link') }}</a>
                                            </li>
                                        @endif
                                        @if (hasPermission('service_read') == true)
                                            <li class="nav-item ">
                                                <a class="nav-link {{ request()->is('admin/front-web/service*') ? 'active' : '' }}"
                                                    href="{{ route('service.index') }}">{{ __('levels.service') }}</a>
                                            </li>
                                        @endif
                                        @if (hasPermission('why_courier_read') == true)
                                            <li class="nav-item ">
                                                <a class="nav-link {{ request()->is('admin/front-web/why-courier*') ? 'active' : '' }}"
                                                    href="{{ route('why.courier.index') }}">{{ __('levels.why_courier') }}</a>
                                            </li>
                                        @endif
                                        @if (hasPermission('faq_read') == true)
                                            <li class="nav-item ">
                                                <a class="nav-link {{ request()->is('admin/front-web/faq*') ? 'active' : '' }}"
                                                    href="{{ route('faq.index') }}">{{ __('levels.faq') }}</a>
                                            </li>
                                        @endif
                                        @if (hasPermission('partner_read') == true)
                                            <li class="nav-item ">
                                                <a class="nav-link {{ request()->is('admin/front-web/partner*') ? 'active' : '' }}"
                                                    href="{{ route('partner.index') }}">{{ __('levels.partner') }}</a>
                                            </li>
                                        @endif
                                        @if (hasPermission('blogs_read') == true)
                                            <li class="nav-item ">
                                                <a class="nav-link {{ request()->is('admin/front-web/blogs*') ? 'active' : '' }}"
                                                    href="{{ route('blogs.index') }}">{{ __('levels.blogs') }}</a>
                                            </li>
                                        @endif
                                        @if (hasPermission('pages_read') == true)
                                            <li class="nav-item ">
                                                <a class="nav-link {{ request()->is('admin/front-web/pages*') ? 'active' : '' }}"
                                                    href="{{ route('pages.index') }}">{{ __('levels.pages') }}</a>
                                            </li>
                                        @endif
                                        @if (hasPermission('section_read') == true)
                                            <li class="nav-item ">
                                                <a class="nav-link {{ request()->is('admin/front-web/section*') ? 'active' : '' }}"
                                                    href="{{ route('section.index') }}">{{ __('levels.section') }}</a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </li>
                        @endif

                        @if (hasPermission('delivery_category_read') == true ||
                                hasPermission('delivery_charge_read') == true ||
                                hasPermission('delivery_type_read') == true ||
                                hasPermission('liquid_fragile_read') == true ||
                                hasPermission('packaging_read') == true)

                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/database-backup*', 'admin/delivery-category*', 'admin/delivery-category*', 'admin/delivery-charge*', 'admin/packaging*', 'admin/delivery-type*', 'admin/liquid-fragile*', 'admin/sms-settings*', 'admin/sms-send-settings*', 'admin/general-settings*', 'admin/notification-settings*', 'admin/googlemap-settings*', 'admin/asset-category*', 'admin/social-login-setting*', 'admin/pay-out/setup*', 'admin/settings/pay-out/setup*', 'admin/settings/invoice-generate-menually*', 'admin/currency*', 'admin/mail-settings*') ? 'active' : '' }} "
                                    href="#" data-toggle="collapse" aria-expanded="false"
                                    data-target="#submenu-0" aria-controls="submenu-0"><i class="fa fa-cogs"></i>
                                    {{ __('menus.settings') }}</a>
                                <div class="{{ request()->is('admin/database-backup*', 'admin/delivery-category*', 'admin/delivery-charge*', 'admin/packaging*', 'admin/delivery-type*', 'admin/liquid-fragile*', 'admin/sms-settings*', 'admin/sms-send-settings*', 'admin/general-settings*', 'admin/notification-settings*', 'admin/googlemap-settings*', 'admin/asset-category*', 'admin/social-login-setting*', 'admin/pay-out/setup*', 'admin/settings/pay-out/setup*', 'admin/settings/invoice-generate-menually*', 'admin/currency*', 'admin/mail-settings*') ? '' : 'collapse' }} submenu"
                                    id="submenu-0" class="collapse submenu">
                                    <ul class="nav flex-column">

                                        @if (hasPermission('general_settings_read') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/general-settings*') ? 'active' : '' }}"
                                                    href="{{ route('general-settings.index') }}">{{ __('menus.general_settings') }}</a>
                                            </li>
                                        @endif

                                        @if (hasPermission('delivery_category_read') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/delivery-category*') ? 'active' : '' }}"
                                                    href="{{ route('delivery-category.index') }}">{{ __('menus.delivery_category') }}</a>
                                            </li>
                                        @endif
                                        @if (hasPermission('delivery_charge_read') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/delivery-charge*') ? 'active' : '' }}"
                                                    href="{{ route('delivery-charge.index') }}">{{ __('menus.delivery_charge') }}</a>
                                            </li>
                                        @endif
                                        @if (hasPermission('delivery_type_read') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/delivery-type*') ? 'active' : '' }}"
                                                    href="{{ route('delivery-type.index') }}">{{ __('menus.delivery_type') }}</a>
                                            </li>
                                        @endif
                                        @if (hasPermission('liquid_fragile_read') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/liquid-fragile*') ? 'active' : '' }}"
                                                    href="{{ route('liquid-fragile.index') }}">{{ __('menus.liquid_fragile') }}</a>
                                            </li>
                                        @endif

                                        @if (hasPermission('sms_settings_read') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/sms-settings*') ? 'active' : '' }}"
                                                    href="{{ route('sms-settings.index') }}">{{ __('menus.sms_settings') }}</a>
                                            </li>
                                        @endif
                                        @if (hasPermission('sms_send_settings_read') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/sms-send-settings*') ? 'active' : '' }}"
                                                    href="{{ route('sms-send-settings.index') }}">{{ __('menus.sms_send_settings') }}</a>
                                            </li>
                                        @endif

                                        @if (hasPermission('notification_settings_read') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/notification-settings*') ? 'active' : '' }}"
                                                    href="{{ route('notification-settings.index') }}">{{ __('menus.notification_settings') }}</a>
                                            </li>
                                        @endif

                                        @if (hasPermission('notification_settings_read') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/googlemap-settings*') ? 'active' : '' }}"
                                                    href="{{ route('googlemap-settings.index') }}">{{ __('menus.google_map_settings') }}</a>
                                            </li>
                                        @endif
                                        @if (hasPermission('mail_settings_read') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/mail-settings*') ? 'active' : '' }}"
                                                    href="{{ route('mail-settings.index') }}">{{ __('menus.mail_settings') }}</a>
                                            </li>
                                        @endif

                                        @if (hasPermission('social_login_settings_update') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/social-login-setting*') ? 'active' : '' }}"
                                                    href="{{ route('social.login.settings.index') }}">{{ __('menus.social_login_settings') }}</a>
                                            </li>
                                        @endif

                                        @if (hasPermission('payout_setup_settings_read') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/settings/pay-out/setup*') ? 'active' : '' }}"
                                                    href="{{ route('payout.setup.settings.index') }}">{{ __('menus.payout_setup') }}</a>
                                            </li>
                                        @endif

                                        @if (hasPermission('packaging_read') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/packaging*') ? 'active' : '' }}"
                                                    href="{{ route('packaging.index') }}">{{ __('menus.packaging') }}</a>
                                            </li>
                                        @endif


                                        @if (hasPermission('currency_read') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/currency*') ? 'active' : '' }}"
                                                    href="{{ route('currency.index') }}">{{ __('settings.currency') }}</a>
                                            </li>
                                        @endif

                                        @if (hasPermission('database_backup_read') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/database-backup*') ? 'active' : '' }}"
                                                    href="{{ route('database.backup.index') }}">{{ __('menus.database_backup') }}</a>
                                            </li>
                                        @endif

                                        @if (hasPermission('invoice_generate_menually') == true)
                                            <li class="nav-item">
                                                <a class="nav-link {{ request()->is('admin/settings/invoice-generate-menually*') ? 'active' : '' }}"
                                                    href="{{ route('invoice.generate.menually.index') }}">{{ __('menus.invoice_generate_menually') }}</a>
                                            </li>
                                        @endif

                                    </ul>
                                </div>
                            </li>
                        @endif

                    </ul>
                </div>
            </div>
        </div>


    </nav>


</div>

<!-- end left sidebar -->
