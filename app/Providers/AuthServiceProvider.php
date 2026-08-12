<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function (User $user) {
            return $user->isSuperAdmin() ? true : null;
        });

        Gate::define('viewWmsControl', fn (User $user) => $user->isWarehouseOperator() || $user->isPurchasing() || $user->isAccounting());
        Gate::define('operateWarehouse', fn (User $user) => $user->isWarehouseOperator());
        Gate::define('controlInventoryFinance', fn (User $user) => $user->isAccounting());
        Gate::define('matchSupplierInvoice', fn (User $user) => $user->isPurchasing() || $user->isAccounting());
    }
}
