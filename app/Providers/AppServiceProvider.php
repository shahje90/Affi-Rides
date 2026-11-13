<?php

namespace App\Providers;

use App\Models\Ride;
use App\Models\RiderProfile;
use App\Policies\RidePolicy;
use App\Policies\RiderProfilePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Ride::class, RidePolicy::class);
        Gate::policy(RiderProfile::class, RiderProfilePolicy::class);
    }
}
