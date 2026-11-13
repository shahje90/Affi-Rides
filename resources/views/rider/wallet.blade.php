@extends('layouts.app')

@section('title', __('Rider wallet'))

@section('content')
    <section class="space-y-5">
        <div class="rounded-2xl bg-emerald-800 p-5 text-white">
            <p class="text-sm text-emerald-100">{{ __('Current balance') }}</p>
            <p class="mt-2 text-3xl font-bold">PKR {{ $profile->wallet_balance }}</p>
        </div>

        <div class="rounded-2xl border border-stone-200 p-5">
            <h1 class="text-lg font-bold">{{ __('Recharge your wallet') }}</h1>
            @if ($qrCodeUrl)
                <img src="{{ $qrCodeUrl }}" alt="{{ __('Payment QR code') }}" class="mx-auto my-4 max-h-56 rounded-xl border border-stone-200">
            @endif
            <dl class="mt-3 space-y-2 text-sm">
                @foreach (['easypaisa_account_title' => 'Easypaisa account title', 'easypaisa_account_number' => 'Easypaisa account number', 'jazzcash_account_number' => 'JazzCash account number'] as $key => $label)
                    @if (App\Support\Setting::get($key))
                        <div class="flex justify-between gap-3"><dt class="text-stone-500">{{ __($label) }}</dt><dd class="text-right font-medium">{{ App\Support\Setting::get($key) }}</dd></div>
                    @endif
                @endforeach
            </dl>
            <form method="POST" action="{{ route('rider.wallet.recharge') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                @csrf
                <label class="block text-sm font-medium">{{ __('Amount in PKR') }}
                    <input name="amount" type="number" inputmode="decimal" min="1" step="0.01" required class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 px-4">
                </label>
                <label class="block text-sm font-medium">{{ __('Transaction ID (TID)') }}
                    <input name="trx_id" required maxlength="100" class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 px-4">
                </label>
                <label class="block text-sm font-medium">{{ __('Payment screenshot (optional)') }}
                    <input name="screenshot" type="file" accept="image/jpeg,image/png,image/webp" class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 p-2 text-sm">
                </label>
                <button type="submit" class="min-h-14 w-full rounded-xl bg-emerald-700 px-5 font-semibold text-white">{{ __('Submit recharge request') }}</button>
            </form>
        </div>

        <section>
            <h2 class="mb-3 text-lg font-bold">{{ __('Wallet history') }}</h2>
            <div class="space-y-3">
                @forelse ($transactions as $transaction)
                    <article class="rounded-xl border border-stone-200 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold">{{ __(ucwords(str_replace('_', ' ', $transaction->type->value))) }}</p>
                                <p class="mt-1 text-xs text-stone-500">{{ $transaction->created_at->format('d M Y, g:i A') }} · {{ $transaction->trx_id ?? __('No TID') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold">PKR {{ $transaction->amount }}</p>
                                <p class="mt-1 text-xs">{{ __(ucfirst($transaction->status->value)) }}</p>
                            </div>
                        </div>
                        @if ($transaction->admin_note)
                            <p class="mt-2 text-sm text-stone-600">{{ $transaction->admin_note }}</p>
                        @endif
                    </article>
                @empty
                    <p class="rounded-xl border border-dashed border-stone-300 p-5 text-center text-sm text-stone-500">{{ __('No wallet transactions yet.') }}</p>
                @endforelse
            </div>
            <div class="mt-4">{{ $transactions->links() }}</div>
        </section>
    </section>
@endsection
