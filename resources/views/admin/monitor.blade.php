@extends('layouts.app')

@section('title', __('Live rides monitor'))

@section('content')
    <section x-data="adminLiveMap({ snapshotUrl: @js(route('admin.monitor.snapshot')) })" class="space-y-4">
        <div>
            <h1 class="text-2xl font-bold">{{ __('Live rides monitor') }}</h1>
            <p class="mt-2 text-sm text-stone-600">{{ __('Online riders and active rides refresh every 10 seconds.') }}</p>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div class="rounded-xl border border-stone-200 p-4"><p class="text-xs text-stone-500">{{ __('Online riders') }}</p><p class="mt-1 text-xl font-bold" x-text="riders.length"></p></div>
            <div class="rounded-xl border border-stone-200 p-4"><p class="text-xs text-stone-500">{{ __('Active rides') }}</p><p class="mt-1 text-xl font-bold" x-text="rides.length"></p></div>
        </div>
        <div id="admin-live-map" class="h-[65vh] min-h-80 overflow-hidden rounded-2xl border border-stone-200" aria-label="{{ __('Live rider and ride map') }}"></div>
        <p x-show="message" x-text="message" class="text-sm text-red-700" role="status"></p>
        <div class="space-y-2">
            <template x-for="ride in rides" :key="ride.id">
                <article class="rounded-xl border border-stone-200 p-4">
                    <p class="font-semibold" x-text="`#${ride.id} · ${ride.status}`"></p>
                    <p class="mt-1 text-sm text-stone-600" x-text="`${ride.passenger} · ${ride.rider || 'Unassigned'}`"></p>
                    <p class="mt-1 text-xs text-stone-500" x-text="`${ride.pickup_address} → ${ride.dropoff_address}`"></p>
                </article>
            </template>
        </div>
    </section>
@endsection
