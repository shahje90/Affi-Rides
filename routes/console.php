<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Services\RideEngineService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('acr:expire-ride-requests', function (RideEngineService $engine): void {
    $count = $engine->expireSearchingRides();
    $this->info("Expired {$count} ride request(s).");
})->purpose('Expire ride requests that were not accepted in time');

Artisan::command('acr:offline-stale-riders', function (RideEngineService $engine): void {
    $count = $engine->takeStaleRidersOffline();
    $this->info("Took {$count} stale rider(s) offline.");
})->purpose('Take riders with stale location updates offline');

Schedule::command('acr:expire-ride-requests')->everyMinute()->withoutOverlapping();
Schedule::command('acr:offline-stale-riders')->everyMinute()->withoutOverlapping();

Artisan::command('acr:create-admin', function (): void {
    $name = text('Administrator name', required: true);
    $phone = text('Administrator phone number', validate: function (string $value): ?string {
        if (! preg_match('/^\+?[0-9]{10,15}$/', $value)) {
            return 'Enter a phone number containing 10 to 15 digits.';
        }

        return User::query()->where('phone', $value)->exists()
            ? 'That phone number is already registered.'
            : null;
    });
    $adminPassword = password('Administrator password', validate: fn (string $value): ?string => strlen($value) < 12
        ? 'Use a password with at least 12 characters.'
        : null);
    $passwordConfirmation = password('Confirm administrator password', validate: fn (string $value): ?string => $value !== $adminPassword
        ? 'The passwords do not match.'
        : null);

    User::query()->create([
        'name' => $name,
        'phone' => $phone,
        'password' => $passwordConfirmation,
        'role' => UserRole::SuperAdmin,
        'status' => UserStatus::Active,
        'language' => 'en',
    ]);

    $this->info('Super administrator account created.');
})->purpose('Create a super administrator using secure interactive prompts');
