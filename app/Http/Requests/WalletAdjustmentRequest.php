<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WalletAdjustmentRequest extends FormRequest
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
            'rider_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', UserRole::Rider->value),
            ],
            'amount' => ['required', 'string', 'regex:/^-?(?:0|[1-9][0-9]{0,7})(?:\.[0-9]{1,2})?$/', 'not_in:0,0.0,0.00,-0,-0.0,-0.00'],
            'admin_note' => ['required', 'string', 'max:500'],
        ];
    }
}
