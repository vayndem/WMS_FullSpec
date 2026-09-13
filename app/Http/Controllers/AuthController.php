<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\UserAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(private DashboardService $dashboard, private UserAccountService $akun) {}

    public function showLoginForm()
    {
        return view('login');
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->safe()->only(['email', 'password']);

        if ($this->akun->akunNonaktifDenganKredensialBenar($credentials['email'], $credentials['password'])) {
            $this->akun->catatLoginGagal($credentials['email'], $request->ip(), true);

            return back()
                ->withErrors(['email' => 'Akun Anda dinonaktifkan. Hubungi administrator sistem.'])
                ->onlyInput('email');
        }

        if (!Auth::attempt($credentials + ['is_active' => true], $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Email atau password tidak valid.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function dashboard()
    {
        /** @var User $user */
        $user = Auth::user();
        $papan = $this->dashboard->untuk($user);

        return view($papan['view'], array_merge(compact('user'), $papan['data']));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
