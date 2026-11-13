@extends('layouts.app')

@section('title', __('Ride history'))

@section('content')
    <section>
        <h1 class="text-2xl font-bold">{{ __('Ride history') }}</h1>
        <div class="mt-5 space-y-3">
            @forelse ($rides as $ride)
                <a href="{{ route('passenger.rides.show', $ride) }}" class="block rounded-2xl border border-stone-200 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold">{{ $ride->pickup_address }} → {{ $ride->dropoff_address }}</p>
                            <p class="mt-1 text-xs text-stone-500">{{ $ride->created_at->format('d M Y, g:i A') }}</p>
                            @if ($ride->rider)
                                <p class="mt-2 text-sm text-stone-600">{{ __('Rider') }}: {{ $ride->rider->name }} · {{ $ride->rider->phone }}</p>
                            @endif
                        </div>
                        <div class="text-right">
                            <p class="font-bold">PKR {{ $ride->final_fare ?? $ride->estimated_fare }}</p>
                            <p class="mt-1 text-xs text-stone-500">{{ __(ucwords(str_replace('_', ' ', $ride->status->value))) }}</p>
                        </div>
                    </div>
                </a>
            @empty
                <p class="rounded-xl border border-dashed border-stone-300 p-6 text-center text-sm text-stone-500">{{ __('Your completed and cancelled rides will appear here.') }}</p>
            @endforelse
        </div>
        <div class="mt-4">{{ $rides->links() }}</div>
    </section>
@endsection
