<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSellerProfileRequest extends FormRequest
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
             * Name of the shop. Must be unique.
             */
            'shop_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('shops', 'shop_name')->ignore($this->user()->shop?->id),
            ],

            /**
             * Optional shop description.
             */
            'shop_description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
