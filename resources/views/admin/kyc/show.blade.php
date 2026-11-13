@extends('layouts.app')

@section('title', __('Review rider documents'))

@section('content')
    <section class="space-y-5">
        <div class="rounded-2xl bg-emerald-800 p-5 text-white">
            <p class="text-sm text-emerald-100">{{ __('KYC application') }} #{{ $profile->id }}</p>
            <h1 class="mt-1 text-2xl font-bold">{{ $profile->user->name }}</h1>
            <p class="mt-2 text-sm">{{ $profile->user->phone }} · {{ $profile->bike_number }}</p>
            <p class="mt-1 text-sm">{{ $profile->bike_model }}</p>
            <p class="mt-2 text-sm">{{ __('CNIC') }}: {{ $profile->cnic_number }}</p>
            <p class="mt-2 text-sm">{{ __('KYC status') }}: {{ __(ucfirst($profile->kyc_status->value)) }}</p>
        </div>

        <div class="grid gap-3">
            @foreach (['cnic-front' => 'CNIC front', 'cnic-back' => 'CNIC back', 'bike-document' => 'Bike document'] as $document => $label)
                <a href="{{ route('admin.kyc.document', [$profile, $document]) }}" target="_blank" rel="noopener" class="flex min-h-12 items-center justify-center rounded-xl border border-emerald-800 font-semibold text-emerald-800">{{ __('View :document', ['document' => __($label)]) }}</a>
            @endforeach
        </div>

        @if ($profile->kyc_note)
            <p class="rounded-xl bg-amber-50 p-4 text-sm text-amber-950">{{ $profile->kyc_note }}</p>
        @endif

        @if ($profile->kyc_status->value === 'pending')
            <form method="POST" action="{{ route('admin.kyc.review', $profile) }}" class="space-y-4 rounded-2xl border border-stone-200 p-5">
                @csrf
                <label class="block text-sm font-medium">{{ __('Decision') }}
                    <select name="kyc_status" required class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 px-3">
                        <option value="approved">{{ __('Approve') }}</option>
                        <option value="rejected">{{ __('Reject') }}</option>
                    </select>
                </label>
                <label class="block text-sm font-medium">{{ __('Review note') }}
                    <textarea name="kyc_note" required maxlength="1000" rows="3" class="mt-2 w-full rounded-xl border border-stone-300 p-3">{{ old('kyc_note') }}</textarea>
                </label>
                <button class="min-h-12 w-full rounded-xl bg-emerald-700 px-4 font-semibold text-white">{{ __('Save decision') }}</button>
            </form>
        @endif
    </section>
@endsection
