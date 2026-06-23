<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class StoreWalletRequest extends FormRequest
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
        return [
            /**
             * Label/name for the wallet (e.g., 'My Wallet').
             */
            'label' => ['required', 'string', 'max:100'],
        ];
    }
}
