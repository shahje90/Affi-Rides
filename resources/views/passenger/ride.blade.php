@extends('layouts.app')

@section('title', __('Ride tracking'))

@section('content')
    <section x-data="rideTracking({
        statusUrl: @js(route('passenger.rides.status', $ride)),
        initialStatus: @js($ride->status->value),
        noRidersFound: @js($ride->status->value === 'cancelled' && $ride->cancel_reason === 'no_riders_found'),
        pickup: [@js((float) $ride->pickup_lat), @js((float) $ride->pickup_lng)],
        rider: @js($ride->rider ? [
            'name' => $ride->rider->name,
            'phone' => $ride->rider->phone,
            'bike_number' => $ride->rider->riderProfile?->bike_number,
            'bike_model' => $ride->rider->riderProfile?->bike_model,
            'lat' => $ride->rider->riderProfile?->current_lat,
            'lng' => $ride->rider->riderProfile?->current_lng
        ] : null)
    })" class="space-y-5">
        <div class="rounded-2xl bg-emerald-800 p-5 text-white">
            <p class="text-sm text-emerald-100">{{ __('Your ride') }} #{{ $ride->id }}</p>
            <h1 class="mt-1 text-2xl font-bold" x-text="statusLabel"></h1>
            <p class="mt-2 text-sm text-emerald-50">{{ $ride->pickup_address }} → {{ $ride->dropoff_address }}</p>
        </div>

        <ol class="grid grid-cols-5 gap-1 text-center text-[10px] font-medium sm:text-xs">
            @foreach (['Searching', 'Accepted', 'Arrived', 'In progress', 'Completed'] as $step => $label)
                <li class="rounded-lg px-1 py-2" :class="stepIndex >= {{ $step }} ? 'bg-emerald-100 text-emerald-900' : 'bg-stone-100 text-stone-500'">
                    <span class="mx-auto mb-1 flex size-6 items-center justify-center rounded-full bg-white">{{ $step + 1 }}</span>{{ __($label) }}
                </li>
            @endforeach
        </ol>

        <div id="live-rider-map" class="h-64 overflow-hidden rounded-2xl border border-stone-200" aria-label="{{ __('Live rider location') }}"></div>
        <section x-show="rider" class="rounded-2xl border border-stone-200 p-5">
            <h2 class="text-lg font-bold">{{ __('Your rider') }}</h2>
            <p class="mt-2 font-semibold" x-text="rider?.name"></p>
            <p class="mt-1 text-sm text-stone-600" x-text="[rider?.bike_model, rider?.bike_number].filter(Boolean).join(' · ')"></p>
            <a x-show="rider?.phone" :href="`tel:${rider?.phone}`" class="mt-4 flex min-h-12 items-center justify-center rounded-xl bg-emerald-700 px-4 font-semibold text-white">{{ __('Call rider') }}</a>
        </section>

        <section x-show="noRidersFound" class="rounded-2xl border border-amber-300 bg-amber-50 p-5">
            <h2 class="font-bold text-amber-950">{{ __('No riders found') }}</h2>
            <p class="mt-2 text-sm text-amber-900">{{ __('No nearby rider accepted in time. Try booking again.') }}</p>
            <a href="{{ route('passenger.home') }}" class="mt-4 flex min-h-12 items-center justify-center rounded-xl bg-emerald-700 px-4 font-semibold text-white">{{ __('Try again') }}</a>
        </section>

        <p class="text-center text-sm text-stone-500" aria-live="polite" x-text="message || @js(__('Ride status refreshes automatically every 10 seconds.'))"></p>
        @if ($ride->status->value === 'searching')
            <form method="POST" action="{{ route('passenger.rides.cancel', $ride) }}" class="rounded-2xl border border-stone-200 p-4">
                @csrf
                <label class="block text-sm font-medium">{{ __('Reason for cancellation') }}
                    <input name="cancel_reason" required maxlength="300" class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 px-4">
                </label>
                <button type="submit" class="mt-3 min-h-12 w-full rounded-xl border border-red-300 px-4 font-semibold text-red-700">{{ __('Cancel ride') }}</button>
            </form>
        @endif
    </section>
@endsection
