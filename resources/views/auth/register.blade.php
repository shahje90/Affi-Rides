@extends('layouts.app')

@section('title', __('create_account'))

@section('content')
    <section class="rounded-2xl border border-stone-200 p-5 shadow-sm">
        <h1 class="text-2xl font-bold">{{ __('create_account') }}</h1>
        <p class="mt-2 text-sm text-stone-600">{{ __('passenger_registration') }}</p>
        <form method="POST" action="{{ route('register.store') }}" class="mt-6 space-y-4">
            @csrf
            <label class="block text-sm font-medium">
                {{ __('full_name') }}
                <input name="name" type="text" autocomplete="name" required maxlength="120" value="{{ old('name') }}" class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 px-4">
            </label>
            <label class="block text-sm font-medium">
                {{ __('phone_number') }}
                <input name="phone" type="tel" inputmode="tel" autocomplete="tel" required value="{{ old('phone') }}" class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 px-4">
            </label>
            <label class="block text-sm font-medium">
                {{ __('password') }}
                <input name="password" type="password" autocomplete="new-password" required class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 px-4">
            </label>
            <label class="block text-sm font-medium">
                {{ __('confirm_password') }}
                <input name="password_confirmation" type="password" autocomplete="new-password" required class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 px-4">
            </label>
            <button type="submit" class="min-h-14 w-full rounded-xl bg-emerald-700 px-5 font-semibold text-white">{{ __('create_account') }}</button>
        </form>
        <p class="mt-5 text-center text-sm text-stone-600">
            {{ __('already_registered') }} <a class="font-semibold text-emerald-800 underline" href="{{ route('login') }}">{{ __('sign_in') }}</a>
        </p>
    </section>
@endsection
