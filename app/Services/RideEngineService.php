<?php

namespace App\Services;

use App\Enums\KycStatus;
use App\Enums\RideStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\Ride;
use App\Models\RiderProfile;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Support\Setting;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RideEngineService
{
    /**
     * @return Collection<int, RiderProfile>
     */
    public function nearbyEligibleRiders(Ride $ride): Collection
    {
        return RiderProfile::query()
            ->online()
            ->approved()
            ->where('wallet_balance', '>=', (string) Setting::get('minimum_rider_wallet_limit', '100.00'))
            ->where('last_location_updated_at', '>=', now()->subMinutes(3))
            ->whereHas('user', function ($query): void {
                $query->where('role', UserRole::Rider->value)
                    ->where('status', UserStatus::Active->value)
                    ->whereDoesntHave('riderRides', function ($rides): void {
                        $rides->whereIn('status', $this->activeStatuses());
                    });
            })
            ->nearby(
                (float) $ride->pickup_lat,
                (float) $ride->pickup_lng,
                (float) Setting::get('search_radius_km', '5'),
            )
            ->with('user:id,name,phone')
            ->get();
    }

    /**
     * @return array<int, array{id: int, pickup_address: string, dropoff_address: string, distance_km: string, estimated_fare: string, created_at: string}>
     */
    public function requestsForRider(User $user): array
    {
        $profile = $user->riderProfile;

        if (
            $user->role !== UserRole::Rider
            || $user->status !== UserStatus::Active
            || ! $profile
            || ! $profile->is_online
            || $profile->kyc_status !== KycStatus::Approved
            || ! $profile->last_location_updated_at
            || $profile->current_lat === null
            || $profile->current_lng === null
            || $profile->last_location_updated_at->lt(now()->subMinutes(3))
            || BigDecimal::of((string) $profile->wallet_balance)->isLessThan(
                BigDecimal::of((string) Setting::get('minimum_rider_wallet_limit', '100.00')),
            )
            || $this->riderHasActiveRide($user->id)
        ) {
            return [];
        }

        $radius = (float) Setting::get('search_radius_km', '5');
        $timeout = (int) Setting::get('ride_request_timeout_seconds', 60);
        $distance = '(6371 * acos(MIN(1, MAX(-1, cos(radians(?)) * cos(radians(rides.pickup_lat)) * cos(radians(rides.pickup_lng) - radians(?)) + sin(radians(?)) * sin(radians(rides.pickup_lat))))))';

        return Ride::query()
            ->where('status', RideStatus::Searching->value)
            ->where('created_at', '>=', now()->subSeconds($timeout))
            ->whereRaw("{$distance} <= (0 + ?)", [
                (float) $profile->current_lat,
                (float) $profile->current_lng,
                (float) $profile->current_lat,
                $radius,
            ])
            ->latest()
            ->limit(10)
            ->get(['id', 'pickup_address', 'dropoff_address', 'distance_km', 'estimated_fare', 'created_at'])
            ->map(fn (Ride $ride): array => [
                'id' => $ride->id,
                'pickup_address' => $ride->pickup_address,
                'dropoff_address' => $ride->dropoff_address,
                'distance_km' => (string) $ride->distance_km,
                'estimated_fare' => (string) $ride->estimated_fare,
                'created_at' => $ride->created_at->toIso8601String(),
            ])
            ->all();
    }

    public function accept(int $rideId, User $rider): Ride
    {
        return DB::transaction(function () use ($rideId, $rider): Ride {
            $ride = Ride::query()->lockForUpdate()->findOrFail($rideId);

            if ($ride->status !== RideStatus::Searching) {
                throw ValidationException::withMessages(['ride' => __('Ride already taken.')]);
            }

            $timeout = (int) Setting::get('ride_request_timeout_seconds', 60);
            if ($ride->created_at->lt(now()->subSeconds($timeout))) {
                throw ValidationException::withMessages(['ride' => __('This ride request has expired.')]);
            }

            $profile = RiderProfile::query()
                ->where('user_id', $rider->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertRiderEligible($rider, $profile, $ride);

            $ride->update([
                'rider_id' => $rider->id,
                'status' => RideStatus::Accepted,
                'accepted_at' => now(),
            ]);

            Log::info('Ride accepted by rider.', ['ride_id' => $ride->id, 'rider_id' => $rider->id]);

            return $ride->refresh();
        });
    }

    public function transition(User $rider, int $rideId, RideStatus $nextStatus, ?string $cancelReason = null): Ride
    {
        return DB::transaction(function () use ($rider, $rideId, $nextStatus, $cancelReason): Ride {
            $ride = Ride::query()->lockForUpdate()->findOrFail($rideId);
            Gate::forUser($rider)->authorize('viewAsRider', $ride);

            $allowed = match ($nextStatus) {
                RideStatus::Arrived => $ride->status === RideStatus::Accepted,
                RideStatus::InProgress => $ride->status === RideStatus::Arrived,
                RideStatus::Completed => $ride->status === RideStatus::InProgress,
                RideStatus::Cancelled => in_array($ride->status, [RideStatus::Accepted, RideStatus::Arrived], true),
                default => false,
            };

            if (! $allowed) {
                throw ValidationException::withMessages([
                    'status' => __('This ride cannot move from :from to :to.', [
                        'from' => $ride->status->value,
                        'to' => $nextStatus->value,
                    ]),
                ]);
            }

            if ($nextStatus === RideStatus::Arrived) {
                $ride->update(['status' => RideStatus::Arrived]);
            } elseif ($nextStatus === RideStatus::InProgress) {
                $ride->update(['status' => RideStatus::InProgress, 'started_at' => now()]);
            } elseif ($nextStatus === RideStatus::Cancelled) {
                $ride->update([
                    'status' => RideStatus::Cancelled,
                    'cancelled_by' => $rider->id,
                    'cancel_reason' => $cancelReason,
                ]);
            } else {
                $this->complete($ride);
            }

            Log::info('Ride status changed.', [
                'ride_id' => $ride->id,
                'rider_id' => $rider->id,
                'status' => $nextStatus->value,
            ]);

            return $ride->refresh();
        });
    }

    public function expireSearchingRides(): int
    {
        $timeout = (int) Setting::get('ride_request_timeout_seconds', 60);
        $cutoff = now()->subSeconds($timeout);

        return DB::transaction(function () use ($cutoff): int {
            $rides = Ride::query()
                ->where('status', RideStatus::Searching->value)
                ->where('created_at', '<', $cutoff)
                ->lockForUpdate()
                ->get();

            foreach ($rides as $ride) {
                $ride->update([
                    'status' => RideStatus::Cancelled,
                    'cancelled_by' => null,
                    'cancel_reason' => 'no_riders_found',
                ]);
            }

            if ($rides->isNotEmpty()) {
                Log::info('Expired ride requests without an accepting rider.', ['count' => $rides->count()]);
            }

            return $rides->count();
        });
    }

    public function takeStaleRidersOffline(): int
    {
        return DB::transaction(function (): int {
            $count = RiderProfile::query()
                ->where('is_online', true)
                ->where(function ($query): void {
                    $query->whereNull('last_location_updated_at')
                        ->orWhere('last_location_updated_at', '<', now()->subMinutes(3));
                })
                ->update(['is_online' => false]);

            if ($count > 0) {
                Log::info('Riders with stale location were taken offline.', ['count' => $count]);
            }

            return $count;
        });
    }

    private function assertRiderEligible(User $rider, RiderProfile $profile, Ride $ride): void
    {
        $minimumWallet = BigDecimal::of((string) Setting::get('minimum_rider_wallet_limit', '100.00'));
        $hasRecentLocation = $profile->last_location_updated_at
            && $profile->last_location_updated_at->greaterThanOrEqualTo(now()->subMinutes(3));
        $isNear = RiderProfile::query()
            ->whereKey($profile->id)
            ->nearby(
                (float) $ride->pickup_lat,
                (float) $ride->pickup_lng,
                (float) Setting::get('search_radius_km', '5'),
            )
            ->exists();

        if (
            $rider->status !== UserStatus::Active
            || ! $profile->is_online
            || $profile->kyc_status !== KycStatus::Approved
            || BigDecimal::of((string) $profile->wallet_balance)->isLessThan($minimumWallet)
            || ! $hasRecentLocation
            || ! $isNear
            || $this->riderHasActiveRide($rider->id)
        ) {
            throw ValidationException::withMessages([
                'ride' => __('You are no longer eligible for this ride request.'),
            ]);
        }
    }

    private function complete(Ride $ride): void
    {
        $profile = RiderProfile::query()
            ->where('user_id', $ride->rider_id)
            ->lockForUpdate()
            ->firstOrFail();
        $fare = BigDecimal::of((string) $ride->estimated_fare);
        $commissionRate = BigDecimal::of((string) Setting::get('commission_percentage', '15'));
        $commission = $fare
            ->multipliedBy($commissionRate)
            ->dividedBy('100', 2, RoundingMode::HalfUp)
            ->toScale(2, RoundingMode::HalfUp);
        $earning = $fare->minus($commission)->toScale(2, RoundingMode::HalfUp);
        $newBalance = BigDecimal::of((string) $profile->wallet_balance)
            ->minus($commission)
            ->toScale(2, RoundingMode::HalfUp);
        $minimumWallet = BigDecimal::of((string) Setting::get('minimum_rider_wallet_limit', '100.00'));

        $ride->update([
            'status' => RideStatus::Completed,
            'final_fare' => (string) $fare,
            'company_commission' => (string) $commission,
            'rider_earning' => (string) $earning,
            'completed_at' => now(),
        ]);
        $profile->update([
            'wallet_balance' => (string) $newBalance,
            'is_online' => ! $newBalance->isLessThan($minimumWallet),
        ]);
        WalletTransaction::query()->create([
            'rider_id' => $ride->rider_id,
            'ride_id' => $ride->id,
            'type' => WalletTransactionType::CommissionDeduction,
            'amount' => (string) $commission->negated(),
            'balance_after' => (string) $newBalance,
            'status' => WalletTransactionStatus::Approved,
            'reviewed_at' => now(),
        ]);

        Log::info('Ride completed and commission deducted.', [
            'ride_id' => $ride->id,
            'rider_id' => $ride->rider_id,
            'commission' => (string) $commission,
            'wallet_balance' => (string) $newBalance,
        ]);
    }

    private function riderHasActiveRide(int $riderId): bool
    {
        return Ride::query()
            ->where('rider_id', $riderId)
            ->whereIn('status', $this->activeStatuses())
            ->exists();
    }

    /**
     * @return array<int, string>
     */
    private function activeStatuses(): array
    {
        return [
            RideStatus::Accepted->value,
            RideStatus::Arrived->value,
            RideStatus::InProgress->value,
        ];
    }
}
