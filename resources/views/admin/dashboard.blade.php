@extends('layouts.app')

@section('title', __('Super admin'))

@section('content')
    <section x-data="adminAlarm({
        pendingUrl: @js(route('admin.pending-items')),
        pollSeconds: 10,
        messages: @js([
            'network' => __('Could not refresh the pending queue. Retrying shortly.'),
            'newDeposit' => __('New recharge from :rider · PKR :amount · TID :tid'),
            'newKyc' => __('New KYC application from :rider')
        ])
    })" class="space-y-6">
        <div class="rounded-2xl bg-emerald-800 p-5 text-white">
            <p class="text-sm text-emerald-100">{{ __('Administration') }}</p>
            <h1 class="mt-1 text-2xl font-bold">{{ __('Recharge review queue') }}</h1>
            <p class="mt-2 text-sm text-emerald-50">{{ __('New deposit and KYC requests are checked every 10 seconds.') }}</p>
        </div>

        <div class="grid grid-cols-2 gap-3">
            @foreach ([
                'active_riders' => 'Active riders',
                'active_rides' => 'Active rides',
                'today_rides' => 'Today’s rides',
                'today_commission' => 'Today’s commission (PKR)',
                'pending_items' => 'Pending items'
            ] as $key => $label)
                <div class="rounded-xl border border-stone-200 p-4">
                    <p class="text-xs text-stone-500">{{ __($label) }}</p>
                    <p class="mt-2 text-xl font-bold">{{ $stats[$key] }}</p>
                </div>
            @endforeach
        </div>

        <nav class="grid grid-cols-2 gap-2 text-center text-sm font-semibold">
            <a href="{{ route('admin.kyc.index') }}" class="flex min-h-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-900">{{ __('KYC review') }}</a>
            <a href="{{ route('admin.users.index') }}" class="flex min-h-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-900">{{ __('People') }}</a>
            <a href="{{ route('admin.monitor') }}" class="flex min-h-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-900">{{ __('Live map') }}</a>
            <a href="{{ route('admin.rides.index') }}" class="flex min-h-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-900">{{ __('All rides') }}</a>
            <a href="{{ route('admin.settings.index') }}" class="flex min-h-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-900">{{ __('Settings') }}</a>
            <a href="{{ route('admin.reports.index') }}" class="flex min-h-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-900">{{ __('Reports') }}</a>
        </nav>

        <div class="flex items-center justify-between gap-3 rounded-2xl border border-red-200 bg-red-50 p-4">
            <div class="flex items-center gap-3">
                <span class="flex size-11 items-center justify-center rounded-full bg-red-700 text-lg font-bold text-white" x-text="pendingCount"></span>
                <div>
                    <p class="font-bold text-red-950">{{ __('Pending items') }}</p>
                    <p class="text-xs text-red-800" x-text="alarmEnabled ? @js(__('Alarm ON')) : @js(__('Alarm OFF'))"></p>
                </div>
            </div>
            <button type="button" @click="enableAlarm()" class="min-h-12 rounded-xl bg-red-700 px-4 text-sm font-bold text-white" x-show="!alarmEnabled">{{ __('Enable Alarm Sound') }}</button>
            <span x-show="alarmEnabled" class="rounded-full bg-emerald-700 px-3 py-2 text-xs font-bold text-white">{{ __('Alarm ON') }}</span>
        </div>
        <audio x-ref="alarm" preload="auto" src="{{ asset('audio/admin-alert.wav') }}"></audio>

        <div x-show="notifications.length" class="fixed inset-x-4 top-20 z-50 mx-auto max-w-md space-y-2" aria-live="assertive">
            <template x-for="notice in notifications" :key="notice.id">
                <div class="rounded-xl bg-red-800 px-4 py-3 text-sm font-semibold text-white shadow-xl" x-text="notice.message"></div>
            </template>
        </div>
        <p x-show="message" x-text="message" class="text-sm text-red-700" role="status"></p>

        <section>
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="text-lg font-bold">{{ __('Pending wallet recharges') }}</h2>
                <span class="rounded-full bg-red-700 px-3 py-1 text-sm font-bold text-white" x-text="deposits.length"></span>
            </div>
            <div class="space-y-3">
                @forelse ($pendingDeposits as $transaction)
                    <article class="rounded-2xl border border-stone-200 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-bold">{{ $transaction->rider->name }}</p>
                                <p class="mt-1 text-xs text-stone-500">{{ $transaction->rider->phone }} · {{ $transaction->created_at->format('d M, g:i A') }}</p>
                                <p class="mt-2 text-sm">{{ __('TID') }}: <span class="font-semibold">{{ $transaction->trx_id }}</span></p>
                            </div>
                            <p class="text-lg font-bold">PKR {{ $transaction->amount }}</p>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <form method="POST" action="{{ route('admin.wallet.approve', $transaction) }}" class="col-span-2 space-y-2">
                                @csrf
                                <label class="sr-only" for="approve-note-{{ $transaction->id }}">{{ __('Review note') }}</label>
                                <input id="approve-note-{{ $transaction->id }}" name="admin_note" required maxlength="500" placeholder="{{ __('Review note required') }}" class="min-h-11 w-full rounded-xl border border-stone-300 px-3 text-sm">
                                <button class="min-h-12 w-full rounded-xl bg-emerald-700 px-4 font-semibold text-white">{{ __('Approve and credit wallet') }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.wallet.reject', $transaction) }}" class="col-span-2">
                                @csrf
                                <label class="sr-only" for="reject-note-{{ $transaction->id }}">{{ __('Rejection reason') }}</label>
                                <input id="reject-note-{{ $transaction->id }}" name="admin_note" required maxlength="500" placeholder="{{ __('Rejection reason required') }}" class="mb-2 min-h-11 w-full rounded-xl border border-stone-300 px-3 text-sm">
                                <button class="min-h-12 w-full rounded-xl border border-red-300 px-4 font-semibold text-red-700">{{ __('Reject recharge') }}</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <p class="rounded-xl border border-dashed border-stone-300 p-5 text-center text-sm text-stone-500">{{ __('No pending recharge requests.') }}</p>
                @endforelse
            </div>
        </section>

        <section>
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="text-lg font-bold">{{ __('Pending KYC applications') }}</h2>
                <span class="rounded-full bg-red-700 px-3 py-1 text-sm font-bold text-white" x-text="kyc.length"></span>
            </div>
            <div class="space-y-2">
                @forelse ($pendingKyc as $profile)
                    <a href="{{ route('admin.kyc.show', $profile) }}" class="flex items-center justify-between rounded-xl border border-stone-200 p-4">
                        <div>
                            <p class="font-semibold">{{ $profile->user->name }}</p>
                            <p class="mt-1 text-xs text-stone-500">{{ $profile->user->phone }}</p>
                        </div>
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-900">{{ __('Review required') }}</span>
                    </a>
                @empty
                    <p class="rounded-xl border border-dashed border-stone-300 p-5 text-center text-sm text-stone-500">{{ __('No pending KYC applications.') }}</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-stone-200 p-5">
            <h2 class="text-lg font-bold">{{ __('Manual wallet adjustment') }}</h2>
            <form method="POST" action="{{ route('admin.wallet.adjustments.store') }}" class="mt-4 space-y-4">
                @csrf
                <label class="block text-sm font-medium">{{ __('Rider') }}
                    <select name="rider_id" required class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 px-3">
                        <option value="">{{ __('Choose a rider') }}</option>
                        @foreach ($riders as $rider)
                            <option value="{{ $rider->id }}">{{ $rider->name }} · {{ $rider->phone }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm font-medium">{{ __('Adjustment amount (negative or positive)') }}
                    <input name="amount" required inputmode="decimal" placeholder="100.00 or -25.00" class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 px-4">
                </label>
                <label class="block text-sm font-medium">{{ __('Mandatory note') }}
                    <textarea name="admin_note" required maxlength="500" rows="3" class="mt-2 w-full rounded-xl border border-stone-300 p-3"></textarea>
                </label>
                <button class="min-h-12 w-full rounded-xl bg-stone-900 px-4 font-semibold text-white">{{ __('Record adjustment') }}</button>
            </form>
        </section>
    </section>
@endsection
