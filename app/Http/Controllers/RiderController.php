<?php

namespace App\Http\Controllers;

use App\Enums\KycStatus;
use App\Enums\RideStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Requests\Auth\RegisterRiderRequest;
use App\Http\Requests\UpdateRiderLocationRequest;
use App\Models\Ride;
use App\Models\RiderProfile;
use App\Models\User;
use App\Support\Setting;
use Brick\Math\BigDecimal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RiderController extends Controller
{
    public function showRegistration(): View
    {
        return view('rider.register');
    }

    public function register(RegisterRiderRequest $request): RedirectResponse
    {
        $files = [
            'cnic_front_image' => $request->file('cnic_front_image')->store('kyc', 'local'),
            'cnic_back_image' => $request->file('cnic_back_image')->store('kyc', 'local'),
            'bike_document_image' => $request->file('bike_document_image')->store('kyc', 'local'),
        ];

        DB::transaction(function () use ($request, $files): void {
            $user = User::create([
                'name' => $request->validated('name'),
                'phone' => $request->validated('phone'),
                'password' => $request->validated('password'),
                'role' => UserRole::Rider,
                'status' => UserStatus::Active,
                'language' => app()->getLocale(),
            ]);

            $user->riderProfile()->create([
                'cnic_number' => $request->validated('cnic_number'),
                'bike_number' => $request->validated('bike_number'),
                'bike_model' => $request->validated('bike_model'),
                ...$files,
                'kyc_status' => KycStatus::Pending,
            ]);
        });

        Auth::attempt($request->only('phone', 'password'));
        $request->session()->regenerate();

        return redirect()->route('rider.kyc-status')
            ->with('status', __('Your rider application has been submitted for review.'));
    }

    public function dashboard(): View
    {
        $user = Auth::user();
        $profile = $user->riderProfile()->firstOrFail();
        $todayEarnings = Ride::query()
            ->where('rider_id', $user->id)
            ->where('status', RideStatus::Completed->value)
            ->whereDate('completed_at', today())
            ->sum('rider_earning');
        $activeRide = Ride::query()
            ->where('rider_id', $user->id)
            ->whereIn('status', [RideStatus::Accepted->value, RideStatus::Arrived->value, RideStatus::InProgress->value])
            ->latest('accepted_at')
            ->first();

        return view('rider.dashboard', [
            'profile' => $profile,
            'activeRide' => $activeRide,
            'todayEarnings' => (string) $todayEarnings,
            'recentRides' => Ride::query()
                ->where('rider_id', $user->id)
                ->with('passenger:id,name')
                ->latest()
                ->limit(8)
                ->get(),
            'minimumWallet' => (string) Setting::get('minimum_rider_wallet_limit', '100.00'),
            'walletEligible' => BigDecimal::of((string) $profile->wallet_balance)
                ->isGreaterThanOrEqualTo(BigDecimal::of((string) Setting::get('minimum_rider_wallet_limit', '100.00'))),
            'idlePollSeconds' => (int) Setting::get('idle_poll_seconds', 45),
            'activePollSeconds' => (int) Setting::get('active_poll_seconds', 10),
            'minMoveMeters' => (int) Setting::get('min_move_meters', 30),
        ]);
    }

    public function kycStatus(): View
    {
        return view('rider.kyc-status', [
            'profile' => Auth::user()->riderProfile()->firstOrFail(),
        ]);
    }

    public function toggleOnline(): RedirectResponse
    {
        $profile = Auth::user()->riderProfile()->firstOrFail();
        $goingOnline = ! $profile->is_online;

        if ($goingOnline) {
            abort_unless($profile->kyc_status === KycStatus::Approved, 403, __('Your KYC must be approved before going online.'));

            $minimumWallet = BigDecimal::of((string) Setting::get('minimum_rider_wallet_limit', '100.00'));
            abort_if(
                BigDecimal::of((string) $profile->wallet_balance)->isLessThan($minimumWallet),
                403,
                __('Recharge your wallet to meet the minimum online balance.'),
            );
        } elseif (Ride::query()->where('rider_id', Auth::id())
            ->whereIn('status', [RideStatus::Accepted->value, RideStatus::Arrived->value, RideStatus::InProgress->value])
            ->exists()) {
            return back()->withErrors(['online' => __('Finish your active ride before going offline.')]);
        }

        DB::transaction(fn () => $profile->update(['is_online' => $goingOnline]));

        return back()->with('status', $goingOnline ? __('You are now online.') : __('You are now offline.'));
    }

    public function activeState(): JsonResponse
    {
        $active = Ride::query()
            ->where('rider_id', Auth::id())
            ->whereIn('status', [RideStatus::Accepted->value, RideStatus::Arrived->value, RideStatus::InProgress->value])
            ->exists();

        return response()->json(['active_ride' => $active]);
    }

    public function updateLocation(UpdateRiderLocationRequest $request): JsonResponse
    {
        $values = $request->validated();

        $updated = RiderProfile::query()->where('user_id', Auth::id())->where('is_online', true)->update([
            'current_lat' => $values['lat'],
            'current_lng' => $values['lng'],
            'last_location_updated_at' => now(),
        ]);

        abort_unless($updated === 1, 403, __('You must be online to share your location.'));

        return response()->json(['updated' => true]);
    }
}
