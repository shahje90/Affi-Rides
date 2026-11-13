<?php

namespace App\Services;

use App\Enums\RideStatus;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RideBookingService
{
    public function __construct(private readonly FareCalculator $fareCalculator) {}

    /**
     * @param  array{pickup_address: string, pickup_lat: string|float, pickup_lng: string|float, dropoff_address: string, dropoff_lat: string|float, dropoff_lng: string|float}  $data
     */
    public function book(int $passengerId, array $data): Ride
    {
        return DB::transaction(function () use ($passengerId, $data): Ride {
            User::query()->lockForUpdate()->findOrFail($passengerId);

            $hasActiveRide = Ride::query()
                ->where('passenger_id', $passengerId)
                ->whereIn('status', [
                    RideStatus::Searching->value,
                    RideStatus::Accepted->value,
                    RideStatus::Arrived->value,
                    RideStatus::InProgress->value,
                ])
                ->exists();

            if ($hasActiveRide) {
                throw ValidationException::withMessages([
                    'ride' => __('You already have an active ride.'),
                ]);
            }

            $fare = $this->fareCalculator->estimate(
                (float) $data['pickup_lat'],
                (float) $data['pickup_lng'],
                (float) $data['dropoff_lat'],
                (float) $data['dropoff_lng'],
            );

            return Ride::query()->create([
                ...$data,
                'passenger_id' => $passengerId,
                'distance_km' => $fare['distance_km'],
                'estimated_fare' => $fare['estimated_fare'],
                'status' => RideStatus::Searching,
            ]);
        });
    }
}
