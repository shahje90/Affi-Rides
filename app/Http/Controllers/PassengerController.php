<?php

namespace App\Http\Controllers;

use App\Enums\RideStatus;
use App\Http\Requests\BookRideRequest;
use App\Http\Requests\EstimateFareRequest;
use App\Models\Ride;
use App\Services\FareCalculator;
use App\Services\RideBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PassengerController extends Controller
{
    public function home(): View|RedirectResponse
    {
        $activeRide = Ride::query()
            ->where('passenger_id', Auth::id())
            ->whereIn('status', [
                RideStatus::Searching->value,
                RideStatus::Accepted->value,
                RideStatus::Arrived->value,
                RideStatus::InProgress->value,
            ])
            ->latest()
            ->first();

        if ($activeRide) {
            return redirect()->route('passenger.rides.show', $activeRide);
        }

        return view('passenger.home');
    }

    public function estimate(EstimateFareRequest $request, FareCalculator $calculator): JsonResponse
    {
        return response()->json($calculator->estimate(
            (float) $request->validated('pickup_lat'),
            (float) $request->validated('pickup_lng'),
            (float) $request->validated('dropoff_lat'),
            (float) $request->validated('dropoff_lng'),
        ));
    }

    public function book(BookRideRequest $request, RideBookingService $bookingService): RedirectResponse
    {
        $ride = $bookingService->book(Auth::id(), $request->validated());

        return redirect()->route('passenger.rides.show', $ride);
    }

    public function history(): View
    {
        return view('passenger.history', [
            'rides' => Ride::query()
                ->where('passenger_id', Auth::id())
                ->with('rider:id,name,phone')
                ->latest()
                ->paginate(15),
        ]);
    }
}
