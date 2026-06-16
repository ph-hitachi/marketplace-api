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
            'name'        => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'price'       => ['sometimes', 'numeric', 'min:0.01', 'max:9999999.99'],
            'stock'       => ['sometimes', 'integer', 'min:0'],
            'tags'        => ['sometimes', 'nullable', 'array', 'max:10'],
            'tags.*'      => ['string', 'max:50'],
            'is_active'   => ['sometimes', 'boolean'],
        ];
    }
}
