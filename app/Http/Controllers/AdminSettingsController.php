<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Setting as SettingModel;
use App\Support\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;

class AdminSettingsController extends Controller
{
    private const KEYS = [
        'base_fare',
        'per_km_rate',
        'minimum_fare',
        'commission_percentage',
        'minimum_rider_wallet_limit',
        'search_radius_km',
        'road_distance_factor',
        'idle_poll_seconds',
        'active_poll_seconds',
        'min_move_meters',
        'easypaisa_account_title',
        'easypaisa_account_number',
        'jazzcash_account_number',
        'support_phone',
        'ride_request_timeout_seconds',
        'fare_rounding_increment',
    ];

    public function index(): View
    {
        return view('admin.settings', [
            'settings' => collect(self::KEYS)->mapWithKeys(fn (string $key): array => [$key => Setting::get($key, '')]),
            'qrCodeUrl' => Setting::get('payment_qr_code_image')
                ? Storage::disk('public')->url(Setting::get('payment_qr_code_image'))
                : null,
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $values = $request->validated();
        $qrFile = $request->file('payment_qr_code_image');
        unset($values['payment_qr_code_image']);
        $oldQrPath = Setting::get('payment_qr_code_image');
        $newQrPath = null;

        if ($qrFile) {
            $newQrPath = $qrFile->store('payment', 'public');

            if (! $newQrPath) {
                throw new RuntimeException('Unable to store the payment QR image.');
            }
        }

        DB::transaction(function () use ($values, $newQrPath): void {
            foreach ($values as $key => $value) {
                SettingModel::query()->updateOrCreate(['key' => $key], ['value' => (string) ($value ?? '')]);
            }

            if ($newQrPath) {
                SettingModel::query()->updateOrCreate(
                    ['key' => 'payment_qr_code_image'],
                    ['value' => $newQrPath],
                );
            }
        });

        foreach (array_keys($values) as $key) {
            Setting::forget($key);
        }
        if ($newQrPath) {
            Setting::forget('payment_qr_code_image');
            if ($oldQrPath) {
                Storage::disk('public')->delete($oldQrPath);
            }
        }

        Log::info('Application settings updated.', ['admin_id' => auth()->id()]);

        return back()->with('status', __('Settings saved.'));
    }
}
