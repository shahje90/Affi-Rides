@extends('layouts.app')

@section('title', __('Application settings'))

@section('content')
    <section>
        <h1 class="text-2xl font-bold">{{ __('Application settings') }}</h1>
        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="mt-5 space-y-4 rounded-2xl border border-stone-200 p-5">
            @csrf
            @method('PUT')
            @foreach ([
                'base_fare' => ['Base fare (PKR)', '0.01'],
                'per_km_rate' => ['Per kilometre rate (PKR)', '0.01'],
                'minimum_fare' => ['Minimum fare (PKR)', '0.01'],
                'commission_percentage' => ['Commission percentage', '0.01'],
                'minimum_rider_wallet_limit' => ['Minimum rider wallet (PKR)', '0.01'],
                'search_radius_km' => ['Search radius (km)', '0.01'],
                'road_distance_factor' => ['Road distance factor', '0.01'],
                'idle_poll_seconds' => ['Idle location polling (seconds)', '1'],
                'active_poll_seconds' => ['Active ride polling (seconds)', '1'],
                'min_move_meters' => ['Minimum movement (metres)', '1'],
                'ride_request_timeout_seconds' => ['Ride request timeout (seconds)', '1'],
                'fare_rounding_increment' => ['Fare rounding increment (PKR)', '1'],
                'easypaisa_account_title' => ['Easypaisa account title', null],
                'easypaisa_account_number' => ['Easypaisa account number', null],
                'jazzcash_account_number' => ['JazzCash account number', null],
                'support_phone' => ['Support phone', null],
            ] as $key => [$label, $step])
                <label class="block text-sm font-medium">{{ __($label) }}
                    <input name="{{ $key }}" value="{{ old($key, $settings[$key]) }}" @if ($step) type="number" step="{{ $step }}" @endif @required(! in_array($key, ['easypaisa_account_title', 'easypaisa_account_number', 'jazzcash_account_number', 'support_phone'], true)) class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 px-4">
                </label>
            @endforeach
            <label class="block text-sm font-medium">{{ __('Payment QR code image') }}
                <input name="payment_qr_code_image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 p-2 text-sm">
            </label>
            @if ($qrCodeUrl)
                <img src="{{ $qrCodeUrl }}" alt="{{ __('Current payment QR code') }}" class="max-h-56 rounded-xl border border-stone-200">
            @endif
            <p class="text-xs text-stone-500">{{ __('JPG, PNG, or WebP, maximum 5 MB.') }}</p>
            <button class="min-h-14 w-full rounded-xl bg-emerald-700 px-5 font-semibold text-white">{{ __('Save settings') }}</button>
        </form>
    </section>
@endsection
