<?php

namespace App\Providers;

use App\Models\Request as RequestModel;
use App\Policies\RequestPolicy;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\ServicePurchase;
use App\Models\ServiceBap;
use App\Policies\AssetPolicy;
use App\Policies\AssetCategoryPolicy;
use App\Policies\ServicePurchasePolicy;
use App\Policies\ServiceBapPolicy;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Policies\ServiceCategoryPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        RequestModel::class => RequestPolicy::class,
        Asset::class => AssetPolicy::class,
        AssetCategory::class => AssetCategoryPolicy::class,
        ServicePurchase::class => ServicePurchasePolicy::class,
        ServiceBap::class => ServiceBapPolicy::class,
        ServiceCategory::class => ServiceCategoryPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function (User $user) {
            return $user->isSuperAdmin() ? true : null;
        });
    }
}
