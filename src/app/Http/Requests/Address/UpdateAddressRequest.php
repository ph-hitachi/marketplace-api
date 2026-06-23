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
            /**
             * Name of the address (e.g., 'Home', 'Work').
             */
            'label'         => ['sometimes', 'string', 'max:100'],

            /**
             * Contact phone number for delivery.
             */
            'phone'         => ['sometimes', 'nullable', 'string', 'max:30'],

            /**
             * Primary street address details.
             */
            'address_line1' => ['sometimes', 'string', 'max:255'],

            /**
             * Secondary address details (e.g., suite, unit, building).
             */
            'address_line2' => ['sometimes', 'nullable', 'string', 'max:255'],

            /**
             * City name.
             */
            'city'          => ['sometimes', 'string', 'max:100'],

            /**
             * Province or state name.
             */
            'province'      => ['sometimes', 'string', 'max:100'],

            /**
             * ZIP or postal code.
             */
            'postal_code'   => ['sometimes', 'string', 'max:20'],

            /**
             * Country name.
             */
            'country'       => ['sometimes', 'string', 'max:100'],
        ];
    }
}
