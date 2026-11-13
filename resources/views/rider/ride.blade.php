@extends('layouts.app')

@section('title', __('Ride in progress'))

@section('content')
    <section class="space-y-5">
        <div class="rounded-2xl bg-emerald-800 p-5 text-white">
            <p class="text-sm text-emerald-100">{{ __('Ride') }} #{{ $ride->id }}</p>
            <h1 class="mt-1 text-2xl font-bold">{{ __(ucwords(str_replace('_', ' ', $ride->status->value))) }}</h1>
            <p class="mt-3 text-sm">{{ $ride->pickup_address }} → {{ $ride->dropoff_address }}</p>
            <p class="mt-2 text-sm">{{ __('Estimated fare') }}: PKR {{ $ride->estimated_fare }}</p>
        </div>

        <section class="rounded-2xl border border-stone-200 p-5">
            <h2 class="text-lg font-bold">{{ __('Passenger') }}</h2>
            <p class="mt-2 font-semibold">{{ $ride->passenger->name }}</p>
            <a href="tel:{{ $ride->passenger->phone }}" class="mt-1 text-sm text-emerald-800 underline">{{ $ride->passenger->phone }}</a>
        </section>

        @if ($ride->status->value === 'accepted')
            <form method="POST" action="{{ route('rider.rides.status', $ride) }}">
                @csrf
                <input type="hidden" name="status" value="arrived">
                <button class="min-h-14 w-full rounded-xl bg-emerald-700 px-5 font-bold text-white">{{ __('I have arrived') }}</button>
            </form>
        @elseif ($ride->status->value === 'arrived')
            <form method="POST" action="{{ route('rider.rides.status', $ride) }}">
                @csrf
                <input type="hidden" name="status" value="in_progress">
                <button class="min-h-14 w-full rounded-xl bg-emerald-700 px-5 font-bold text-white">{{ __('Start ride') }}</button>
            </form>
        @elseif ($ride->status->value === 'in_progress')
            <form method="POST" action="{{ route('rider.rides.status', $ride) }}">
                @csrf
                <input type="hidden" name="status" value="completed">
                <button class="min-h-14 w-full rounded-xl bg-emerald-700 px-5 font-bold text-white">{{ __('Complete ride') }}</button>
            </form>
        @endif

        @if (in_array($ride->status->value, ['accepted', 'arrived'], true))
            <form method="POST" action="{{ route('rider.rides.status', $ride) }}" class="rounded-2xl border border-stone-200 p-4">
                @csrf
                <input type="hidden" name="status" value="cancelled">
                <label class="block text-sm font-medium">{{ __('Reason for cancellation') }}
                    <input name="cancel_reason" required maxlength="300" class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 px-4">
                </label>
                <button class="mt-3 min-h-12 w-full rounded-xl border border-red-300 px-4 font-semibold text-red-700">{{ __('Cancel ride') }}</button>
            </form>
        @endif

        <a href="{{ route('rider.dashboard') }}" class="flex min-h-12 items-center justify-center rounded-xl border border-stone-300 font-semibold text-stone-700">{{ __('Back to dashboard') }}</a>
    </section>
@endsection
