<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelRideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isRole('passenger') ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return ['cancel_reason' => ['required', 'string', 'max:300']];
    }
}
