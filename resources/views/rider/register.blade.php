@extends('layouts.app')

@section('title', __('Rider registration'))

@section('content')
    <section>
        <h1 class="text-2xl font-bold">{{ __('Apply to ride with ACR') }}</h1>
        <p class="mt-2 text-sm text-stone-600">{{ __('Upload clear images for identity and bike verification.') }}</p>
        <form method="POST" action="{{ route('rider.register.store') }}" enctype="multipart/form-data" class="mt-5 space-y-4 rounded-2xl border border-stone-200 p-5">
            @csrf
            <label class="block text-sm font-medium">{{ __('Full name') }}
                <input name="name" required autocomplete="name" value="{{ old('name') }}" class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 px-4">
            </label>
            <label class="block text-sm font-medium">{{ __('Phone number') }}
                <input name="phone" type="tel" inputmode="tel" required value="{{ old('phone') }}" class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 px-4">
            </label>
            <label class="block text-sm font-medium">{{ __('Password') }}
                <input name="password" type="password" required autocomplete="new-password" class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 px-4">
            </label>
            <label class="block text-sm font-medium">{{ __('Confirm password') }}
                <input name="password_confirmation" type="password" required autocomplete="new-password" class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 px-4">
            </label>
            <label class="block text-sm font-medium">{{ __('CNIC number (13 digits)') }}
                <input name="cnic_number" inputmode="numeric" pattern="[0-9]{13}" maxlength="13" required value="{{ old('cnic_number') }}" class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 px-4">
            </label>
            <label class="block text-sm font-medium">{{ __('Bike registration number') }}
                <input name="bike_number" required value="{{ old('bike_number') }}" class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 px-4">
            </label>
            <label class="block text-sm font-medium">{{ __('Bike model') }}
                <input name="bike_model" required value="{{ old('bike_model') }}" class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 px-4">
            </label>
            @foreach (['cnic_front_image' => 'CNIC front image', 'cnic_back_image' => 'CNIC back image', 'bike_document_image' => 'Bike document image'] as $field => $label)
                <label class="block text-sm font-medium">{{ __($label) }}
                    <input name="{{ $field }}" type="file" accept="image/jpeg,image/png,image/webp" required class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 p-2 text-sm">
                </label>
            @endforeach
            <p class="text-xs leading-5 text-stone-500">{{ __('JPG, PNG, or WebP only; maximum 5 MB per file. Documents are stored privately.') }}</p>
            <button type="submit" class="min-h-14 w-full rounded-xl bg-emerald-700 px-5 font-semibold text-white">{{ __('Submit rider application') }}</button>
        </form>
    </section>
@endsection
