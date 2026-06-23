<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

        public function rules(): array
    {
        return [
            /**
             * Name of the product.
             */
            'name'        => ['sometimes', 'string', 'max:255'],

            /**
             * Detailed description of the product.
             */
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],

            /**
             * Price of the product (minimum 0.01).
             */
            'price'       => ['sometimes', 'numeric', 'min:0.01', 'max:9999999.99'],

            /**
             * Available stock quantity.
             */
            'stock'       => ['sometimes', 'integer', 'min:0'],

            /**
             * List of tags (maximum 10 tags).
             */
            'tags'        => ['sometimes', 'nullable', 'array', 'max:10'],

            /**
             * Individual tag string.
             */
            'tags.*'      => ['string', 'max:50'],

            /**
             * Activation status of the product.
             */
            'is_active'   => ['sometimes', 'boolean'],
        ];
    }
}
