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
            /**
             * User's full name.
             */
            'name'             => ['required', 'string', 'max:255'],

            /**
             * Unique email address.
             */
            'email'            => ['required', 'string', 'email', 'max:255', 'unique:users,email'],

            /**
             * Password (must be confirmed).
             */
            'password'         => ['required', 'confirmed', Password::defaults()],

            /**
             * Password confirmation (must match password).
             */
            'password_confirmation' => ['required', 'string'],

            /**
             * Role, must be 'customer' or 'seller'.
             */
            'role'             => ['required', Rule::in(['customer', 'seller'])],

            /**
             * Required if registering as a seller. Unique shop name.
             */
            'shop_name'        => [
                'required_if:role,seller',
                'nullable', 'string', 'max:255', 'unique:shops,shop_name',
            ],

            /**
             * Required if registering as a seller. Description of the shop.
             */
            'shop_description' => [
                'required_if:role,seller',
                'nullable', 'string', 'max:1000',
            ],
        ];
    }
}
