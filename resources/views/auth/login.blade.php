@extends('layouts.app')

@section('title', __('sign_in'))

@section('content')
    <section class="rounded-2xl border border-stone-200 p-5 shadow-sm">
        <h1 class="text-2xl font-bold">{{ __('sign_in') }}</h1>
        <p class="mt-2 text-sm text-stone-600">{{ __('login_prompt') }}</p>
        <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4">
            @csrf
            <label class="block text-sm font-medium">
                {{ __('phone_number') }}
                <input name="phone" type="tel" inputmode="tel" autocomplete="tel" required value="{{ old('phone') }}" class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 px-4">
            </label>
            <label class="block text-sm font-medium">
                {{ __('password') }}
                <input name="password" type="password" autocomplete="current-password" required class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 px-4">
            </label>
            <label class="flex min-h-11 items-center gap-2 text-sm text-stone-600">
                <input name="remember" type="checkbox" value="1" class="size-4 rounded border-stone-300">
                {{ __('remember_me') }}
            </label>
            <button type="submit" class="min-h-14 w-full rounded-xl bg-emerald-700 px-5 font-semibold text-white">{{ __('sign_in') }}</button>
        </form>
        <p class="mt-5 text-center text-sm text-stone-600">
            {{ __('need_account') }} <a class="font-semibold text-emerald-800 underline" href="{{ route('register') }}">{{ __('create_account') }}</a>
        </p>
    </section>
@endsection
