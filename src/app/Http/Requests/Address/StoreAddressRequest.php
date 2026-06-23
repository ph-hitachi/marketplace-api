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
            /**
             * Name of the address (e.g., 'Home', 'Work').
             */
            'label'         => ['required', 'string', 'max:100'],

            /**
             * Contact phone number for delivery.
             */
            'phone'         => ['nullable', 'string', 'max:30'],

            /**
             * Primary street address details.
             */
            'address_line1' => ['required', 'string', 'max:255'],

            /**
             * Secondary address details (e.g., suite, unit, building).
             */
            'address_line2' => ['nullable', 'string', 'max:255'],

            /**
             * City name.
             */
            'city'          => ['required', 'string', 'max:100'],

            /**
             * Province or state name.
             */
            'province'      => ['required', 'string', 'max:100'],

            /**
             * ZIP or postal code.
             */
            'postal_code'   => ['required', 'string', 'max:20'],

            /**
             * Country name.
             */
            'country'       => ['nullable', 'string', 'max:100'],
        ];
    }
}
