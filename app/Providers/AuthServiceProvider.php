<?php

namespace App\Providers;

use App\Enums\UserType;
use App\Models\Backend\Role;
use App\Models\User;
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
        \App\Models\Backend\Branch::class => \App\Policies\BranchPolicy::class,
        \App\Models\Driver::class => \App\Policies\DriverPolicy::class,
        \App\Models\DriverRoster::class => \App\Policies\DriverRosterPolicy::class,
        \App\Models\DriverTimeLog::class => \App\Policies\DriverTimeLogPolicy::class,
        // New DHL-grade modules (branch scoped)
        \App\Models\Quotation::class => \App\Policies\BranchScopedPolicy::class,
        \App\Models\Contract::class => \App\Policies\BranchScopedPolicy::class,
        \App\Models\AddressBook::class => \App\Policies\BranchScopedPolicy::class,
        \App\Models\KycRecord::class => \App\Policies\BranchScopedPolicy::class,
        \App\Models\DangerousGood::class => \App\Policies\BranchScopedPolicy::class,
        \App\Models\Ics2Filing::class => \App\Policies\BranchScopedPolicy::class,
        \App\Models\AwbStock::class => \App\Policies\BranchScopedPolicy::class,
        \App\Models\Manifest::class => \App\Policies\BranchScopedPolicy::class,
        \App\Models\Ecmr::class => \App\Policies\BranchScopedPolicy::class,
        \App\Models\SortationBin::class => \App\Policies\BranchScopedPolicy::class,
        \App\Models\WhLocation::class => \App\Policies\BranchScopedPolicy::class,
        \App\Models\ReturnOrder::class => \App\Policies\BranchScopedPolicy::class,
        \App\Models\Claim::class => \App\Policies\BranchScopedPolicy::class,
        \App\Models\SurchargeRule::class => \App\Policies\BranchScopedPolicy::class,
        \App\Models\CashOffice::class => \App\Policies\BranchScopedPolicy::class,
        \App\Models\FxRate::class => \App\Policies\BranchScopedPolicy::class,
        // Zones & Carriers
        \App\Models\Zone::class => \App\Policies\BranchScopedPolicy::class,
        \App\Models\Lane::class => \App\Policies\BranchScopedPolicy::class,
        \App\Models\Carrier::class => \App\Policies\BranchScopedPolicy::class,
        \App\Models\CarrierService::class => \App\Policies\BranchScopedPolicy::class,
        \App\Models\WhatsappTemplate::class => \App\Policies\BranchScopedPolicy::class,
        \App\Models\EdiProvider::class => \App\Policies\BranchScopedPolicy::class,
        \App\Models\Survey::class => \App\Policies\BranchScopedPolicy::class,
        \App\Models\WorkflowTask::class => \App\Policies\WorkflowTaskPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::before(function (User $user) {
            if (isset($user->user_type) && (int) $user->user_type === UserType::ADMIN) {
                return true;
            }

            return null;
        });

        Gate::define('admin.roles.viewAny', fn (User $user): bool => $user->hasPermission('role_read'));
        Gate::define('admin.roles.view', fn (User $user, Role $role): bool => $user->hasPermission('role_read'));
        Gate::define('admin.roles.create', fn (User $user): bool => $user->hasPermission('role_create'));
        Gate::define('admin.roles.update', fn (User $user, Role $role): bool => $user->hasPermission('role_update'));
        Gate::define('admin.roles.delete', fn (User $user, Role $role): bool => $user->hasPermission('role_delete'));

        Gate::define('admin.users.viewAny', fn (User $user): bool => $user->hasPermission('user_read'));
        Gate::define('admin.users.view', fn (User $user, User $subject): bool => $user->hasPermission('user_read'));
        Gate::define('admin.users.create', fn (User $user): bool => $user->hasPermission('user_create'));
        Gate::define('admin.users.update', fn (User $user, User $subject): bool => $user->hasPermission('user_update'));
        Gate::define('admin.users.delete', fn (User $user, User $subject): bool => $user->hasPermission('user_delete'));
    }
}
