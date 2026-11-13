@extends('layouts.app')

@section('title', __('KYC review'))

@section('content')
    <section>
        <h1 class="text-2xl font-bold">{{ __('Rider KYC applications') }}</h1>
        <div class="mt-5 space-y-3">
            @forelse ($profiles as $profile)
                <a href="{{ route('admin.kyc.show', $profile) }}" class="block rounded-2xl border border-stone-200 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-bold">{{ $profile->user->name }}</p>
                            <p class="mt-1 text-sm text-stone-500">{{ $profile->user->phone }} · {{ $profile->bike_number }}</p>
                            <p class="mt-1 text-xs text-stone-500">{{ $profile->created_at->format('d M Y, g:i A') }}</p>
                        </div>
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold">{{ __(ucfirst($profile->kyc_status->value)) }}</span>
                    </div>
                </a>
            @empty
                <p class="rounded-xl border border-dashed border-stone-300 p-6 text-center text-sm text-stone-500">{{ __('No KYC applications need review.') }}</p>
            @endforelse
        </div>
        <div class="mt-4">{{ $profiles->links() }}</div>
    </section>
@endsection
