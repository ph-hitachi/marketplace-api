<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price'       => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'stock'       => ['required', 'integer', 'min:0'],
            'tags'        => ['nullable', 'array', 'max:10'],
            'tags.*'      => ['string', 'max:50'],
            'is_active'   => ['sometimes', 'boolean'],
        ];
    }
}
