<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'         => ['required', 'confirmed', Password::defaults()],
            'role'             => ['required', Rule::in(['customer', 'seller'])],
            // Seller-only fields
            'shop_name'        => [
                'required_if:role,seller',
                'nullable', 'string', 'max:255', 'unique:shops,shop_name',
            ],
            'shop_description' => [
                'required_if:role,seller',
                'nullable', 'string', 'max:1000',
            ],
        ];
    }
}
