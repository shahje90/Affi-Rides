<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRiderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{10,15}$/', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'cnic_number' => ['required', 'regex:/^[0-9]{13}$/'],
            'bike_number' => ['required', 'string', 'max:30'],
            'bike_model' => ['required', 'string', 'max:100'],
            'cnic_front_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'cnic_back_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'bike_document_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
