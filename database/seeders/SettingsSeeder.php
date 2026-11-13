<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'base_fare' => '60.00',
            'per_km_rate' => '30.00',
            'minimum_fare' => '80.00',
            'commission_percentage' => '15.00',
            'minimum_rider_wallet_limit' => '100.00',
            'search_radius_km' => '5',
            'road_distance_factor' => '1.3',
            'idle_poll_seconds' => '45',
            'active_poll_seconds' => '10',
            'min_move_meters' => '30',
            'payment_qr_code_image' => '',
            'easypaisa_account_title' => '',
            'easypaisa_account_number' => '',
            'jazzcash_account_number' => '',
            'support_phone' => '',
            'ride_request_timeout_seconds' => '60',
            'fare_rounding_increment' => '5',
        ];

        foreach ($defaults as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
