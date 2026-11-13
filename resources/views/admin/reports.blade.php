@extends('layouts.app')

@section('title', __('Earnings and commission reports'))

@section('content')
    <section>
        <h1 class="text-2xl font-bold">{{ __('Earnings and commission reports') }}</h1>
        <form method="GET" action="{{ route('admin.reports.index') }}" class="mt-4 space-y-3 rounded-2xl border border-stone-200 p-4">
            <div class="grid grid-cols-2 gap-3">
                <label class="block text-sm font-medium">{{ __('From') }}<input type="date" name="from" value="{{ $from }}" class="mt-2 min-h-11 w-full rounded-xl border border-stone-300 px-2"></label>
                <label class="block text-sm font-medium">{{ __('To') }}<input type="date" name="to" value="{{ $to }}" class="mt-2 min-h-11 w-full rounded-xl border border-stone-300 px-2"></label>
            </div>
            <button class="min-h-11 w-full rounded-xl bg-emerald-700 px-4 font-semibold text-white">{{ __('Apply date range') }}</button>
        </form>

        <div class="mt-5 grid grid-cols-2 gap-3">
            <div class="rounded-xl border border-stone-200 p-4"><p class="text-xs text-stone-500">{{ __('Completed rides') }}</p><p class="mt-2 text-xl font-bold">{{ $rideCount }}</p></div>
            <div class="rounded-xl border border-stone-200 p-4"><p class="text-xs text-stone-500">{{ __('Company commission (PKR)') }}</p><p class="mt-2 text-xl font-bold">{{ $commission }}</p></div>
            <div class="col-span-2 rounded-xl border border-stone-200 p-4"><p class="text-xs text-stone-500">{{ __('Rider earnings (PKR)') }}</p><p class="mt-2 text-xl font-bold">{{ $riderEarnings }}</p></div>
        </div>

        <a href="{{ route('admin.reports.export', ['from' => $from, 'to' => $to]) }}" class="mt-5 flex min-h-12 items-center justify-center rounded-xl border border-emerald-800 px-4 font-semibold text-emerald-800">{{ __('Export CSV') }}</a>
    </section>
@endsection
