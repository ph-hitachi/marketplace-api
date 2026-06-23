<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class TopupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

        public function rules(): array
    {
        return [
            /**
             * Amount to top up the wallet (minimum 1, maximum 50000).
             */
            'amount' => ['required', 'numeric', 'min:1', 'max:50000'],
        ];
    }
}
