<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Models\ApiUser;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('*', function ($view) {
            $view->with('user', session('user_data'));
        });

        Auth::viaRequest('api-session', function ($request) {
            if ($request->session()->has('user_data')) {
                return new ApiUser($request->session()->get('user_data'));
            }
            return null;
        });

        config(['auth.guards.web.driver' => 'api-session']);
    }
}
