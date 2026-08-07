<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Models\ApiUser;

class AuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->session()->has('jwt_token')) {
            return redirect()->route('login');
        }

        $sessionUser = $request->session()->get('user_data');

        if ($sessionUser) {
            $user = new ApiUser($sessionUser);
            $user->exists = true;

            Auth::setUser($user);
            View::share('user', $user);
        }

        return $next($request);
    }
}
