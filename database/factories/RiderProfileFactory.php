<?php

namespace Database\Factories;

use App\Enums\KycStatus;
use App\Models\RiderProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RiderProfile>
 */
class RiderProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->rider(),
            'cnic_number' => fake()->numerify('#############'),
            'bike_number' => strtoupper(fake()->bothify('??-####')),
            'bike_model' => fake()->randomElement(['Honda CD 70', 'Honda CG 125', 'United 70']),
            'kyc_status' => KycStatus::Pending,
            'is_online' => false,
            'wallet_balance' => '0.00',
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => ['kyc_status' => KycStatus::Approved]);
    }
}
