<?php

namespace Database\Factories;

use App\Enums\RideStatus;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ride>
 */
class RideFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'passenger_id' => User::factory(),
            'rider_id' => null,
            'pickup_address' => 'Attock City',
            'pickup_lat' => '33.7667',
            'pickup_lng' => '72.3592',
            'dropoff_address' => 'Sanjwal',
            'dropoff_lat' => '33.7550',
            'dropoff_lng' => '72.4330',
            'distance_km' => '8.50',
            'estimated_fare' => '315.00',
            'final_fare' => null,
            'company_commission' => '0.00',
            'rider_earning' => '0.00',
            'status' => RideStatus::Searching,
        ];
    }
}
