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
            /**
             * Must be a valid address ID belonging to the authenticated user.
             */
            'address_id'     => ['required', 'integer', "exists:addresses,id,user_id,{$userId}"],

            /**
             * Must be either 'wallet' or 'cod'.
             */
            'payment_method' => ['required', 'string', 'in:wallet,cod'],

            /**
             * Required if payment_method is wallet. Must be a valid wallet ID belonging to the authenticated user.
             */
            'wallet_id'      => ['required_if:payment_method,wallet', 'nullable', 'integer', "exists:wallets,id,user_id,{$userId}"],

            /**
             * List of items in the order.
             */
            'items'          => ['required', 'array', 'min:1'],

            /**
             * ID of the product. Must exist in the products table.
             */
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],

            /**
             * Quantity of the product to order. Minimum 1, maximum 999.
             */
            'items.*.quantity'   => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }
}
