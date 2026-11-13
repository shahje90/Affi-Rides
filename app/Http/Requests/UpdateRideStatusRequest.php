<?php

namespace App\Http\Requests;

use App\Enums\RideStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRideStatusRequest extends FormRequest
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
            'status' => ['required', Rule::in([
                RideStatus::Arrived->value,
                RideStatus::InProgress->value,
                RideStatus::Completed->value,
                RideStatus::Cancelled->value,
            ])],
            'cancel_reason' => ['required_if:status,cancelled', 'nullable', 'string', 'max:300'],
        ];
    }
}
