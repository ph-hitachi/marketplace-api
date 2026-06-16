<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'address_id'     => ['required', 'integer', "exists:addresses,id,user_id,{$userId}"],
            'payment_method' => ['required', 'string', 'in:wallet,cod'],
            'wallet_id'      => ['required_if:payment_method,wallet', 'nullable', 'integer', "exists:wallets,id,user_id,{$userId}"],
            'items'          => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'   => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }
}
