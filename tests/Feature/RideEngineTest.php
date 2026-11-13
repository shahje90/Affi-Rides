<?php

namespace Tests\Feature;

use App\Enums\KycStatus;
use App\Enums\RideStatus;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\Ride;
use App\Models\RiderProfile;
use App\Models\User;
use App\Services\RideEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RideEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_nearby_riders_include_only_eligible_profiles(): void
    {
        $ride = Ride::factory()->create(['pickup_lat' => '33.76670000', 'pickup_lng' => '72.35920000']);
        $eligible = $this->makeRiderProfile([
            'is_online' => true,
            'kyc_status' => KycStatus::Approved,
            'wallet_balance' => '500.00',
            'current_lat' => '33.76700000',
            'current_lng' => '72.35900000',
            'last_location_updated_at' => now(),
        ]);
        $this->makeRiderProfile([
            'is_online' => true,
            'kyc_status' => KycStatus::Pending,
            'wallet_balance' => '500.00',
            'current_lat' => '33.76670000',
            'current_lng' => '72.35920000',
            'last_location_updated_at' => now(),
        ]);
        $this->makeRiderProfile([
            'is_online' => true,
            'kyc_status' => KycStatus::Approved,
            'wallet_balance' => '99.99',
            'current_lat' => '33.76670000',
            'current_lng' => '72.35920000',
            'last_location_updated_at' => now(),
        ]);
        $this->makeRiderProfile([
            'is_online' => true,
            'kyc_status' => KycStatus::Approved,
            'wallet_balance' => '500.00',
            'current_lat' => '34.50000000',
            'current_lng' => '72.90000000',
            'last_location_updated_at' => now(),
        ]);
        $stale = $this->makeRiderProfile([
            'is_online' => true,
            'kyc_status' => KycStatus::Approved,
            'wallet_balance' => '500.00',
            'current_lat' => '33.76670000',
            'current_lng' => '72.35920000',
            'last_location_updated_at' => now()->subMinutes(4),
        ]);
        Ride::factory()->create([
            'rider_id' => $stale->user_id,
            'status' => RideStatus::Accepted,
        ]);

        $riders = app(RideEngineService::class)->nearbyEligibleRiders($ride);

        $this->assertSame([$eligible->id], $riders->modelKeys());
    }

    public function test_only_the_first_rider_can_accept_a_searching_ride(): void
    {
        $first = $this->makeRiderProfile([
            'is_online' => true,
            'kyc_status' => KycStatus::Approved,
            'wallet_balance' => '500.00',
            'current_lat' => '33.76670000',
            'current_lng' => '72.35920000',
            'last_location_updated_at' => now(),
        ]);
        $second = $this->makeRiderProfile([
            'is_online' => true,
            'kyc_status' => KycStatus::Approved,
            'wallet_balance' => '500.00',
            'current_lat' => '33.76670000',
            'current_lng' => '72.35920000',
            'last_location_updated_at' => now(),
        ]);
        $ride = Ride::factory()->create([
            'pickup_lat' => '33.76670000',
            'pickup_lng' => '72.35920000',
        ]);
        $engine = app(RideEngineService::class);

        $accepted = $engine->accept($ride->id, $first->user);
        $this->assertSame($first->user_id, $accepted->rider_id);

        try {
            $engine->accept($ride->id, $second->user);
            $this->fail('A second rider must not accept a taken ride.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('ride', $exception->errors());
        }

        $this->assertSame(RideStatus::Accepted, $ride->fresh()->status);
        $this->assertSame($first->user_id, $ride->fresh()->rider_id);
    }

    public function test_eligible_online_rider_receives_only_nearby_live_requests(): void
    {
        $profile = $this->makeRiderProfile([
            'is_online' => true,
            'kyc_status' => KycStatus::Approved,
            'wallet_balance' => '500.00',
            'current_lat' => '33.76670000',
            'current_lng' => '72.35920000',
            'last_location_updated_at' => now(),
        ]);
        $nearbyRide = Ride::factory()->create([
            'pickup_lat' => '33.76670000',
            'pickup_lng' => '72.35920000',
        ]);
        Ride::factory()->create([
            'pickup_lat' => '34.50000000',
            'pickup_lng' => '72.90000000',
        ]);

        $requests = app(RideEngineService::class)->requestsForRider($profile->user);

        $this->assertSame([$nearbyRide->id], array_column($requests, 'id'));
    }

    public function test_ride_completion_deducts_commission_and_takes_low_balance_rider_offline(): void
    {
        $profile = $this->makeRiderProfile([
            'is_online' => true,
            'kyc_status' => KycStatus::Approved,
            'wallet_balance' => '105.00',
            'current_lat' => '33.76670000',
            'current_lng' => '72.35920000',
            'last_location_updated_at' => now(),
        ]);
        $ride = Ride::factory()->create([
            'pickup_lat' => '33.76670000',
            'pickup_lng' => '72.35920000',
            'estimated_fare' => '80.00',
        ]);
        $engine = app(RideEngineService::class);

        $engine->accept($ride->id, $profile->user);
        $engine->transition($profile->user, $ride->id, RideStatus::Arrived);
        $engine->transition($profile->user, $ride->id, RideStatus::InProgress);
        $completed = $engine->transition($profile->user, $ride->id, RideStatus::Completed);

        $this->assertSame('12.00', $completed->company_commission);
        $this->assertSame('68.00', $completed->rider_earning);
        $this->assertSame(RideStatus::Completed, $completed->status);
        $this->assertSame('93.00', $profile->fresh()->wallet_balance);
        $this->assertFalse($profile->fresh()->is_online);
        $this->assertDatabaseHas('wallet_transactions', [
            'rider_id' => $profile->user_id,
            'ride_id' => $ride->id,
            'type' => WalletTransactionType::CommissionDeduction->value,
            'amount' => '-12.00',
            'balance_after' => '93.00',
            'status' => WalletTransactionStatus::Approved->value,
        ]);
    }

    public function test_expired_searches_are_cancelled_with_retry_reason_and_stale_riders_go_offline(): void
    {
        $ride = Ride::factory()->create([
            'status' => RideStatus::Searching,
            'created_at' => now()->subMinutes(2),
        ]);
        $staleProfile = $this->makeRiderProfile([
            'is_online' => true,
            'last_location_updated_at' => now()->subMinutes(4),
        ]);
        $engine = app(RideEngineService::class);

        $this->assertSame(1, $engine->expireSearchingRides());
        $this->assertSame(1, $engine->takeStaleRidersOffline());
        $this->assertSame(RideStatus::Cancelled, $ride->fresh()->status);
        $this->assertSame('no_riders_found', $ride->fresh()->cancel_reason);
        $this->assertFalse($staleProfile->fresh()->is_online);
    }

    public function test_passenger_status_poll_identifies_a_no_riders_result(): void
    {
        $ride = Ride::factory()->create([
            'status' => RideStatus::Cancelled,
            'cancelled_by' => null,
            'cancel_reason' => 'no_riders_found',
        ]);

        $this->actingAs($ride->passenger)->getJson(route('passenger.rides.status', $ride))
            ->assertOk()
            ->assertJsonPath('no_riders_found', true)
            ->assertJsonPath('status', RideStatus::Cancelled->value);
    }

    private function makeRiderProfile(array $attributes): RiderProfile
    {
        $user = User::factory()->rider()->create();

        return RiderProfile::factory()->create([
            'user_id' => $user->id,
            ...$attributes,
        ]);
    }
}
