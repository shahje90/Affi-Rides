<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewWalletTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isRole('super_admin') ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return ['admin_note' => ['required', 'string', 'max:500']];
    }
}
