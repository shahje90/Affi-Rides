@extends('layouts.app')

@section('title', __('dashboard'))

@section('content')
    <section class="rounded-2xl bg-emerald-800 p-5 text-white">
        <p class="text-sm text-emerald-100">{{ __('dashboard') }}</p>
        <h1 class="mt-2 text-2xl font-bold">{{ auth()->user()->name }}</h1>
        <p class="mt-2 text-sm">{{ auth()->user()->phone }}</p>
    </section>
    <form method="POST" action="{{ route('logout') }}" class="mt-5">
        @csrf
        <button type="submit" class="min-h-12 w-full rounded-xl border border-stone-300 px-4 font-semibold text-stone-700">{{ __('sign_out') }}</button>
    </form>
@endsection
