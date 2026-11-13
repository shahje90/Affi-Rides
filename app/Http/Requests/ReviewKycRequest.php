<?php

namespace App\Http\Requests;

use App\Enums\KycStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewKycRequest extends FormRequest
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
            'kyc_status' => ['required', Rule::in([KycStatus::Approved->value, KycStatus::Rejected->value])],
            'kyc_note' => ['required', 'string', 'max:1000'],
        ];
    }
}
