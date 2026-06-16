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
        $profileId = ($this->user() && $this->user()->sellerProfile) ? $this->user()->sellerProfile->id : null;

        return [
            'shop_name'        => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('seller_profiles', 'shop_name')->ignore($profileId),
            ],
            'shop_description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
