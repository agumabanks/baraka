<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\User::class => \App\Policies\CustomerPolicy::class,
        \App\Models\Customer::class => \App\Policies\CustomerPolicy::class,
        \App\Models\Shipment::class => \App\Policies\ShipmentPolicy::class,
        \App\Models\Backend\Parcel::class => \App\Policies\ParcelPolicy::class,
        \App\Models\Bag::class => \App\Policies\ErpModelPolicy::class,
        \App\Models\TransportLeg::class => \App\Policies\ErpModelPolicy::class,
        \App\Models\ScanEvent::class => \App\Policies\ErpModelPolicy::class,
        \App\Models\Route::class => \App\Policies\ErpModelPolicy::class,
        \App\Models\Epod::class => \App\Policies\ErpModelPolicy::class,
        \App\Models\RateCard::class => \App\Policies\ErpModelPolicy::class,
        \App\Models\Invoice::class => \App\Policies\ErpModelPolicy::class,
        \App\Models\CodReceipt::class => \App\Policies\ErpModelPolicy::class,
        \App\Models\Settlement::class => \App\Policies\ErpModelPolicy::class,
        \App\Models\Commodity::class => \App\Policies\ErpModelPolicy::class,
        \App\Models\HsCode::class => \App\Policies\ErpModelPolicy::class,
        \App\Models\CustomsDoc::class => \App\Policies\ErpModelPolicy::class,
        \App\Models\ApiKey::class => \App\Policies\ErpModelPolicy::class,
        \App\Models\Webhook::class => \App\Policies\ErpModelPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
