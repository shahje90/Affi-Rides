@extends('layouts.app')

@section('title', __('Rider dashboard'))

@section('content')
    <section x-data="riderTracker({
        online: @js($profile->is_online),
        activeUrl: @js(route('rider.active-state')),
        locationUrl: @js(route('rider.location')),
        idleSeconds: @js($idlePollSeconds),
        activeSeconds: @js($activePollSeconds),
        minMoveMeters: @js($minMoveMeters),
        messages: @js([
            'offline' => __('You are offline. Connect to the internet to update your location.'),
            'permission' => __('Location access is blocked. Allow location access in your browser settings.'),
            'gpsError' => __('Could not read your GPS location. Check your device settings and try again.'),
            'updated' => __('Location updated.'),
            'waiting' => __('Location is unchanged; waiting for movement.'),
            'networkError' => __('Unable to reach the server. Your location will retry automatically.')
        ])
    })" class="space-y-5">
        <div class="rounded-2xl bg-emerald-800 p-5 text-white">
            <p class="text-sm text-emerald-100">{{ __('Welcome back') }}</p>
            <h1 class="mt-1 text-2xl font-bold">{{ auth()->user()->name }}</h1>
            <p class="mt-3 text-sm">{{ __('KYC status') }}: {{ __(ucfirst($profile->kyc_status->value)) }}</p>
            <p class="mt-1 text-sm">{{ __('Wallet balance') }}: PKR {{ $profile->wallet_balance }}</p>
        </div>

        @if ($profile->kyc_status->value !== 'approved')
            <a href="{{ route('rider.kyc-status') }}" class="block rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">
                {{ __('Your KYC is :status.', ['status' => $profile->kyc_status->value]) }} {{ __('View application status') }}
            </a>
        @endif

        @if ($activeRide)
            <a href="{{ route('rider.rides.show', $activeRide) }}" class="block rounded-xl border border-emerald-300 bg-emerald-50 p-4">
                <p class="font-semibold text-emerald-950">{{ __('You have an active ride') }}</p>
                <p class="mt-1 text-sm text-emerald-800">{{ $activeRide->pickup_address }} → {{ $activeRide->dropoff_address }}</p>
                <span class="mt-3 inline-flex min-h-10 items-center rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white">{{ __('Open ride') }}</span>
            </a>
        @elseif ($profile->is_online)
            <section x-data="rideRequestPoller({
                requestsUrl: @js(route('rider.ride-requests')),
                acceptUrl: @js(url('/rider/rides/__RIDE__/accept')),
                pollSeconds: @js($activePollSeconds)
            })" class="rounded-2xl border border-amber-300 bg-amber-50 p-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-bold text-amber-950">{{ __('Ride requests') }}</h2>
                        <p class="mt-1 text-xs text-amber-900" x-text="alarmEnabled ? @js(__('Ride alerts enabled')) : @js(__('Enable sound to hear new requests'))"></p>
                    </div>
                    <button type="button" @click="enableAlerts()" class="min-h-11 rounded-xl bg-amber-400 px-3 text-xs font-bold text-stone-950" x-show="!alarmEnabled">{{ __('Enable ride alerts') }}</button>
                </div>
                <p class="mt-3 min-h-5 text-sm text-amber-950" role="status" x-text="message"></p>
                <div class="mt-3 space-y-3">
                    <template x-for="request in requests" :key="request.id">
                        <article class="rounded-xl bg-white p-4 shadow-sm">
                            <p class="font-semibold" x-text="`${request.pickup_address} → ${request.dropoff_address}`"></p>
                            <p class="mt-1 text-sm text-stone-600" x-text="`${request.distance_km} km · PKR ${request.estimated_fare}`"></p>
                            <button type="button" @click="accept(request.id)" :disabled="accepting === request.id" class="mt-3 min-h-12 w-full rounded-xl bg-emerald-700 px-4 font-semibold text-white disabled:bg-stone-400" x-text="accepting === request.id ? @js(__('Accepting...')) : @js(__('Accept ride'))"></button>
                        </article>
                    </template>
                </div>
                <p x-show="requests.length === 0" class="mt-2 text-sm text-amber-950">{{ __('No ride requests nearby right now.') }}</p>
            </section>
        @endif

        <div class="grid grid-cols-2 gap-3">
            <div class="rounded-2xl border border-stone-200 p-4">
                <p class="text-xs text-stone-500">{{ __('Wallet balance') }}</p>
                <p class="mt-2 text-xl font-bold">PKR {{ $profile->wallet_balance }}</p>
            </div>
            <div class="rounded-2xl border border-stone-200 p-4">
                <p class="text-xs text-stone-500">{{ __('Today’s earnings') }}</p>
                <p class="mt-2 text-xl font-bold">PKR {{ $todayEarnings }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('rider.online.toggle') }}" class="rounded-2xl border border-stone-200 p-4">
            @csrf
            <p class="text-sm text-stone-600">{{ __('Minimum wallet to go online') }}: PKR {{ $minimumWallet }}</p>
            @if (! $walletEligible)
                <p class="mt-2 text-sm font-medium text-red-700">{{ __('Recharge to go online.') }}</p>
            @endif
            <button type="submit" @disabled($profile->kyc_status->value !== 'approved' || (! $profile->is_online && ! $walletEligible)) class="mt-4 min-h-14 w-full rounded-xl px-5 font-bold text-white disabled:cursor-not-allowed disabled:bg-stone-400 {{ $profile->is_online ? 'bg-red-700' : 'bg-emerald-700' }}">
                {{ $profile->is_online ? __('Go offline') : __('Go online') }}
            </button>
            <p class="mt-3 min-h-6 text-center text-sm" role="status" x-text="message"></p>
        </form>

        <div class="grid grid-cols-2 gap-3">
            <a href="{{ route('rider.wallet') }}" class="flex min-h-12 items-center justify-center rounded-xl border border-emerald-800 font-semibold text-emerald-800">{{ __('Wallet and recharge') }}</a>
            <a href="{{ route('rider.kyc-status') }}" class="flex min-h-12 items-center justify-center rounded-xl border border-stone-300 font-semibold text-stone-700">{{ __('Application status') }}</a>
        </div>

        <section>
            <h2 class="mb-3 text-lg font-bold">{{ __('Recent trips') }}</h2>
            <div class="space-y-3">
                @forelse ($recentRides as $ride)
                    <article class="rounded-xl border border-stone-200 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold">{{ $ride->passenger?->name ?? __('Passenger') }}</p>
                                <p class="mt-1 text-xs text-stone-500">{{ $ride->pickup_address }} → {{ $ride->dropoff_address }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold">PKR {{ $ride->final_fare ?? $ride->estimated_fare }}</p>
                                <p class="mt-1 text-xs text-stone-500">{{ __(ucwords(str_replace('_', ' ', $ride->status->value))) }}</p>
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="rounded-xl border border-dashed border-stone-300 p-5 text-center text-sm text-stone-500">{{ __('No trips yet.') }}</p>
                @endforelse
            </div>
        </section>
    </section>
@endsection
