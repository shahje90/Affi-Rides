<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Requests\AdminUsersFilterRequest;
use App\Http\Requests\UpdateUserStatusRequest;
use App\Models\Ride;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(AdminUsersFilterRequest $request): View
    {
        $filters = $request->validated();

        return view('admin.users.index', [
            'role' => $filters['role'] ?? UserRole::Rider->value,
            'users' => User::query()
                ->whereIn('role', [UserRole::Rider, UserRole::Passenger])
                ->when($filters['role'] ?? null, fn ($query, $role) => $query->where('role', $role))
                ->with('riderProfile:id,user_id,kyc_status,wallet_balance,is_online')
                ->orderBy('name')
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function show(User $user): View
    {
        abort_unless(in_array($user->role, [UserRole::Rider, UserRole::Passenger], true), 404);
        $user->load('riderProfile');

        return view('admin.users.show', [
            'user' => $user,
            'transactions' => $user->isRole(UserRole::Rider)
                ? WalletTransaction::query()->where('rider_id', $user->id)->latest()->paginate(20)
                : null,
            'rides' => $user->isRole(UserRole::Passenger)
                ? Ride::query()->where('passenger_id', $user->id)->with('rider:id,name,phone')->latest()->paginate(20)
                : null,
        ]);
    }

    public function updateStatus(UpdateUserStatusRequest $request, User $user): RedirectResponse
    {
        abort_unless(in_array($user->role, [UserRole::Rider, UserRole::Passenger], true), 404);
        $status = UserStatus::from($request->validated('status'));

        DB::transaction(function () use ($user, $status): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $lockedUser->update(['status' => $status]);

            if ($status === UserStatus::Suspended && $lockedUser->isRole(UserRole::Rider)) {
                $lockedUser->riderProfile()->update(['is_online' => false]);
            }

            Log::info('User status changed by admin.', [
                'user_id' => $lockedUser->id,
                'status' => $status->value,
                'admin_id' => auth()->id(),
            ]);
        });

        return back()->with('status', __('User status updated.'));
    }
}
