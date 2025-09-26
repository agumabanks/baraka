@extends('frontend.layouts.master')
@section('title', __('levels.dashboard'))
@section('content')
<section class="py-5 bg-light">
  <div class="container">
    @if(Auth::check())
      <div class="mb-4">
        <h2 class="h4 mb-1">{{ __('levels.welcome') }}, {{ Auth::user()->name }} 👋</h2>
        <p class="text-muted mb-0">{{ __('messages.here_is_overview') ?? 'Here’s a quick overview of your activity.' }}</p>
      </div>

      @php
        $hasShipments = Schema::hasTable('shipments');
        $user = Auth::user();
        $shipmentsCount = $hasShipments ? $user->shipments()->count() : 0;
        $inTransit = $hasShipments ? $user->shipments()->where('current_status', \App\Enums\ShipmentStatus::DEPART)->orWhere('current_status', \App\Enums\ShipmentStatus::OUT_FOR_DELIVERY)->count() : 0;
        $delivered = $hasShipments ? $user->shipments()->where('current_status', \App\Enums\ShipmentStatus::DELIVERED)->count() : 0;
        $recent = $hasShipments ? $user->shipments()->with(['originBranch','destBranch'])->latest()->limit(5)->get() : collect();
      @endphp

      <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <div class="text-uppercase small text-muted">{{ __('levels.shipments') }}</div>
                  <div class="fs-4 fw-bold">{{ $shipmentsCount }}</div>
                </div>
                <i class="fa fa-box fs-3 text-primary"></i>
              </div>
            </div>
          </div>
          @php
            $payments = collect();
            if (Schema::hasTable('payments') && optional($user->merchant)->id) {
              $payments = \App\Models\Backend\Payment::where('merchant_id', $user->merchant->id)
                          ->latest()->limit(5)->get();
            }
          @endphp
          <div class="card shadow-sm mt-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
              <h5 class="mb-0">{{ __('levels.transaction_history') ?? 'Transaction History' }}</h5>
            </div>
            <div class="card-body p-0">
              @if($payments->isEmpty())
                <div class="p-4 text-center text-muted">{{ __('messages.no_transactions') ?? 'No transactions found.' }}</div>
              @else
                <div class="table-responsive">
                  <table class="table table-hover mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>{{ __('levels.date') ?? 'Date' }}</th>
                        <th>{{ __('levels.amount') ?? 'Amount' }}</th>
                        <th>{{ __('levels.reference') ?? 'Reference' }}</th>
                      </tr>
                    </thead>
                    <tbody>
                    @foreach($payments as $p)
                      <tr>
                        <td>{{ $p->created_at?->format('Y-m-d H:i') }}</td>
                        <td>{{ currency($p->amount) }}</td>
                        <td>{{ $p->transaction_id ?? '—' }}</td>
                      </tr>
                    @endforeach
                    </tbody>
                  </table>
                </div>
              @endif
            </div>
          </div>
        </div>

        <div class="col-6 col-md-3">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <div class="text-uppercase small text-muted">{{ __('levels.in_transit') ?? 'In Transit' }}</div>
                  <div class="fs-4 fw-bold">{{ $inTransit }}</div>
                </div>
                <i class="fa fa-truck-fast fs-3 text-warning"></i>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <div class="text-uppercase small text-muted">{{ __('levels.delivered') ?? 'Delivered' }}</div>
                  <div class="fs-4 fw-bold">{{ $delivered }}</div>
                </div>
                <i class="fa fa-circle-check fs-3 text-success"></i>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  @php
                    $unpaidInvoices = 0;
                    if (Schema::hasTable('invoices')) {
                      if (Schema::hasColumn('invoices', 'customer_id')) {
                        $unpaidInvoices = \App\Models\Invoice::where('customer_id', $user->id ?? 0)
                          ->where('status','!=','PAID')->count();
                      } elseif (Schema::hasColumn('invoices', 'merchant_id')) {
                        $mid = optional($user->merchant)->id;
                        if ($mid) {
                          $unpaidInvoices = \App\Models\Backend\Merchantpanel\Invoice::where('merchant_id', $mid)
                            ->where('status','!=', \App\Enums\InvoiceStatus::PAID)->count();
                        }
                      }
                    }
                  @endphp
                  <div class="text-uppercase small text-muted">{{ __('levels.invoices') ?? 'Invoices' }}</div>
                  <div class="fs-4 fw-bold">{{ $unpaidInvoices }}</div>
                </div>
                <i class="fa fa-file-invoice fs-3 text-info"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-4">
        <div class="col-lg-8">
          <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
              <h5 class="mb-0">{{ __('levels.recent_shipments') ?? 'Recent Shipments' }}</h5>
              <a href="{{ route('tracking.index') }}" class="btn btn-sm btn-outline-primary">{{ __('levels.track') ?? 'Track' }}</a>
            </div>
            <div class="card-body p-0">
              @if($recent->isEmpty())
                <div class="p-4 text-center text-muted">{{ __('messages.no_recent_shipments') ?? 'No recent shipments yet.' }}</div>
              @else
                <div class="table-responsive">
                  <table class="table table-hover mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>#</th>
                        <th>{{ __('levels.origin') ?? 'Origin' }}</th>
                        <th>{{ __('levels.destination') ?? 'Destination' }}</th>
                        <th>{{ __('levels.status') ?? 'Status' }}</th>
                        <th>{{ __('levels.created_at') ?? 'Created' }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($recent as $s)
                        <tr>
                          <td>{{ $s->id }}</td>
                          <td>{{ $s->originBranch->name ?? '—' }}</td>
                          <td>{{ $s->destBranch->name ?? '—' }}</td>
                          <td>
                            <span class="badge bg-secondary">{{ __("ShipmentStatus.".$s->current_status->value) ?? $s->current_status->value }}</span>
                          </td>
                          <td>{{ $s->created_at?->format('Y-m-d') }}</td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              @endif
            </div>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="card shadow-sm mb-4">
            <div class="card-body">
              <h6 class="mb-3">{{ __('levels.quick_actions') ?? 'Quick Actions' }}</h6>
              <div class="d-grid gap-2">
                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#shipModal"><i class="fa fa-ship me-2"></i>{{ __('levels.ship') }}</a>
                <a href="{{ route('tracking.index') }}" class="btn btn-primary"><i class="fa fa-location-crosshairs me-2"></i>{{ __('levels.track_a_shipment') ?? 'Track a shipment' }}</a>
                <a href="{{ route('contact.send.page') }}" class="btn btn-outline-secondary"><i class="fa fa-headset me-2"></i>{{ __('levels.support') ?? 'Contact support' }}</a>
              </div>
            </div>
          </div>
          <div class="card shadow-sm">
            <div class="card-body">
              <h6 class="mb-2">{{ __('levels.profile') ?? 'Profile' }}</h6>
              <p class="small text-muted mb-1">{{ Auth::user()->email }}</p>
              <p class="small text-muted">{{ Auth::user()->phone_e164 ?? Auth::user()->mobile ?? '' }}</p>
              <a href="#" class="btn btn-sm btn-outline-primary">{{ __('levels.edit_profile') ?? 'Edit profile' }}</a>
            </div>
          </div>
          @php
            $myNotifications = collect();
            if (Schema::hasTable('notifications') && isset($user)) {
              $notificationQuery = \App\Models\Backend\Notification::query();
              $matchedColumn = null;

              if (Schema::hasColumn('notifications', 'user_id')) {
                $matchedColumn = 'user_id';
              } elseif (Schema::hasColumn('notifications', 'merchant_id')) {
                $matchedColumn = 'merchant_id';
              } elseif (Schema::hasColumn('notifications', 'notifiable_id')) {
                $matchedColumn = 'notifiable_id';
                $notificationQuery->where('notifiable_type', \App\Models\User::class);
              }

              if ($matchedColumn) {
                $myNotifications = $notificationQuery->where($matchedColumn, $user->id)
                  ->latest()->limit(5)->get();
              }
            }
          @endphp
          <div class="card shadow-sm mt-4">
            <div class="card-body">
              <h6 class="mb-3">{{ __('levels.notifications') ?? 'Notifications' }}</h6>
              @forelse($myNotifications as $n)
                <div class="d-flex justify-content-between border-bottom py-2">
                  <div>
                    <div class="fw-semibold">{{ $n->title }}</div>
                    <div class="small text-muted">{{ $n->type }} • {{ $n->created_at?->diffForHumans() }}</div>
                  </div>
                </div>
              @empty
                <div class="text-muted small">{{ __('messages.no_notifications') ?? 'No notifications.' }}</div>
              @endforelse
            </div>
          </div>

          @php
            $openTickets = 0; $recentTickets = collect();
            if (Schema::hasTable('supports') && isset($user) && Schema::hasColumn('supports', 'user_id')) {
              $openTickets = \App\Models\Backend\Support::where('user_id', $user->id)->whereIn('status',[1,2])->count();
              $recentTickets = \App\Models\Backend\Support::where('user_id', $user->id)->latest()->limit(5)->get();
            }
          @endphp
          <div class="card shadow-sm mt-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
              <h6 class="mb-0">{{ __('levels.service_requests') ?? 'Service Requests' }}</h6>
              <span class="badge bg-secondary">{{ $openTickets }}</span>
            </div>
            <div class="card-body p-0">
              @if($recentTickets->isEmpty())
                <div class="p-3 text-muted small">{{ __('messages.no_service_requests') ?? 'No service requests yet.' }}</div>
              @else
                <ul class="list-group list-group-flush">
                  @foreach($recentTickets as $t)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <div class="text-truncate" style="max-width: 220px;">
                        <div class="fw-semibold">{{ $t->subject }}</div>
                        <div class="small text-muted">{{ $t->created_at?->diffForHumans() }}</div>
                      </div>
                      <span class="badge bg-light text-dark">{!! $t->my_status !!}</span>
                    </li>
                  @endforeach
                </ul>
              @endif
            </div>
          </div>
        </div>
      </div>
    @else
      <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
          <div class="card shadow-sm">
            <div class="card-body p-4 text-center">
              <img src="{{ optional(settings())->logo_image ?? static_asset('images/default/logo1.png') }}" alt="Logo" height="48" class="mb-3">
              <h3 class="h5 mb-2">{{ __('levels.welcome') }} {{ settings()->name ?? '' }}</h3>
              <p class="text-muted">{{ __('messages.sign_in_to_continue') ?? 'Sign in to view your dashboard.' }}</p>
              <a href="{{ route('login') }}" class="btn btn-primary">{{ __('levels.login') }}</a>
              <a href="{{ route('customer.sign-up') }}" class="btn btn-outline-secondary ms-2">{{ __('levels.register') }}</a>
            </div>
          </div>
        </div>
      </div>
    @endif
  </div>
</section>

<!-- Ship Modal -->
<div class="modal fade" id="shipModal" tabindex="-1" aria-labelledby="shipModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="shipModalLabel">{{ __('levels.ship') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Get Started Now Section -->
        <div class="mb-4">
          <h6 class="fw-bold">{{ __('levels.get_started_now') }}</h6>
          <div class="row g-2">
            <div class="col-md-6">
              <a href="{{ route('portal.create_shipment') }}" class="btn btn-outline-primary w-100 text-start">
                <i class="fa fa-plus me-2"></i>{{ __('levels.create_shipment') }}
              </a>
            </div>
            <div class="col-md-6">
              <a href="{{ route('portal.create_shipment_from_past') }}" class="btn btn-outline-primary w-100 text-start">
                <i class="fa fa-history me-2"></i>{{ __('levels.create_shipment_from_past') }}
              </a>
            </div>
            <div class="col-md-6">
              <a href="{{ route('portal.create_shipment_from_favorite') }}" class="btn btn-outline-primary w-100 text-start">
                <i class="fa fa-star me-2"></i>{{ __('levels.create_shipment_from_favorite') }}
              </a>
            </div>
            <div class="col-md-6">
              <a href="{{ route('portal.get_rate_quote') }}" class="btn btn-outline-primary w-100 text-start">
                <i class="fa fa-calculator me-2"></i>{{ __('levels.get_rate_and_time_quote') }}
              </a>
            </div>
            <div class="col-md-6">
              <a href="{{ route('portal.schedule_pickup') }}" class="btn btn-outline-primary w-100 text-start">
                <i class="fa fa-calendar me-2"></i>{{ __('levels.schedule_pickup') }}
              </a>
            </div>
            <div class="col-md-6">
              <a href="{{ route('portal.upload_shipment_file') }}" class="btn btn-outline-primary w-100 text-start">
                <i class="fa fa-upload me-2"></i>{{ __('levels.upload_shipment_file') }}
              </a>
            </div>
            <div class="col-md-6">
              <a href="{{ route('portal.order_supplies') }}" class="btn btn-outline-primary w-100 text-start">
                <i class="fa fa-shopping-cart me-2"></i>{{ __('levels.order_supplies') }}
              </a>
            </div>
          </div>
        </div>

        <!-- Explore Section -->
        <div class="mb-4">
          <h6 class="fw-bold">{{ __('levels.explore') }}</h6>
          <div class="row g-2">
            <div class="col-md-6">
              <a href="{{ route('portal.delivery_services') }}" class="btn btn-outline-secondary w-100 text-start">
                <i class="fa fa-truck me-2"></i>{{ __('levels.delivery_services') }}
              </a>
            </div>
            <div class="col-md-6">
              <a href="{{ route('portal.optional_services') }}" class="btn btn-outline-secondary w-100 text-start">
                <i class="fa fa-cogs me-2"></i>{{ __('levels.optional_services') }}
              </a>
            </div>
            <div class="col-md-6">
              <a href="{{ route('portal.customs_services') }}" class="btn btn-outline-secondary w-100 text-start">
                <i class="fa fa-globe me-2"></i>{{ __('levels.customs_services') }}
              </a>
            </div>
            <div class="col-md-6">
              <a href="{{ route('portal.surcharges') }}" class="btn btn-outline-secondary w-100 text-start">
                <i class="fa fa-dollar-sign me-2"></i>{{ __('levels.surcharges') }}
              </a>
            </div>
            <div class="col-md-6">
              <a href="{{ route('portal.solutions') }}" class="btn btn-outline-secondary w-100 text-start">
                <i class="fa fa-lightbulb me-2"></i>{{ __('levels.solutions') }}
              </a>
            </div>
          </div>
        </div>

        <!-- MyBaraka+ Section -->
        <div class="mb-4">
          <h6 class="fw-bold">{{ __('levels.mybaraka_plus') }}</h6>
          <div class="row g-2">
            <div class="col-md-6">
              <a href="{{ route('portal.learn') }}" class="btn btn-outline-info w-100 text-start">
                <i class="fa fa-graduation-cap me-2"></i>{{ __('levels.learn') }}
              </a>
            </div>
            <div class="col-md-6">
              <a href="{{ route('portal.about_mybaraka_plus') }}" class="btn btn-outline-info w-100 text-start">
                <i class="fa fa-info-circle me-2"></i>{{ __('levels.about_mybaraka_plus') }}
              </a>
            </div>
            <div class="col-md-6">
              <a href="{{ route('portal.whats_new') }}" class="btn btn-outline-info w-100 text-start">
                <i class="fa fa-newspaper me-2"></i>{{ __('levels.whats_new_with_mybaraka_plus') }}
              </a>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('levels.cancel') }}</button>
      </div>
    </div>
  </div>
</div>

@endsection
