<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function redirectUser(): RedirectResponse
    {
        $route = match (Auth::user()->role) {
            UserRole::SuperAdmin => 'admin.dashboard',
            UserRole::Rider => 'rider.dashboard',
            UserRole::Passenger => 'passenger.home',
        };

        return redirect()->route($route);
    }

    public function __invoke(): View
    {
        return view('dashboard');
    }
}
