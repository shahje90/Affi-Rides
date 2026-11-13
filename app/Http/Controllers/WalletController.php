<?php

namespace App\Http\Controllers;

use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Http\Requests\StoreRechargeRequest;
use App\Models\WalletTransaction;
use App\Support\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function index(): View
    {
        return view('rider.wallet', [
            'profile' => Auth::user()->riderProfile()->firstOrFail(),
            'transactions' => WalletTransaction::query()
                ->where('rider_id', Auth::id())
                ->latest()
                ->paginate(15),
            'qrCodeUrl' => Setting::get('payment_qr_code_image')
                ? Storage::disk('public')->url(Setting::get('payment_qr_code_image'))
                : null,
        ]);
    }

    public function storeRecharge(StoreRechargeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $screenshotPath = $request->file('screenshot')?->store('recharges', 'local');

        DB::transaction(function () use ($data, $screenshotPath): void {
            WalletTransaction::query()->create([
                'rider_id' => Auth::id(),
                'type' => WalletTransactionType::Deposit,
                'amount' => $data['amount'],
                'trx_id' => $data['trx_id'],
                'screenshot_path' => $screenshotPath,
                'status' => WalletTransactionStatus::Pending,
            ]);
        });

        return back()->with('status', __('Your recharge request has been submitted for review.'));
    }
}
