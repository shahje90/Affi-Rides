<!DOCTYPE html>
@php($isUrdu = app()->getLocale() === 'ur')
<html lang="{{ app()->getLocale() }}" dir="{{ $isUrdu ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#047857">
    <meta name="description" content="{{ __('Local rides in Attock and nearby areas.') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="icon" href="{{ asset('icons/acr-icon.svg') }}" type="image/svg+xml">
    <title>@yield('title', __('brand')) · {{ __('brand') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <div class="mx-auto min-h-screen max-w-lg bg-white shadow-xl">
        <header class="flex items-center justify-between border-b border-stone-100 px-5 py-4">
            <a href="{{ route('home') }}" class="font-bold tracking-tight text-emerald-800">{{ __('brand') }}</a>
            <div class="flex items-center gap-2 text-sm">
                <button type="button" data-install-app hidden class="min-h-10 rounded-lg bg-emerald-700 px-3 text-xs font-semibold text-white">{{ __('Add to Home Screen') }}</button>
                <span class="sr-only">{{ __('language') }}</span>
                <a href="{{ route('language.switch', 'en') }}" lang="en" class="{{ app()->getLocale() === 'en' ? 'font-bold text-emerald-800' : 'text-stone-500' }}">EN</a>
                <span class="text-stone-300">|</span>
                <a href="{{ route('language.switch', 'ur') }}" lang="ur" class="{{ app()->getLocale() === 'ur' ? 'font-bold text-emerald-800' : 'text-stone-500' }}">اردو</a>
            </div>
        </header>

        <main class="px-5 pb-28 pt-6">
            @if (session('status'))
                <div role="status" class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                    {{ session('status') }}
                </div>
            @endif
            @yield('content')
        </main>

        <nav aria-label="Primary navigation" class="safe-area-bottom fixed inset-x-0 bottom-0 z-30 mx-auto grid max-w-lg grid-cols-4 border-t border-stone-200 bg-white/95 px-2 pt-2 shadow-[0_-8px_24px_rgba(0,0,0,0.06)] backdrop-blur">
            <a href="{{ route('home') }}" class="flex min-h-14 flex-col items-center justify-center gap-1 rounded-xl text-xs font-medium text-emerald-800">
                <span aria-hidden="true" class="text-lg">⌂</span>{{ __('home') }}
            </a>
            <a href="{{ auth()->check() && auth()->user()->isRole('passenger') ? route('passenger.rides.index') : (auth()->check() ? route('dashboard') : route('login')) }}" class="flex min-h-14 flex-col items-center justify-center gap-1 rounded-xl text-xs font-medium text-stone-500">
                <span aria-hidden="true" class="text-lg">↗</span>{{ __('trips') }}
            </a>
            <a href="{{ auth()->check() && auth()->user()->isRole('rider') ? route('rider.wallet') : (auth()->check() ? route('dashboard') : route('login')) }}" class="flex min-h-14 flex-col items-center justify-center gap-1 rounded-xl text-xs font-medium text-stone-500">
                <span aria-hidden="true" class="text-lg">₨</span>{{ __('wallet') }}
            </a>
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="flex min-h-14 flex-col items-center justify-center gap-1 rounded-xl text-xs font-medium text-stone-500">
                <span aria-hidden="true" class="text-lg">●</span>{{ __('account') }}
            </a>
        </nav>
        <p data-pwa-status class="sr-only" role="status" aria-live="polite"></p>

        @if ($errors->any())
            <div x-data="{ open: true }" x-show="open" x-transition class="fixed inset-x-4 bottom-24 z-40 mx-auto max-w-md rounded-xl bg-red-700 px-4 py-3 text-sm text-white shadow-lg" role="alert">
                <div class="flex items-start justify-between gap-3">
                    <span>{{ $errors->first() }}</span>
                    <button type="button" class="min-h-8 min-w-8 text-lg" aria-label="Dismiss message" @click="open = false">×</button>
                </div>
            </div>
        @endif
    </div>
</body>
</html>
