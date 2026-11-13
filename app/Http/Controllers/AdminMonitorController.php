<?php

namespace App\Http\Controllers;

use App\Enums\RideStatus;
use App\Models\Ride;
use App\Models\RiderProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AdminMonitorController extends Controller
{
    public function index(): View
    {
        return view('admin.monitor');
    }

    public function snapshot(): JsonResponse
    {
        $riders = RiderProfile::query()
            ->online()
            ->whereNotNull('current_lat')
            ->whereNotNull('current_lng')
            ->with('user:id,name,phone')
            ->get(['id', 'user_id', 'current_lat', 'current_lng', 'bike_number', 'last_location_updated_at'])
            ->map(fn (RiderProfile $profile): array => [
                'id' => $profile->id,
                'name' => $profile->user->name,
                'phone' => $profile->user->phone,
                'lat' => $profile->current_lat,
                'lng' => $profile->current_lng,
                'bike_number' => $profile->bike_number,
                'updated_at' => $profile->last_location_updated_at?->toIso8601String(),
            ]);

        $rides = Ride::query()
            ->whereIn('status', [
                RideStatus::Searching->value,
                RideStatus::Accepted->value,
                RideStatus::Arrived->value,
                RideStatus::InProgress->value,
            ])
            ->with(['passenger:id,name', 'rider:id,name'])
            ->get([
                'id',
                'passenger_id',
                'rider_id',
                'pickup_address',
                'pickup_lat',
                'pickup_lng',
                'dropoff_address',
                'dropoff_lat',
                'dropoff_lng',
                'status',
            ])
            ->map(fn (Ride $ride): array => [
                'id' => $ride->id,
                'status' => $ride->status->value,
                'passenger' => $ride->passenger->name,
                'rider' => $ride->rider?->name,
                'pickup' => [$ride->pickup_lat, $ride->pickup_lng],
                'dropoff' => [$ride->dropoff_lat, $ride->dropoff_lng],
                'pickup_address' => $ride->pickup_address,
                'dropoff_address' => $ride->dropoff_address,
            ]);

        return response()->json(['riders' => $riders, 'rides' => $rides]);
    }
}
