@extends('layouts.app')

@section('title', __('Account details'))

@section('content')
    <section class="space-y-5">
        <div class="rounded-2xl bg-emerald-800 p-5 text-white">
            <h1 class="text-2xl font-bold">{{ $user->name }}</h1>
            <p class="mt-2">{{ $user->phone }}</p>
            <p class="mt-1 text-sm">{{ __(ucwords(str_replace('_', ' ', $user->role->value))) }} · {{ __(ucfirst($user->status->value)) }}</p>
            @if ($user->riderProfile)
                <p class="mt-2 text-sm">{{ $user->riderProfile->bike_model }} · {{ $user->riderProfile->bike_number }}</p>
                <p class="mt-1 text-sm">{{ __('KYC') }}: {{ $user->riderProfile->kyc_status->value }} · PKR {{ $user->riderProfile->wallet_balance }}</p>
            @endif
        </div>

        <form method="POST" action="{{ route('admin.users.status', $user) }}" class="rounded-2xl border border-stone-200 p-4">
            @csrf
            <label class="block text-sm font-medium">{{ __('Account status') }}
                <select name="status" class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 px-3">
                    <option value="active" @selected($user->status->value === 'active')>{{ __('Activate') }}</option>
                    <option value="suspended" @selected($user->status->value === 'suspended')>{{ __('Suspend') }}</option>
                </select>
            </label>
            <button class="mt-3 min-h-12 w-full rounded-xl bg-stone-900 px-4 font-semibold text-white">{{ __('Update account') }}</button>
        </form>

        @if ($transactions)
            <section>
                <h2 class="mb-3 text-lg font-bold">{{ __('Wallet ledger') }}</h2>
                <div class="space-y-2">
                    @forelse ($transactions as $transaction)
                        <article class="rounded-xl border border-stone-200 p-4">
                            <div class="flex justify-between gap-3">
                                <div>
                                    <p class="font-semibold">{{ __(ucwords(str_replace('_', ' ', $transaction->type->value))) }}</p>
                                    <p class="mt-1 text-xs text-stone-500">{{ $transaction->created_at->format('d M Y, g:i A') }} · {{ $transaction->trx_id }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold">PKR {{ $transaction->amount }}</p>
                                    <p class="mt-1 text-xs text-stone-500">{{ __('Balance') }}: {{ $transaction->balance_after ?? '—' }}</p>
                                </div>
                            </div>
                            @if ($transaction->admin_note)<p class="mt-2 text-sm text-stone-600">{{ $transaction->admin_note }}</p>@endif
                        </article>
                    @empty
                        <p class="rounded-xl border border-dashed border-stone-300 p-5 text-center text-sm text-stone-500">{{ __('No wallet transactions.') }}</p>
                    @endforelse
                </div>
                {{ $transactions->links() }}
            </section>
        @endif

        @if ($rides)
            <section>
                <h2 class="mb-3 text-lg font-bold">{{ __('Passenger ride history') }}</h2>
                <div class="space-y-2">
                    @forelse ($rides as $ride)
                        <article class="rounded-xl border border-stone-200 p-4">
                            <p class="font-semibold">{{ $ride->pickup_address }} → {{ $ride->dropoff_address }}</p>
                            <p class="mt-1 text-sm text-stone-500">{{ $ride->status->value }} · PKR {{ $ride->final_fare ?? $ride->estimated_fare }}</p>
                        </article>
                    @empty
                        <p class="rounded-xl border border-dashed border-stone-300 p-5 text-center text-sm text-stone-500">{{ __('No rides found.') }}</p>
                    @endforelse
                </div>
                {{ $rides->links() }}
            </section>
        @endif
    </section>
@endsection
