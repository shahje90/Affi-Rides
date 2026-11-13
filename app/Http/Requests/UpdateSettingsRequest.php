<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isRole('super_admin') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'base_fare' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'per_km_rate' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'minimum_fare' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'commission_percentage' => ['required', 'numeric', 'decimal:0,2', 'between:0,100'],
            'minimum_rider_wallet_limit' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'search_radius_km' => ['required', 'numeric', 'decimal:0,2', 'between:0.5,50'],
            'road_distance_factor' => ['required', 'numeric', 'decimal:0,2', 'between:1,3'],
            'idle_poll_seconds' => ['required', 'integer', 'between:5,300'],
            'active_poll_seconds' => ['required', 'integer', 'between:5,120'],
            'min_move_meters' => ['required', 'integer', 'between:5,1000'],
            'ride_request_timeout_seconds' => ['required', 'integer', 'between:15,600'],
            'fare_rounding_increment' => ['required', 'integer', 'in:5,10'],
            'easypaisa_account_title' => ['nullable', 'string', 'max:120'],
            'easypaisa_account_number' => ['nullable', 'string', 'max:40'],
            'jazzcash_account_number' => ['nullable', 'string', 'max:40'],
            'support_phone' => ['nullable', 'string', 'max:20'],
            'payment_qr_code_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
