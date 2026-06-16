<?php

namespace App\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label'         => ['sometimes', 'string', 'max:100'],
            'phone'         => ['sometimes', 'nullable', 'string', 'max:30'],
            'address_line1' => ['sometimes', 'string', 'max:255'],
            'address_line2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city'          => ['sometimes', 'string', 'max:100'],
            'province'      => ['sometimes', 'string', 'max:100'],
            'postal_code'   => ['sometimes', 'string', 'max:20'],
            'country'       => ['sometimes', 'string', 'max:100'],
        ];
    }
}
