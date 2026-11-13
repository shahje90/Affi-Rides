<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterPassengerRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $phone = (string) $request->validated('phone');
        $key = 'login:'.$request->ip().'|'.mb_strtolower($phone);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors([
                'phone' => __('Too many login attempts. Try again in :seconds seconds.', [
                    'seconds' => RateLimiter::availableIn($key),
                ]),
            ])->onlyInput('phone');
        }

        if (! Auth::attempt($request->only('phone', 'password'), $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);

            return back()->withErrors(['phone' => __('These credentials do not match our records.')])
                ->onlyInput('phone');
        }

        if (Auth::user()->status === UserStatus::Suspended) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['phone' => __('Your account is suspended. Contact support for assistance.')])
                ->onlyInput('phone');
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->route($this->destination(Auth::user()->role));
    }

    public function register(RegisterPassengerRequest $request): RedirectResponse
    {
        $user = User::create([
            ...$request->safe()->only(['name', 'phone']),
            'password' => $request->validated('password'),
            'role' => UserRole::Passenger,
            'status' => UserStatus::Active,
            'language' => app()->getLocale(),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('passenger.home');
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function destination(UserRole $role): string
    {
        return match ($role) {
            UserRole::SuperAdmin => 'admin.dashboard',
            UserRole::Rider => 'rider.dashboard',
            UserRole::Passenger => 'passenger.home',
        };
    }
}
