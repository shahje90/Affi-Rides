<?php

namespace App\Http\Controllers;

use App\Enums\RideStatus;
use App\Http\Requests\UpdateRideStatusRequest;
use App\Models\Ride;
use App\Services\RideEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RiderRideController extends Controller
{
    public function requests(Request $request, RideEngineService $engine): JsonResponse
    {
        return response()->json(['requests' => $engine->requestsForRider($request->user())]);
    }

    public function accept(Ride $ride, RideEngineService $engine): JsonResponse
    {
        $accepted = $engine->accept($ride->id, Auth::user());

        return response()->json([
            'message' => __('Ride accepted.'),
            'redirect' => route('rider.rides.show', $accepted),
        ]);
    }

    public function show(Ride $ride): View
    {
        $this->authorize('viewAsRider', $ride);

        return view('rider.ride', ['ride' => $ride->load('passenger:id,name,phone')]);
    }

    public function transition(UpdateRideStatusRequest $request, Ride $ride, RideEngineService $engine): RedirectResponse
    {
        $status = RideStatus::from($request->validated('status'));
        $updated = $engine->transition(
            Auth::user(),
            $ride->id,
            $status,
            $request->validated('cancel_reason'),
        );

        $message = $status === RideStatus::Completed
            ? __('Ride completed. Your wallet ledger has been updated.')
            : __('Ride status updated.');

        if (in_array($updated->status, [RideStatus::Completed, RideStatus::Cancelled], true)) {
            return redirect()->route('rider.dashboard')->with('status', $message);
        }

        return redirect()->route('rider.rides.show', $updated)->with('status', $message);
    }
}
