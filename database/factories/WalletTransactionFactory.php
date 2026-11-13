<?php

namespace Database\Factories;

use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WalletTransaction>
 */
class WalletTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rider_id' => User::factory()->rider(),
            'type' => WalletTransactionType::Deposit,
            'amount' => '500.00',
            'balance_after' => null,
            'status' => WalletTransactionStatus::Pending,
        ];
    }
}
