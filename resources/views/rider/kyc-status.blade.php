@extends('layouts.app')

@section('title', __('Application status'))

@section('content')
    <section class="rounded-2xl border border-stone-200 p-5">
        <p class="text-sm text-stone-500">{{ __('Rider verification') }}</p>
        <h1 class="mt-2 text-2xl font-bold">{{ __(ucfirst($profile->kyc_status->value)) }}</h1>
        @if ($profile->kyc_note)
            <div class="mt-4 rounded-xl bg-amber-50 p-4 text-sm text-amber-950">{{ $profile->kyc_note }}</div>
        @endif
        <p class="mt-4 text-sm leading-6 text-stone-600">
            @if ($profile->kyc_status->value === 'pending')
                {{ __('Your documents are being reviewed. We will update your status here.') }}
            @elseif ($profile->kyc_status->value === 'rejected')
                {{ __('Please review the note above and contact support if you need help.') }}
            @else
                {{ __('Your account is approved. You can now manage your availability.') }}
            @endif
        </p>
        <a href="{{ route('rider.dashboard') }}" class="mt-5 flex min-h-12 items-center justify-center rounded-xl bg-emerald-700 px-4 font-semibold text-white">{{ __('Continue to rider dashboard') }}</a>
    </section>
@endsection
