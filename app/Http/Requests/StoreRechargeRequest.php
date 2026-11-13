<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRechargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isRole('rider') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:1', 'max:99999999.99'],
            'trx_id' => ['required', 'string', 'max:100', Rule::unique('wallet_transactions', 'trx_id')],
            'screenshot' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
