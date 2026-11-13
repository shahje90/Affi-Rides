@extends('layouts.app')

@section('title', __('All rides'))

@section('content')
    <section>
        <h1 class="text-2xl font-bold">{{ __('All rides') }}</h1>
        <form method="GET" class="mt-4 space-y-3 rounded-2xl border border-stone-200 p-4">
            <label class="block text-sm font-medium">{{ __('Status') }}
                <select name="status" class="mt-2 min-h-11 w-full rounded-xl border border-stone-300 px-3">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ __(ucwords(str_replace('_', ' ', $status->value))) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm font-medium">{{ __('Rider') }}
                <select name="rider_id" class="mt-2 min-h-11 w-full rounded-xl border border-stone-300 px-3">
                    <option value="">{{ __('All riders') }}</option>
                    @foreach ($riders as $rider)
                        <option value="{{ $rider->id }}" @selected(($filters['rider_id'] ?? '') == $rider->id)>{{ $rider->name }}</option>
                    @endforeach
                </select>
            </label>
            <div class="grid grid-cols-2 gap-3">
                <label class="block text-sm font-medium">{{ __('From') }}<input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="mt-2 min-h-11 w-full rounded-xl border border-stone-300 px-2"></label>
                <label class="block text-sm font-medium">{{ __('To') }}<input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="mt-2 min-h-11 w-full rounded-xl border border-stone-300 px-2"></label>
            </div>
            <button class="min-h-11 w-full rounded-xl bg-emerald-700 px-4 font-semibold text-white">{{ __('Apply filters') }}</button>
        </form>

        <div class="mt-5 space-y-3">
            @forelse ($rides as $ride)
                <article class="rounded-2xl border border-stone-200 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-bold">#{{ $ride->id }} · {{ __(ucwords(str_replace('_', ' ', $ride->status->value))) }}</p>
                            <p class="mt-1 text-xs text-stone-500">{{ $ride->created_at->format('d M Y, g:i A') }}</p>
                        </div>
                        <p class="font-bold">PKR {{ $ride->final_fare ?? $ride->estimated_fare }}</p>
                    </div>
                    <p class="mt-3 text-sm">{{ $ride->pickup_address }} → {{ $ride->dropoff_address }}</p>
                    <p class="mt-1 text-xs text-stone-500">{{ __('Passenger') }}: {{ $ride->passenger->name }} · {{ __('Rider') }}: {{ $ride->rider?->name ?? __('Unassigned') }}</p>
                </article>
            @empty
                <p class="rounded-xl border border-dashed border-stone-300 p-6 text-center text-sm text-stone-500">{{ __('No rides match these filters.') }}</p>
            @endforelse
        </div>
        <div class="mt-4">{{ $rides->links() }}</div>
    </section>
@endsection
