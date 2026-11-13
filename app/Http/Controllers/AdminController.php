<?php

namespace App\Http\Controllers;

use App\Enums\KycStatus;
use App\Enums\RideStatus;
use App\Enums\UserRole;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Models\Ride;
use App\Models\RiderProfile;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'active_riders' => RiderProfile::query()
                    ->online()
                    ->approved()
                    ->whereHas('user', fn ($query) => $query->where('status', 'active'))
                    ->count(),
                'active_rides' => Ride::query()
                    ->whereIn('status', [
                        RideStatus::Searching->value,
                        RideStatus::Accepted->value,
                        RideStatus::Arrived->value,
                        RideStatus::InProgress->value,
                    ])
                    ->count(),
                'today_rides' => Ride::query()->whereDate('created_at', today())->count(),
                'today_commission' => (string) Ride::query()
                    ->where('status', RideStatus::Completed->value)
                    ->whereDate('completed_at', today())
                    ->sum('company_commission'),
                'pending_items' => WalletTransaction::query()
                    ->where('type', WalletTransactionType::Deposit)
                    ->where('status', WalletTransactionStatus::Pending)
                    ->count()
                    + RiderProfile::query()->where('kyc_status', KycStatus::Pending)->count(),
            ],
            'pendingDeposits' => WalletTransaction::query()
                ->where('type', WalletTransactionType::Deposit)
                ->where('status', WalletTransactionStatus::Pending)
                ->with('rider:id,name,phone')
                ->latest()
                ->limit(50)
                ->get(),
            'pendingKyc' => RiderProfile::query()
                ->where('kyc_status', KycStatus::Pending)
                ->with('user:id,name,phone')
                ->latest()
                ->limit(50)
                ->get(),
            'riders' => User::query()
                ->where('role', UserRole::Rider)
                ->orderBy('name')
                ->get(['id', 'name', 'phone']),
        ]);
    }

    public function pendingItems(): JsonResponse
    {
        $deposits = WalletTransaction::query()
            ->where('type', WalletTransactionType::Deposit)
            ->where('status', WalletTransactionStatus::Pending)
            ->with('rider:id,name,phone')
            ->latest()
            ->get(['id', 'rider_id', 'amount', 'trx_id', 'created_at']);
        $kyc = RiderProfile::query()
            ->where('kyc_status', KycStatus::Pending)
            ->with('user:id,name,phone')
            ->latest()
            ->get(['id', 'user_id', 'created_at']);

        return response()->json([
            'deposits' => $deposits->map(fn (WalletTransaction $transaction): array => [
                'id' => $transaction->id,
                'rider_name' => $transaction->rider->name,
                'amount' => (string) $transaction->amount,
                'trx_id' => $transaction->trx_id,
            ]),
            'kyc' => $kyc->map(fn (RiderProfile $profile): array => [
                'id' => $profile->id,
                'rider_name' => $profile->user->name,
            ]),
        ]);
    }
}
