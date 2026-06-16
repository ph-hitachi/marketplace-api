<?php

namespace App\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label'         => ['required', 'string', 'max:100'],
            'phone'         => ['nullable', 'string', 'max:30'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city'          => ['required', 'string', 'max:100'],
            'province'      => ['required', 'string', 'max:100'],
            'postal_code'   => ['required', 'string', 'max:20'],
            'country'       => ['nullable', 'string', 'max:100'],
        ];
    }
}
