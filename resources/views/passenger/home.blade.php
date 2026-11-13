@extends('layouts.app')

@section('title', __('Book a ride'))

@section('content')
    <section x-data="passengerMap({
        estimateUrl: @js(route('passenger.fare')),
        messages: @js([
            'select' => __('Select pickup and drop-off on the map.'),
            'estimating' => __('Calculating fare...'),
            'estimateError' => __('Could not estimate the fare. Please try again.'),
            'gpsError' => __('Could not access your location. You can select pickup on the map.')
        ])
    })" class="space-y-5">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-emerald-800">ACR · ATTOCK</p>
            <h1 class="mt-1 text-2xl font-bold">{{ __('Where are you going?') }}</h1>
            <p class="mt-2 text-sm text-stone-600">{{ __('Tap once for pickup and again for drop-off.') }}</p>
        </div>

        <div id="ride-map" class="h-72 overflow-hidden rounded-2xl border border-stone-200" aria-label="{{ __('Select pickup and drop-off points on the map') }}"></div>
        <div class="grid grid-cols-2 gap-3">
            <button type="button" @click="useCurrentLocation()" class="min-h-12 rounded-xl border border-emerald-800 px-3 text-sm font-semibold text-emerald-800">{{ __('Use my location') }}</button>
            <button type="button" @click="resetPoints()" class="min-h-12 rounded-xl border border-stone-300 px-3 text-sm font-semibold text-stone-700">{{ __('Choose points again') }}</button>
        </div>

        <form method="POST" action="{{ route('passenger.rides.store') }}" class="space-y-4 rounded-2xl border border-stone-200 p-5">
            @csrf
            <input type="hidden" name="pickup_lat" x-ref="pickupLat">
            <input type="hidden" name="pickup_lng" x-ref="pickupLng">
            <input type="hidden" name="dropoff_lat" x-ref="dropoffLat">
            <input type="hidden" name="dropoff_lng" x-ref="dropoffLng">
            <label class="block text-sm font-medium">{{ __('Pickup address') }}
                <input name="pickup_address" required maxlength="255" value="{{ old('pickup_address') }}" x-ref="pickupAddress" class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 px-4" placeholder="{{ __('Choose pickup on map') }}">
            </label>
            <label class="block text-sm font-medium">{{ __('Drop-off address') }}
                <input name="dropoff_address" required maxlength="255" value="{{ old('dropoff_address') }}" x-ref="dropoffAddress" class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 px-4" placeholder="{{ __('Choose destination on map') }}">
            </label>
            <div class="rounded-xl bg-emerald-50 p-4" aria-live="polite">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm font-medium text-emerald-950">{{ __('Estimated fare') }}</span>
                    <span class="text-xl font-bold text-emerald-900" x-text="fare ? `PKR ${fare}` : '—'"></span>
                </div>
                <p class="mt-1 text-xs text-emerald-800" x-text="distance ? `${distance} km` : message"></p>
            </div>
            <button type="submit" :disabled="!canBook" class="min-h-14 w-full rounded-xl bg-emerald-700 px-5 font-bold text-white disabled:cursor-not-allowed disabled:bg-stone-400">{{ __('Book a ride') }}</button>
        </form>

        <p role="status" class="min-h-5 text-center text-sm text-stone-600" x-text="locationMessage"></p>
    </section>
@endsection
