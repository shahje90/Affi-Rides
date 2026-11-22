<?php

namespace App\Http\Controllers;

use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Http\Requests\ReviewWalletTransactionRequest;
use App\Http\Requests\WalletAdjustmentRequest;
use App\Models\RiderProfile;
use App\Models\WalletTransaction;
use App\Support\Setting;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AdminWalletController extends Controller
{
    public function approve(ReviewWalletTransactionRequest $request, WalletTransaction $walletTransaction): RedirectResponse
    {
        $this->reviewDeposit($walletTransaction->id, WalletTransactionStatus::Approved, $request->validated('admin_note'));

        return back()->with('status', __('Recharge approved and rider wallet credited.'));
    }

    public function reject(ReviewWalletTransactionRequest $request, WalletTransaction $walletTransaction): RedirectResponse
    {
        $this->reviewDeposit($walletTransaction->id, WalletTransactionStatus::Rejected, $request->validated('admin_note'));

        return back()->with('status', __('Recharge request rejected.'));
    }

    public function adjust(WalletAdjustmentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data): void {
            $profile = RiderProfile::query()
                ->where('user_id', $data['rider_id'])
                ->lockForUpdate()
                ->firstOrFail();
            $amount = BigDecimal::of($data['amount'])->toScale(2, RoundingMode::HalfUp);
            $newBalance = BigDecimal::of((string) $profile->wallet_balance)
                ->plus($amount)
                ->toScale(2, RoundingMode::HalfUp);

            if ($newBalance->isLessThan(BigDecimal::zero())) {
                throw ValidationException::withMessages([
                    'amount' => __('This adjustment cannot make the rider wallet negative.'),
                ]);
            }

            $profile->update([
                'wallet_balance' => (string) $newBalance,
                'is_online' => $profile->is_online && $newBalance->isGreaterThanOrEqualTo(
                    BigDecimal::of((string) Setting::get('minimum_rider_wallet_limit', '100.00')),
                ),
            ]);
            WalletTransaction::query()->create([
                'rider_id' => $data['rider_id'],
                'type' => WalletTransactionType::Adjustment,
                'amount' => (string) $amount,
                'balance_after' => (string) $newBalance,
                'status' => WalletTransactionStatus::Approved,
                'admin_note' => $data['admin_note'],
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            Log::info('Rider wallet manually adjusted.', [
                'rider_id' => $data['rider_id'],
                'amount' => (string) $amount,
                'balance_after' => (string) $newBalance,
                'reviewed_by' => Auth::id(),
            ]);
        });

        return back()->with('status', __('Wallet adjustment recorded.'));
    }

    private function reviewDeposit(int $transactionId, WalletTransactionStatus $status, string $note): void
    {
        DB::transaction(function () use ($transactionId, $status, $note): void {
            $transaction = WalletTransaction::query()->lockForUpdate()->findOrFail($transactionId);

            if (
                $transaction->type !== WalletTransactionType::Deposit
                || $transaction->status !== WalletTransactionStatus::Pending
            ) {
                throw ValidationException::withMessages([
                    'wallet_transaction' => __('This recharge request has already been reviewed.'),
                ]);
            }

            if ($status === WalletTransactionStatus::Approved) {
                $profile = RiderProfile::query()
                    ->where('user_id', $transaction->rider_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $newBalance = BigDecimal::of((string) $profile->wallet_balance)
                    ->plus(BigDecimal::of((string) $transaction->amount))
                    ->toScale(2, RoundingMode::HalfUp);
                $profile->update(['wallet_balance' => (string) $newBalance]);
                $transaction->balance_after = (string) $newBalance;
            }

            $transaction->update([
                'status' => $status,
                'admin_note' => $note,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'balance_after' => $transaction->balance_after,
            ]);

            Log::info('Rider recharge request reviewed.', [
                'transaction_id' => $transaction->id,
                'rider_id' => $transaction->rider_id,
                'status' => $status->value,
                'reviewed_by' => Auth::id(),
            ]);
        });
    }
}
