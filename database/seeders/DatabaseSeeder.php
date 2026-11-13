<?php

namespace Database\Seeders;

use App\Enums\KycStatus;
use App\Enums\UserRole;
use App\Models\RiderProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SettingsSeeder::class);

        if (! app()->environment('local', 'testing')) {
            return;
        }

        User::factory()->superAdmin()->create([
            'name' => 'ACR Administrator',
            'phone' => '03000000001',
            'email' => 'admin@acrapp.test',
            'password' => 'password',
        ]);

        for ($i = 1; $i <= 5; $i++) {
            $rider = User::factory()->rider()->create([
                'name' => "Demo Rider {$i}",
                'phone' => sprintf('030000000%02d', 10 + $i),
                'email' => "rider{$i}@acrapp.test",
                'password' => 'password',
            ]);

            RiderProfile::factory()->create([
                'user_id' => $rider->id,
                'kyc_status' => KycStatus::Approved,
                'wallet_balance' => '1000.00',
            ]);
        }

        for ($i = 1; $i <= 3; $i++) {
            User::factory()->create([
                'name' => "Demo Passenger {$i}",
                'phone' => sprintf('030000000%02d', 20 + $i),
                'email' => "passenger{$i}@acrapp.test",
                'password' => 'password',
                'role' => UserRole::Passenger,
            ]);
        }
    }
}
