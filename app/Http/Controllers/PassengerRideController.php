<?php

namespace App\Http\Controllers;

use App\Enums\RideStatus;
use App\Http\Requests\CancelRideRequest;
use App\Models\Ride;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PassengerRideController extends Controller
{
    public function show(Ride $ride): View
    {
        $this->authorize('viewAsPassenger', $ride);

        return view('passenger.ride', ['ride' => $ride->load('rider.riderProfile')]);
    }

    public function status(Ride $ride): JsonResponse
    {
        $this->authorize('viewAsPassenger', $ride);
        $ride->loadMissing('rider.riderProfile');

        return response()->json([
            'status' => $ride->status->value,
            'no_riders_found' => $ride->status === RideStatus::Cancelled && $ride->cancel_reason === 'no_riders_found',
            'rider' => $ride->rider ? [
                'name' => $ride->rider->name,
                'phone' => $ride->rider->phone,
                'bike_number' => $ride->rider->riderProfile?->bike_number,
                'bike_model' => $ride->rider->riderProfile?->bike_model,
                'lat' => $ride->rider->riderProfile?->current_lat,
                'lng' => $ride->rider->riderProfile?->current_lng,
            ] : null,
        ]);
    }

    public function cancel(CancelRideRequest $request, Ride $ride): RedirectResponse
    {
        DB::transaction(function () use ($request, $ride): void {
            $lockedRide = Ride::query()->lockForUpdate()->findOrFail($ride->id);
            $this->authorize('viewAsPassenger', $lockedRide);
            abort_unless($lockedRide->status === RideStatus::Searching, 422, __('Only a searching ride can be cancelled by the passenger.'));

            $lockedRide->update([
                'status' => RideStatus::Cancelled,
                'cancelled_by' => Auth::id(),
                'cancel_reason' => $request->validated('cancel_reason'),
            ]);
        });

        return redirect()->route('passenger.home')->with('status', __('Ride cancelled.'));
    }
}
