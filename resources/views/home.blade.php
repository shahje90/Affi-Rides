@extends('layouts.app')

@section('title', __('home'))

@section('content')
    <section class="rounded-3xl bg-gradient-to-br from-emerald-800 to-emerald-600 p-6 text-white shadow-lg">
        <p class="mb-3 inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-semibold tracking-wide">ACR · ATTOCK</p>
        <h1 class="text-3xl font-bold leading-tight">{{ __('welcome') }}</h1>
        <p class="mt-3 text-sm text-emerald-50">{{ __('coverage') }}</p>
        <a href="{{ route('register') }}" class="mt-6 flex min-h-14 items-center justify-center rounded-2xl bg-amber-400 px-5 text-base font-bold text-stone-950 shadow-sm active:scale-[0.99]">
            {{ __('book_ride') }}
        </a>
    </section>

    <section id="ride" class="mt-6 rounded-2xl border border-stone-200 p-5">
        <h2 class="text-lg font-bold">{{ __('tagline') }}</h2>
        <p class="mt-2 text-sm leading-6 text-stone-600">{{ __('footer') }}</p>
        <div class="mt-5 grid grid-cols-1 gap-3">
            <a href="{{ route('register') }}" class="flex min-h-14 items-center justify-center rounded-xl bg-emerald-700 px-4 font-semibold text-white">{{ __('get_started') }}</a>
            <a href="{{ route('rider.register') }}" class="flex min-h-14 items-center justify-center rounded-xl border border-emerald-800 px-4 font-semibold text-emerald-800">{{ __('ride_with_us') }}</a>
        </div>
    </section>

    <div class="mt-5 flex items-center justify-between text-sm text-stone-600">
        <span>{{ __('sign_in') }}</span>
        <span class="font-medium text-emerald-800">PKR · {{ now()->timezone(config('app.timezone'))->format('g:i A') }}</span>
    </div>
@endsection
