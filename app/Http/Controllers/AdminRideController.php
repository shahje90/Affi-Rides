<?php

namespace App\Http\Controllers;

use App\Enums\RideStatus;
use App\Http\Requests\AdminRidesFilterRequest;
use App\Models\Ride;
use App\Models\User;
use Illuminate\View\View;

class AdminRideController extends Controller
{
    public function index(AdminRidesFilterRequest $request): View
    {
        $filters = $request->validated();

        return view('admin.rides.index', [
            'filters' => $filters,
            'rides' => Ride::query()
                ->with(['passenger:id,name,phone', 'rider:id,name,phone'])
                ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
                ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
                ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
                ->when($filters['rider_id'] ?? null, fn ($query, $riderId) => $query->where('rider_id', $riderId))
                ->latest()
                ->paginate(25)
                ->withQueryString(),
            'riders' => User::query()->where('role', 'rider')->orderBy('name')->get(['id', 'name']),
            'statuses' => RideStatus::cases(),
        ]);
    }
}
