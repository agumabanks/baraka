@extends('backend.partials.master')
@section('title')
    {{ __('menus.dashboard') }}
@endsection

@php
    $breadcrumbs = [
        [
            'title' => __('menus.dashboard'),
            'url' => route('dashboard.index'),
            'active' => true,
            'icon' => 'fas fa-tachometer-alt'
        ]
    ];

    $contextualActions = [
        [
            'title' => __('dashboard.book_shipment'),
            'url' => route('admin.booking.step1'),
            'icon' => 'fas fa-clipboard-check',
            'type' => 'link'
        ],
        [
            'title' => __('dashboard.bulk_upload'),
            'url' => route('parcel.parcel-import'),
            'icon' => 'fas fa-file-upload',
            'type' => 'link'
        ],
        [
            'type' => 'divider'
        ],
        [
            'title' => __('dashboard.view_all_parcels'),
            'url' => route('parcel.index'),
            'icon' => 'fas fa-dolly',
            'type' => 'link'
        ]
    ];
@endphp

@section('breadcrumb')
    <x-breadcrumb :breadcrumbs="$breadcrumbs" :contextualActions="$contextualActions" />
@endsection

@section('maincontent')
    <div class="container-fluid dashboard-content ">
        <div class="ecommerce-widget">

            <div class="row ">
                <div class="col-lg-12 dashboard-filter mb-3">
                    <form action="{{ route('dashboard.index', ['test' => 'custom']) }}" method="get">
                        <button type="submit" class="btn btn-sm btn-primary float-right group-btn ml-0"
                            style="margin-left: 0px">{{ __('levels.filter') }}</button>
                        <input type="hidden" name="days" value="custom" />
                        <input type="text" name="filter_date" placeholder="YYYY-MM-DD" autocomplete="off"
                            class="form-control dashboard-filter-input date_range_picker float-right group-input"
                            value="{{ $request->filter_date }}" style="width: 15%;" required />
                    </form>

                </div>
            </div>
            <!-- Row 1: Business Health KPIs -->
            <div class="row mb-4">
                <!-- SLA Status -->
                <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                    <div class="kpi-card--skeleton" aria-live="polite" aria-label="Loading SLA status">
                        <div class="skeleton skeleton-icon"></div>
                        <div class="skeleton-content">
                            <div class="skeleton skeleton-title"></div>
                            <div class="skeleton skeleton-value"></div>
                            <div class="skeleton skeleton-subtitle"></div>
                        </div>
                    </div>
                </div>

                <!-- Exceptions -->
                <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                    <div class="kpi-card--skeleton" aria-live="polite" aria-label="Loading exceptions">
                        <div class="skeleton skeleton-icon"></div>
                        <div class="skeleton-content">
                            <div class="skeleton skeleton-title"></div>
                            <div class="skeleton skeleton-value"></div>
                            <div class="skeleton skeleton-subtitle"></div>
                        </div>
                    </div>
                </div>

                <!-- On-time Delivery % -->
                <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                    <div class="kpi-card--skeleton" aria-live="polite" aria-label="Loading delivery performance">
                        <div class="skeleton skeleton-icon"></div>
                        <div class="skeleton-content">
                            <div class="skeleton skeleton-title"></div>
                            <div class="skeleton skeleton-value"></div>
                            <div class="skeleton skeleton-subtitle"></div>
                        </div>
                    </div>
                </div>

                <!-- Open Tickets -->
                <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                    <div class="kpi-card--skeleton" aria-live="polite" aria-label="Loading support tickets">
                        <div class="skeleton skeleton-icon"></div>
                        <div class="skeleton-content">
                            <div class="skeleton skeleton-title"></div>
                            <div class="skeleton skeleton-value"></div>
                            <div class="skeleton skeleton-subtitle"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 2: Work in Progress -->
            <div class="row mb-4">
                <!-- Today's Queue -->
                <div class="col-xl-6 col-lg-12 mb-3">
                    <x-workflow-queue />
                </div>

                <!-- Cash Collection -->
                <div class="col-xl-6 col-lg-12 mb-3">
                    <div class="chart--skeleton" aria-live="polite" aria-label="Loading cash collection chart">
                        <div class="skeleton-chart-header">
                            <div class="skeleton skeleton-chart-title"></div>
                            <div class="skeleton-chart-controls">
                                <div class="skeleton skeleton-chart-control"></div>
                                <div class="skeleton skeleton-chart-control"></div>
                            </div>
                        </div>
                        <div class="skeleton-chart-area">
                            <div class="skeleton-chart-bars">
                                <div class="skeleton skeleton-chart-bar"></div>
                                <div class="skeleton skeleton-chart-bar"></div>
                                <div class="skeleton skeleton-chart-bar"></div>
                                <div class="skeleton skeleton-chart-bar"></div>
                                <div class="skeleton skeleton-chart-bar"></div>
                                <div class="skeleton skeleton-chart-bar"></div>
                                <div class="skeleton skeleton-chart-bar"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 3: Trends & Statements -->
            <div class="row mb-4">
                <!-- Delivery Man Statement -->
                <div class="col-xl-6 col-lg-12 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ __('dashboard.delivery_man') }} {{ __('dashboard.statements') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="kpi-card--skeleton" aria-live="polite" aria-label="Loading delivery man statement">
                                <div class="skeleton-content">
                                    <div class="skeleton skeleton-title"></div>
                                    <div class="skeleton skeleton-value"></div>
                                    <div class="skeleton skeleton-subtitle"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Merchant Statement -->
                <div class="col-xl-6 col-lg-12 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">{{ __('dashboard.merchant') }} {{ __('dashboard.statements') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="kpi-card--skeleton" aria-live="polite" aria-label="Loading merchant statement">
                                <div class="skeleton-content">
                                    <div class="skeleton skeleton-title"></div>
                                    <div class="skeleton skeleton-value"></div>
                                    <div class="skeleton skeleton-subtitle"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 4: Charts -->
            <div class="row mb-4">
                @if (hasPermission('income_expense_charts') == true)
                    <div class="col-xl-6 col-lg-12 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="chart--skeleton" aria-live="polite" aria-label="Loading income expense chart">
                                    <div class="skeleton-chart-header">
                                        <div class="skeleton skeleton-chart-title"></div>
                                    </div>
                                    <div class="skeleton-chart-area">
                                        <div class="skeleton-chart-bars">
                                            <div class="skeleton skeleton-chart-bar"></div>
                                            <div class="skeleton skeleton-chart-bar"></div>
                                            <div class="skeleton skeleton-chart-bar"></div>
                                            <div class="skeleton skeleton-chart-bar"></div>
                                            <div class="skeleton skeleton-chart-bar"></div>
                                            <div class="skeleton skeleton-chart-bar"></div>
                                            <div class="skeleton skeleton-chart-bar"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if (hasPermission('courier_revenue_charts') == true)
                    <div class="col-xl-6 col-lg-12 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="chart--skeleton" aria-live="polite" aria-label="Loading courier revenue chart">
                                    <div class="skeleton-chart-header">
                                        <div class="skeleton skeleton-chart-title"></div>
                                    </div>
                                    <div class="skeleton-chart-area">
                                        <div class="skeleton-chart-bars">
                                            <div class="skeleton skeleton-chart-bar"></div>
                                            <div class="skeleton skeleton-chart-bar"></div>
                                            <div class="skeleton skeleton-chart-bar"></div>
                                            <div class="skeleton skeleton-chart-bar"></div>
                                            <div class="skeleton skeleton-chart-bar"></div>
                                            <div class="skeleton skeleton-chart-bar"></div>
                                            <div class="skeleton skeleton-chart-bar"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Row 5: Quick Actions -->
            <div class="row">
                @if (hasPermission('booking_create') == true)
                    <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                        <a href="{{ route('admin.booking.step1') }}" class="card h-100 text-decoration-none">
                            <div class="card-body text-center">
                                <i class="fas fa-clipboard-check fa-2x text-primary mb-3"></i>
                                <h6 class="card-title">{{ __('dashboard.book_shipment') }}</h6>
                            </div>
                        </a>
                    </div>
                @endif

                @if (hasPermission('parcel_create') == true)
                    <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                        <a href="{{ route('parcel.parcel-import') }}" class="card h-100 text-decoration-none">
                            <div class="card-body text-center">
                                <i class="fas fa-file-upload fa-2x text-success mb-3"></i>
                                <h6 class="card-title">Bulk Upload</h6>
                            </div>
                        </a>
                    </div>
                @endif


                @if (hasPermission('parcel_read') == true)
                    <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                        <a href="{{ route('parcel.index') }}" class="card h-100 text-decoration-none">
                            <div class="card-body text-center">
                                <i class="fa fa-dolly fa-2x text-warning mb-3"></i>
                                <h6 class="card-title">View All Parcels</h6>
                            </div>
                        </a>
                    </div>
                @endif
            </div>
            <div class="row header-summery">


                @if (hasPermission('total_parcel') == true)
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <x-kpi-card
                            title="{{ __('dashboard.total_parcel') }}"
                            :value="$data['total_parcel']"
                            subtitle="This month"
                            kpi="total-parcels"
                            drilldownRoute="{{ route('parcel.filter',['parcel_date' => $request->date]) }}"
                            tooltip="Total parcels processed this month"
                            :trend="['value' => 12.5, 'direction' => 'up']"
                            state="success"
                        />
                    </div>
                @endif

                                @if (hasPermission('total_user') == true)
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <x-kpi-card
                            title="{{ __('dashboard.total_user') }}"
                            :value="$data['total_user']"
                            subtitle="This month"
                            kpi="total-users"
                            drilldownRoute="{{ route('users.filter',['date' => $request->date]) }}"
                            tooltip="Total registered users"
                            :trend="['value' => 8.3, 'direction' => 'up']"
                            state="success"
                        />
                    </div>
                @endif

                @if (hasPermission('total_merchant') == true)
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <x-kpi-card
                            title="{{ __('dashboard.total_merchant') }}"
                            :value="$data['total_merchant']"
                            subtitle="This month"
                            kpi="total-merchants"
                            drilldownRoute="{{ route('merchant.index',['date' => $request->date]) }}"
                            tooltip="Total registered merchants"
                            :trend="['value' => 5.7, 'direction' => 'up']"
                            state="success"
                        />
                    </div>
                @endif


                @if (hasPermission('total_delivery_man') == true)
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <x-kpi-card
                            title="{{ __('dashboard.total_delivery_man') }}"
                            :value="$data['total_delivery_man']"
                            subtitle="This month"
                            kpi="total-delivery-men"
                            drilldownRoute="{{ route('deliveryman.index',['date' => $request->date]) }}"
                            tooltip="Total delivery personnel"
                            :trend="['value' => 3.2, 'direction' => 'up']"
                            state="success"
                        />
                    </div>
                @endif


                @if (hasPermission('total_hubs') == true)
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <x-kpi-card
                            title="{{ __('dashboard.total_hubs') }}"
                            :value="$data['total_hubs']"
                            subtitle="This month"
                            kpi="total-hubs"
                            drilldownRoute="{{ route('hubs.filter',['date' => $request->date]) }}"
                            tooltip="Total operational hubs"
                            :trend="['value' => 1.8, 'direction' => 'stable']"
                            state="success"
                        />
                    </div>
                @endif


                @if (hasPermission('total_accounts') == true)
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <x-kpi-card
                            title="{{ __('dashboard.total_accounts') }}"
                            :value="$data['total_accounts']"
                            subtitle="This month"
                            kpi="total-accounts"
                            drilldownRoute="{{ route('accounts.index',['date' => $request->date]) }}"
                            tooltip="Total financial accounts"
                            :trend="['value' => 4.1, 'direction' => 'up']"
                            state="success"
                        />
                    </div>
                @endif

                {{-- CLIENT MANAGEMENT --}}
                @if (hasPermission('total_customers') == true)
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <x-kpi-card
                            title="{{ __('dashboard.total_customers') }}"
                            :value="$data['total_customers'] ?? 0"
                            subtitle="This month"
                            kpi="total-customers"
                            drilldownRoute="{{ route('admin.customers.index') }}"
                            tooltip="Total customer accounts"
                            :trend="['value' => 6.9, 'direction' => 'up']"
                            state="success"
                        />
                    </div>
                @endif

                {{-- PARCEL ONBOARDING (BOOKING WIZARD) --}}
                @if (hasPermission('booking_create') == true)
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <x-kpi-card
                            title="{{ __('dashboard.book_shipment') }}"
                            :value="$data['total_bookings_today'] ?? 0"
                            subtitle="Today"
                            kpi="book-shipment"
                            drilldownRoute="{{ route('admin.booking.step1') }}"
                            tooltip="New shipment bookings today"
                            :trend="['value' => 15.2, 'direction' => 'up']"
                            state="success"
                        />
                    </div>
                @endif

                @if (hasPermission('total_partial_deliverd') == true)
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <x-kpi-card
                            title="{{ __('dashboard.total_partial_deliverd') }}"
                            :value="$data['total_partial_deliverd']"
                            subtitle="This month"
                            kpi="total-partial-delivered"
                            drilldownRoute="{{ route('parcel.filter', ['parcel_status' => \App\Enums\ParcelStatus::PARTIAL_DELIVERED,'parcel_date'=>$request->date]) }}"
                            tooltip="Parcels with partial delivery"
                            :trend="['value' => 2.1, 'direction' => 'down']"
                            state="warning"
                        />
                    </div>
                @endif

                @if (hasPermission('total_parcels_deliverd') == true)
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <x-kpi-card
                            title="{{ __('dashboard.total_deliverd') }}"
                            :value="$data['total_deliverd']"
                            subtitle="This month"
                            kpi="total-parcels-delivered"
                            drilldownRoute="{{ route('parcel.filter', ['parcel_status' => \App\Enums\ParcelStatus::DELIVERED,'parcel_date'=>$request->date]) }}"
                            tooltip="Successfully delivered parcels"
                            :trend="['value' => 18.7, 'direction' => 'up']"
                            state="success"
                        />
                    </div>
                @endif


            </div>
            {{-- salary and account section --}}

            @if (hasPermission('all_statements') == true)
                <div class="row mb-4">
                    <div class="col-md-4">
                        <ul class="list-group mt-2">
                            <li class="list-group-item profile-list-group-item text-center">
                                <span class="font-weight-bold "> {{ __('dashboard.delivery_man') }}
                                    {{ __('dashboard.statements') }}</span>
                            </li>
                            <li class="list-group-item profile-list-group-item">
                                <span class="float-left font-weight-bold"> {{ __('income.title') }} </span>
                                <span class="float-right"> {{ settings()->currency }}{{ $d_income }}</span>
                            </li>
                            <li class="list-group-item profile-list-group-item">
                                <span class="float-left font-weight-bold">{{ __('expense.title') }} </span>
                                <span class="float-right"> {{ settings()->currency }}{{ $d_expense }}</span>
                            </li>
                            <li class="list-group-item profile-list-group-item">
                                <span class="float-left font-weight-bold"> {{ __('dashboard.balance') }}</span>
                                <span class="float-right"> {{ settings()->currency }}{{ $d_income - $d_expense }}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <ul class="list-group mt-2">
                            <li class="list-group-item profile-list-group-item text-center">
                                <span class=" font-weight-bold"> {{ __('dashboard.merchant') }}
                                    {{ __('dashboard.statements') }} </span>
                            </li>
                            <li class="list-group-item profile-list-group-item">
                                <span class="float-left font-weight-bold"> {{ __('income.title') }} </span>
                                <span class="float-right"> {{ settings()->currency }}{{ $m_income }}</span>
                            </li>
                            <li class="list-group-item profile-list-group-item">
                                <span class="float-left font-weight-bold">{{ __('expense.title') }} </span>
                                <span class="float-right"> {{ settings()->currency }}{{ $m_expense }}</span>
                            </li>
                            <li class="list-group-item profile-list-group-item">
                                <span class="float-left font-weight-bold"> {{ __('dashboard.balance') }}</span>
                                <span class="float-right"> {{ settings()->currency }}{{ $m_income - $m_expense }}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <ul class="list-group mt-2 ">
                            <li class="list-group-item profile-list-group-item text-center">
                                <span class="font-weight-bold">{{ __('hub.title') }}
                                    {{ __('dashboard.statements') }}</span>
                            </li>
                            <li class="list-group-item profile-list-group-item">
                                <span class="float-left font-weight-bold"> {{ __('income.title') }} </span>
                                <span class="float-right"> {{ settings()->currency }}{{ $h_income }}</span>
                            </li>
                            <li class="list-group-item profile-list-group-item">
                                <span class="float-left font-weight-bold">{{ __('expense.title') }} </span>
                                <span class="float-right"> {{ settings()->currency }}{{ $h_expense }}</span>
                            </li>
                            <li class="list-group-item profile-list-group-item">
                                <span class="float-left font-weight-bold"> {{ __('dashboard.balance') }}</span>
                                <span class="float-right"> {{ settings()->currency }}{{ $h_income - $h_expense }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            @endif

            <div class="row">
                @if (hasPermission('income_expense_charts') == true)
                    <div class="col-xl-6 col-lg-12 col-md-12 col-sm-12 col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">{{ __('income.title') }} / {{ __('expense.title') }} {{ __('dashboard.trends') }}</h5>
                            </div>
                            <div class="card-body">
                                <div class="apexcharts chart-container"
                                     id="apexincomeexpense"
                                     data-chart-type="income-expense"
                                     data-lazy-load="true"
                                     role="img"
                                     aria-label="Income and expense trend chart showing daily financial data over time"
                                     tabindex="0"
                                     aria-describedby="income-expense-desc">
                                    <div class="chart-loading-state" aria-live="polite" aria-label="Loading income expense chart">
                                        <div class="chart-skeleton">
                                            <div class="skeleton-chart-header"></div>
                                            <div class="skeleton-chart-area"></div>
                                        </div>
                                    </div>
                                </div>
                                <div id="income-expense-desc" class="sr-only">
                                    This area chart displays income and expense trends over the selected time period. Data points represent daily totals. Use arrow keys to navigate data points when chart is focused.
                                </div>
                            </div>
                            <div class="card-footer">
                                <p class="display-7 font-weight-bold">
                                    <span class="legend-text text-primary d-inline-block" aria-label="Total income">{{ settings()->currency }}
                                        {{ $data['income'] }}</span>
                                    <span class="text-secondary float-right" aria-label="Total expense">{{ settings()->currency }}
                                        {{ $data['expense'] }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                @if (hasPermission('courier_revenue_charts') == true)
                    <div class="col-xl-6 col-lg-12 col-md-12 col-sm-12 col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">{{ __('dashboard.courier') }} {{ __('dashboard.revenue') }}</h5>
                            </div>
                            <div class="card-body courier-pie-charts">
                                <div class="apexcharts chart-container"
                                     id="apexpiecourierrevenue"
                                     data-chart-type="courier-revenue"
                                     data-lazy-load="true"
                                     role="img"
                                     aria-label="Courier revenue breakdown showing income and expense distribution"
                                     tabindex="0"
                                     aria-describedby="courier-revenue-desc">
                                    <div class="chart-loading-state" aria-live="polite" aria-label="Loading courier revenue chart">
                                        <div class="chart-skeleton">
                                            <div class="skeleton-chart-header"></div>
                                            <div class="skeleton-chart-area"></div>
                                        </div>
                                    </div>
                                </div>
                                <div id="courier-revenue-desc" class="sr-only">
                                    This polar area chart shows courier revenue breakdown between income and expenses as proportional segments. Use tab to navigate between legend items for detailed values.
                                </div>
                            </div>
                            <div class="card-footer">
                                <p class="display-7 font-weight-bold">
                                    <span class="text-primary d-inline-block" aria-label="Courier income">{{ settings()->currency }}
                                        {{ $data['courier_income'] }}</span>
                                    <span class="text-secondary float-right" aria-label="Courier expense">{{ settings()->currency }}
                                        {{ $data['courier_expense'] }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                    {{-- courier revenue pie charts --}}
                @endif
            </div>
            <!-- recent parcel  -->

            @if (hasPermission('calendar_read') == true)
                <div class="row mb-5">
                    <div class=" col-12 ">
                        <div class="card mb-0 mt-4">
                            <div class="card-body ">
                                <div style="overflow:hidden;">
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div id="datetimepicker12"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif


        </div>
    </div>

    </div>
    </div>
    <!-- end wrapper  -->
@endsection

<!-- css  -->
@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <link rel="stylesheet" type="text/css" href="{{ static_asset('backend/vendor/calender/main.css') }}" />
    <!-- Tempus Dominus Styles -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/eonasdan-bootstrap-datetimepicker/4.17.49/css/bootstrap-datetimepicker.min.css"
        integrity="sha512-ipfmbgqfdejR27dWn02UftaAzUfxJ3HR4BDQYuITYSj6ZQfGT1NulP4BOery3w/dT2PYAe3bG5Zm/owm7MuFhA==" crossorigin="anonymous"
        referrerpolicy="no-referrer" />
    <style>
        .notification .nav-link.nav-icons {
            margin-top: 0px !important;
        }

        .admin-panel.notification .nav-link.nav-icons .indicator {
            top: 15px !important;
        }
        /* Chart Loading States and Accessibility */
        .chart-container {
            position: relative;
            min-height: 300px;
            border-radius: var(--border-radius-base);
            transition: outline 0.2s ease;
        }

        .chart-loading-state {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--neutral-50);
            border-radius: var(--border-radius-base);
            z-index: 1;
        }

        .chart-skeleton {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .skeleton-chart-header {
            height: 24px;
            background: linear-gradient(90deg, var(--neutral-200) 25%, var(--neutral-100) 50%, var(--neutral-200) 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            margin-bottom: var(--spacing-md);
            border-radius: var(--border-radius-small);
        }

        .skeleton-chart-area {
            flex: 1;
            background: linear-gradient(90deg, var(--neutral-200) 25%, var(--neutral-100) 50%, var(--neutral-200) 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: var(--border-radius-small);
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Focus management for accessibility */
        .chart-container:focus {
            outline: 2px solid var(--focus-ring);
            outline-offset: 2px;
        }

        /* Screen reader only content */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        /* High contrast mode support */
        @media (prefers-contrast: high) {
            .chart-container {
                border: 1px solid var(--neutral-500);
            }
        }

        /* Reduced motion support */
        @media (prefers-reduced-motion: reduce) {
            .chart-loading-state,
            .skeleton-chart-header,
            .skeleton-chart-area {
                animation: none;
            }

            .chart-container {
                transition: none;
            }
        }

        /* Mobile responsive adjustments */
        @media (max-width: 768px) {
            .chart-container {
                min-height: 250px;
            }

            .chart-loading-state {
                padding: var(--spacing-sm);
            }
        }

        /* Dark mode support */
        [data-theme="dark"] .chart-loading-state {
            background: var(--neutral-900);
        }

        [data-theme="dark"] .skeleton-chart-header,
        [data-theme="dark"] .skeleton-chart-area {
            background: linear-gradient(90deg, var(--neutral-700) 25%, var(--neutral-800) 50%, var(--neutral-700) 75%);
        }
    </style>
@endpush
<!-- js  -->
@push('scripts')
    
    @include('backend.dashboard-charts')
    @include('backend.calender-js')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script type="text/javascript"
        src="{{ static_asset('backend/js/date-range-picker/dashboard-date-range-picker-custom.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- datetime -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/js/bootstrap-datetimepicker.min.js"
        crossorigin="anonymous"></script>


    <script type="text/javascript">
        $('#datetimepicker12').datetimepicker({
            inline: true,
            sideBySide: true
        });
    </script>

    <!-- Real-time Dashboard Updates - DISABLED -->
    <script>
        // Real-time dashboard functionality disabled - API endpoints not configured
        // To enable: Implement /api/v10/dashboard/updates and /api/v10/dashboard/realtime-status endpoints
        
        console.info('Real-time dashboard updates: Disabled (endpoints not configured)');
        console.info('Dashboard will use static data from server render');

        // All real-time update functions disabled
        // Uncomment when API endpoints are implemented

        // Real-time functions disabled - uncomment when API endpoints are ready
    </script>
    {{-- Breadcrumb Drill-down Integration --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initializeKpiDrilldown();
        });

        function initializeKpiDrilldown() {
            // Handle KPI card drill-down clicks
            document.querySelectorAll('.kpi-drilldown[data-breadcrumb-update]').forEach(link => {
                link.addEventListener('click', function(e) {
                    const drilldownData = JSON.parse(this.dataset.drilldown || '{}');

                    if (drilldownData.url && drilldownData.title) {
                        e.preventDefault();

                        // Update breadcrumb with drill-down path
                        updateBreadcrumbForDrilldown(drilldownData);

                        // Navigate to the drill-down URL
                        window.location.href = drilldownData.url;
                    }
                });
            });
        }

        function updateBreadcrumbForDrilldown(drilldownData) {
            // Get current breadcrumb data
            const currentBreadcrumbs = @json($breadcrumbs ?? []);

            // Create new breadcrumb path
            const newBreadcrumbs = [
                ...currentBreadcrumbs.map(crumb => ({ ...crumb, active: false })),
                {
                    title: drilldownData.title,
                    url: drilldownData.url,
                    active: true,
                    icon: getKpiIcon(drilldownData.kpi),
                    data: {
                        'drilldown-filters': drilldownData.filters || {}
                    }
                }
            ];

            // Store in session storage for persistence
            sessionStorage.setItem('breadcrumb-path', JSON.stringify(newBreadcrumbs));
            sessionStorage.setItem('drilldown-filters', JSON.stringify(drilldownData.filters || {}));
        }

        function getKpiIcon(kpi) {
            const iconMap = {
                'total-parcels': 'fas fa-box',
                'total-users': 'fas fa-users',
                'total-merchants': 'fas fa-store',
                'total-delivery-men': 'fas fa-truck',
                'total-hubs': 'fas fa-map-marker-alt',
                'total-accounts': 'fas fa-calculator',
                'total-customers': 'fas fa-user-friends',
                'book-shipment': 'fas fa-clipboard-check',
                'total-partial-delivered': 'fas fa-exclamation-triangle',
                'total-parcels-delivered': 'fas fa-check-circle'
            };

            return iconMap[kpi] || 'fas fa-chart-bar';
        }

        // Restore breadcrumb state on page load
        function restoreBreadcrumbState() {
            const storedPath = sessionStorage.getItem('breadcrumb-path');
            if (storedPath) {
                try {
                    const breadcrumbs = JSON.parse(storedPath);
                    // Update the breadcrumb component if it exists
                    if (window.BreadcrumbManager) {
                        window.BreadcrumbManager.updateBreadcrumbs(breadcrumbs);
                    }
                } catch (e) {
                    console.warn('Failed to restore breadcrumb state:', e);
                }
            }
        }

        // Call restore on page load
        restoreBreadcrumbState();
    </script>
@endpush
