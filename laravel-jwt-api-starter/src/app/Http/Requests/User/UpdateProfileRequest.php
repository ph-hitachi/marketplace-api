<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
        public function rules(): array
    {
        $userId = $this->user() ? $this->user()->id : null;
        return [
            /**
             * Updated full name of the user.
             */
            'name'     => ['required', 'string', 'max:255'],

            /**
             * Updated email address. Must be unique.
             */
            'email'    => [
                'required',
                'string',
                'email',
                'max:255',
                \Illuminate\Validation\Rule::unique('users', 'email')->ignore($userId),
            ],

            /**
             * Optional new password (must be confirmed).
             */
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],

            /**
             * Password confirmation (required if password is provided, must match password).
             */
            'password_confirmation' => ['required_with:password', 'nullable', 'string'],
        ];
    }
}
