@extends('layouts.app')

@section('title', __('People management'))

@section('content')
    <section>
        <h1 class="text-2xl font-bold">{{ __('Riders and passengers') }}</h1>
        <form method="GET" class="mt-4">
            <label class="block text-sm font-medium">{{ __('Account type') }}
                <select name="role" class="mt-2 min-h-12 w-full rounded-xl border border-stone-300 px-3" onchange="this.form.submit()">
                    <option value="rider" @selected($role === 'rider')>{{ __('Riders') }}</option>
                    <option value="passenger" @selected($role === 'passenger')>{{ __('Passengers') }}</option>
                </select>
            </label>
        </form>
        <div class="mt-5 space-y-3">
            @forelse ($users as $user)
                <a href="{{ route('admin.users.show', $user) }}" class="block rounded-2xl border border-stone-200 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-bold">{{ $user->name }}</p>
                            <p class="mt-1 text-sm text-stone-500">{{ $user->phone }}</p>
                            @if ($user->riderProfile)
                                <p class="mt-1 text-xs text-stone-500">{{ __('KYC') }}: {{ $user->riderProfile->kyc_status->value }} · PKR {{ $user->riderProfile->wallet_balance }}</p>
                            @endif
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $user->status->value === 'active' ? 'bg-emerald-100 text-emerald-900' : 'bg-red-100 text-red-900' }}">{{ __(ucfirst($user->status->value)) }}</span>
                    </div>
                </a>
            @empty
                <p class="rounded-xl border border-dashed border-stone-300 p-6 text-center text-sm text-stone-500">{{ __('No accounts found.') }}</p>
            @endforelse
        </div>
        <div class="mt-4">{{ $users->links() }}</div>
    </section>
@endsection
