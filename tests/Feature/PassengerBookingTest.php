<?php

namespace Tests\Feature;

use App\Enums\RideStatus;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PassengerBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_passenger_can_book_a_ride_with_a_server_calculated_fare(): void
    {
        $passenger = User::factory()->create();

        $response = $this->actingAs($passenger)->post(route('passenger.rides.store'), [
            'pickup_address' => 'Attock City',
            'pickup_lat' => '33.76670000',
            'pickup_lng' => '72.35920000',
            'dropoff_address' => 'Sanjwal',
            'dropoff_lat' => '33.75500000',
            'dropoff_lng' => '72.43300000',
        ]);

        $ride = Ride::query()->firstOrFail();
        $response->assertRedirect(route('passenger.rides.show', $ride));
        $this->assertSame(RideStatus::Searching, $ride->status);
        $this->assertNotSame('0.00', $ride->estimated_fare);
        $this->assertSame($passenger->id, $ride->passenger_id);
    }

    public function test_passenger_cannot_book_more_than_one_active_ride(): void
    {
        $passenger = User::factory()->create();
        Ride::factory()->create(['passenger_id' => $passenger->id, 'status' => RideStatus::Searching]);

        $this->actingAs($passenger)->from(route('passenger.home'))->post(route('passenger.rides.store'), [
            'pickup_address' => 'Attock City',
            'pickup_lat' => '33.76670000',
            'pickup_lng' => '72.35920000',
            'dropoff_address' => 'Sanjwal',
            'dropoff_lat' => '33.75500000',
            'dropoff_lng' => '72.43300000',
        ])->assertSessionHasErrors('ride');

        $this->assertSame(1, Ride::query()->where('passenger_id', $passenger->id)->count());
    }

    public function test_passengers_cannot_view_another_passengers_ride(): void
    {
        $ride = Ride::factory()->create();
        $otherPassenger = User::factory()->create();

        $this->actingAs($otherPassenger)->get(route('passenger.rides.show', $ride))->assertForbidden();
    }
}
